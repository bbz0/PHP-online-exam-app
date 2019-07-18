<?php
	session_start();

	function flashMessage($message, $class) {
		$_SESSION['flashMessage'] = $message;
		$_SESSION['msgClass'] = $class;
	}

	function unsetMsg() {
		unset($_SESSION['flashMessage']);
		unset($_SESSION['msgClass']);
	}

	function redirect($page) {
		header('location: ' . URLROOT . '/' . $page);
	}

	function isLoggedIn($user) {
		if (isset($_SESSION[$user])) {
			return true;
		} else {
			return false;
		}
	}