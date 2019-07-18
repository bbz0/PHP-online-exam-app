<?php
	class Examinees extends Controller
	{
		public function __construct()
		{
			parent::__construct();
			$this->examineeModel = $this->model('Examinee');
		}

		public function register()
		{
			$data = [
				'title' => 'Examinee Registration'
			];

			if (!isLoggedIn('examinee')) {
				if ($_SERVER['REQUEST_METHOD'] == 'POST') {
					$data['examinee'] = $this->sanitize($_POST);
					$data['examinee'] = $this->checkExistingUsername($data['examinee'], $this->examineeModel);
					$data['examinee'] = $this->confirmPassword($data['examinee']);
					if ($data['examinee']['errorCount'] === 0) {
						if ($this->examineeModel->register($data['examinee'])) {
							flashMessage('Examinee successfully registered, Welcome!', 'success');
							redirect('examinees/login');
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

		public function login()
		{
			$data = [
				'title' => 'Examinee Login'
			];

			if (!isLoggedIn('examinee')) {
				if ($_SERVER['REQUEST_METHOD'] == 'POST') {
					$data['loginData'] = $this->sanitize($_POST);
					$data['loginData'] = $this->checkUsername($data['loginData'], $this->examineeModel);
					if ($data['loginData']['errorCount'] == 0) {
						$examinee = $this->examineeModel->login($data['loginData']['username'], $data['loginData']['password']);
						if ($examinee) {
							$this->setUserSession($examinee, 'examinee');
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

		public function dashboard()
		{
			$data = [
				'title' => 'Examinee Dashboard'
			];

			if (isLoggedIn('examinee')) {
				$data = $this->setSessionData($data, 'examinee');
				$data['records'] = $this->examineeModel->getRecords($data['examinee']['ID']);
				$stats = $this->examineeModel->getStats($data['examinee']['ID']);
				$data['examinee'] = array_merge($data['examinee'], $stats);
				$this->loadView('examinees', 'dashboard', $data);
			} else {
				flashMessage('Please Login first!', 'warning');
				redirect('examiners/login');
			}
		}

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