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

		public function model($model)
		{
			require_once '../src/models/' . $model . '.php';
			return new $model;
		}
	}