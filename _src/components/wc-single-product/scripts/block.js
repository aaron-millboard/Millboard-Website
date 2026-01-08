window.addEventListener('load', () => {

	let unitPrice = null;

	const syncRadiosToSelect = (radios) => {

		radios.forEach((radio) => {
			const radioName = radio.getAttribute('name');
			const radioValue = radio.getAttribute('value');
			const selectElement = document.querySelector(`select[name="${radioName}"]`);
			
			if (selectElement) {
				selectElement.value = radioValue;
				selectElement.dispatchEvent(new Event('change', { bubbles: true }));
				
				// Reset after dispatching the event
				unitPrice = null;
				setTimeout(() => {
					resetQuantity();
				}, 200);
			}
		});

	};

	const syncSelectToRadios = (selectElement) => {
		if (!selectElement) return;
		const selectName = selectElement.getAttribute('name');
		const selectValue = selectElement.value;
		const radioElement = document.querySelector(`.product__variations__selector input[name="${selectName}"][value="${selectValue}"]`);
		
		if (radioElement) {
			radioElement.checked = true;
		}

		// Reset after WooCommerce updates the price
		unitPrice = null;
		setTimeout(() => {
			resetQuantity();
		}, 200);

	};	

    // Update select dropdowns to match checked radio buttons
    const selectors = document.querySelectorAll('.product__variations__selector');

    selectors.forEach((selector) => {

        const radios = selector.querySelectorAll('input[type="radio"]');

		syncRadiosToSelect(radios);

        // Make first radion button checked by default
        radios.forEach((radio, index) => {
            if (index === 0) {
                radio.checked = true;
                const radioName = radio.getAttribute('name');
                const radioValue = radio.getAttribute('value');
                const selectElement = document.querySelector(`select[name="${radioName}"]`);
                
                if (selectElement) {
                    selectElement.value = radioValue;
                    selectElement.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        });

    });


	// Handle radio button changes for variation selection
	document.addEventListener('change', (event) => {

		if (event.target.matches('.product__variations__selector input')) {
			const checkedRadios = document.querySelectorAll('.product__variations__selector input:checked');
			syncRadiosToSelect(checkedRadios);
		}

        // make it vice versa - when select changes, update radio buttons
        if (event.target.matches('.variations select')) {
			const selectElement = event.target;
			syncSelectToRadios(selectElement);
        }

	});

	const quantityInput = document.querySelector('.quantity input[type="number"], input.qty');
    const resetQuantity = () => {
        if (quantityInput) {
            quantityInput.value = 1;
        }
    }
	
	// Function to update total price
	const updateTotalPrice = () => {

		if (!quantityInput) return;
		
		// Re-query the price element each time (it may be recreated by WooCommerce)
		let priceAmount = document.querySelector('.woocommerce-variation-price .woocommerce-Price-amount.amount bdi');
		
		if (!priceAmount) return;
		
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
		quantityInput.addEventListener('input', updateTotalPrice);
	}

});