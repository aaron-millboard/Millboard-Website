window.addEventListener('load', () => {

    const quantityInput = document.querySelector('.quantity input[type="number"], input.qty');
	const wastageCheckbox = document.querySelector('.quantity-wastage-checkbox');
	let quantityBeforeWastage = null;
	let isButtonClick = false;

	// Handle +/- buttons for quantity
	const minusButton = document.querySelector('.quantity-minus');
	const plusButton = document.querySelector('.quantity-plus');
	
	if (minusButton && quantityInput) {
		minusButton.addEventListener('click', () => {
			const currentValue = parseInt(quantityInput.value) || 1;
			const minValue = parseInt(quantityInput.getAttribute('min')) || 1;
			const step = parseInt(quantityInput.getAttribute('step')) || 1;
			
			if (currentValue > minValue) {
				isButtonClick = true;
				quantityInput.value = currentValue - step;
				
				// Update the base quantity if wastage is checked
				if (wastageCheckbox && wastageCheckbox.checked && quantityBeforeWastage !== null) {
					quantityBeforeWastage = Math.round((currentValue - step) / 1.1);
				}
				
				quantityInput.dispatchEvent(new Event('change', { bubbles: true }));
				setTimeout(() => { isButtonClick = false; }, 10);
			}
		});
	}
	
	if (plusButton && quantityInput) {
		plusButton.addEventListener('click', () => {
			const currentValue = parseInt(quantityInput.value) || 1;
			const maxValue = parseInt(quantityInput.getAttribute('max')) || Infinity;
			const step = parseInt(quantityInput.getAttribute('step')) || 1;
			
			if (currentValue < maxValue) {
				isButtonClick = true;
				quantityInput.value = currentValue + step;
				
				// Update the base quantity if wastage is checked
				if (wastageCheckbox && wastageCheckbox.checked && quantityBeforeWastage !== null) {
					quantityBeforeWastage = Math.round((currentValue + step) / 1.1);
				}
				
				quantityInput.dispatchEvent(new Event('change', { bubbles: true }));
				setTimeout(() => { isButtonClick = false; }, 10);
			}
		});
	}

	// Handle wastage checkbox
	if (wastageCheckbox && quantityInput) {
		wastageCheckbox.addEventListener('change', (e) => {
			if (e.target.checked) {
				// Store current quantity
				quantityBeforeWastage = parseInt(quantityInput.value) || 1;
				// Add 10% and round to nearest integer
				const withWastage = Math.round(quantityBeforeWastage * 1.1);
				quantityInput.value = withWastage;
				quantityInput.dispatchEvent(new Event('change', { bubbles: true }));
			} else {
				// Restore previous quantity
				if (quantityBeforeWastage !== null) {
					quantityInput.value = quantityBeforeWastage;
					quantityBeforeWastage = null;
					quantityInput.dispatchEvent(new Event('change', { bubbles: true }));
				}
			}
		});
	}
	
	// Reset checkbox when quantity is manually changed (but not by +/- buttons)
	if (quantityInput && wastageCheckbox) {
		quantityInput.addEventListener('input', () => {
			if (wastageCheckbox.checked && !isButtonClick) {
				wastageCheckbox.checked = false;
				quantityBeforeWastage = null;
			}
		});
	}

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