export default class Calculator {

    constructor(element) {

        // constants
        this.SQUARE_METERS_TO_FEET = 10.7639;

        // Global variables
        this.element = element;
        this.opened = false;
        this.cta = document.querySelector('.product-calculator-cta');
        this.closeBtn = this.element.querySelector('.product-calculator__header--close');

        // Dynamic variables
        this.unit = 'sqm';
        this.areaInSqm = 1; // We use if to calc total sqm area and boards if uints = sqft (to prevent back and forth conversions)
        this.wastage = false;
        this.boardsTotal = 0;

        // Inputs
        this.lengthInput = this.element.querySelector('#calculator_length');
        this.widthInput = this.element.querySelector('#calculator_width');
        this.areaInput = this.element.querySelector('#calculator_area');
        this.unitRadios = this.element.querySelectorAll('input[name="calculator_unit"]');
        this.wastageCheckbox = this.element.querySelector('input[name="calculator_wastage"]');
        this.taxIncludedCheckbox = this.element.querySelector('input[name="calculator_tax"]');

        // Data from product
        this.price = this.element.dataset.price ? parseFloat(this.element.dataset.price) : null;
        this.boardArea = this.element.dataset.boardArea ? parseFloat(this.element.dataset.boardArea) : null;
        this.taxIncluded = this.element.dataset.taxIncluded === 'true'; // this is the global state from WC settings, not changeable
        this.taxRate = this.element.dataset.taxRate ? parseFloat(this.element.dataset.taxRate) : null;

        this.init();
    }

    init() {

        // 1. Init open/close on CTA click
        [this.cta, this.closeBtn].forEach(btn => {
            if (btn) {
                btn.addEventListener('click', (event) => {
                    this.toggleCalculator(event);
                });
            }
        });

        // 2. Prevent any non-numeric input in the calculator fields
        [this.lengthInput, this.widthInput, this.areaInput].forEach(input => {
            input.addEventListener("keypress", (event) => {
                if(!/[0-9.]/.test(event.key)) {
                    event.preventDefault();
                }
            });
        });

        // 3. Calculate area on length or width change
        [this.lengthInput, this.widthInput].forEach(input => {
            input.addEventListener('change', () => {
                this.calculateArea();
            });
        });

        // 4. Listen for area input changes to update length and width proportionally
        this.areaInput.addEventListener('change', () => {
            this.updateLengthWidth();
        });

        // 5. Listen for unit changes to convert values accordingly
        this.unitRadios.forEach(radio => {
            radio.addEventListener('change', (event) => {
                this.unit = event.target.value;
                this.convertUnits();
            });
        });

        // 6. Listen for wastage checkbox changes to recalculate area and price
        if (this.wastageCheckbox) {
            this.wastageCheckbox.addEventListener('change', () => {
                this.wastage = this.wastageCheckbox.checked;
                this.handleWastageChange();
            });
        }

        // 7. Listen for tax included checkbox changes to recalculate price
        if (this.taxIncludedCheckbox && this.taxRate !== null) {
            this.taxIncludedCheckbox.addEventListener('change', () => {
                this.updateResultPrice();
            });
        }

        // Update results on page load
        this.updateResults();

    }

    toggleCalculator(event) {

        event.preventDefault();

        if (this.opened) {

            // Hide calculator
            this.element.style.display = 'none';

            // Set calculator opened flag
            this.opened = false;

        } else {

            // Show calculator
            this.element.style.display = 'block';

            // Set calculator opened flag
            this.opened = true;
        }

    }

    calculateArea() {

        let length = parseFloat(this.lengthInput.value) || 1;
        let width = parseFloat(this.widthInput.value) || 1;
        let area = length * width;
        this.areaInput.value = area > 1 ? area.toFixed(2) : 1;

        // Update results box
        this.updateResults();

    }

    updateLengthWidth() {

        const area = parseFloat(this.areaInput.value) || 1;
        const length = parseFloat(this.lengthInput.value) || 1;
        const width = parseFloat(this.widthInput.value) || 1;

        if (length && width) {
            const aspectRatio = length / width;
            this.lengthInput.value = (Math.sqrt(area * aspectRatio)).toFixed(2);
            this.widthInput.value = (Math.sqrt(area / aspectRatio)).toFixed(2);
        }

        // Update results box
        this.updateResults();

    }

