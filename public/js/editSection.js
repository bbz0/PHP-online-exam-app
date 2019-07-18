const editSection = {

	forms: ['input--question', 'input--choiceA', 'input--choiceB', 'input--choiceC', 'input--choiceD', 'input--choiceE'],

	btn: {
		addQuestionBtn: document.querySelector('.add--question'),
		addChoiceBtn: document.querySelectorAll('.add--choice'),
		removeQBtn: document.querySelectorAll('.remove--question'),
		removeChoiceBtn: document.querySelectorAll('.remove--choice'),
		addEChoiceBtn: document.querySelectorAll('.add--edit--choice'),
		submitBtn: document.querySelector('.save--questions'),
		addUCBtn: document.querySelectorAll('.add--update--choice')
	},

	init: function() {
		console.log('Connect');
		editSection.btn.addQuestionBtn.addEventListener('click', editSection.addQuestion);
		editSection.btn.removeQBtn.forEach((btn) => {
			btn.addEventListener('click', (event) => {
				editSection.removeQuestion(event);
			});
		});
		editSection.btn.addChoiceBtn.forEach((btn) => {
			btn.addEventListener('click', (event) => {
				editSection.addChoice(event);
			});
		});
		editSection.btn.removeChoiceBtn.forEach((btn) => {
			btn.addEventListener('click', (event) => {
				editSection.removeChoice(event);
			});
		});
		editSection.btn.addUCBtn.forEach((btn) => {
			btn.addEventListener('click', (event) => {
				editSection.addChoice(event);
			});
		});
	},

	addQuestion: function() {
		let questionsCont = document.querySelector('.questions--container');
		var currNum;
		if (questionsCont.lastElementChild != null) {
			currNum = Number(questionsCont.lastElementChild.id);
		} else {
			currNum = 0;
		}

		let newNum = currNum + 1;
		let html = `<div class="question mb-5" id="${newNum}">
						<div class="form-group">
							<button type="button" class="btn btn-danger btn-sm float-right mb-2 remove--question">Remove</button>
							<label for="question[${newNum}]">Question</label>
							<textarea class="form-control input--question" name="question[${newNum}]" id="question[${newNum}]" maxlength="255"></textarea>
							<span class="invalid-feedback"></span>
						</div>
						<div class="choices--container">
							
							<div class="input-group mt-3">
								<div class="input-group-prepend">
									<div class="input-group-text">
										<div class="form-check">
											<input class="form-check-input" type="radio" id="correctAnswerA[${newNum}]" name="correctAnswer[${newNum}]" value="A" checked>
											<label class="form-check-label" for="correctAnswerA[${newNum}]">A</label>
										</div>
									</div>
								</div>
								<input type="text" class="form-control input--choiceA" name="choiceA[${newNum}]" id="choiceA[${newNum}]" maxlength="255">
							</div>
							<div class="input-group mt-3">
								<div class="input-group-prepend">
									<div class="input-group-text">
										<div class="form-check">
											<input class="form-check-input" type="radio" id="correctAnswerB[${newNum}]" name="correctAnswer[${newNum}]" value="B">
											<label class="form-check-label" for="correctAnswerB[${newNum}]">B</label>
										</div>
									</div>
								</div>
								<input type="text" class="form-control input--choiceA" name="choiceB[${newNum}]" id="choiceB[${newNum}]" maxlength="255">
							</div>

						</div>
						<button type="button" class="btn btn-success btn-sm mt-3 add--choice">Add Choice</button>
					</div>`;
		questionsCont.insertAdjacentHTML('beforeend', html);
		let newQuestion = questionsCont.lastElementChild;
		let removeQBtn = newQuestion.children[0].children[0];
		removeQBtn.addEventListener('click', (event) => {
			editSection.removeQuestion(event);
		});
		let addCBtn = newQuestion.children[2];
		addCBtn.addEventListener('click', (event) => {
			editSection.addChoice(event);
		})
	},

	removeQuestion: function(event) {
		let questionsCont = event.target.parentElement.parentElement.parentElement;
		let question = event.target.parentElement.parentElement;
		questionsCont.removeChild(question);
	},

	addChoice: function(event) {
		let choicesCont = event.target.parentElement.children[1];
		let questionNum = choicesCont.parentElement.id;
		let lastChoice = choicesCont.lastElementChild.children[0].children[0].children[0].children[0].value;
		var radioName, inputName
		if (event.target.classList.contains('add--update--choice')) {
			radioName = 'updateCorrectAnswer';
			inputName = 'updateChoice';
		} else {
			radioName = 'correctAnswer';
			inputName = 'choice';
		}
		let nextChoice = editSection.incrementChoice(lastChoice);
		let html = `<div class="input-group mt-3">
						<div class="input-group-prepend">
							<div class="input-group-text">
								<div class="form-check">
									<input class="form-check-input" type="radio" id="correctAnswer${nextChoice}[${questionNum}]" name="${radioName}[${questionNum}]" value="${nextChoice}">
									<label class="form-check-label" for="correctAnswer${nextChoice}[${questionNum}]">${nextChoice}</label>
								</div>
							</div>
						</div>
						<input type="text" class="form-control input--choice${nextChoice}" name="${inputName}${nextChoice}[${questionNum}]" id="choice${nextChoice}[${questionNum}]" maxlength="255">
						<div class="input-group-append">
							<button class="btn btn-danger btn-sm remove--choice">Remove</button>
						</div>
					</div>`;
		if (lastChoice !== 'E') {
			if (lastChoice === 'C' || lastChoice === 'D') {
				let removeCBtn = choicesCont.lastElementChild.children[2];
				choicesCont.lastElementChild.removeChild(removeCBtn);
			}
			choicesCont.insertAdjacentHTML('beforeend', html);
			choicesCont.lastElementChild.children[2].children[0].addEventListener('click', (event) => {
				editSection.removeChoice(event);
			});
			if (lastChoice === 'D') {
				event.target.setAttribute('disabled', '');
			}
		}
	},

	removeChoice: function(event) {
		let choicesCont = event.target.parentElement.parentElement.parentElement;
		let choice = event.target.parentElement.parentElement;
		choicesCont.removeChild(choice);
		let lastChoice = choicesCont.lastElementChild;
		let lastLetterChoice = lastChoice.children[0].children[0].children[0].children[0].value;
		if (lastLetterChoice.charCodeAt(0) > 66 ) {
			let newRBtn = `<div class="input-group-append"><button class="btn btn-danger btn-sm remove--choice">Remove</button></div>`;
			lastChoice.insertAdjacentHTML('beforeend', newRBtn);
			lastChoice.children[2].children[0].addEventListener('click', (event) => {
				editSection.removeChoice(event);
			});
		}
		if (lastLetterChoice.charCodeAt(0) < 69) {
			let addCBtn = choicesCont.nextElementSibling;
			addCBtn.removeAttribute('disabled');
		}
	},

	save: function() {
		let errorCount = 0
		editSection.forms.forEach((f) => {
			f = document.querySelectorAll('.' + f);
			if (f != null) {
				f.forEach((form) => {
					if (form.value.length < 5) {
						if (form.classList.contains('input--question')) {
							form.nextElementSibling.innerText = 'Must be at least 5 characters';
						}
						form.classList.remove('is-valid');
						form.classList.add('is-invalid');
						errorCount++;
					} else {
						form.classList.remove('is-invalid');
						form.classList.add('is-valid');
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

	incrementChoice: function(letter) {
		return String.fromCharCode(letter.charCodeAt(0) + 1);
	}
};

document.addEventListener('DOMContentLoaded', editSection.init);