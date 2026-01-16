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
		if( radiosPerGroup.length === 0 ) return;

		// Set first as active by default
		radiosPerGroup[0].checked = true;

		// Update select by passing the first radio as selected
		syncRadiosToSelect([radiosPerGroup[0]]);

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
		quantityInput.addEventListener('input', updateTotalPrice);
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
	const updateCalculatorResults = () => {
		if (!areaInput || !resultArea || !resultPrice) return;

		let inputValue = parseFloat(areaInput.value) || 0;
		const selectedUnit = document.querySelector('input[name="calculator_unit"]:checked')?.value || 'meters';
		const includeWastage = wastageCheckbox?.checked || false;

		// Convert to square meters if needed
		let areaInMeters = selectedUnit === 'feet' ? inputValue / SQUARE_METERS_TO_FEET : inputValue;

		// Add wastage if checked
		if (includeWastage) {
			areaInMeters = areaInMeters * 1.1;
		}

		// Round up the area in meters
		const totalSquareMeters = Math.ceil(areaInMeters);

		// Update area display - always show square meters, rounded up, no decimals
		resultArea.textContent = totalSquareMeters;

		// Sync main product quantity with total square meters (without triggering price update)
		if (mainQuantityInput && mainQuantityInput.value != totalSquareMeters) {

			// Set flag to prevent main wastage handler from applying wastage again
			if (window.calculatorEnabled) {
				window.calculatorEnabled.set(true);
			}

			mainQuantityInput.value = totalSquareMeters;

			// Reset flag after a short delay
			setTimeout(() => {
				if (window.calculatorEnabled) {
					window.calculatorEnabled.set(false);
				}
			}, 50);
		}

		// Calculate price
		if (calculatorUnitPrice === null) {
			calculatorUnitPrice = getUnitPrice();
		}

		if (calculatorUnitPrice) {
			const totalPrice = totalSquareMeters * calculatorUnitPrice;
			const currencySymbol = document.querySelector('.woocommerce-variation-price .woocommerce-Price-currencySymbol')?.textContent || '£';
			resultPrice.textContent = `${currencySymbol}${totalPrice.toFixed(2)}`;
		}

		// trigger price update in main quantity input
		if (mainQuantityInput) {
			mainQuantityInput.dispatchEvent(new Event('change', { bubbles: true }));
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
				// Trigger input event so WooCommerce updates
				areaInput.dispatchEvent(new Event('input', { bubbles: true }));
			}
		}

		previousUnit = newUnit;
		updateCalculatorResults();
	};

	// Open calculator
	calculatorCta.addEventListener('click', (e) => {
		e.preventDefault();
		
		// Sync calculator with main product values
		if (mainQuantityInput && areaInput) {
			const mainQty = parseInt(mainQuantityInput.value) || 1;
			areaInput.value = mainQty;
		}
		
		// Reset unit to meters
		const metersRadio = document.querySelector('input[name="calculator_unit"][value="meters"]');
		if (metersRadio) {
			metersRadio.checked = true;
			previousUnit = 'meters';
		}
		
		// Sync wastage checkbox
		if (wastageCheckbox && mainWastageCheckbox) {
			wastageCheckbox.checked = mainWastageCheckbox.checked;
		}
		
		// Show calculator
		calculator.style.display = 'block';
		
		// Reset and update
		calculatorUnitPrice = null;
		updateCalculatorResults();
	});

	// Close calculator
	if (calculatorClose) {
		calculatorClose.addEventListener('click', (e) => {
			e.preventDefault();
			calculator.style.display = 'none';
		});
	}

	// Handle unit changes with conversion
	unitRadios.forEach(radio => {
		radio.addEventListener('change', (e) => {
			handleUnitChange(e.target.value);
		});
	});

	// Handle wastage checkbox
	if (wastageCheckbox) {
		wastageCheckbox.addEventListener('change', (e) => {
			updateCalculatorResults();
			// Sync with main wastage checkbox
			if (mainWastageCheckbox) {
				mainWastageCheckbox.checked = e.target.checked;
				mainWastageCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
			}
		});
	}

	// Sync main wastage checkbox to calculator
	if (mainWastageCheckbox && wastageCheckbox) {
		mainWastageCheckbox.addEventListener('change', (e) => {
			wastageCheckbox.checked = e.target.checked;
			updateCalculatorResults();
		});
	}

	// Handle input changes
	if (areaInput) {
		areaInput.addEventListener('input', updateCalculatorResults);
		areaInput.addEventListener('change', updateCalculatorResults);
	}

	// Sync main quantity input with calculator input (convert from selected unit to meters)
	if (mainQuantityInput && areaInput) {
		mainQuantityInput.addEventListener('input', () => {
			const mainValue = parseInt(mainQuantityInput.value) || 0;
			if (mainValue > 0 && areaInput.value != mainValue) {
				areaInput.value = mainValue;
				// Reset unit to meters when syncing from main
				const metersRadio = document.querySelector('input[name="calculator_unit"][value="meters"]');
				if (metersRadio && !metersRadio.checked) {
					previousUnit = 'meters';
					metersRadio.checked = true;
				}
				updateCalculatorResults();
			}
		});
	}

	// Reset calculator when variation changes
	// Listen to WooCommerce's variation events for reliable price updates
	const variationForm = document.querySelector('.variations_form');
	
	if (variationForm) {
		variationForm.addEventListener('found_variation', () => {
			calculatorUnitPrice = null;
			setTimeout(() => {
				updateCalculatorResults();
			}, 100);
		});
		
		variationForm.addEventListener('show_variation', () => {
			calculatorUnitPrice = null;
			setTimeout(() => {
				updateCalculatorResults();
			}, 100);
		});
	}
	
	// Fallback for custom radio buttons
	document.addEventListener('change', (event) => {
		if (event.target.matches('.product__variations__radio-group input')) {
			calculatorUnitPrice = null;
			setTimeout(() => {
				updateCalculatorResults();
			}, 300);
		}
	});

});


// Wastage box handling
window.addEventListener('load', () => {

	const quantityContainers = document.querySelectorAll('.quantity');
	let isSyncingFromCalculator = false;

	quantityContainers.forEach((container) => {

		// Look for wastage checkbox in parent wrapper (sibling of .quantity)
		const wrapper = container.parentElement;
		const wastageCheckbox = wrapper ? wrapper.querySelector('.quantity-wastage-checkbox') : null;
		const quantityInput = container.querySelector('input[type="number"], input.qty');

		// Skip if this is the calculator's quantity input
		if (quantityInput?.closest('.product__calculator')) {
			return;
		}

		// Handle wastage checkbox
		if (wastageCheckbox && quantityInput) {
			
			let baseValue = null;

			wastageCheckbox.addEventListener('change', (event) => {

				// Don't apply wastage if syncing from calculator (calculator already applied it)
				if (isSyncingFromCalculator) return;

				let value = parseInt(quantityInput.value) || 1;

				if (event.target.checked) {
					// Store base value before adding wastage
					baseValue = value;
					// Add 10% and round up
					value = Math.ceil(value * 1.1);
				} else {
					// Use stored base value if available, otherwise divide and round up
					value = baseValue !== null ? baseValue : Math.ceil(value / 1.1);
					baseValue = null;
				}

				quantityInput.value = value;
				quantityInput.dispatchEvent(new Event('change', { bubbles: true }));

			});
		}
	});

	// Expose flag for calculator to use
	window.calculatorEnabled = {
		set: (value) => { isSyncingFromCalculator = value; }
	};
});