<?php
	// helper functions
	session_start();

	// set flash message into session
	function flashMessage($message, $class) {
		$_SESSION['flashMessage'] = $message;
		$_SESSION['msgClass'] = $class;
	}

	// unset flash message
	function unsetMsg() {
		unset($_SESSION['flashMessage']);
		unset($_SESSION['msgClass']);
	}

	// page redirect
	function redirect($page) {
		header('location: ' . URLROOT . '/' . $page);
	}

	// check if a user is logged in
	function isLoggedIn($user) {
		if (isset($_SESSION[$user])) {
			return true;
		} else {
			return false;
		}
	}