    convertUnits() {

        const area = parseFloat(this.areaInput.value) || 1;

        // Update total area (and not length/width) when unit changes and them recalculate length/width based on new area
        if (this.unit === 'sqft') {
            this.areaInput.value = (area * this.SQUARE_METERS_TO_FEET).toFixed(2);
        } else {
            this.areaInput.value = (area / this.SQUARE_METERS_TO_FEET).toFixed(2);
        }

        // Recalculate length and width backwards based on new area value
        // As we have square values, we have no other way to recalculate length and width except to keep the same aspect ratio and calculate them based on new area value
        this.updateLengthWidth();

    }

    handleWastageChange() {

        const area = parseFloat(this.areaInput.value) || 1;

        if (this.wastage) {
            this.areaInput.value = (area * 1.1).toFixed(2);
        } else {
            this.areaInput.value = (area / 1.1).toFixed(2);
        }

        // Recalculate length and width backwards based on new area value
        // As we have square values, we have no other way to recalculate length and width except to keep the same aspect ratio and calculate them based on new area value
        this.updateLengthWidth();

    }

    updateResults() {

        this.updateResultArea();

        this.updateResultBoards();

        this.updateResultPrice();

        this.updateQuantityInput();

    }

    updateResultArea() {

        // Get result elements
        const areaElement = this.element.querySelector('[data-result="area"]');
        let area = parseFloat(this.areaInput.value) || 1;

        if(this.unit === 'sqft') {
            area = area / this.SQUARE_METERS_TO_FEET;
            area = area.toFixed(2);
        } else {
            area = area.toFixed(2);
        }

        // Update total area in sqm global variable to use it in price calculation
        this.areaInSqm = parseFloat(area);

        // Update area result
        areaElement.textContent = area;

    }

    updateResultBoards() {

        // Get result element
        const boardsElement = this.element.querySelector('[data-result="boards"]');

        // we round them up as we can't have a fraction of board
        let boards = this.boardArea ? Math.ceil(this.areaInSqm / this.boardArea) : 1;
        // Update boards result
        boardsElement.textContent = boards;
        // Update global variable for boards total to use it in price calculation
        this.boardsTotal = boards;

    }

    updateResultPrice() {

        const priceElement = this.element.querySelector('[data-result="price"]');
        const taxIncludedChecked = this.taxIncludedCheckbox ? this.taxIncludedCheckbox.checked : this.taxIncluded;

        // Price - we calculate price based on boards number and price from dataset
        let price = this.price * this.boardsTotal;

        // Resolve all scenarios when tax IS included
        if(this.taxIncluded) {

            // If tax is included in price and tax included checkbox is checked, we just show price as is, no need to calculate anything
            if (taxIncludedChecked) {
                // do nothing, price is already correct
            }

            // If tax is included in price but tax included checkbox is not checked, we need to remove tax from price to show it without tax
            if (!taxIncludedChecked && this.taxRate !== null) {
                price = price / (1 + (this.taxRate / 100));
            }

        }

        // Resolve all scenarios when tax is NOT included
        if(!this.taxIncluded) {

            if(!taxIncludedChecked) {
                // If tax is not included in price and tax included checkbox is not checked, we just show price as is, no need to calculate anything
                // do nothing, price is already correct
            }

            if(taxIncludedChecked && this.taxRate !== null) {
                // If tax is not included in price but tax included checkbox is checked, we need to add tax to price to show it with tax
                price = price * (1 + (this.taxRate / 100));
            }

        }

        // Update price result
        priceElement.textContent = price.toFixed(2);

    }

    updateQuantityInput() {
        // so we don't want to touch qty input when calculator is closed
        if(this.opened) {
            const quantityInput = document.querySelector('input.qty');
            if (quantityInput) {
                quantityInput.value = this.boardsTotal;

                // Trigger change event on quantity input to update any listeners (like WC variation forms)
                quantityInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    }

}