<?php
	// controller class for static pages
	class Pages extends Controller
	{
		public function index()
		{
			$data = [
				'test' => 'Hello World'
			];

			// if there are users logged in redirect to their respective dashboards
			if (isLoggedIn('examiner')) {
				redirect('examiners/dashboard');
			} elseif (isLoggedIn('examinee')) {
				redirect('examinees/dashboard');
			} else {
				echo $this->twig->render('index.html.twig', $data);
			}
		}
	}