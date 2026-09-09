import PageHeaderVideo from './PageHeaderVideo.js';

window.addEventListener('DOMContentLoaded', () => {
    const headers = document.querySelectorAll('.page-header--has-video');

    [...headers].forEach((header) => {
        new PageHeaderVideo(header);
    });
});
