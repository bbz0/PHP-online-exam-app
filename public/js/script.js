const app = {

	other: {
		messageCont: document.querySelector('.message--container'),
		flashMessage: document.querySelector('.flash--message')
	},

	init: function() {
		app.flashMessage();
	},

	flashMessage: function() {
		if (app.other.messageCont !== null) {
			setTimeout(() => {
				app.other.messageCont.removeChild(app.other.flashMessage);
			}, 10000);
		}
	}
}

document.addEventListener('DOMContentLoaded', app.init);