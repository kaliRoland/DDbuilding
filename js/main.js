document.addEventListener('DOMContentLoaded', () => {
    // --- State ---
    let cart = [];
    let products = [];
    let currentPage = 1; // For shop page pagination
    let debounceTimer; // For search input

    // --- DOM Elements (Common) ---
    const cartButton = document.getElementById('cart-button');
    const closeCartButton = document.getElementById('close-cart-button');
    const cartModal = document.getElementById('cart-modal');
    const cartBackdrop = document.getElementById('cart-backdrop');
    const cartPanel = document.getElementById('cart-panel');
    const cartItemsContainer = document.getElementById('cart-items-container');
    const emptyCartMessage = document.getElementById('empty-cart-message');
    const cartCount = document.getElementById('cart-count');
    const cartSubtotal = document.getElementById('cart-subtotal');
    const checkoutButton = document.getElementById('checkout-button');
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    // --- DOM Elements (Homepage Specific) ---
    const featuredProductsGrid = document.getElementById('featured-products-grid');

    // --- DOM Elements (Shop Page Specific) ---
    const productGridShop = document.getElementById('product-grid'); // This will be the same ID as homepage, but on products.php
    const searchInput = document.getElementById('search-input');
    const categoryFilterShop = document.getElementById('category-filter'); // Renamed to avoid conflict
    const priceFilter = document.getElementById('price-filter');
    const priceValue = document.getElementById('price-value');
    const sortFilter = document.getElementById('sort-filter');
    const clearFiltersBtn = document.getElementById('clear-filters-btn');
    const paginationContainer = document.getElementById('pagination');

    // --- Rendering (Common) ---
    function renderProductCard(product) {
        return `
            <div class="group bg-blue-900 rounded-xl border border-blue-800 overflow-hidden hover:border-orange-500/50 transition duration-300 flex flex-col">
                <div class="relative overflow-hidden h-64 bg-blue-950">
                    <a href="product.php?id=${product.id}">
                        <img src="${product.image_main}" alt="${product.name}" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    </a>
                    <span class="absolute top-3 left-3 bg-blue-950/90 text-white text-xs font-bold px-2 py-1 rounded border border-blue-700 uppercase tracking-wide">
                        ${product.category}
                    </span>
                </div>
                <div class="p-6 flex-1 flex flex-col">
                    <h3 class="text-lg font-bold text-white mb-1 group-hover:text-orange-400 transition">
                        <a href="product.php?id=${product.id}">${product.name}</a>
                    </h3>
                    <p class="text-2xl font-bold text-blue-200 mb-4">NGN${parseFloat(product.price).toFixed(2)}</p>
                    <button 
                        onclick="addToCart(${product.id})"
                        class="mt-auto w-full bg-blue-800 hover:bg-orange-500 hover:text-white text-white font-semibold py-3 rounded transition duration-300 flex items-center justify-center gap-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        Add to Cart
                    </button>
                </div>
            </div>
        `;
    }

    function renderProducts(productsToRender, targetGrid) {
        if (!targetGrid) return; // Ensure targetGrid exists
        targetGrid.innerHTML = productsToRender.map(product => renderProductCard(product)).join('');
    }

    function renderCart(cart) {
        cartCount.innerText = cart.reduce((acc, item) => acc + item.quantity, 0);
        
        const total = cart.reduce((acc, item) => acc + (item.price * item.quantity), 0);
        cartSubtotal.innerText = 'NGN' + total.toFixed(2);

        if (cart.length === 0) {
            emptyCartMessage.classList.remove('hidden');
            checkoutButton.disabled = true;
            cartItemsContainer.innerHTML = '';
            cartItemsContainer.appendChild(emptyCartMessage);
        } else {
            emptyCartMessage.classList.add('hidden');
            checkoutButton.disabled = false;
            cartItemsContainer.innerHTML = cart.map(item => `
                <div class="flex gap-4 bg-blue-900/50 p-3 rounded-lg border border-blue-800">
                    <img src="${item.image_main}" class="w-20 h-20 object-cover rounded bg-blue-950">
                    <div class="flex-1">
                        <div class="flex justify-between items-start">
                            <h4 class="font-semibold text-white text-sm">${item.name}</h4>
                            <button onclick="removeFromCart(${item.id})" class="text-blue-300 hover:text-red-400 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                            </button>
                        </div>
                        <p class="text-orange-400 font-bold text-sm mt-1">NGN${parseFloat(item.price).toFixed(2)}</p>
                        <div class="flex items-center mt-3 gap-3">
                            <button onclick="updateQuantity(${item.id}, -1)" class="w-6 h-6 rounded bg-blue-800 hover:bg-blue-700 flex items-center justify-center text-white text-xs">-</button>
                            <span class="text-sm font-medium text-white w-4 text-center">${item.quantity}</span>
                            <button onclick="updateQuantity(${item.id}, 1)" class="w-6 h-6 rounded bg-blue-800 hover:bg-blue-700 flex items-center justify-center text-white text-xs">+</button>
                        </div>
                    </div>
                </div>
            `).join('');
        }
    }

    // --- Logic (Common) ---
    async function updateCart() {
        const response = await fetch('api/cart.php?action=get');
        cart = await response.json();
        renderCart(cart);
    }

    window.addToCart = async (id) => {
        const data = new FormData();
        data.append('id', id);
        await fetch('api/cart.php?action=add', {
            method: 'POST',
            body: data
        });
        await updateCart();
        openCart();
    };

    window.removeFromCart = async (id) => {
        const data = new FormData();
        data.append('id', id);
        await fetch('api/cart.php?action=remove', {
            method: 'POST',
            body: data
        });
        await updateCart();
    };

    window.updateQuantity = async (id, change) => {
        const data = new FormData();
        data.append('id', id);
        data.append('change', change);
        await fetch('api/cart.php?action=update', {
            method: 'POST',
            body: data
        });
        await updateCart();
    };

    // --- UI Controls (Common) ---
    function openCart() {
        cartModal.classList.remove('hidden');
        // Small timeout to allow display:block to apply before opacity transition
        setTimeout(() => {
            cartBackdrop.classList.remove('opacity-0');
            cartPanel.classList.remove('translate-x-full');
        }, 10);
        document.body.style.overflow = 'hidden';
    }

    function closeCart() {
        cartBackdrop.classList.add('opacity-0');
        cartPanel.classList.add('translate-x-full');
        setTimeout(() => {
            cartModal.classList.add('hidden');
            document.body.style.overflow = '';
        }, 500);
    }

    cartButton.addEventListener('click', openCart);
    closeCartButton.addEventListener('click', closeCart);
    cartBackdrop.addEventListener('click', closeCart);
    
    mobileMenuButton.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');
    });

    // --- WordPress Blog Integration ---
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

        return `
            <div class="bg-slate-800 rounded-lg shadow-lg overflow-hidden flex flex-col">
                <a href="${post.link}" target="_blank">
                    <img src="${imageUrl}" alt="${post.title.rendered}" class="w-full h-48 object-cover">
                </a>
                <div class="p-6 flex-1 flex flex-col">
                    <h3 class="text-xl font-bold text-white mb-2 leading-tight">
                        <a href="${post.link}" target="_blank" class="hover:text-amber-400 transition">${post.title.rendered}</a>
                    </h3>
                    <p class="text-slate-400 text-sm mb-3">By ${authorName} on ${new Date(post.date).toLocaleDateString()}</p>
                    <div class="text-slate-300 text-sm mb-4 flex-1">${post.excerpt.rendered.substring(0, 100)}...</div>
                    <a href="${post.link}" target="_blank" class="text-amber-400 hover:text-amber-300 font-semibold mt-auto">Read More</a>
                </div>
            </div>
        `;
    }

    async function fetchAndRenderHomepageBlogPosts() {
        const blogPostsContainer = document.getElementById('blog-posts-container');
        if (!blogPostsContainer) return;

        blogPostsContainer.innerHTML = '<p class="text-center text-slate-400 col-span-full">Loading blog posts...</p>';

        try {
            // Fetch latest 3 posts, including featured media and author info
            const response = await fetch(`${WORDPRESS_API_URL}/posts?_embed&per_page=3`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const posts = await response.json();

            if (posts.length > 0) {
                blogPostsContainer.innerHTML = posts.map(renderBlogPostCard).join('');
            } else {
                blogPostsContainer.innerHTML = '<p class="text-center text-slate-400 col-span-full">No blog posts found.</p>';
            }
        } catch (error) {
            console.error('Error fetching WordPress blog posts:', error);
            blogPostsContainer.innerHTML = `<p class="text-center text-red-400 col-span-full">Failed to load blog posts. Please check the WORDPRESS_API_URL and try again.</p>`;
        }
    }


    // --- Shop Page Specific Logic ---
    async function getShopProducts() {
        const search = searchInput ? searchInput.value : '';
        const category = categoryFilterShop ? categoryFilterShop.value : '';
        const maxPrice = priceFilter ? priceFilter.value : '';
        const sort = sortFilter ? sortFilter.value : 'created_at_desc';
        
        const apiUrl = '/DD/api/products.php'; 

        const url = new URL(apiUrl, window.location.origin);
        url.searchParams.append('action', 'get_all');
        if (search) url.searchParams.append('search', search);
        if (category) url.searchParams.append('category', category);
        if (maxPrice) url.searchParams.append('max_price', maxPrice);
        if (sort) url.searchParams.append('sort', sort);

        try {
            const response = await fetch(url);
            const data = await response.json();

            if (data.status === 'success') {
                renderProducts(data.products, productGridShop);
                renderShopPagination(data.pagination);
                if (data.categories && categoryFilterShop) {
                    populateShopCategoryFilter(data.categories);
                }
            } else {
                console.error(`Error fetching shop products: ${data.message}`);
                if (productGridShop) productGridShop.innerHTML = `<p class="text-red-400 text-center col-span-full">Error loading products: ${data.message}</p>`;
            }
        } catch (error) {
            console.error('Failed to fetch shop products:', error);
            if (productGridShop) productGridShop.innerHTML = '<p class="text-red-400 text-center col-span-full">Failed to load products. Please try again later.</p>';
        }
    }

    function renderShopPagination(pagination) {
        if (!paginationContainer) return;
        paginationContainer.innerHTML = '';
        if (pagination.total_pages <= 1) return;

        for (let i = 1; i <= pagination.total_pages; i++) {
            const button = document.createElement('button');
            button.textContent = i;
            button.className = `px-3 py-1 rounded ${i === pagination.page ? 'bg-amber-500 text-slate-900' : 'bg-slate-700 hover:bg-slate-600'}`;
            button.addEventListener('click', () => {
                currentPage = i;
                getShopProducts(i);
            });
            paginationContainer.appendChild(button);
        }
    }

    function populateShopCategoryFilter(categories) {
        if (!categoryFilterShop) return;
        const currentCategory = categoryFilterShop.value;
        // Clear all options except "All Categories"
        while (categoryFilterShop.options.length > 1) {
            categoryFilterShop.remove(1);
        }
        categories.forEach(category => {
            const option = new Option(category, category);
            categoryFilterShop.add(option);
        });
        categoryFilterShop.value = currentCategory; // Restore previous selection
    }

    function clearShopFilters() {
        if (searchInput) searchInput.value = '';
        if (categoryFilterShop) categoryFilterShop.value = '';
        if (priceFilter) {
            priceFilter.value = priceFilter.max;
            if (priceValue) priceValue.textContent = `NGN${priceFilter.max}`;
        }
        if (sortFilter) sortFilter.value = 'created_at_desc';
        currentPage = 1;
        getShopProducts();
    }

    // --- Initial Load ---
    async function initialize() {
        await updateCart(); // Always update cart

        const productGridHomepage = document.getElementById('product-grid-homepage');
        if (productGridHomepage) {
             // Fetch all products for homepage product grid
             const allProductsResponse = await fetch('api/products.php?action=get_all&limit=8');
             const allProductsData = await allProductsResponse.json();
             if (allProductsData.status === 'success') {
                 renderProducts(allProductsData.products, productGridHomepage);
             }
        }

        // Check if it's the homepage (index.php)
        if (document.getElementById('featured-products-grid')) { // A unique element on index.php
            // Fetch featured products for homepage
            const featuredProductsResponse = await fetch('api/products.php?action=get_featured');
            const featuredProducts = await featuredProductsResponse.json();
            renderProducts(featuredProducts, featuredProductsGrid);
        } 
        
        // Fetch and render blog posts for the homepage
        fetchAndRenderHomepageBlogPosts();

        // Check if it's the shop page (products.php)
        if (document.getElementById('search-input') && document.getElementById('category-filter') && document.getElementById('price-filter')) {
            if (priceValue && priceFilter) priceValue.textContent = `NGN${priceFilter.value}`;
            getShopProducts();

            // Event Listeners for Shop Page
            if (searchInput) {
                searchInput.addEventListener('keyup', () => {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => { currentPage = 1; getShopProducts(); }, 300);
                });
            }
            if (categoryFilterShop) {
                categoryFilterShop.addEventListener('change', () => { currentPage = 1; getShopProducts(); });
            }
            if (priceFilter) {
                priceFilter.addEventListener('input', () => {
                    if (priceValue) priceValue.textContent = `NGN${priceFilter.value}`;
                });
                priceFilter.addEventListener('change', () => { currentPage = 1; getShopProducts(); });
            }
            if (sortFilter) {
                sortFilter.addEventListener('change', () => { currentPage = 1; getShopProducts(); });
            }
            if (clearFiltersBtn) {
                clearFiltersBtn.addEventListener('click', clearShopFilters);
            }
        }
    }

    initialize();
});