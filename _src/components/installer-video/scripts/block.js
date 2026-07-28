// Installer Video — click the poster to play the video inline (YouTube/Vimeo).
// Falls back to opening the link in a new tab for unrecognised URLs.

function toEmbedUrl(url) {
    try {
        const u = new URL(url);
        const host = u.hostname.replace(/^www\./, '');

        if (host === 'youtu.be') {
            const id = u.pathname.slice(1);
            return id ? `https://www.youtube.com/embed/${id}?autoplay=1` : null;
        }
        if (host === 'youtube.com' || host === 'm.youtube.com') {
            const id = u.searchParams.get('v');
            return id ? `https://www.youtube.com/embed/${id}?autoplay=1` : null;
        }
        if (host === 'vimeo.com' || host === 'player.vimeo.com') {
            const id = u.pathname.split('/').filter(Boolean).pop();
            return id ? `https://player.vimeo.com/video/${id}?autoplay=1` : null;
        }
    } catch (e) {
        return null;
    }
    return null;
}

document.addEventListener('click', function (event) {
    const media = event.target.closest('.installer-video__media--playable');
    if (!media) {
        return;
    }

    event.preventDefault();

    const url = media.dataset.video;
    if (!url) {
        return;
    }

    const embed = toEmbedUrl(url);
    if (!embed) {
        window.open(url, '_blank', 'noopener');
        return;
    }

    const iframe = document.createElement('iframe');
    iframe.className = 'installer-video__iframe';
    iframe.src = embed;
    iframe.setAttribute('allow', 'autoplay; fullscreen; picture-in-picture');
    iframe.setAttribute('allowfullscreen', '');
    iframe.setAttribute('title', 'Video player');

    media.innerHTML = '';
    media.appendChild(iframe);
});
