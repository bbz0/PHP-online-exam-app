<?php
	// Exams class
	class Exams extends Controller
	{
		private $examModel;

		public function __construct()
		{
			parent::__construct();
			$this->examModel = $this->model('Exam'); // load exam model class
			$this->examineeModel = $this->model('Examinee'); // load examinee model class
		}

		// exams index page, loads all available exams, and exams seen by user
		public function index()
		{
			$data = [
				'title' => 'Exams'
			];

			// get all 'open' permission exams
			$data['exams'] = $this->examModel->getAllExams();
			if (!empty($_SESSION)) {
				$data = $this->setSessionData($data, array_keys($_SESSION)[0]);
			}
			// if an examinee user is logged in
			if (isLoggedIn('examinee')) {
				// get all exams where user is invited or permitted to take
				$invitationExams = $this->examModel->getInvitations($data['examinee']['ID']);
				if ($invitationExams) {
					$data['exams'] = array_merge($data['exams'], $invitationExams);
				}
			}
			// load view
			$this->loadView('exams', 'index', $data);
		}

		// exam method
		public function exam($id)
		{
			// get exam data using id
			$data['exam'] = $this->examModel->getExam($id);
			$data['title'] = $data['exam']['name'];
			// if there is a logged in user, store user data
			if (!empty($_SESSION)) {
				$data = $this->setSessionData($data, array_keys($_SESSION)[0]);
			}
			// if user is trying to access unfinished exam they are redirected
			if ($data['exam']['questionsNum'] < 10 || $data['exam']['sectionsCount'] < 1) {
				redirect('exams');
			}
			// check if user is 'invited' to take exam
			if ($data['exam']['accessType'] === 'inviteOnly') {
				if (!$this->examModel->checkInvitation($data['examinee']['ID'], $data['exam']['ID'])) {
					redirect('exams');
				} 
			}
			// if user is logged in, only examinees can take exams
			if (isLoggedIn('examinee')) {
				// check if user is permitted to take exam
				if ($data['exam']['accessType'] === 'approved') {
					$data['examinee']['request'] = $this->examModel->getRequest($data['examinee']['ID'], $id);
				}
				// listen for post request
				if ($_SERVER['REQUEST_METHOD'] == 'POST') {
					// the user starts to take the exam
					if (isset($_POST['startExam'])) {
						// get exam sections
						$data['exam']['sections'] = $this->examModel->getSections($id);
						// map exam sections and questions
						for ($i = 0; $i < count($data['exam']['sections']); $i++) {
							$section = $data['exam']['sections'][$i]['name'];
							$data['exam']['sections'][$i]['questions'] = $this->examModel->getQuestions($section, $id);
						}
						// create exam record
						$data['recordID'] = $this->examModel->createRecord($data['exam']['ID'], $data['examinee']['ID'], $data['exam']['questionsNum']);
						// if record is successfully created, load view and exam begins
						if ($data['recordID']) {
							$data['title'] = $data['exam']['name'] . ' - Exam in Progress';
							$this->loadView('exams', 'examination', $data);
						} else {
							flashMessage('Something went wrong', 'danger');
							redirect('exams/show' . $id);
						}
					// if the user has finished the exam
					} elseif (isset($_POST['finishExam'])) {
						$correctAnswers = 0;
						// evaluate exam by section
						for ($i = 0; $i < count($_POST['questionID']); $i++) {
							for ($x = 0; $x < count($_POST['questionID'][$i]); $x++) {
								$question = $this->examModel->getQuestion($_POST['questionID'][$i][$x]);
								if (isset($_POST['choice'][$i][$x])) {
									if ($_POST['choice'][$i][$x] === $question['correctAnswer']) {
										$correctAnswers++;
									}
								}
							}
						}
						// check if user passed the exam
						if ($correctAnswers >= $data['exam']['passingScore']) {
							$result = 'Passed';
						} else {
							$result = 'Failed';
						}
						// update exam record
						$examRecorded = $this->examModel->recordExam($correctAnswers, $result, $_POST['recordID'], $data['examinee']['ID']);
						// if exam successfully recorded redirect to results page
						if ($examRecorded) {
							redirect('exams/result/' . $data['exam']['ID'] . '/' . $data['examinee']['ID'] . '/' . $_POST['recordID']);
						} else {
							flashMessage('Something went wrong', 'danger');
							redirect('exams/exam/' . $id);
						}
					// if user requests permission to take exam
					} elseif (isset($_POST['requestAccess'])) {
						if ($this->examModel->createRequest($data['examinee']['ID'], $id, $data['exam']['creatorID'])) {
							flashMessage('Request sent', 'success');
						} else {
							flashMessage('Something went wrong', 'danger');
						}
						redirect('exams/exam/' . $id);
					}
				} else {
					$this->loadView('exams', 'show', $data);
				}
			} else {
				$this->loadView('exams', 'show', $data);
			}
		}

		// results page, takes in exam id, examinee id, and record id
		public function result($examId, $examineeId, $recordId)
		{
			// check if examinee is logged in and if examinee is owner of record
			if (isLoggedIn('examinee') && $examineeId === $_SESSION['examinee']['id']) {
				// get record
				$data['record'] = $this->examModel->getRecord($examId, $examineeId, $recordId);
				$data['title'] = 'Results - ' . $data['record']['examName'];
				// store user data
				$data = $this->setSessionData($data, 'examinee');
				// load view
				$this->loadView('exams', 'result', $data);
			} else {
				flashMessage('You dont\'t have permission for that!', 'danger');
				redirect('exams');
			}
		}

		// create exam method
		public function create()
		{
			$data = [
				'title' => 'Create Exam'
			];

			// check if an examiner is logged in, redirect if not
			if (isLoggedIn('examiner')) {
				// store user data
				$data = $this->setSessionData($data, 'examiner');
				// listen for post request, if no post request load view
				if ($_SERVER['REQUEST_METHOD'] == 'POST') {
					// sanitize user input
					$data['examData'] = $this->sanitize($_POST);
					// if no error
					if ($data['examData']['errorCount'] === 0) {
						// calculate total time
						$data['examData'] = $this->totalTime($data['examData']);
						// create exam, store to database
						$examCreated = $this->examModel->createExam($data['examData'], $data['examiner']['ID']);
						// create sections, store to database
						$sectionsCreated = $this->examModel->createSections($data['examData']['section'], $examCreated);
						// if both successfully created, redirect to exam edit page
						if ($examCreated && $sectionsCreated) {
							flashMessage('Exam successfully created!', 'success');
							redirect('exams/edit/' . $examCreated);
						} else {
							flashMessage('Something went wrong', 'danger');
							redirect('examiners/dashboard');
						}
					} else {
						$this->loadView('exams', 'create', $data);
					}
				} else {
					$this->loadView('exams', 'create', $data);
				}
			} else {
				flashMessage('You need to log in first!', 'warning');
				redirect('examiners/login');
			}
		}

		// edit exam method, takes in exam id, and if there is a section specified, go to edit questions method
		public function edit($id, $section = '')
		{
			$data = [
				'title' => 'Edit Exam'
			];
			// check if examiner is logged in
			if (isLoggedIn('examiner')) {
				// store user data
				$data = $this->setSessionData($data, 'examiner');
				// get exam from database
				$data['examData'] = $this->examModel->getExam($id);
				// check if user is exam creator
				if ($data['examiner']['ID'] === $data['examData']['creatorID']) {
					// if exam is 'invite only', get invited examinees from database
					if ($data['examData']['accessType'] === 'inviteOnly') {
						$data['examData']['invited'] = $this->examModel->getInvitedExaminee($data['examData']['ID']);
					// if exam is 'permission only', get permission requests from database
					} elseif ($data['examData']['accessType'] === 'approved') {
						$data['examData']['requests'] = $this->examModel->getExamRequests($data['examiner']['ID'], $data['examData']['ID']);
					}
					// get exam sections from database
					$data['examData']['sections'] = $this->examModel->getSections($data['examData']['ID']);
					// if section parameter is 'invite', go to invite method
					if ($section === 'invite') {
						$this->inviteExaminees($id, $data);
					// else, go to questions method
					} elseif($section) {
						$this->questions($id, $section, $data);
					// else, proceed to with the edit method
					} else {
						// listen for post request, if not load view
						if ($_SERVER['REQUEST_METHOD'] == 'POST') {
							// if user saves exam settings
							if (isset($_POST['submit'])) {
								// sanitize user input
								$data['examData'] = $this->sanitize($_POST);
								// if no errors
								if ($data['examData']['errorCount'] === 0) {
									// recalculate total time
									$data['examData'] = $this->totalTime($data['examData']);
									// update exam to database
									$examUpdated = $this->examModel->updateExam($data['examData'], $id);
									// update sections
									$sectionsUpdated = $this->examModel->updateSections($data['examData']['sectionEdit'], $data['examData']['sectionID'], $id);
									// create new sections
									$sectionsCreated = $this->examModel->createSections($data['examData']['section'], $id);
									// if created successfully redirect
									if ($examUpdated && $sectionsUpdated && $sectionsCreated) {
										flashMessage('Settings saved.', 'success');
										redirect('exams/edit/' . $id);
									} else {
										flashMessage('Something went wrong.', 'danger');
										redirect('exams/edit/' . $id);
									}
								}
							// if user has approved permission requests
							} elseif (isset($_POST['approveRequest'])) {
								// if request is successfully approved
								if ($this->examModel->approveRequest($_POST['requestID'])) {
									flashMessage('Request Approved', 'success');
									redirect('exams/edit/' . $id);
								} else {
									flashMessage('Something went wrong.', 'danger');
									redirect('exams/edit/' . $id);
								}
							}
							
						} else {
							$this->loadView('exams', 'edit', $data);
						}
					}
				} else {
					redirect('examiners/dashboard');
				}
			} else {
				redirect('/');
			}
		}

		// edit questions method, or edit section page
		public function questions($id, $section, $data)
		{
			$data['title'] = $section . ' Questions';
			$data['currSection'] = $section;
			// get questions from database
			$data['loadedQuestions'] = $this->examModel->getQuestions($section, $id);
			$data['questionsCount'] = count($data['loadedQuestions']);
			// store user data
			$data = $this->setSessionData($data, 'examiner');
			// listen for post request, if none load view
			if ($_SERVER['REQUEST_METHOD'] == 'POST') {
				// filter and sanitize user input
				$_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
				$data['errorCount'] = 0;
				// sanitize edited questions and store them to data
				if (isset($_POST['updateID'])) {
					$data = $this->sanitizeQuestion($_POST, $data, 'updateID');
				}
				// sanitize new questions and store them to data
				if (isset($_POST['question'])) {
					$data = $this->sanitizeQuestion($_POST, $data, 'question');
				}
				// if no error
				if ($data['errorCount'] === 0) {
					// update questions in database
					if (isset($data['updatedQuestions'])) {
						$isEdited = $this->examModel->updateQuestions($data['updatedQuestions']);
					}
					// add new questions to database
					if (isset($data['newQuestions'])) {
						$isPosted = $this->examModel->storeQuestions($data['newQuestions'], $section, $id);
					}
					// if successful redirect to edit exam page
					if ($isEdited || $isPosted) {
						flashMessage('Questions saved.', 'success');
						redirect('exams/edit/' . $id);
					} else {
						flashMessage('Something went wrong', 'danger');
						redirect('exams/edit/' . $id . '/' . $section);
					}
				// if error load view with questions data
				} else {
					$data['loadedQuestions'] = $data['updatedQuestions'];
					array_push($data['loadedQuestions'], $data['newQuestions']);
					$this->loadView('exams', 'edit-questions', $data);
				}
			} else {
				$this->loadView('exams', 'edit-questions', $data);
			}
		}

		// invite examinees method for 'invitation' type exams
		public function inviteExaminees($id, $data)
		{	
			// check if exam is invitation type and if examiner user is logged in
			if ($data['examData']['accessType'] === 'inviteOnly' && isLoggedIn('examiner')) {
				// store user data
				$data = $this->setSessionData($data, 'examiner');
				// check for post request
				if ($_SERVER['REQUEST_METHOD'] == 'POST') {
					// if examiner invited examinee
					if (isset($_POST['inviteExaminee'])) {
						// 'invite' the examinee and update database
						if ($this->examModel->createInvite($id, $data['examiner']['ID'], $_POST['examineeID'])) {
							flashMessage('Examinee invited', 'success');
							redirect('exams/edit/' . $id);
						} else {
							flashMessage('Something went wrong', 'danger');
							redirect('exams/edit/' . $id . '/invite');
						}
					// if examiner deletes invitation
					} elseif (isset($_POST['deleteInvite'])) {
						// delete invitation in database
						if ($this->examModel->uninviteExaminee($_POST['inviteID'])) {
							flashMessage('Examinee no longer invited', 'warning');
							redirect('exams/edit/' . $id);
						} else {
							flashMessage('Something went wrong', 'danger');
							redirect('exams/edit/' . $id);
						}
					}
				} else {
					// get all examinees data that are not invited
					$data['examinees'] = $this->examModel->getUninviteExaminee($data['examData']['ID']);
					$this->loadView('exams', 'invite', $data);
				}
			} else {
				redirect('exams/edit' . $id);
			}
		}

		// delete exam method, takes in an id, 
		// if id AND section is not empty, delete section method gets called, 
		// if id AND section AND question id is not empty, delete question method gets called
		public function delete($id, $section = '', $questionID = '')
		{
			// if examiner is logged in
			if (isLoggedIn('examiner')) {
				// get exam data
				$data['examData'] = $this->examModel->getExam($id);
				// check if logged in examiner is creator of exam
				if ($_SESSION['examiner']['id'] === $data['examData']['creatorID']) {
					// if section is not empty call delete section method
					if (isset($section) && isset($questionID)) {
						$this->deleteSection($id, $section, $questionID);
					} else {
						// delete exam from database, as well as all sections and questions associated with it then redirect to dashboard
						if ($this->examModel->deleteExam($id)) {
							flashMessage('Exam deleted', 'danger');
							redirect('examiners/dashboard');
						} else {
							flashMessage('Something went wrong', 'danger');
							redirect('exams/edit/' . $id);
						}
					}
				// redirect if not owner
				} else {
					redirect('examiners/dashboard');
				}
			} else {
				redirect('/');
			}
		}

		// delete section method, if question id is not empty, delete question method gets called
		public function deleteSection($id, $section, $questionID = '')
		{
			// call delete question if question id is not empty
			if (!empty($questionID)) {
				$this->deleteQuestion($id, $section, $questionID);
			// delete section from database, as well as all questions associated with it then redirect to edit exam page
			} else {
				if ($this->examModel->deleteSection($section, $id)) {
					flashMessage('Section Deleted', 'danger');
					redirect('exams/edit/' . $id);
				} else {
					flashMessage('Something went wrong', 'danger');
					redirect('exams/edit/' . $id);
				}
			}
		}

		// delete question method
		public function deleteQuestion($id, $section, $questionID)
		{
			// delete question from database then redirect to edit section page
			if ($this->examModel->deleteQuestion($id, $section, $questionID)) {
				flashMessage('Question Deleted', 'warning');
				redirect('exams/edit/' . $id . '/' . $section);
			} else {
				flashMessage('Something went wrong', 'danger');
				redirect('exams/edit/' . $id . '/' . $section);
			}
		}

		// sanitize question method, filters and sanitizes user input in edit section page
		public function sanitizeQuestion($post, $data, $key)
		{
			// choices array, all questions have 5 choices
			$choices = ['A', 'B', 'C', 'D', 'E'];
			// depending on key, filter the new questions and edited questions
			if ($key === 'updateID') {
				$dataSection = 'updatedQuestions';
				$questionKey = 'updateQuestion';
				$correctKey = 'updateCorrectAnswer';
				$choiceKey = 'updateChoice';
			} else {
				$dataSection = 'newQuestions';
				$questionKey = 'question';
				$correctKey = 'correctAnswer';
				$choiceKey = 'choice';
			}

			// sanitize, filter and map questions and their choices into the data variable then return data
			// loop through the questions
			for ($i = 0; $i < count($post[$key]); $i++) {
				if ($key === 'updateID') {
					$data[$dataSection][$i]['ID'] = $post['updateID'][$i];
				}
				$data[$dataSection][$i]['correctAnswer'] = $post[$correctKey][$i];
				if (strlen($post[$questionKey][$i]) > 4) {
					$data[$dataSection][$i]['question'] = trim(substr(strip_tags($post[$questionKey][$i]), 0, 255));
				} else {
					$data['error'][$dataSection][$i]['question'] = 'must be at least 5 characters';
					$data['errorCount']++;
				}
				// loop through the choices in the question then map
				for ($x = 0; $x < count($choices); $x++) {
					if (isset($post[$choiceKey . $choices[$x]][$i])) {
						if (strlen($post[$choiceKey . $choices[$x]][$i]) < 5) {
							$data['error'][$dataSection][$i][$choices[$x]] = 'must be at least 5 characters';
							$data['errorCount']++;
						} else {
							$data[$dataSection][$i][$choices[$x]] = trim(substr(strip_tags($post[$choiceKey . $choices[$x]][$i]), 0, 255));
						}
					} else {
						$data[$dataSection][$i][$choices[$x]] = 'N/A';
					}
				}
			}

			return $data;
		}
	}