window.addEventListener('load', () => {

	// Step 1. Initialize all variables
	const quantityInput = document.querySelector('.quantity input[type="number"], input.qty');
	let unitPrice = null;

	// Function to update total price
	const updateTotalPrice = () => {
		if (!quantityInput) {
			return;
		}

		// Re-query the price element each time (it may be recreated by WooCommerce)
		let priceAmount = document.querySelector('.product__add-to-cart-wrapper .woocommerce-Price-amount.amount bdi');

		if (!priceAmount) {
			return;
		}

		// Get unit price if not already stored or if variation changed
		if (unitPrice === null) {
			const priceText = priceAmount.textContent;
			unitPrice = parseFloat(priceText.replace(/[^0-9.]/g, ''));
		}

		const quantity = parseInt(quantityInput.value) || 1;
		const totalPrice = (unitPrice * quantity).toFixed(2);

		// Get the currency symbol element
		const currencySymbol = priceAmount.querySelector('.woocommerce-Price-currencySymbol');

		// Update the price display, preserving the currency symbol element
		if (currencySymbol) {
			priceAmount.innerHTML = '';
			priceAmount.appendChild(currencySymbol);
			priceAmount.appendChild(document.createTextNode(totalPrice));
		} else {
			priceAmount.textContent = totalPrice;
		}
	};

	// Listen for quantity changes
	if (quantityInput) {
		quantityInput.addEventListener('change', updateTotalPrice);
	}
});