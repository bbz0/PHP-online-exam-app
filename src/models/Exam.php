<?php
	// the exam db model
	class Exam
	{
		private $db;

		public function __construct()
		{
			// instantiate database class
			$this->db = new Database;
		}

		// CREATE METHODS
		// create exam method
		public function createExam($data, $id)
		{
			$this->db->query('INSERT INTO exams(name, descr, accessType, hours, minutes, totalTime, passingRate, creatorID) VALUES (:name, :descr, :accessType, :hours, :minutes, :totalTime, :passingRate, :creatorID)');
			$this->db->bind(':name', $data['name']);
			$this->db->bind(':descr', $data['desc']);
			$this->db->bind(':accessType', $data['accessType']);
			$this->db->bind(':hours', $data['hours']);
			$this->db->bind(':minutes', $data['mins']);
			$this->db->bind(':totalTime', $data['totalTime']);
			$this->db->bind(':passingRate', $data['passingRate']);
			$this->db->bind(':creatorID', $id);
			$this->db->execute();
			// get id of created exam
			$examID = $this->db->getID();

			// increment number of exams examiner created in database
			$this->db->query('UPDATE examiners SET exams = exams + 1 WHERE ID = :ID');
			$this->db->bind(':ID', $id);
			$examinerUpdated = $this->db->execute();

			// whether successful or not
			if ($examID && $examinerUpdated) {
				return $examID;
			} else {
				return false;
			}
		}

		// create section method
		public function createSections($data, $id)
		{
			$errorCounter = 0;
			foreach($data as $index) {
				$this->db->query('INSERT INTO sections(name, examID) VALUES(:name, :examID)');
				$this->db->bind(':name', $index);
				$this->db->bind(':examID', $id);
				if (!$this->db->execute()) {
					$errorCounter++;
				}

				$this->db->query('UPDATE exams SET sectionsCount = sectionsCount + 1 WHERE ID = :ID');
				$this->db->bind(':ID', $id);
				if (!$this->db->execute()) {
					$errorCounter++;
				}
			}

			if ($errorCounter > 0) {
				return false;
			} else {
				return true;
			}
		}

		// create questions method, takes in an array of questions, loops through, and stores each into the database
		public function storeQuestions($data, $section, $examID)
		{
			$errorCounter = 0;
			foreach ($data as $index) {
				$this->db->query('INSERT INTO questions(question, a, b, c, d, e, correctAnswer, section, examID) VALUES(:question, :a, :b, :c, :d, :e, :correctAnswer, :section, :examID)');
				$this->db->bind(':question', $index['question']);
				$this->db->bind(':a', $index['A']);
				$this->db->bind(':b', $index['B']);
				$this->db->bind(':c', $index['C']);
				$this->db->bind(':d', $index['D']);
				$this->db->bind(':e', $index['E']);
				$this->db->bind(':correctAnswer', $index['correctAnswer']);
				$this->db->bind(':section', $section);
				$this->db->bind(':examID', $examID);
				if (!$this->db->execute()) {
					$errorCounter++;
				}
				// increment number of questions in exam table
				$this->db->query('UPDATE sections SET questionsNum = questionsNum + 1 WHERE name = :name AND examID = :examID');
				$this->db->bind(':name', $section);
				$this->db->bind(':examID', $examID);
				if (!$this->db->execute()) {
					$errorCounter++;
				}
				// increment number of questions in section table
				$this->db->query('UPDATE exams SET questionsNum = questionsNum + 1 WHERE ID = :ID');
				$this->db->bind(':ID', $examID);
				if (!$this->db->execute()) {
					$errorCounter++;
				}

				if (!$this->calcPassing($examID)) {
					$errorCounter++;
				}
			}

			if ($errorCounter > 0) {
				return false;
			} else {
				return true;
			}
		}

		// create exam record method when examinee takes an exam
		public function createRecord($examID, $examineeID, $items)
		{
			$this->db->query('INSERT INTO examRecords(examID, examineeID, items) VALUES(:examID, :examineeID, :items)');
			$this->db->bind(':examID', $examID);
			$this->db->bind(':examineeID', $examineeID);
			$this->db->bind(':items', $items);
			$this->db->execute();
			$recordId = $this->db->getID();

			// increment exams taken on examinee table
			$this->db->query('UPDATE examinees SET examsTaken = examsTaken + 1 WHERE ID = :ID');
			$this->db->bind(':ID', $examineeID);
			$examineeUpdated = $this->db->execute();

			if ($recordId && $examineeUpdated) {
				return $recordId;
			} else {
				return false;
			}
		}

		// create request method, create request when examinee sends a permission request on permission type exams
		public function createRequest($examineeID, $examID, $examinerID)
		{
			$this->db->query('INSERT INTO requests(examineeID, examID, examinerID, isSent) VALUES(:examineeID, :examID, :examinerID, TRUE)');
			$this->db->bind(':examineeID', $examineeID);
			$this->db->bind(':examID', $examID);
			$this->db->bind(':examinerID', $examinerID);
			if ($this->db->execute()) {
				return true;
			} else {
				return false;
			}
		}

		// create invite method, creates invitations when examiner invites examinees to take invitation type exams
		public function createInvite($examID, $examinerID, $examineeID)
		{
			$this->db->query('INSERT INTO invitations(examID, examinerID, examineeID) VALUES(:examID, :examinerID, :examineeID)');
			$this->db->bind(':examID', $examID);
			$this->db->bind(':examinerID', $examinerID);
			$this->db->bind(':examineeID', $examineeID);
			if ($this->db->execute()) {
				return true;
			} else {
				return false;
			}
		}

		// GET METHODS
		// get all exams stored in db
		public function getAllExams()
		{
			$this->db->query('SELECT exams.ID, name, descr, accessType, hours, minutes, questionsNum, passingRate,
								examiners.username as examinerName 
								FROM `exams` 
								INNER JOIN examiners 
								ON exams.creatorID = examiners.ID
								WHERE NOT accessType = "inviteOnly" AND questionsNum >= 10 AND sectionsCount >= 0');
			$exams = $this->db->resultSet();

			return $exams;
		}

		// get invitation only exams that the logged in examinee is invited
		public function getInviteExam($id)
		{
			$this->db->query('SELECT exams.ID, name, descr, accessType, hours, minutes, questionsNum,
								examiners.username as examinerName 
								FROM `exams` 
								INNER JOIN examiners 
								ON exams.creatorID = examiners.ID
								WHERE accessType = "inviteOnly" AND questionsNum >= 10 AND sectionsCount >= 0 AND exams.ID = :ID');
			$this->db->bind(':ID', $id);
			$exam = $this->db->single();

			return $exam;
		}

		// get all exams the logged in examiner has created
		public function getExaminerExams($id)
		{
			$this->db->query('SELECT * FROM exams WHERE creatorID = :creatorID');
			$this->db->bind(':creatorID', $id);
			$exams = $this->db->resultSet();

			return $exams;
		}

		// get all single exam data
		public function getExam($id)
		{
			$this->db->query('SELECT exams.ID, name, descr, accessType, hours, minutes, totalTime, sectionsCount, passingScore, passingRate, questionsNum, creatorID, examiners.username as examinerName
				FROM exams
				INNER JOIN examiners ON exams.creatorID = examiners.ID
				WHERE exams.ID = :ID');
			$this->db->bind(':ID', $id);
			$row = $this->db->single();

			return $row;
		}

		// get all sections in an exam
		public function getSections($id)
		{
			$this->db->query('SELECT ID, name, questionsNum FROM sections WHERE examID = :examID');
			$this->db->bind(':examID', $id);
			$sections = $this->db->resultSet();

			return $sections;
		}

		// get all questions in a section in an exam
		public function getQuestions($section, $id)
		{
			$this->db->query('SELECT * FROM questions WHERE section = :section AND examID = :examID');
			$this->db->bind(':section', $section);
			$this->db->bind(':examID', $id);
			$questions = $this->db->resultSet();

			return $questions;
		}

		// get single question
		public function getQuestion($id)
		{
			$this->db->query('SELECT * FROM questions WHERE ID = :ID');
			$this->db->bind(':ID', $id);
			$question = $this->db->single();

			return $question;
		}

		// get examinee exam record
		public function getRecord($examId, $examineeId, $id)
		{
			$this->db->query('SELECT score, items, result, started, finished, 
				exams.name as examName, 
				examinees.firstName as examineeName
				FROM examRecords 
				INNER JOIN exams ON exams.ID = :examID
				INNER JOIN examinees ON examinees.ID = :examineeID
				WHERE examRecords.ID = :ID');
			$this->db->bind(':examID', $examId);
			$this->db->bind(':examineeID', $examineeId);
			$this->db->bind(':ID', $id);
			$record = $this->db->single();

			return $record;
		}

		// get all examinee requests on a permission type exam
		public function getExamRequests($examinerID, $examID)
		{
			$this->db->query('SELECT requests.ID, examineeID, examinees.username as examineeName, examinees.firstName as examineeFName, examinees.lastName as examineeLName
				FROM requests 
				INNER JOIN examinees ON examinees.ID = examineeID
				WHERE examinerID = :examinerID AND examID = :examID AND approved = FALSE');
			$this->db->bind(':examinerID', $examinerID);
			$this->db->bind(':examID', $examID);
			$requests = $this->db->resultSet();

			return $requests;
		}

		// get single request sent by examinee
		public function getRequest($examineeId, $examId)
		{
			$this->db->query('SELECT approved, isSent FROM requests WHERE examineeID = :examineeID AND examID = :examID');
			$this->db->bind(':examineeID', $examineeId);
			$this->db->bind(':examID', $examId);
			$request = $this->db->single();

			return $request;
		}

		// get all invited examinees in an exam
		public function getInvitedExaminee($examID)
		{
			$this->db->query('SELECT examinees.ID as examineeID, examinees.username, examinees.firstName, examinees.lastName, invitations.ID as ID
				FROM examinees
				INNER JOIN invitations ON examinees.ID = invitations.examineeID AND invitations.examID = :examID');
			$this->db->bind(':examID', $examID);
			$invited = $this->db->resultSet();

			return $invited;
		}

		// get all uninvited examinees in an exam
		public function getUninviteExaminee($examID)
		{
			$this->db->query('SELECT examinees.ID, examinees.username, examinees.firstName, examinees.lastName 
				FROM examinees WHERE examinees.ID NOT IN(SELECT examineeID FROM invitations WHERE examID = :examID)');
			$this->db->bind(':examID', $examID);
			$examinees = $this->db->resultSet();

			return $examinees;
		}

		// get all exams where examinee is invited to take
		public function getInvitations($examineeID)
		{
			$this->db->query('SELECT examID FROM invitations WHERE examineeID = :examineeID');
			$this->db->bind(':examineeID', $examineeID);
			$invites = $this->db->resultSet();

			if (count($invites) > 0) {
				$exams = [];
				for ($i = 0; $i < count($invites); $i++) {
					$this->db->query('SELECT exams.ID, name, descr, accessType, hours, minutes, questionsNum, passingRate,
										examiners.username as examinerName 
										FROM `exams` 
										INNER JOIN examiners 
										ON exams.creatorID = examiners.ID
										WHERE accessType = "inviteOnly" AND questionsNum >= 10 AND sectionsCount >= 0 AND exams.ID = :ID');
					$this->db->bind(':ID', $invites[$i]['examID']);
					$exam = $this->db->single();
					if (isset($exam['ID'])) {
						array_push($exams, $exam);
					}
				}
				return $exams;
			} else {
				return false;
			}
		}

		// check if examinee is invited in exam
		public function checkInvitation($examineeID, $examID)
		{
			$this->db->query('SELECT examineeID, examID FROM invitations WHERE examineeID = :examineeID AND examID = :examID');
			$this->db->bind(':examineeID', $examineeID);
			$this->db->bind(':examID', $examID);
			$row = $this->db->single();

			if ($this->db->rowCount() > 0) {
				return true;
			} else {
				return false;
			}
		}

		// UPDATE METHODS
		// updates exam details in db
		public function updateExam($data, $id)
		{
			$this->db->query('UPDATE exams SET name = :name, descr = :descr, hours = :hours, minutes = :minutes, totalTime = :totalTime, passingRate = :passingRate WHERE ID = :ID');
			$this->db->bind(':name', $data['name']);
			$this->db->bind(':descr', $data['desc']);
			$this->db->bind(':hours', $data['hours']);
			$this->db->bind(':minutes', $data['mins']);
			$this->db->bind(':totalTime', $data['totalTime']);
			$this->db->bind(':passingRate', $data['passingRate']);
			$this->db->bind(':ID', $id);
			$examUpdated = $this->db->execute();
			$passingUpdated = $this->calcPassing($id);

			if ($examUpdated && $passingUpdated) {
				return true;
			} else {
				return false;
			}
		}

		// update section settings in an exam
		public function updateSections($data, $sectionId, $examId)
		{
			$errorCount = 0;
			for ($i = 0; $i < count($data); $i++) {
				$this->db->query('UPDATE sections SET name = :name WHERE ID = :ID AND examID = :examID');
				$this->db->bind(':name', $data[$i]);
				$this->db->bind(':examID', $examId);
				$this->db->bind(':ID', $sectionId[$i]);

				if (!$this->db->execute()) {
					$errorCount++;
				}
			}

			if ($errorCount > 0) {
				return false;
			} else {
				return true;
			}
		}

		// update questions, takes in an array of questions, loops through them then updates
		public function updateQuestions($data)
		{
			$errorCounter = 0;
			foreach($data as $index) {
				$this->db->query('UPDATE questions SET question = :question, a = :a, b = :b, c = :c, d = :d, e = :e, correctAnswer = :correctAnswer WHERE ID = :ID');
				$this->db->bind(':ID', $index['ID']);
				$this->db->bind(':question', $index['question']);
				$this->db->bind(':a', $index['A']);
				$this->db->bind(':b', $index['B']);
				$this->db->bind(':c', $index['C']);
				$this->db->bind(':d', $index['D']);
				$this->db->bind(':e', $index['E']);
				$this->db->bind(':correctAnswer', $index['correctAnswer']);
				if (!$this->db->execute()) {
					$errorCounter++;
				}
			}

			if ($errorCounter > 0) {
				return false;
			} else {
				return true;
			}
		}

		// when examinee finishes exam, record the results to db
		public function recordExam($score, $result, $ID, $examineeID)
		{
			$this->db->query('UPDATE examRecords SET score = :score, result = :result, finished = CURRENT_TIMESTAMP WHERE ID = :ID');
			$this->db->bind(':score', $score);
			$this->db->bind(':result', $result);
			$this->db->bind(':ID', $ID);
			$examRecorded = $this->db->execute();

			if ($result === 'Passed') {
				$this->db->query('UPDATE examinees SET totalScore = totalScore + :score, examsPassed = examsPassed + 1 WHERE ID = :ID');
			} else {
				$this->db->query('UPDATE examinees SET totalScore = totalScore + :score, examsFailed = examsFailed + 1 WHERE ID = :ID');
			}
			$this->db->bind(':score', $score);
			$this->db->bind(':ID', $examineeID);
			$examineeUpdated = $this->db->execute();

			if ($examRecorded && $examineeUpdated) {
				return true;
			} else {
				return false;
			}
		}

		// approve a permission request on permission type exam
		public function approveRequest($id)
		{
			$this->db->query('UPDATE requests SET approved = TRUE WHERE ID = :ID');
			$this->db->bind(':ID', $id);

			if ($this->db->execute()) {
				return true;
			} else {
				return false;
			}
		}

		// DELETE METHODS
		// delete exam, delete all invitations, requests, and questions associated with the exam
		public function deleteExam($id)
		{
			$this->db->query('DELETE FROM invitations WHERE examID = :examID');
			$this->db->bind(':examID', $id);
			$invitationsDeleted = $this->db->execute();

			$this->db->query('DELETE FROM requests WHERE examID = :examID');
			$this->db->bind(':examID', $id);
			$requestsDeleted = $this->db->execute();

			$this->db->query('DELETE FROM questions WHERE examID = :examID');
			$this->db->bind(':examID', $id);
			$questionsDeleted = $this->db->execute();

			$this->db->query('DELETE FROM sections WHERE examID = :examID');
			$this->db->bind(':examID', $id);
			$sectionsDeleted = $this->db->execute();

			$this->db->query('DELETE FROM exams WHERE ID = :ID');
			$this->db->bind(':ID', $id);
			$examDeleted = $this->db->execute();

			if ($invitationsDeleted && $requestsDeleted && $questionsDeleted && $sectionsDeleted && $examDeleted) {
				return true;
			} else {
				return false;
			}
		}

		// delete section, also deletes all questions associated with the section
		public function deleteSection($section, $id)
		{
			$this->db->query('DELETE FROM questions WHERE section = :section AND examID = :examID');
			$this->db->bind(':section', $section);
			$this->db->bind(':examID', $id);
			$questionsDeleted = $this->db->execute();
			$questionsDeleted = $this->db->rowCount();

			$this->db->query('UPDATE exams SET questionsNum = questionsNum - :questionsDeleted WHERE ID = :ID');
			$this->db->bind(':questionsDeleted', $questionsDeleted);
			$this->db->bind(':ID', $id);
			$questionsDeleted = $this->db->execute();

			$this->db->query('DELETE FROM sections WHERE name = :name AND examID = :examID');
			$this->db->bind(':name', $section);
			$this->db->bind(':examID', $id);
			$sectionDeleted = $this->db->execute();

			$this->db->query('UPDATE exams SET sectionsCount = sectionsCount - 1 WHERE ID = :ID');
			$this->db->bind(':ID', $id);
			$examUpdated = $this->db->execute();

			$passingUpdated = $this->calcPassing($id);

			if ($questionsDeleted && $sectionDeleted && $examUpdated && $passingUpdated) {
				return true;
			} else {
				return false;
			}
		}

		// delete question
		public function deleteQuestion($id, $section, $questionID)
		{
			$this->db->query('DELETE FROM questions WHERE ID = :ID');
			$this->db->bind(':ID', $questionID);
			$questionDeleted = $this->db->execute();

			$this->db->query('UPDATE sections SET questionsNum = questionsNum - 1 WHERE name = :name AND examID = :examID');
			$this->db->bind(':name', $section);
			$this->db->bind(':examID', $id);
			$sectionUpdated = $this->db->execute();

			$this->db->query('UPDATE exams SET questionsNum = questionsNum - 1 WHERE ID = :ID');
			$this->db->bind(':ID', $id);
			$examUpdated = $this->db->execute();

			$passingUpdated = $this->calcPassing($id);

			if ($questionDeleted && $sectionUpdated && $examUpdated && $passingUpdated) {
				return true;
			} else {
				return false;
			}
		}

		// delete invitation
		public function uninviteExaminee($id)
		{
			$this->db->query('DELETE FROM invitations WHERE ID = :ID');
			$this->db->bind(':ID', $id);

			if ($this->db->execute()) {
				return true;
			} else {
				return false;
			}
		}

		// calculate exam passing score, is called when the number of questions on an exam change
		public function calcPassing($id)
		{
			$this->db->query('UPDATE exams SET passingScore = ceil((passingRate / 100) * questionsNum) WHERE ID = :ID');
			$this->db->bind(':ID', $id);

			if ($this->db->execute()) {
				return true;
			} else {
				return false;
			}
		}

	}