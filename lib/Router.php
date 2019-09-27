<?php
	// router class
	class Router
	{
		protected $controller = 'Pages'; // set default current controller to 'Pages'
		protected $method = 'index'; // set default current controller method to 'index'
		protected $params = []; // array of paremeters passed to controller method

		public function __construct()
		{
			$url = $this->getUrl();
			$url = $this->assignController($url);
			$url = $this->assignMethod($url);
			$url = $this->assignParams($url);
			// calls the current controller class and method with the arguments if there are any
			call_user_func_array([$this->controller, $this->method], $this->params);
		}

		// gets the url, separates the parameters and stores the strings into an array, then return the array
		public function getUrl()
		{
			if (isset($_GET['url'])) {
				$url = rtrim($_GET['url'], '/');
				$url = filter_var($url, FILTER_SANITIZE_URL);
				$url = explode('/', $url);
				return $url;
			}
		}

		// from the url array, the 0 index is the name of the controller class
		// method then checks if controller class exists and reassigns the current controller property
		// if controller class does not exist the default class is loaded
		public function assignController($url)
		{
			if (file_exists('../src/controllers/' . ucwords($url[0]) . '.php')) {
				$this->controller = ucwords($url[0]);
				unset($url[0]); // unset the 0 index
			}
			require_once '../src/controllers/' . $this->controller . '.php';
			$this->controller = new $this->controller;
			return $url;
		}

		// checks the 1 index in the url array
		// reassigns the current controller method if it exists
		public function assignMethod($url)
		{
			if (isset($url[1])) {
				if (method_exists($this->controller, $url[1])) {
					$this->method = $url[1];
					unset($url[1]);
					return $url;
				}
			}
		}

		// check the other indexes of the url array
		// if there are, the method stores them in the params array
		// these parameters are usually page ids
		public function assignParams($url)
		{
			if ($url) {
				$this->params = array_values($url);
			} else {
				$this->params = [];
			}
			return $url;
		}
	}