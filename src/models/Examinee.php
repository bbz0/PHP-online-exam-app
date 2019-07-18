<?php
	class Examinee
	{
		private $db;

		public function __construct()
		{
			$this->db = new Database;
		}

		public function register($data)
		{
			$data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

			$this->db->query('INSERT INTO examinees(username, password, firstName, lastName) VALUES (:username, :password, :firstName, :lastName)');
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
			$this->db->query('SELECT * FROM examinees WHERE username = :username');
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
			$this->db->query('SELECT * FROM examinees WHERE username = :username');
			$this->db->bind(':username', $username);

			$row = $this->db->single();

			if($this->db->rowCount() > 0) {
				return true;
			} else {
				return false;
			}	
		}

		public function getStats($id)
		{
			$this->db->query('SELECT examsTaken, examsPassed, examsFailed, totalScore FROM examinees WHERE ID = :ID');
			$this->db->bind(':ID', $id);
			$stats = $this->db->single();

			return $stats;
		}

		public function getRecords($id)
		{
			$this->db->query('SELECT exams.name as examName, score, items, result, started, finished 
				FROM examRecords 
				INNER JOIN exams ON exams.ID = examID
				WHERE examineeID = :examineeID');
			$this->db->bind(':examineeID', $id);
			$records = $this->db->resultSet();

			return $records;
		}
	}