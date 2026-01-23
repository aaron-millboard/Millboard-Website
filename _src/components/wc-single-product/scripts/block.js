window.addEventListener('load', () => {

	// Step 1. Initialize all variables
    const radioGroups = document.querySelectorAll('.product__variations__radio-group');
	const quantityInput = document.querySelector('.quantity input[type="number"], input.qty');

	let unitPrice = null;

    const resetQuantity = () => {
		const quantityInputs = document.querySelectorAll('.quantity input[type="number"], input.qty');
		quantityInputs.forEach((input) => {
			input.value = 1;
		});
    }

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
		const radioElement = document.querySelector(`.product__variations__radio-group input[name="${selectName}"][value="${selectValue}"]`);

		if (radioElement) {
			radioElement.checked = true;
		}

		// Reset after WooCommerce updates the price
		unitPrice = null;
		setTimeout(() => {
			resetQuantity();
		}, 200);

	};

	// Step 2. Set default selections on page load for each radio group (size, color, etc.)
    radioGroups.forEach((radioGroup) => {

		// Collect all available radios in the group (single terms)
        const radiosPerGroup = radioGroup.querySelectorAll('input[type="radio"]');

		// Bail early if no radios found
		if (radiosPerGroup.length === 0) {
			return;
		}

		// // Set first as active by default
		// radiosPerGroup[0].checked = true;

		// // Update select by passing the first radio as selected
		// syncRadiosToSelect([radiosPerGroup[0]]);
    });


	// Handle radio button changes for variation selection
	document.addEventListener('change', (event) => {

		// Collect all currently checked radios
		const checkedRadios = document.querySelectorAll('.product__variations__radio-group input:checked');

		// Check if this event is coming from our radio buttons and sync to select
		if (event.target.matches('.product__variations__radio-group input')) {

			// Update all attributes (not just the changed one)
			syncRadiosToSelect(checkedRadios);
		}

        // make it vice versa - when select changes, update radio buttons
        if (event.target.matches('.variations select')) {
			const selectElement = event.target;
			syncSelectToRadios(selectElement);
        }

	});

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
	}

});


