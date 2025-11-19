 import Testimonials from './Testimonials.js';

 window.addEventListener('DOMContentLoaded', () => {
     const items = document.querySelectorAll('.testimonials:has(.slider)');

     [...items].forEach((item) => {
         new Testimonials(item);
     });
 });
