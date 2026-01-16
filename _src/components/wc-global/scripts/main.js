window.addEventListener('load', () => {

	// Get all quantity containers (for cart page with multiple products)
	const quantityContainers = document.querySelectorAll('.quantity');

	// Function to initialize quantity inputs with +/- buttons
	const initQuantityInputs = (container) => {
		const quantityInput = container.querySelector('input[type="number"], input.qty');
		const minusButton = container.querySelector('.quantity-minus');
		const plusButton = container.querySelector('.quantity-plus');
		
		if (!quantityInput) return;
		
		let isButtonClick = false;

		// Handle minus button
		if (minusButton) {
			minusButton.addEventListener('click', () => {
				const currentValue = parseInt(quantityInput.value) || 1;
				const minValue = parseInt(quantityInput.getAttribute('min')) || 1;
				const step = parseInt(quantityInput.getAttribute('step')) || 1;
				
				if (currentValue > minValue) {
					isButtonClick = true;
					quantityInput.value = currentValue - step;
					
					quantityInput.dispatchEvent(new Event('change', { bubbles: true }));
					setTimeout(() => { isButtonClick = false; }, 10);
				}
			});
		}
		
		// Handle plus button
		if (plusButton) {
			plusButton.addEventListener('click', () => {
				const currentValue = parseInt(quantityInput.value) || 1;
				const maxValue = parseInt(quantityInput.getAttribute('max')) || Infinity;
				const step = parseInt(quantityInput.getAttribute('step')) || 1;
				
				if (currentValue < maxValue) {
					isButtonClick = true;
					quantityInput.value = currentValue + step;
					
					quantityInput.dispatchEvent(new Event('change', { bubbles: true }));
					setTimeout(() => { isButtonClick = false; }, 10);
				}
			});
		}
	};
	
	// Initialize all quantity inputs on the page
	quantityContainers.forEach((container) => {
		initQuantityInputs(container);
	});

	// Auto-update cart when quantity changes (with debounce)
	let timeout;
	const cartForm = document.querySelector('form.woocommerce-cart-form');
	if (cartForm) {
		cartForm.addEventListener('change', function(event) {
			if (event.target.matches('input.qty')) {
				if (timeout !== undefined) {
					clearTimeout(timeout);
				}
				timeout = setTimeout(function() {
					const updateCartButton = document.querySelector("[name='update_cart']");
					if (updateCartButton) {
						updateCartButton.removeAttribute('disabled');
						updateCartButton.click();
					}
				}, 1000);
			}
		});
	}

});