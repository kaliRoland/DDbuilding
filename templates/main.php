<?php require_once 'config/database.php'; ?>
    <!-- Hero Section -->
    <section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
        <!-- Background accent -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-blue-800 via-blue-950 to-blue-950 -z-10"></div>
        
        <div class="container mx-auto px-6 text-center relative z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-900 border border-blue-700 text-orange-400 text-xs font-bold uppercase tracking-wider mb-6">
                <span class="w-2 h-2 rounded-full bg-orange-500 animate-pulse"></span>
                New 2025 Series Available
            </div>
            <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold text-white mb-6 tracking-tight leading-tight">
                Power the Grid. <br/>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-orange-500">Secure the Perimeter.</span>
            </h1>
            <p class="text-lg text-blue-200 mb-10 max-w-2xl mx-auto">
                Industrial-grade solar components and high-definition surveillance systems designed for resilience in any environment.
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="#products" class="px-8 py-4 bg-orange-500 hover:bg-orange-400 text-white font-bold rounded hover:scale-105 transition transform duration-200">
                    Browse Catalog
                </a>
                <a href="#" class="px-8 py-4 bg-blue-800 hover:bg-blue-700 border border-blue-600 text-white font-semibold rounded hover:border-blue-500 transition duration-200">
                    Consult an Expert
                </a>
            </div>

            <!-- Stats Strip -->
            <div class="mt-20 grid grid-cols-2 md:grid-cols-4 gap-8 border-t border-blue-800 pt-10 max-w-4xl mx-auto">
                <div>
                    <p class="text-3xl font-bold text-white">25yr</p>
                    <p class="text-xs uppercase tracking-wider text-blue-300 mt-1">Panel Warranty</p>
                </div>
                <div>
                    <p class="text-3xl font-bold text-white">4K</p>
                    <p class="text-xs uppercase tracking-wider text-blue-300 mt-1">UHD Resolution</p>
                </div>
                <div>
                    <p class="text-3xl font-bold text-white">99%</p>
                    <p class="text-xs uppercase tracking-wider text-blue-300 mt-1">Uptime Rating</p>
                </div>
                <div>
                    <p class="text-3xl font-bold text-white">24/7</p>
                    <p class="text-xs uppercase tracking-wider text-blue-300 mt-1">Tech Support</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Features Section -->
    <section class="py-20 bg-blue-950">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-bold text-white text-center mb-12">Why Choose Us?</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-blue-900 p-8 rounded-lg text-center border border-blue-800 hover:border-orange-500/50 transition duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-solar-panel mx-auto text-orange-400 mb-4"><path d="M11.77 17.51c-2.48-.92-4.32-2.8-5.32-5.32"/><path d="M12 2v6h6"/><path d="M22 13v3c0 1.6-1.4 3-3 3H3c-1.6 0-3-1.4-3-3V7c0-1.6 1.4-3 3-3h1"/><path d="M13 18v-1.5a.5.5 0 0 1 1 0V18a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-1.5a.5.5 0 0 1 1 0V18a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-1.5a.5.5 0 0 1 1 0V18"/><path d="M22 17v-1.5a.5.5 0 0 0-1 0V17a1 1 0 0 1-1 1h-1a1 1 0 0 1-1-1v-1.5a.5.5 0 0 0-1 0V17a1 1 0 0 1-1 1h-1a1 1 0 0 1-1-1v-1.5a.5.5 0 0 0-1 0V17"/></svg>
                    <h3 class="text-xl font-bold text-white mb-2">Sustainable Energy</h3>
                    <p class="text-blue-300 text-sm">Harness the power of the sun with our cutting-edge solar infrastructure solutions.</p>
                </div>
                <!-- Feature 2 -->
                <div class="bg-blue-900 p-8 rounded-lg text-center border border-blue-800 hover:border-orange-500/50 transition duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-camera-off mx-auto text-orange-400 mb-4"><path d="M10.4 4.1L8.8 2.5C7.6 1.3 5.9 1 4 2.2C2.1 3.4 1.8 5.1 3 7L4.1 8.4"/><path d="M6 10.4V11c0 1.1 0.9 2 2 2h4.5M10.4 19.5H8c-2.2 0-4-1.8-4-4V7c0-1.2.5-2.3 1.3-3.1"/><path d="M20.2 19.5c-1.6 1-3.5 1-5 0l-1.3-.8"/><path d="M22 7c-1.2-1.8-3.3-2.2-5-1.5L13.5 6"/><path d="M21.5 13.5L16.2 17.7C15.5 18.2 14.8 18.4 14 18.4H8a4 4 0 0 1-4-4V11c0-.7.2-1.4.6-2L3 7c-1.2-1.8-1.5-4.1 0-5.4C4.2 0.4 6 0 7.5 1.7L9.5 4"/><path d="M2 2L22 22"/></svg>
                    <h3 class="text-xl font-bold text-white mb-2">Uncompromised Security</h3>
                    <p class="text-blue-300 text-sm">Advanced surveillance systems and robust perimeter defense for total peace of mind.</p>
                </div>
                <!-- Feature 3 -->
                <div class="bg-blue-900 p-8 rounded-lg text-center border border-blue-800 hover:border-orange-500/50 transition duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award mx-auto text-orange-400 mb-4"><circle cx="12" cy="8" r="7"/><path d="M8.21 13.89 7 22l5-3 5 3-1.21-8.11"/></svg>
                    <h3 class="text-xl font-bold text-white mb-2">Expert Support</h3>
                    <p class="text-blue-300 text-sm">Our dedicated team of professionals provides 24/7 support and expert guidance.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section class="py-20 bg-blue-950">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-bold text-white text-center mb-12">Featured Products</h2>
            <div id="featured-products-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Featured products will be injected here by JavaScript -->
            </div>
        </div>
    </section>



    <!-- Product List -->
    <section id="products" class="py-20 bg-blue-950">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-bold text-white text-center mb-12">Shop</h2>
            
            <div id="product-grid-homepage" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- JS will inject products here -->
            </div>
            
            <div class="text-center mt-12">
                <a href="products.php" class="px-8 py-4 bg-orange-500 hover:bg-orange-400 text-white font-bold rounded hover:scale-105 transition transform duration-200">
                    View More Products
                </a>
            </div>
        </div>
    </section>

<!-- Blog Section -->
<section class="py-20 bg-slate-850">
    <div class="container mx-auto px-6">
        <div class="flex justify-between items-end mb-10">
            <div>
                <h2 class="text-2xl font-bold text-white mb-4">From the Blog</h2>
                <div class="h-1 w-20 bg-amber-500 mt-2 rounded-full"></div>
            </div>
            <a href="blog.php" class="text-amber-400 text-sm hover:text-amber-300 transition flex items-center gap-1">View All Posts <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></a>
        </div>

        <div id="blog-posts-container" class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Blog posts will be injected here by JavaScript -->
        </div>
    </div>
</section>