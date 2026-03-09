<?php session_start(); ?>
<?php include 'includes/header.php'; ?>

<main class="container mx-auto px-6 py-8 pt-32">
    <div class="flex flex-col md:flex-row gap-8">
        <!-- Filters -->
        <aside class="w-full md:w-1/4">
            <div class="bg-blue-900 p-6 rounded-xl border border-blue-800">
                <h3 class="text-xl font-bold text-white mb-6">Filters</h3>
                
                <!-- Search -->
                <div class="mb-6">
                    <label for="search-input" class="block text-sm font-medium text-blue-200 mb-2">Search by name</label>
                    <input type="text" id="search-input" placeholder="e.g. Drone, Camera" class="w-full bg-blue-950 border border-blue-700 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-orange-500 transition">
                </div>
                
                <!-- Category -->
                <div class="mb-6">
                    <label for="category-filter" class="block text-sm font-medium text-blue-200 mb-2">Category</label>
                    <select id="category-filter" class="w-full bg-blue-950 border border-blue-700 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-orange-500 transition">
                        <option value="">All Categories</option>
                    </select>
                </div>

                <!-- Price Range -->
                <div class="mb-6">
                    <label for="price-filter" class="block text-sm font-medium text-blue-200 mb-2">Max Price</label>
                    <input type="range" id="price-filter" min="0" max="20000000" value="20000000" class="w-full h-2 bg-blue-700 rounded-lg appearance-none cursor-pointer">
                    <div class="text-right text-sm text-blue-200 mt-1" id="price-value">NGN20000000</div>
                </div>

                <!-- Sorting -->
                <div class="mb-6">
                    <label for="sort-filter" class="block text-sm font-medium text-blue-200 mb-2">Sort by</label>
                    <select id="sort-filter" class="w-full bg-blue-950 border border-blue-700 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-orange-500 transition">
                        <option value="created_at_desc">Latest</option>
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                        <option value="name_asc">Name: A to Z</option>
                        <option value="name_desc">Name: Z to A</option>
                    </select>
                </div>

                <!-- Clear Filters -->
                <button id="clear-filters-btn" class="w-full text-center bg-blue-800 hover:bg-blue-700 text-white font-medium py-2 rounded-lg transition">Clear Filters</button>
            </div>
        </aside>

        <!-- Product Grid -->
        <section class="w-full md:w-3/4">
            <div id="product-grid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-8">
                <!-- Products will be loaded here by JavaScript -->
            </div>
            <div id="pagination" class="mt-8 flex justify-center space-x-2">
                <!-- Pagination will be loaded here -->
            </div>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
