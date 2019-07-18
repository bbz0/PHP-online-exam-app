<?php
	class Examiner
	{
		private $db;

		public function __construct()
		{
			$this->db = new Database;
		}

		public function register($data)
		{
			$data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

			$this->db->query('INSERT INTO examiners(username, password, firstName, lastName) VALUES (:username, :password, :firstName, :lastName)');
			$this->db->bind(':username', $data['username']);
			$this->db->bind(':password', $data['password']);
			$this->db->bind(':firstName', $data['firstName']);
			$this->db->bind(':lastName', $data['lastName']);

			if ($this->db->execute()) {
				return true;
			} else {
				return false;
			}
		}

		public function login($username, $password)
		{
			$this->db->query('SELECT * FROM examiners WHERE username = :username');
			$this->db->bind(':username', $username);
			$row = $this->db->single();

			$hashedPassword = $row['password'];
			if (password_verify($password, $hashedPassword)) {
				return $row;
			} else {
				return false;
			}
		}

		public function checkUsername($username)
		{
			$this->db->query('SELECT * FROM examiners WHERE username = :username');
			$this->db->bind(':username', $username);

			$row = $this->db->single();

			if($this->db->rowCount() > 0) {
				return true;
			} else {
				return false;
			}	
		}

		public function getRequests($id)
		{
			$this->db->query('SELECT requests.ID, examID, examineeID, 
				exams.name as examName,
				examinees.username as examineeName
				FROM requests 
				INNER JOIN exams ON exams.ID = examID
				INNER JOIN examinees ON examinees.ID = examineeID
				WHERE examinerID = :examinerID
				AND approved = FALSE');
			$this->db->bind(':examinerID', $id);

			$requests = $this->db->resultSet();

			return $requests;
		}

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
	}