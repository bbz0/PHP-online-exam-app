const register = {

	forms: ['.input--firstName', '.input--lastName', '.input--username', '.input--password', '.input--confirm'],

	init: function() {
		console.log('connect');
		let regBtn = document.querySelector('.register--btn').addEventListener('click', register.register);
	},

	register: function() {
		let errorCount = 0;
		register.forms.forEach((f) => {
			f = document.querySelector(f);
			if (f.value.length > 0) {
				if (f.classList.contains('input--firstName') || f.classList.contains('input--lastName')) {
					if (f.value.length < 1) {
						f.classList.remove('is-valid');
						f.classList.add('is-invalid');
					} else {
						f.classList.remove('is-invalid');
						f.classList.add('is-valid');
					}
				} else if (f.classList.contains('input--username')) {
					if (f.value.length < 4) {
						f.nextElementSibling.innerText = 'must be at least 4 characters';
						f.classList.remove('is-valid');
						f.classList.add('is-invalid');
					} else {
						f.classList.remove('is-invalid');
						f.classList.add('is-valid');
					}
				} else if (f.classList.contains('input--password')) {
					if (f.value.length < 6) {
						f.nextElementSibling.innerText = 'must be at least 6 characters';
						f.classList.remove('is-valid');
						f.classList.add('is-invalid');
					} else {
						f.classList.remove('is-invalid');
						f.classList.add('is-valid');
					}
				} else if (f.classList.contains('input--confirm')) {
					let passForm = document.querySelector('.input--password');
					if (f.value.length !== passForm.value.length) {
						f.nextElementSibling.innerText = 'passwords do not match';
						f.classList.remove('is-valid');
						f.classList.add('is-invalid');
					} else {
						f.classList.remove('is-invalid');
						f.classList.add('is-valid');
					}
				}
			} else {
				f.classList.add('is-invalid');
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

document.addEventListener('DOMContentLoaded', register.init);

