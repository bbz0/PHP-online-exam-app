<?php
	// Examiner user class
	class Examiners extends Controller
	{
		private $examinerModel;
		private $examModel;

		public function __construct()
		{
			parent::__construct();
			$this->examinerModel = $this->model('Examiner'); // load examiner user model class
			$this->examModel = $this->model('Exam'); // load exam model class
		}

		// examiner user registration method
		public function register()
		{
			$data = [
				'title' => 'Examiner Registration',
			];
			// if not logged in
			if (!isLoggedIn('examiner') && !isLoggedIn('examinee')) {
				// listen for post request, if none load view
				if ($_SERVER['REQUEST_METHOD'] == 'POST') {
					// sanitize user input
					$data['examinerData'] = $this->sanitize($_POST);
					$data['examinerData'] = $this->checkExistingUsername($data['examinerData'], $this->examinerModel);
					$data['examinerData'] = $this->confirmPassword($data['examinerData']);
					// if no error proceed with registration
					if ($data['examinerData']['errorCount'] === 0) {
						// call model method to register user
						if ($this->examinerModel->register($data['examinerData'])) {
							// if successful redirect to login page
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

		// examiner user login method
		public function login()
		{
			$data = [
				'title' => 'Examiner Login'
			];
			// if not logged in 
			if (!isLoggedIn('examiner') && !isLoggedIn('examinee')) {
				// listen for post request, if none load view
				if ($_SERVER['REQUEST_METHOD'] == 'POST') {
					// sanitize user input
					$data['loginData'] = $this->sanitize($_POST);
					$data['loginData'] = $this->checkUsername($data['loginData'], $this->examinerModel);
					// if no error proceed with login
					if ($data['loginData']['errorCount'] == 0) {
						// call model method to authenticate user
						$examiner = $this->examinerModel->login($data['loginData']['username'], $data['loginData']['password']);
						// if logged in set session
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

		// examiner user dashboard method
		public function dashboard()
		{
			$data = [
				'title' => 'Examiner Dashboard'
			];
			// check if logged in
			if (isLoggedIn('examiner')) {
				// retrieve user data
				$data = $this->setSessionData($data, 'examiner');
				$data['exams'] = $this->examModel->getExaminerExams($data['examiner']['ID']);
				// load dashboard view
				$this->loadView('examiners', 'dashboard', $data);
			} else {
				flashMessage('Please Login first!', 'warning');
				redirect('/');
			}
		}

		// unsets current user session then logs out the user
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