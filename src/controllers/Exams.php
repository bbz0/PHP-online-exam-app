<?php
	class Exams extends Controller
	{
		private $examModel;

		public function __construct()
		{
			parent::__construct();
			$this->examModel = $this->model('Exam');
			$this->examineeModel = $this->model('Examinee');
		}

		public function index()
		{
			$data = [
				'title' => 'Exams'
			];

			$data['exams'] = $this->examModel->getAllExams();
			if (!empty($_SESSION)) {
				$data = $this->setSessionData($data, array_keys($_SESSION)[0]);
			}
			if (isLoggedIn('examinee')) {
				$invitationExams = $this->examModel->getInvitations($data['examinee']['ID']);
				if ($invitationExams) {
					$data['exams'] = array_merge($data['exams'], $invitationExams);
				}
			}
			$this->loadView('exams', 'index', $data);
		}

		public function exam($id)
		{
			$data['exam'] = $this->examModel->getExam($id);
			$data['title'] = $data['exam']['name'];
			if (!empty($_SESSION)) {
				$data = $this->setSessionData($data, array_keys($_SESSION)[0]);
			}

			if ($data['exam']['questionsNum'] < 10 || $data['exam']['sectionsCount'] < 1) {
				redirect('exams');
			}

			if ($data['exam']['accessType'] === 'inviteOnly') {
				if (!$this->examModel->checkInvitation($data['examinee']['ID'], $data['exam']['ID'])) {
					redirect('exams');
				} 
			}

			if (isLoggedIn('examinee')) {
				if ($data['exam']['accessType'] === 'approved') {
					$data['examinee']['request'] = $this->examModel->getRequest($data['examinee']['ID'], $id);
				}
				if ($_SERVER['REQUEST_METHOD'] == 'POST') {
					if (isset($_POST['startExam'])) {
						$data['exam']['sections'] = $this->examModel->getSections($id);
						for ($i = 0; $i < count($data['exam']['sections']); $i++) {
							$section = $data['exam']['sections'][$i]['name'];
							$data['exam']['sections'][$i]['questions'] = $this->examModel->getQuestions($section, $id);
						}
						$data['recordID'] = $this->examModel->createRecord($data['exam']['ID'], $data['examinee']['ID'], $data['exam']['questionsNum']);
						if ($data['recordID']) {
							$data['title'] = $data['exam']['name'] . ' - Exam in Progress';
							$this->loadView('exams', 'examination', $data);
						} else {
							flashMessage('Something went wrong', 'danger');
							redirect('exams/show' . $id);
						}
					} elseif (isset($_POST['finishExam'])) {
						$correctAnswers = 0;
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
						if ($correctAnswers >= $data['exam']['passingScore']) {
							$result = 'Passed';
						} else {
							$result = 'Failed';
						}
						$examRecorded = $this->examModel->recordExam($correctAnswers, $result, $_POST['recordID'], $data['examinee']['ID']);
						if ($examRecorded) {
							redirect('exams/result/' . $data['exam']['ID'] . '/' . $data['examinee']['ID'] . '/' . $_POST['recordID']);
						} else {
							flashMessage('Something went wrong', 'danger');
							redirect('exams/exam/' . $id);
						}
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

		public function result($examId, $examineeId, $recordId)
		{
			if (isLoggedIn('examinee') && $examineeId === $_SESSION['examinee']['id']) {
				$data['record'] = $this->examModel->getRecord($examId, $examineeId, $recordId);
				$data['title'] = 'Results - ' . $data['record']['examName'];
				$data = $this->setSessionData($data, 'examinee');
				$this->loadView('exams', 'result', $data);
			} else {
				flashMessage('You dont\'t have permission for that!', 'danger');
				redirect('exams');
			}
		}

		public function create()
		{
			$data = [
				'title' => 'Create Exam'
			];

			if (isLoggedIn('examiner')) {
				$data = $this->setSessionData($data, 'examiner');
				if ($_SERVER['REQUEST_METHOD'] == 'POST') {
					$data['examData'] = $this->sanitize($_POST);
					if ($data['examData']['errorCount'] === 0) {
						$data['examData'] = $this->totalTime($data['examData']);
						$examCreated = $this->examModel->createExam($data['examData'], $data['examiner']['ID']);
						$sectionsCreated = $this->examModel->createSections($data['examData']['section'], $examCreated);
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

		public function edit($id, $section = '')
		{
			$data = [
				'title' => 'Edit Exam'
			];

			if (isLoggedIn('examiner')) {
				$data = $this->setSessionData($data, 'examiner');
				$data['examData'] = $this->examModel->getExam($id);
				if ($data['examiner']['ID'] === $data['examData']['creatorID']) {
					if ($data['examData']['accessType'] === 'inviteOnly') {
						$data['examData']['invited'] = $this->examModel->getInvitedExaminee($data['examData']['ID']);
					} elseif ($data['examData']['accessType'] === 'approved') {
						$data['examData']['requests'] = $this->examModel->getExamRequests($data['examiner']['ID'], $data['examData']['ID']);
					}
					$data['examData']['sections'] = $this->examModel->getSections($data['examData']['ID']);
					if ($section === 'invite') {
						$this->inviteExaminees($id, $data);
					} elseif($section) {
						$this->questions($id, $section, $data);
					} else {
						if ($_SERVER['REQUEST_METHOD'] == 'POST') {
							if (isset($_POST['submit'])) {
								$data['examData'] = $this->sanitize($_POST);
								if ($data['examData']['errorCount'] === 0) {
									$data['examData'] = $this->totalTime($data['examData']);
									$examUpdated = $this->examModel->updateExam($data['examData'], $id);
									$sectionsUpdated = $this->examModel->updateSections($data['examData']['sectionEdit'], $data['examData']['sectionID'], $id);
									$sectionsCreated = $this->examModel->createSections($data['examData']['section'], $id);
									if ($examUpdated && $sectionsUpdated && $sectionsCreated) {
										flashMessage('Settings saved.', 'success');
										redirect('exams/edit/' . $id);
									} else {
										flashMessage('Something went wrong.', 'danger');
										redirect('exams/edit/' . $id);
									}
								}
							} elseif (isset($_POST['approveRequest'])) {
								// die(print_r($_POST));
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

		public function questions($id, $section, $data)
		{
			$data['title'] = $section . ' Questions';
			$data['currSection'] = $section;
			$data['loadedQuestions'] = $this->examModel->getQuestions($section, $id);
			$data['questionsCount'] = count($data['loadedQuestions']);
			$data = $this->setSessionData($data, 'examiner');
			if ($_SERVER['REQUEST_METHOD'] == 'POST') {
				$_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
				$data['errorCount'] = 0;
				if (isset($_POST['updateID'])) {
					$data = $this->sanitizeQuestion($_POST, $data, 'updateID');
				}
				if (isset($_POST['question'])) {
					$data = $this->sanitizeQuestion($_POST, $data, 'question');
				}

				if ($data['errorCount'] === 0) {
					if (isset($data['updatedQuestions'])) {
						$isEdited = $this->examModel->updateQuestions($data['updatedQuestions']);
					}
					if (isset($data['newQuestions'])) {
						$isPosted = $this->examModel->storeQuestions($data['newQuestions'], $section, $id);
					}
					if ($isEdited || $isPosted) {
						flashMessage('Questions saved.', 'success');
						redirect('exams/edit/' . $id);
					} else {
						flashMessage('Something went wrong', 'danger');
						redirect('exams/edit/' . $id . '/' . $section);
					}
				} else {
					$data['loadedQuestions'] = $data['updatedQuestions'];
					array_push($data['loadedQuestions'], $data['newQuestions']);
					$this->loadView('exams', 'edit-questions', $data);
				}
			} else {
				$this->loadView('exams', 'edit-questions', $data);
			}
		}

		public function inviteExaminees($id, $data)
		{
			if ($data['examData']['accessType'] === 'inviteOnly' && isLoggedIn('examiner')) {
				$data = $this->setSessionData($data, 'examiner');
				if ($_SERVER['REQUEST_METHOD'] == 'POST') {
					if (isset($_POST['inviteExaminee'])) {
						if ($this->examModel->createInvite($id, $data['examiner']['ID'], $_POST['examineeID'])) {
							flashMessage('Examinee invited', 'success');
							redirect('exams/edit/' . $id);
						} else {
							flashMessage('Something went wrong', 'danger');
							redirect('exams/edit/' . $id . '/invite');
						}
					} elseif (isset($_POST['deleteInvite'])) {
						if ($this->examModel->uninviteExaminee($_POST['inviteID'])) {
							flashMessage('Examinee no longer invited', 'warning');
							redirect('exams/edit/' . $id);
						} else {
							flashMessage('Something went wrong', 'danger');
							redirect('exams/edit/' . $id);
						}
					}
				} else {
					$data['examinees'] = $this->examModel->getUninviteExaminee($data['examData']['ID']);
					$this->loadView('exams', 'invite', $data);
				}
			} else {
				redirect('exams/edit' . $id);
			}
		}

		public function delete($id, $section = '', $questionID = '')
		{
			if (isLoggedIn('examiner')) {
				$data['examData'] = $this->examModel->getExam($id);
				if ($_SESSION['examiner']['id'] === $data['examData']['creatorID']) {
					if (isset($section) && isset($questionID)) {
						$this->deleteSection($id, $section, $questionID);
					} else {
						if ($this->examModel->deleteExam($id)) {
							flashMessage('Exam deleted', 'danger');
							redirect('examiners/dashboard');
						} else {
							flashMessage('Something went wrong', 'danger');
							redirect('exams/edit/' . $id);
						}
					}
				} else {
					redirect('examiners/dashboard');
				}
			} else {
				redirect('/');
			}
		}

		public function deleteSection($id, $section, $questionID = '')
		{
			if (!empty($questionID)) {
				$this->deleteQuestion($id, $section, $questionID);
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

		public function deleteQuestion($id, $section, $questionID)
		{
			if ($this->examModel->deleteQuestion($id, $section, $questionID)) {
				flashMessage('Question Deleted', 'warning');
				redirect('exams/edit/' . $id . '/' . $section);
			} else {
				flashMessage('Something went wrong', 'danger');
				redirect('exams/edit/' . $id . '/' . $section);
			}
		}

		public function sanitizeQuestion($post, $data, $key)
		{
			$choices = ['A', 'B', 'C', 'D', 'E'];
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