<?php
	class Pages extends Controller
	{
		public function index()
		{
			$data = [
				'test' => 'Hello World'
			];

			echo $this->twig->render('index.html.twig', $data);
		}
	}