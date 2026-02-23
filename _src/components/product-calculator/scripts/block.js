import Calculator from './Calculator.js';

window.addEventListener('DOMContentLoaded', () => {
	const calculator = document.querySelector('.product-calculator');
	if(calculator) {
		new Calculator(calculator);
	}

});