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
		const product = document.querySelector('.woocommerce.product');
		const currencyCode = product ? product.dataset.currencyCode : null;
		let locale = product ? product.dataset.locale : "en_GB"; // Default to en_GB if locale is not provided
		if (locale) {
			locale = locale.replace('_', '-');
		}

		let priceAmount = document.querySelector('.product__add-to-cart-wrapper .woocommerce-Price-amount.amount bdi');

		if (!priceAmount) {
			return;
		}

		// Get unit price if not already stored or if variation changed
		if (unitPrice === null) {
			unitPrice = product.dataset.price;
			unitPrice = parseFloat(unitPrice).toFixed(2);
		}

		const quantity = parseInt(quantityInput.value) || 1;

		let totalPrice = (unitPrice * quantity).toFixed(2);

        // Format price with currency symbol and 2 decimals
        totalPrice = new Intl.NumberFormat(locale, {
            style: "currency",
            currency: currencyCode
        }).format(totalPrice);

		priceAmount.innerHTML = '';
		priceAmount.appendChild(document.createTextNode(totalPrice));

	};

	// Listen for quantity changes
	if (quantityInput) {
		quantityInput.addEventListener('change', updateTotalPrice);
	}
});