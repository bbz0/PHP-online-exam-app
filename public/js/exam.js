const exam = {
	timesUp: false,
	time: {
		hours: Number(document.querySelector('.exam--hours').innerText) * 60 * 60 * 1000,
		minutes: Number(document.querySelector('.exam--minutes').innerText) * 60 * 1000,
	},

	submitExam: function() {
		document.querySelector('.submit--exam').click();
	},

	countDown: function() {
		let endTime = new Date().getTime() + exam.time.hours + exam.time.minutes;
		let totalSec = 0;

		let clock = setInterval(() => {
			totalSec += 100

			if (totalSec === 1000) {
				let remainingTime = exam.calcTime(endTime);
				if (remainingTime.hours < 10) {
					let hours = '0' + remainingTime.hours;
					document.querySelector('.count--h').innerText = hours;
				} else{
					document.querySelector('.count--h').innerText = remainingTime.hours;
				}

				if (remainingTime.minutes < 10) {
					let minutes = '0' + remainingTime.minutes;
					document.querySelector('.count--m').innerText = minutes;
				} else {
					document.querySelector('.count--m').innerText = remainingTime.minutes;
				}

				if (remainingTime.seconds < 10) {
					let seconds = '0' + remainingTime.seconds;
					document.querySelector('.count--s').innerText = seconds;
				} else {
					document.querySelector('.count--s').innerText = remainingTime.seconds;
				}

				if (remainingTime.total < 0) {
					exam.submitExam();
				}
			}

			if (totalSec === 1000) {
				totalSec = 0;
			}
		}, 100);
	},

	calcTime: function(endTime) {
		let now = new Date().getTime();
		let difference = endTime - now;
		let hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
		let minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
		let seconds = Math.floor((difference % (1000 * 60)) / 1000);

		return {
			hours: hours,
			minutes: minutes,
			seconds: seconds,
			total: difference
		}
	},

	init: function() {
		exam.countDown();
		window.addEventListener('unload', (e) => {
			exam.submitExam();
		});
	},
};

exam.init();