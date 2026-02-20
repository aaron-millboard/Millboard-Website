import Calculator from './Calculator.js';

window.addEventListener('DOMContentLoaded', () => {
	const calculator = document.querySelector('.product-calculator');
	new Calculator(calculator);
});