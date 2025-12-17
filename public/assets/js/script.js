// public/assets/js/script.js

document.addEventListener('DOMContentLoaded', function() {
    const bookingForm = document.getElementById('bookingForm');
    const mentorSelect = document.getElementById('mentor');
    const durationSelect = document.getElementById('duration');
    const estimatedPriceSpan = document.getElementById('estimatedPrice');
    const bookNowButton = bookingForm.querySelector('button[type="submit"]');

    function updateEstimatedPrice() {
        const mentorId = mentorSelect.value;
        const durationMinutes = durationSelect.value;

        if (mentorId && durationMinutes) {
            // Enable the book now button if both mentor and duration are selected
            bookNowButton.disabled = false;

            // Make an AJAX request to get_price.php
            fetch('api/get_price.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `mentor_id=${mentorId}&duration_minutes=${durationMinutes}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    estimatedPriceSpan.textContent = `$${data.price}`;
                } else {
                    estimatedPriceSpan.textContent = `$0.00`;
                    console.error('Error fetching price:', data.message);
                }
            })
            .catch(error => {
                estimatedPriceSpan.textContent = `$0.00`;
                console.error('Network error fetching price:', error);
            });
        } else {
            // Disable button and reset price if selections are incomplete
            bookNowButton.disabled = true;
            estimatedPriceSpan.textContent = `$0.00`;
        }
    }

    // Add event listeners to the mentor and duration selects
    mentorSelect.addEventListener('change', updateEstimatedPrice);
    durationSelect.addEventListener('change', updateEstimatedPrice);

    // Initial price update in case values are pre-filled (though not in this current setup)
    updateEstimatedPrice();
});

