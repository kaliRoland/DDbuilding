document.addEventListener('DOMContentLoaded', () => {
    // IMPORTANT: Replace with your actual WordPress REST API URL
    const WORDPRESS_API_URL = 'http://localhost/wordpress/wp-json/wp/v2'; 

    function renderBlogPostCard(post) {
        // Extract featured image URL, or use a placeholder
        const imageUrl = post._embedded && post._embedded['wp:featuredmedia'] && post._embedded['wp:featuredmedia'][0] && post._embedded['wp:featuredmedia'][0].source_url
            ? post._embedded['wp:featuredmedia'][0].source_url
            : 'https://placehold.co/400x250/1e293b/ffffff?text=No+Image';

        // Extract author name
        const authorName = post._embedded && post._embedded['author'] && post._embedded['author'][0] && post._embedded['author'][0].name
            ? post._embedded['author'][0].name
            : 'Admin';

        // Get excerpt without HTML tags and truncate
        const excerpt = post.excerpt.rendered.replace(/(<([^>]+)>)/gi, "").substring(0, 150); // Remove HTML, then truncate

        return `
            <div class="bg-blue-900 rounded-lg shadow-lg overflow-hidden flex flex-col">
                <a href="${post.link}" target="_blank">
                    <img src="${imageUrl}" alt="${post.title.rendered}" class="w-full h-48 object-cover">
                </a>
                <div class="p-6 flex-1 flex flex-col">
                    <h3 class="text-xl font-bold text-white mb-2 leading-tight">
                        <a href="${post.link}" target="_blank" class="hover:text-orange-400 transition">${post.title.rendered}</a>
                    </h3>
                    <p class="text-blue-300 text-sm mb-3">By ${authorName} on ${new Date(post.date).toLocaleDateString()}</p>
                    <div class="text-blue-200 text-sm mb-4 flex-1">${excerpt}...</div>
                    <a href="${post.link}" target="_blank" class="text-orange-400 hover:text-orange-300 font-semibold mt-auto">Read More</a>
                </div>
            </div>
        `;
    }

    async function fetchAllBlogPosts() {
        const allBlogPostsContainer = document.getElementById('all-blog-posts-container');
        if (!allBlogPostsContainer) return;

        allBlogPostsContainer.innerHTML = '<p class="text-center text-blue-300 col-span-full">Loading blog posts...</p>';

        try {
            // Fetch all posts, including featured media and author info
            const response = await fetch(`${WORDPRESS_API_URL}/posts?_embed`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const posts = await response.json();

            if (posts.length > 0) {
                allBlogPostsContainer.innerHTML = posts.map(renderBlogPostCard).join('');
            } else {
                allBlogPostsContainer.innerHTML = '<p class="text-center text-blue-300 col-span-full">No blog posts found.</p>';
            }
        } catch (error) {
            console.error('Error fetching WordPress blog posts:', error);
            allBlogPostsContainer.innerHTML = `<p class="text-center text-red-400 col-span-full">Failed to load blog posts. Please check the WORDPRESS_API_URL and try again.</p>`;
        }
    }

    fetchAllBlogPosts();
});