document.addEventListener('DOMContentLoaded', function () {
	var form = document.getElementById('registration-form');
	var wheel = document.getElementById('winner-wheel');
	initializeProfilePhotoModal();
	initializeCountdowns();

	if (form) {
		form.addEventListener('submit', function (event) {
			var firstName = document.getElementById('firstName');
			var lastName = document.getElementById('lastName');
			var email = document.getElementById('email');

			if (!firstName.value.trim() || !lastName.value.trim() || !email.value.trim()) {
				event.preventDefault();
				window.alert('يرجى إكمال جميع البيانات المطلوبة.');
				return;
			}

			if (!email.validity.valid) {
				event.preventDefault();
				window.alert('يرجى إدخال بريد إلكتروني صحيح.');
			}
		});
	}

	if (!wheel) {
		initializeParticipantManagement();
		return;
	}

	initializeParticipantManagement();

	var participants = JSON.parse(wheel.getAttribute('data-participants') || '[]');
	var labels = wheel.querySelector('.wheel-labels');
	var button = document.getElementById('spin-button');
	var result = document.getElementById('draw-result');
	var eligibleCount = document.getElementById('eligible-count');
	var winnerCount = document.getElementById('winner-count');
	var historyContent = document.getElementById('winner-history-content');
	var isSpinning = false;
	var autoWinner = JSON.parse(wheel.getAttribute('data-auto-winner') || 'null');
	var drawEndAt = Number(wheel.getAttribute('data-draw-end-at') || '0');

	function renderWheel() {
		labels.innerHTML = '';
		if (!participants.length) {
			wheel.style.background = 'var(--line)';
			return;
		}

		var segmentAngle = 360 / participants.length;
		var colors = ['#087f82', '#d98b45', '#3c7a70', '#b95e4d', '#5e7181', '#c49a45'];
		var gradient = participants.map(function (_, index) {
			var start = index * segmentAngle;
			return colors[index % colors.length] + ' ' + start + 'deg ' + (start + segmentAngle) + 'deg';
		}).join(', ');
		wheel.style.background = 'conic-gradient(from -90deg, ' + gradient + ')';

		participants.forEach(function (participant, index) {
			var angle = (index + .5) * segmentAngle;
			var radians = (angle - 90) * Math.PI / 180;
			var label = document.createElement('span');
			label.className = 'wheel-label';
			label.textContent = (participant.firstName + ' ' + participant.lastName).trim();
			label.style.left = (50 + Math.cos(radians) * 35) + '%';
			label.style.top = (50 + Math.sin(radians) * 35) + '%';
			label.style.transform = 'translate(-50%, -50%)';
			labels.appendChild(label);
		});
	}

	function showMessage(message) {
		result.className = 'draw-result';
		result.innerHTML = '<p class="result-kicker">حالة السحب</p><h3>' + message + '</h3>';
	}

	function addWinnerToHistory(winner) {
		if (!historyContent) {
			return;
		}
		if (historyContent.querySelector('.empty-state')) {
			historyContent.innerHTML = '';
		}
		var table = historyContent.querySelector('table');
		if (!table) {
			historyContent.innerHTML = '<div class="history-table-wrap"><table class="history-table"><thead><tr><th>#</th><th>اسم الفائز</th><th>رقم السحب</th><th>التاريخ</th></tr></thead><tbody></tbody></table></div>';
			table = historyContent.querySelector('table');
		}
		var row = table.querySelector('tbody').insertRow(0);
		row.innerHTML = '<td>' + winner.drawNumber + '</td><td>' + winner.name + '</td><td>' + winner.drawNumber + '</td><td dir="ltr">' + winner.createdAt + '</td>';
	}

	function spin() {
		if (isSpinning || !participants.length) {
			return;
		}
		isSpinning = true;
		button.disabled = true;
		showMessage('جارٍ تدوير العجلة...');

		fetch('incloud/select_winner.php', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
			.then(function (response) { return response.json().then(function (data) { return { ok: response.ok, data: data }; }); })
			.then(function (response) {
				if (!response.ok || !response.data.success) {
					throw new Error(response.data.message || 'حدث خطأ أثناء إجراء السحب.');
				}

				var winner = response.data.winner;
				var winnerIndex = participants.findIndex(function (participant) { return Number(participant.id) === Number(winner.id); });
				if (winnerIndex < 0) {
					throw new Error('تعذر مطابقة الفائز مع العجلة.');
				}

				var segmentAngle = 360 / participants.length;
				var targetRotation = 360 * (5 + Math.floor(Math.random() * 2)) - ((winnerIndex + .5) * segmentAngle);
				wheel.style.transform = 'rotate(' + targetRotation + 'deg)';

				window.setTimeout(function () {
					result.className = 'draw-result winner-result';
					result.innerHTML = '<p class="result-kicker">مبروك!</p><h3>الفائز هو:</h3><strong class="winner-name">' + winner.name + '</strong><p>رقم السحب: ' + winner.drawNumber + '</p>';
					participants = participants.filter(function (participant) { return Number(participant.id) !== Number(winner.id); });
					wheel.setAttribute('data-participants', JSON.stringify(participants));
					wheel.style.transition = 'none';
					wheel.style.transform = 'rotate(0deg)';
					window.requestAnimationFrame(function () {
						wheel.style.transition = '';
						renderWheel();
					});
					eligibleCount.textContent = participants.length;
					winnerCount.textContent = Number(winnerCount.textContent) + 1;
					addWinnerToHistory(winner);
					if (!participants.length) {
						button.disabled = true;
					}
					isSpinning = false;
				}, 5500);
			})
			.catch(function (error) {
				showMessage(error.message || 'حدث خطأ أثناء إجراء السحب.');
				button.disabled = false;
				isSpinning = false;
			});
	}

	button.addEventListener('click', spin);
	renderWheel();

	function initializeParticipantManagement() {
		document.querySelectorAll('.delete-participant-form').forEach(function (deleteForm) {
			deleteForm.addEventListener('submit', function (event) {
				if (!window.confirm('هل أنت متأكد من حذف هذا المشترك')) {
					event.preventDefault();
				}
			});
		});
	}

	function initializeCountdowns() {
		document.querySelectorAll('[data-countdown-end]').forEach(function (countdown) {
			var endTimestamp = Number(countdown.getAttribute('data-countdown-end')) * 1000;
			if (!endTimestamp) {
				return;
			}

			function updateCountdown() {
				var remaining = Math.max(0, endTimestamp - Date.now());
				if (remaining <= 0) {
					countdown.textContent = '00 أيام : 00 ساعة : 00 دقيقة : 00 ثانية';
					return;
				}

				var totalSeconds = Math.floor(remaining / 1000);
				var days = Math.floor(totalSeconds / 86400);
				var hours = Math.floor((totalSeconds % 86400) / 3600);
				var minutes = Math.floor((totalSeconds % 3600) / 60);
				var seconds = totalSeconds % 60;
				countdown.textContent = String(days).padStart(2, '0') + ' أيام : ' + String(hours).padStart(2, '0') + ' ساعة : ' + String(minutes).padStart(2, '0') + ' دقيقة : ' + String(seconds).padStart(2, '0') + ' ثانية';
				window.setTimeout(updateCountdown, 1000);
			}

			updateCountdown();
		});
	}

	if (autoWinner && participants.length) {
		var winnerIndex = participants.findIndex(function (participant) {
			return Number(participant.id) === Number(autoWinner.id);
		});
		if (winnerIndex >= 0) {
			var segmentAngle = 360 / participants.length;
			var targetRotation = 360 * (5 + Math.floor(Math.random() * 2)) - ((winnerIndex + .5) * segmentAngle);
			wheel.style.transition = 'transform 5.4s cubic-bezier(.12, .72, .18, 1)';
			wheel.style.transform = 'rotate(' + targetRotation + 'deg)';
			window.setTimeout(function () {
				result.className = 'draw-result winner-result';
				result.innerHTML = '<p class="result-kicker">مبروك!</p><h3>تم اختيار الفائز</h3><strong class="winner-name">' + autoWinner.name + '</strong><p>رقم السحب: ' + autoWinner.drawNumber + '</p>';
				button.disabled = true;
				if (eligibleCount) {
					eligibleCount.textContent = '0';
				}
			}, 5500);
		}
	}

	function initializeProfilePhotoModal() {
		var modal = document.getElementById('profile-photo-modal');
		var photo = document.querySelector('.profile-photo-trigger');
		if (!modal || !photo) {
			return;
		}

		function closeModal() {
			modal.hidden = true;
			document.body.classList.remove('modal-open');
		}

		function openModal(event) {
			event.preventDefault();
			event.stopPropagation();
			modal.hidden = false;
			document.body.classList.add('modal-open');
		}

		photo.addEventListener('click', openModal);
		modal.querySelectorAll('[data-close-profile-photo]').forEach(function (closeButton) {
			closeButton.addEventListener('click', closeModal);
		});
		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape' && !modal.hidden) {
				closeModal();
			}
		});
	}
});
