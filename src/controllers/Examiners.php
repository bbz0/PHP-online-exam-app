<?php
	class Examiners extends Controller
	{
		private $examinerModel;
		private $examModel;

		public function __construct()
		{
			parent::__construct();
			$this->examinerModel = $this->model('Examiner');
			$this->examModel = $this->model('Exam');
		}

		public function register()
		{
			$data = [
				'title' => 'Examiner Registration',
			];

			if (!isLoggedIn('examiner') && !isLoggedIn('examinee')) {
				if ($_SERVER['REQUEST_METHOD'] == 'POST') {
					$data['examinerData'] = $this->sanitize($_POST);
					$data['examinerData'] = $this->checkExistingUsername($data['examinerData'], $this->examinerModel);
					$data['examinerData'] = $this->confirmPassword($data['examinerData']);
					if ($data['examinerData']['errorCount'] === 0) {
						if ($this->examinerModel->register($data['examinerData'])) {
							flashMessage('Registration Successful!', 'success');
							redirect('examiners/login');
						} else {
							flashMessage('Something went wrong.', 'danger');
							$this->loadView('examiners', 'register', $data);
						}
					} else {
						$this->loadView('examiners', 'register', $data);
					}
				} else {
					$this->loadView('examiners', 'register', $data);
				}
			} else {
				redirect('/');
			}
		}

		public function login()
		{
			$data = [
				'title' => 'Examiner Login'
			];

			if (!isLoggedIn('examiner') && !isLoggedIn('examinee')) {
				if ($_SERVER['REQUEST_METHOD'] == 'POST') {
					$data['loginData'] = $this->sanitize($_POST);
					$data['loginData'] = $this->checkUsername($data['loginData'], $this->examinerModel);
					if ($data['loginData']['errorCount'] == 0) {
						$examiner = $this->examinerModel->login($data['loginData']['username'], $data['loginData']['password']);
						if ($examiner) {
							$this->setUserSession($examiner, 'examiner');
						} else {
							$data['loginData']['error']['password'] = 'password: incorrect password';
							$this->loadView('examiners', 'login', $data);
						}
					} else {
						$this->loadView('examiners', 'login', $data);
					}
				} else {
					$this->loadView('examiners', 'login', $data);
				}
			} else {
				redirect('/');
			}
		}

		public function dashboard()
		{
			$data = [
				'title' => 'Examiner Dashboard'
			];

			if (isLoggedIn('examiner')) {
				$data = $this->setSessionData($data, 'examiner');
				$data['exams'] = $this->examModel->getExaminerExams($data['examiner']['ID']);
				$this->loadView('examiners', 'dashboard', $data);
			} else {
				flashMessage('Please Login first!', 'warning');
				redirect('/');
			}
		}

		public function logout()
		{
			unset($_SESSION['examiner']['id']);
			unset($_SESSION['examiner']['username']);
			unset($_SESSION['examiner']['firstName']);
			unset($_SESSION['examiner']['lastName']);
			unset($_SESSION['examiner']);
			session_destroy();
			redirect('examiners/login');
		}
	}