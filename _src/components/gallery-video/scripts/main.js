import GalleryVideo from './GalleryVideo.js';

window.addEventListener('load', () => {
    const galleries = document.querySelectorAll('.gallery-video') ?? [];
    galleries.forEach((gallery) => {
        new GalleryVideo(gallery);
    });
});
