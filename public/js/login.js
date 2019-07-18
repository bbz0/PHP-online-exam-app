const login = {

	forms: ['.input--username', '.input--password'],

	init: function() {
		document.querySelector('.login--btn').addEventListener('click', login.login)
	},

	login: function() {
		let errorCount = 0;
		login.forms.forEach((f) => {
			f = document.querySelector(f);
			if (f.classList.contains('input--username')) {
				if (f.value.length < 4) {
					f.nextElementSibling.innerText = 'Must be at least 4 characters';
					f.classList.remove('is-valid');
					f.classList.add('is-invalid');
				} else {
					f.classList.remove('is-invalid');
					f.classList.add('is-valid');
				}
			} else if (f.classList.contains('input--password')) {
				if (f.value.length < 6) {
					f.nextElementSibling.innerText = 'Must be at least 6 characters';
					f.classList.remove('is-valid');
					f.classList.add('is-invalid');
				} else {
					f.classList.remove('is-invalid');
					f.classList.add('is-valid');
				}
			}
			if (f.classList.contains('is-invalid')) {
				errorCount++;
			}
		});
		if (errorCount > 0) {
			return false;
		} else {
			return true;
		}
	},

};
