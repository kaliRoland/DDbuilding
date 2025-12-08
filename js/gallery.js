document.addEventListener('DOMContentLoaded', () => {
    // Load GLightbox library
    const glightboxScript = document.createElement('script');
    glightboxScript.src = 'https://cdn.jsdelivr.net/npm/glightbox';
    glightboxScript.onload = () => {
        // Initialize GLightbox after loading
        const lightbox = GLightbox({
            selector: '.glightbox',
            touchNavigation: true,
            loop: true,
            autoplayVideos: true
        });
    };
    document.head.appendChild(glightboxScript);

    const glightboxLink = document.createElement('link');
    glightboxLink.rel = 'stylesheet';
    glightboxLink.href = 'https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css';
    document.head.appendChild(glightboxLink);

    function getYouTubeVideoId(url) {
        const regExp = /^.*(youtu\.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
        const match = url.match(regExp);
        return (match && match[2].length === 11) ? match[2] : null;
    }

    function renderGalleryItem(item) {
        let imagesHtml = '';
        const imagePaths = [item.image_path_1, item.image_path_2, item.image_path_3, item.image_path_4, item.image_path_5];
        
        // Filter out empty paths and create image tags with lightbox
        const validImages = imagePaths.filter(path => path);
        if (validImages.length > 0) {
            imagesHtml = `
                <div class="grid grid-cols-2 gap-2 mb-4">
                    ${validImages.map(path => `
                        <a href="${path}" class="glightbox" data-gallery="gallery-${item.id}">
                            <img src="${path}" alt="${item.title}" class="w-full h-24 object-cover rounded cursor-pointer hover:opacity-80 transition-opacity">
                        </a>
                    `).join('')}
                </div>
            `;
        }

        let youtubeEmbedHtml = '';
        if (item.youtube_url) {
            const videoId = getYouTubeVideoId(item.youtube_url);
            if (videoId) {
                youtubeEmbedHtml = `
                    <div class="relative pt-[56.25%] mb-4"> <!-- 16:9 Aspect Ratio -->
                        <iframe
                            class="absolute top-0 left-0 w-full h-full rounded-lg"
                            src="https://www.youtube.com/embed/${videoId}"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                        ></iframe>
                    </div>
                `;
            }
        }

        return `
            <div class="bg-blue-900 rounded-lg shadow-lg overflow-hidden flex flex-col p-4">
                ${youtubeEmbedHtml}
                ${imagesHtml}
                <div class="p-2">
                    <h3 class="text-white font-semibold mb-2">${item.title}</h3>
                    <p class="text-blue-200 text-sm">${item.description}</p>
                </div>
            </div>
        `;
    }

    async function fetchAndRenderGalleryItems() {
        const galleryItemsContainer = document.getElementById('gallery-items-container');
        if (!galleryItemsContainer) return;

        galleryItemsContainer.innerHTML = '<p class="text-center text-slate-400 col-span-full">Loading gallery items...</p>';

        try {
            const response = await fetch('api/gallery.php?action=get_all');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();

            if (data.status === 'success') {
                if (data.items && data.items.length > 0) {
                    galleryItemsContainer.innerHTML = data.items.map(renderGalleryItem).join('');
                    // Re-initialize lightbox after rendering
                    if (typeof GLightbox !== 'undefined') {
                        GLightbox({
                            selector: '.glightbox',
                            touchNavigation: true,
                            loop: true,
                            autoplayVideos: true
                        });
                    }
                } else {
                    galleryItemsContainer.innerHTML = '<p class="text-center text-slate-400 col-span-full">No gallery items found. Add some from the admin panel!</p>';
                }
            } else {
                galleryItemsContainer.innerHTML = `<p class="text-center text-red-400 col-span-full">Error: ${data.message || 'Failed to load gallery items'}</p>`;
            }
        } catch (error) {
            console.error('Error fetching gallery items:', error);
            galleryItemsContainer.innerHTML = `<p class="text-center text-red-400 col-span-full">Failed to load gallery items. Error: ${error.message}</p>`;
        }
    }

    fetchAndRenderGalleryItems();
});