/**
 * Системаи муосири фурӯши чиптаҳои қатора (2026-2027)
 * Скрипти интерактивӣ барои интихоби ҷой ва ҳисобкунаки 15-дақиқагӣ
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Интихоби ҷой дар нақшаи вагон (Seat Selection)
    const seatBoxes = document.querySelectorAll('.seat-available');
    const selectedSeatInput = document.getElementById('selectedSeatInput');
    const seatDisplayBadge = document.getElementById('selectedSeatNumberDisplay');
    const submitSeatBtn = document.getElementById('submitSeatBtn');
    const seatPriceDisplay = document.getElementById('seatPriceDisplay');
    const basePrice = parseFloat(document.getElementById('baseCarPrice')?.value || 0);

    seatBoxes.forEach(seat => {
        seat.addEventListener('click', function() {
            // Агар ҷой банд ё фурӯхташуда бошад, ҳеҷ кор намекунад
            if (this.classList.contains('seat-sold') || this.classList.contains('seat-reserved')) {
                return;
            }

            // Тоза кардани интихоби қаблӣ
            seatBoxes.forEach(s => s.classList.remove('seat-selected'));

            // Интихоби ҷойи нав
            this.classList.add('seat-selected');
            const seatId = this.getAttribute('data-seat-id');
            const seatNumber = this.getAttribute('data-seat-number');
            const seatType = this.getAttribute('data-seat-type');

            if (selectedSeatInput) {
                selectedSeatInput.value = seatId;
            }

            if (seatDisplayBadge) {
                seatDisplayBadge.textContent = `Ҷойи №${seatNumber} (${seatType})`;
                seatDisplayBadge.classList.remove('hidden');
            }

            if (seatPriceDisplay) {
                seatPriceDisplay.textContent = basePrice.toFixed(2) + ' сомонӣ';
            }

            if (submitSeatBtn) {
                submitSeatBtn.disabled = false;
                submitSeatBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        });
    });

    // 2. Ҳисобкунаки 15 дақиқа барои бандкунӣ (15-Minute Reservation Countdown Timer)
    const countdownElement = document.getElementById('reservationCountdown');
    if (countdownElement) {
        let expireTimestamp = parseInt(countdownElement.getAttribute('data-expires-timestamp'), 10);

        function updateCountdown() {
            const now = Math.floor(Date.now() / 1000);
            let timeLeft = expireTimestamp - now;

            if (timeLeft <= 0) {
                countdownElement.innerHTML = "<span class='text-red-400 font-bold'>Муҳлат ба охир расид!</span>";
                alert('Муҳлати 15-дақиқагии банд кардани ин ҷой ба анҷом расид. Ҷой худкор холӣ шуд.');
                window.location.reload();
                return;
            }

            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;

            const formattedTime = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            countdownElement.textContent = formattedTime;
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);
    }
});

// Функсияи чопи чипта (Print / Save as PDF)
function printTicket() {
    window.print();
}