// Decking Calculator
window.addEventListener('load', () => {

	const calculator = document.querySelector('.product__calculator');
	const calculatorCta = document.querySelector('.product__calculator-cta');
	const calculatorClose = document.querySelector('.product__calculator__header--close');
	const areaInput = calculator?.querySelector('.product__calculator-input-wrapper input[name="quantity"]');
	const unitRadios = document.querySelectorAll('input[name="calculator_unit"]');
	const wastageCheckbox = calculator?.querySelector('input[name="calculator_wastage"]');
	const mainWastageCheckbox = document.querySelector('.product__toggles .quantity-wastage-checkbox');
	const mainQuantityInput = document.querySelector('.product__toggles .quantity input[type="number"]');
	const resultArea = calculator?.querySelector('[data-result="area"]');
	const resultPrice = calculator?.querySelector('[data-result="price"]');

	if (!calculator || !calculatorCta) return;

	let calculatorUnitPrice = null;
	let previousUnit = 'meters';
	const SQUARE_METERS_TO_FEET = 10.764;

	// Get unit price from WooCommerce (always divide by main quantity to get true unit price)
	const getUnitPrice = () => {
		const priceElement = document.querySelector('.woocommerce-variation-price .woocommerce-Price-amount.amount bdi');
		if (!priceElement) return null;

		const priceText = priceElement.textContent;
		const displayedPrice = parseFloat(priceText.replace(/[^0-9.]/g, ''));

		// Get current main quantity to calculate actual unit price
		const currentMainQty = mainQuantityInput ? parseInt(mainQuantityInput.value) || 1 : 1;

		// Divide by quantity to get true unit price
		return displayedPrice / currentMainQty;
	};

	// Calculate and update results
	const updateCalculatorResults = (forceUpdate = false) => {

		if (!areaInput || !resultArea || !resultPrice) return;

		let selectedUnit = document.querySelector('input[name="calculator_unit"]:checked')?.value || 'meters';
		const includeWastage = wastageCheckbox?.checked || false;

		let sqMeters;
		let areaInMeters;

		if (forceUpdate) {
			sqMeters = mainQuantityInput ? parseInt(mainQuantityInput.value) || 1 : 1;
			selectedUnit = 'meters'; // Force meters for main quantity sync
		} else {
			sqMeters = parseInt(areaInput.value) || 1;
		}

		// Convert to square meters if needed
		areaInMeters = selectedUnit === 'feet' ? sqMeters / SQUARE_METERS_TO_FEET : sqMeters;

		// Add wastage if checked
		if (includeWastage) {
			if(!forceUpdate) {
				areaInMeters = areaInMeters * 1.1;
			}
		}

		// Round up the area in meters
		let totalSquareMeters = Math.ceil(areaInMeters);

		// Update area display - always show square meters, rounded up, no decimals
		resultArea.textContent = totalSquareMeters;

		// Adjust areaInput value based on units and wastage enabled
		if(forceUpdate) {
			let adjustedInputValue = totalSquareMeters;
			if (selectedUnit === 'feet') {
				adjustedInputValue = Math.ceil(totalSquareMeters * SQUARE_METERS_TO_FEET);
			}
			if (includeWastage) {
				adjustedInputValue = Math.ceil(adjustedInputValue / 1.1);
			}
			areaInput.value = adjustedInputValue;
		} else {
			mainQuantityInput.value = totalSquareMeters;
			mainQuantityInput.dispatchEvent(new Event('change', { bubbles: true }));
		}

		// Get unit price if not cached
		if (calculatorUnitPrice === null) {
			calculatorUnitPrice = getUnitPrice();
		}

		if (calculatorUnitPrice) {
			const totalPrice = totalSquareMeters * calculatorUnitPrice;
			const currencySymbol = document.querySelector('.woocommerce-variation-price .woocommerce-Price-currencySymbol')?.textContent || '£';
			resultPrice.textContent = `${currencySymbol}${totalPrice.toFixed(2)}`;
		}

	};

	// Handle unit conversion in input field
	const handleUnitChange = (newUnit) => {

		if (!areaInput) return;

		const currentValue = parseFloat(areaInput.value) || 0;

		// Only convert if there's a unit change
		if (previousUnit !== newUnit && currentValue > 0) {
			let newValue;

			if (previousUnit === 'meters' && newUnit === 'feet') {
				// Converting from meters to feet - multiply and round up
				newValue = Math.ceil(currentValue * SQUARE_METERS_TO_FEET);
			} else if (previousUnit === 'feet' && newUnit === 'meters') {
				// Converting from feet to meters - divide and round up
				newValue = Math.ceil(currentValue / SQUARE_METERS_TO_FEET);
			}

			if (newValue !== undefined) {
				areaInput.value = newValue;
			}
		}

		previousUnit = newUnit;
		updateCalculatorResults();
	};

	// Open calculator
	calculatorCta.addEventListener('click', (event) => {

		event.preventDefault();

		if(calculator.opened === true) return;

		calculatorUnitPrice = null;

		// Sync calculator with main product values
		if (mainQuantityInput && areaInput) {
			areaInput.value = parseInt(mainQuantityInput.value) || 1;
		}

		// Reset unit to meters
		const metersRadio = document.querySelector('input[name="calculator_unit"][value="meters"]');
		metersRadio.checked = true;
		previousUnit = 'meters';

		// Sync wastage checkbox
		if (wastageCheckbox && mainWastageCheckbox) {
			wastageCheckbox.checked = mainWastageCheckbox.checked;
		}

		// Show calculator
		calculator.style.display = 'block';

		// Update calculator display
		updateCalculatorResults(true);

		// Set calculator opened flag
		calculator.opened = true;

	});

	// Close calculator
	if (calculatorClose) {
		calculatorClose.addEventListener('click', (event) => {
			event.preventDefault();
			calculator.style.display = 'none';
			calculator.opened = false;
		});
	}

	// Handle unit changes with conversion
	unitRadios.forEach(radio => {
		radio.addEventListener('change', (event) => {
			handleUnitChange(event.target.value);
		});
	});

	// Handle wastage checkbox
	if (wastageCheckbox && mainWastageCheckbox) {
		wastageCheckbox.addEventListener('change', (event) => {
			setTimeout(() => {
				// Sync main checkbox state
				mainWastageCheckbox.checked = event.target.checked;
				updateCalculatorResults();
			}, 100);
		});
		mainWastageCheckbox.addEventListener('change', (event) => {
			setTimeout(() => {
				// Sync calculator checkbox state
				wastageCheckbox.checked = event.target.checked;
				updateCalculatorResults(true);
			}, 100);
		});
	}

	// Handle quantity input changes
	if (areaInput) {
		areaInput.addEventListener('change', (event) => {
			setTimeout(() => {
				updateCalculatorResults();
			}, 100);
		});
	}

	// Sync from main quantity input changes
	if (mainQuantityInput) {
		mainQuantityInput.addEventListener('input', (event) => {
			setTimeout(() => {
				updateCalculatorResults(true);
			}, 100);
		});
	}

	// Reset calculator when variation changes
	// Listen to WooCommerce's variation events for reliable price updates
	const variationForm = document.querySelector('.variations_form');

	if (variationForm && typeof jQuery !== 'undefined') {
		jQuery(variationForm).on('found_variation', () => {
			calculatorUnitPrice = null; // Reset to get new price
			// Wait for WooCommerce to update the price in DOM
			setTimeout(() => {
				updateCalculatorResults(true);
			}, 50);
		});

		jQuery(variationForm).on('show_variation', () => {
			calculatorUnitPrice = null; // Reset to get new price
			// Wait for WooCommerce to update the price in DOM
			setTimeout(() => {
				updateCalculatorResults(true);
			}, 50);
		});
	}

});


// Wastage box handling
window.addEventListener('load', () => {

	const container = document.querySelector('.quantity--with-wastage');

	// Look for wastage checkbox in parent wrapper (sibling of .quantity)
	const wrapper = container.parentElement;
	const wastageCheckbox = wrapper ? wrapper.querySelector('.quantity-wastage-checkbox') : null;
	const quantityInput = container.querySelector('input[type="number"], input.qty');

	if(!wrapper || !wastageCheckbox || !quantityInput ) return;

	// Handle wastage checkbox
	if (wastageCheckbox && quantityInput) {

		wastageCheckbox.addEventListener('click', (event) => {

			let value = parseInt(quantityInput.value) || 1;

			if (event.target.checked) {
				// Add 10% and round up
				value = Math.ceil(value * 1.1);
			} else {
				// Remove 10% and round down
				value = Math.floor(value / 1.1);
			}

			if( value < 1 ) value = 1;

			quantityInput.value = value;
			quantityInput.dispatchEvent(new Event('change', { bubbles: true }));

		});
	}

});
