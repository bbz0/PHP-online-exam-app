const createExam = {

	forms: ['.input--name', '.input--desc', '.input--section'],

	btn: {
		addSBtn: document.querySelector('.add--section'),
		delSBtn: document.querySelectorAll('.delete--section'),
		createBtn: document.querySelector('.create--btn')
	},

	init: function() {
		createExam.btn.addSBtn.addEventListener('click', createExam.addSection);
		createExam.btn.delSBtn.forEach((btn) => {
			btn.addEventListener('click', (event) => {
				createExam.deleteSection(event);
			});
		});
		createExam.btn.createBtn.addEventListener('click', createExam.createExam);
	},

	addSection: function() {
		let sectionCont = document.querySelector('.section--container');
		let html = `<div class="form-group">
						<div class="row">
							<div class="col-md-8">
								<input class="form-control input--section" type="text" name="section[]" placeholder="Section Name" maxlength="50">
								<span class="invalid-feedback"></span>
							</div>
							<div class="col-md-4">
								<button type="button" class="btn btn-danger delete--section">Remove</button>
							</div>
						</div>
					</div>`;
		sectionCont.insertAdjacentHTML('beforeend', html);
		let newSection = sectionCont.lastElementChild;
		let newRBtn = newSection.children[0].children[1].children[0];
		newRBtn.addEventListener('click', (event) => {
			createExam.deleteSection(event);
		});
	},

	deleteSection: function(event) {
		let sectionCont = document.querySelector('.section--container');
		let section = event.target.parentElement.parentElement.parentElement;
		sectionCont.removeChild(section);
	},

	createExam: function() {
		let errorCount = 0;
		createExam.forms.forEach((f) => {
			f = document.querySelectorAll(f);
			if (f != null) {
				f.forEach((form) => {
					if (form.classList.contains('input--desc')) {
						if (form.value.length < 10) {
							form.nextElementSibling.innerText = 'Must be at least 10 characters';
							form.classList.remove('is-valid');
							form.classList.add('is-invalid');
							errorCount++;
						} else {
							form.classList.remove('is-invalid');
							form.classList.add('is-valid');
						}
					} else {
						if (form.value.length < 4) {
							form.nextElementSibling.innerText = 'Must be at least 4 characters';
							form.classList.remove('is-valid');
							form.classList.add('is-invalid');
							errorCount++;
						} else {
							form.classList.remove('is-invalid');
							form.classList.add('is-valid');
						}
					}
				});
			}
		});
		if (errorCount > 0) {
			return false;
		} else {
			return true;
		}
	},

};

document.addEventListener('DOMContentLoaded', createExam.init);

