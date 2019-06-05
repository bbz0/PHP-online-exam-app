<?php
	class Router
	{
		protected $controller = 'Pages';
		protected $method = 'index';
		protected $params = [];

		public function __construct()
		{
			$url = $this->getUrl();
			$url = $this->assignController($url);
			$url = $this->assignMethod($url);
			$url = $this->assignParams($url);
			call_user_func_array([$this->controller, $this->method], $this->params);
		}

		public function getUrl()
		{
			if (isset($_GET['url'])) {
				$url = rtrim($_GET['url'], '/');
				$url = filter_var($url, FILTER_SANITIZE_URL);
				$url = explode('/', $url);
				return $url;
			}
		}

		public function assignController($url)
		{
			if (file_exists('../src/controllers/' . ucwords($url[0]) . '.php')) {
				$this->controller = ucwords($url[0]);
				unset($url[0]);
			}
			require_once '../src/controllers/' . $this->controller . '.php';
			$this->controller = new $this->controller;
			return $url;
		}

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