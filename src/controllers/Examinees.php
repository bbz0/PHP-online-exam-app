<?php
	// Examinee user class
	class Examinees extends Controller
	{
		public function __construct()
		{
			parent::__construct();
			$this->examineeModel = $this->model('Examinee'); // load examinee user model class
		}

		// loads the examinee registration page, if there is a post request, data is sanitized and passed on to the model
		public function register()
		{
			$data = [
				'title' => 'Examinee Registration'
			];
			// check if there are no logged in users
			if (!isLoggedIn('examinee')) {
				// listen for post request, if not load view
				if ($_SERVER['REQUEST_METHOD'] == 'POST') {
					// call main controller class methods
					$data['examinee'] = $this->sanitize($_POST);
					$data['examinee'] = $this->checkExistingUsername($data['examinee'], $this->examineeModel);
					$data['examinee'] = $this->confirmPassword($data['examinee']);

					// if no error pass registration input to the model and register the user
					if ($data['examinee']['errorCount'] === 0) {
						// if there's no error in the model
						if ($this->examineeModel->register($data['examinee'])) {
							flashMessage('Examinee successfully registered, Welcome!', 'success');
							redirect('examinees/login');
						// if something went wrong
						} else {
							flashMessage('Something went wrong', 'danger');
							$this->loadView('examinees', 'register', $data);
						}
					} else {
						$this->loadView('examinees', 'register', $data);
					}
				} else {
					$this->loadView('examinees', 'register', $data);
				}
			} else {
				redirect('/');
			}
		}

		// loads the examinee login page, if there is a post request, data is sanitized and passed on to the model
		public function login()
		{
			$data = [
				'title' => 'Examinee Login'
			];
			// check if there are no logged in users
			if (!isLoggedIn('examinee')) {
				// listen for post request, if not load view
				if ($_SERVER['REQUEST_METHOD'] == 'POST') {
					// call main controller class methods
					$data['loginData'] = $this->sanitize($_POST);
					$data['loginData'] = $this->checkUsername($data['loginData'], $this->examineeModel);

					// if no error pass login input to the model and login the user
					if ($data['loginData']['errorCount'] == 0) {
						$examinee = $this->examineeModel->login($data['loginData']['username'], $data['loginData']['password']);
						// if successfully logged in, a method is called to set the user session
						if ($examinee) {
							$this->setUserSession($examinee, 'examinee');
						// if something went wrong
						} else {
							$data['loginData']['error']['password'] = 'incorrect password';
							$this->loadView('examinees', 'login', $data);
						}
					} else {
						$this->loadView('examinees', 'login', $data);
					}
				} else {
					$this->loadView('examinees', 'login', $data);
				}
			} else {
				redirect('/');
			}
		}

		// user dashboard page
		public function dashboard()
		{
			$data = [
				'title' => 'Examinee Dashboard'
			];
			// checks if user is logged in
			if (isLoggedIn('examinee')) {
				$data = $this->setSessionData($data, 'examinee');
				// get user data
				$data['records'] = $this->examineeModel->getRecords($data['examinee']['ID']);
				$stats = $this->examineeModel->getStats($data['examinee']['ID']);
				$data['examinee'] = array_merge($data['examinee'], $stats);
				// load view
				$this->loadView('examinees', 'dashboard', $data);
			// if user is not logged in
			} else {
				flashMessage('Please Login first!', 'warning');
				redirect('examiners/login');
			}
		}

		// unsets current user session then logs out the user
		public function logout()
		{
			unset($_SESSION['examinees']['id']);
			unset($_SESSION['examinees']['username']);
			unset($_SESSION['examinees']['firstName']);
			unset($_SESSION['examinees']['lastName']);
			unset($_SESSION['examinees']);
			session_destroy();
			redirect('examinees/login');
		}
	}