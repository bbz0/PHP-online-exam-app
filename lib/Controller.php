<?php
	class Controller
	{
		protected $loader;
		protected $twig;

		public function __construct()
		{
			$this->loader = new \Twig\Loader\FilesystemLoader('../views/');
			$this->twig = new \Twig\Environment($this->loader);
		}

		public function loadView($method, $view, $data)
		{
			$data = $this->checkMsgs($data);
			echo $this->twig->render( $method . '/' . $view . '.html.twig', $data);
		}

		public function model($model)
		{
			require_once '../src/models/' . $model . '.php';
			return new $model;
		}

		// Sanitize post data
		public function sanitize($post)
		{
			unset($post['submit']);
			$post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

			$keys = array_keys($post);
			$data = [];
			$data['errorCount'] = 0;
			for ($i = 0; $i < count($post); $i++) {
				if ($keys[$i] === 'firstName' || $keys[$i] === 'lastName') {
					if (strlen($post[$keys[$i]]) > 0) {
						$post[$keys[$i]] = ucwords($post[$keys[$i]]);
						$data[$keys[$i]] = trim(substr(strip_tags($post[$keys[$i]]), 0, 50));
					} else {
						$data['error'][$keys[$i]] = 'invalid data';
						$data['errorCount']++;
					}
				} elseif ($keys[$i] === 'username' || $keys[$i] === 'name') {
					if (strlen($post[$keys[$i]]) > 3) {
						$data[$keys[$i]] = trim(substr(strip_tags($post[$keys[$i]]), 0, 50));
					} else {
						$data['error'][$keys[$i]] = 'must be at least 4 characters';
						$data['errorCount']++;
					}
				} elseif ($keys[$i] === 'password') {
					if (strlen($post[$keys[$i]]) > 5) {
						$data[$keys[$i]] = trim(substr(strip_tags($post[$keys[$i]]), 0, 50));
					} else {
						$data['error'][$keys[$i]] = 'must be at least 6 characters';
						$data['errorCount']++;
					}
				} elseif ($keys[$i] === 'section' || $keys[$i] === 'sectionEdit' || $keys[$i] === 'sectionID') {
					for ($x = 0; $x < count($post[$keys[$i]]); $x++) {
						if ($keys[$i] === 'sectionID') {
							$data[$keys[$i]][$x] = trim(substr(strip_tags($post[$keys[$i]][$x]), 0, 50));
						} elseif (strlen($post[$keys[$i]][$x]) > 3) {
							$post[$keys[$i]][$x] = ucwords($post[$keys[$i]][$x]);
							$data[$keys[$i]][$x] = trim(substr(strip_tags($post[$keys[$i]][$x]), 0, 50));
						} else {
							$data['error'][$keys[$i]][$x] = 'must be at least 4 characters';
							$data['errorCount']++;
						}
					}
				} elseif ($keys[$i] === 'desc') { 
					if (strlen($post[$keys[$i]]) > 9) {
						$data[$keys[$i]] = trim(substr(strip_tags($post[$keys[$i]]), 0, 255));
					} else {
						$data['error'][$keys[$i]] = 'must be at least 10 characters';
						$data['errorCount']++;
					}
				} else {
					$data[$keys[$i]] = trim(substr(strip_tags($post[$keys[$i]]), 0, 255));
				}
			}

			return $data;
		}

		public function checkExistingUsername($data, $model)
		{	
			if (!empty($data['username'])) {
				if ($model->checkUsername($data['username'])) {
					$data['error']['username'] = 'User already exists';
					$data['errorCount']++;
				}
			}
			return $data;
		}

		public function confirmPassword($data)
		{
			if (!empty($data['password']) && !empty($data['confirmPassword'])) {
				if (strlen($data['password']) >= 4 && strlen($data['confirmPassword']) >= 4) {
					if ($data['password'] !== $data['confirmPassword']) {
						$data['error']['confirmPassword'] = 'confirmPassword: Passwords do not match';
						$data['errorCount']++;
					}
				}
			}
			return $data;
		}

		public function checkUsername($data, $model)
		{
			if (!empty($data['username'])) {
				if (!$model->checkUsername($data['username'])) {
					$data['error']['username'] = 'No user found';
					$data['errorCount']++;
				}
			}
			return $data;
		}

		// check for any flash messages and store into data
		public function checkMsgs($data)
		{
			if (isset($_SESSION['flashMessage']) && isset($_SESSION['msgClass'])) {
				$data['msgs']['flashMessage'] = $_SESSION['flashMessage'];
				$data['msgs']['msgClass'] = $_SESSION['msgClass'];
				unsetMsg();
			}
			return $data;
		}

		public function setUserSession($user, $userType)
		{
			$_SESSION[$userType]['id'] = $user['ID'];
			$_SESSION[$userType]['username'] = $user['username'];
			$_SESSION[$userType]['firstName'] = $user['firstName'];
			$_SESSION[$userType]['lastName'] = $user['lastName'];
			flashMessage('Welcome ' . $_SESSION[$userType]['username'] . '!', 'success');
			redirect( $userType . 's/dashboard');
		}

		public function setSessionData($data, $user)
		{
			$data[$user]['ID'] = $_SESSION[$user]['id'];
			$data[$user]['username'] = $_SESSION[$user]['username'];
			$data[$user]['firstName'] = $_SESSION[$user]['firstName'];
			$data[$user]['lastName'] = $_SESSION[$user]['lastName'];
			return $data;
		}

		public function totalTime($data)
		{
			$totalTime = 0;
			$totalTime += ($data['hours'] * 60 * 60);
			$totalTime += ($data['mins'] * 60);
			$totalTime *= 1000;
			$data['totalTime'] = $totalTime;
			return $data;
		}
	}