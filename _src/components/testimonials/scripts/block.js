 import Testimonials from './Testimonials.js';

 window.addEventListener('DOMContentLoaded', () => {
     const items = document.querySelectorAll('.testimonials');

     [...items].forEach((item) => {
        console.log(item);
         new Testimonials(item);
     });
 });
