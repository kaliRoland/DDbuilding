<?php
require_once __DIR__ . '/../config/env.php';
$meta_pixel_id = getenv('META_PIXEL_ID') ?: '';
$meta_domain_verification = getenv('META_DOMAIN_VERIFICATION') ?: '';
$page_title = isset($page_title) && trim((string)$page_title) !== '' ? trim((string)$page_title) : 'DDbuilding Tech - Advanced Systems';
$meta_description = isset($meta_description) && trim((string)$meta_description) !== '' ? trim((string)$meta_description) : 'Shop smart energy and security products from DDbuilding Tech.';
$canonical_url = isset($canonical_url) && trim((string)$canonical_url) !== '' ? trim((string)$canonical_url) : '';
$structured_data = isset($structured_data) && trim((string)$structured_data) !== '' ? trim((string)$structured_data) : '';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title, ENT_QUOTES) ?></title>
    <meta name="description" content="<?= htmlspecialchars($meta_description, ENT_QUOTES) ?>">
    <?php if ($canonical_url !== ''): ?>
    <link rel="canonical" href="<?= htmlspecialchars($canonical_url, ENT_QUOTES) ?>">
    <?php endif; ?>
    <link rel="icon" href="uploads/logo/logo.png">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide-react@0.292.0/dist/lucide-react.js"></script>
    <script>
        // Custom Tailwind config with brand colors
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
                    },
                    colors: {
                        slate: {
                            850: '#1e3a5f', // Custom darker blue
                        },
                        brand: {
                            blue: '#1e3a8a',
                            'blue-light': '#3b82f6',
                            'blue-dark': '#0f172a',
                            white: '#ffffff',
                            orange: '#f97316'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #0f172a; 
        }
        ::-webkit-scrollbar-thumb {
            background: #3b82f6; 
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #f97316; 
        }
        
        /* Hide number input arrows */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>

    <script>
        // Fetch WordPress posts from REST API
        async function fetchWordPressPosts() {
            try {
            const response = await fetch('api/blog.php?per_page=10');
                const posts = await response.json();
                console.log('WordPress Posts:', posts);
                return posts;
            } catch (error) {
                console.error('Error fetching WordPress posts:', error);
                return [];
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', async () => {
            const posts = await fetchWordPressPosts();
            // Posts are now available for use throughout the page
            window.wordPressPosts = posts;
            
            // Auto-populate blog containers if they exist
            if (document.getElementById('all-blog-posts-container') || document.getElementById('featured-blog-posts')) {
                displayBlogPosts(posts);
            }
        });

        // Function to display blog posts
        function displayBlogPosts(posts) {
            if (!posts || posts.length === 0) return;
            
            // Display on blog page (all posts)
            const allPostsContainer = document.getElementById('all-blog-posts-container');
            if (allPostsContainer) {
                posts.forEach(post => {
                    const postCard = createPostCard(post);
                    allPostsContainer.appendChild(postCard);
                });
            }
            
            // Display on homepage (featured posts - first 3)
            const featuredContainer = document.getElementById('featured-blog-posts');
            if (featuredContainer) {
                posts.slice(0, 3).forEach(post => {
                    const postCard = createPostCard(post);
                    featuredContainer.appendChild(postCard);
                });
            }
        }

        // Create a blog post card element
        function createPostCard(post) {
            const div = document.createElement('div');
            div.className = 'bg-blue-900 rounded-lg overflow-hidden border border-blue-800 hover:border-orange-500/50 transition duration-300';
            
            const date = new Date(post.date).toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
            
            const excerpt = post.excerpt.rendered
                .replace(/<[^>]*>/g, '')
                .substring(0, 150) + '...';
            
            div.innerHTML = `
                ${post.featured_media ? `<img src="${post.featured_media}" alt="${post.title.rendered}" class="w-full h-48 object-cover">` : ''}
                <div class="p-6">
                    <p class="text-orange-400 text-sm font-semibold mb-2">${date}</p>
                    <h3 class="text-xl font-bold text-white mb-3 line-clamp-2">${post.title.rendered}</h3>
                    <p class="text-blue-300 text-sm mb-4 line-clamp-2">${excerpt}</p>
                    <a href="${post.link}" target="_blank" class="inline-flex items-center gap-2 text-orange-400 hover:text-orange-300 font-semibold transition">
                        Read More
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            `;
            return div;
        }
    </script>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-5NVJVRF7');</script>
    <!-- End Google Tag Manager -->
<?php if ($meta_domain_verification): ?>
    <meta name="facebook-domain-verification" content="<?= htmlspecialchars($meta_domain_verification, ENT_QUOTES) ?>">
<?php endif; ?>
<?php if ($meta_pixel_id): ?>
    <!-- Meta Pixel Code -->
    <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
        n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
        document,'script','https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '<?= htmlspecialchars($meta_pixel_id, ENT_QUOTES) ?>');
        fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=<?= urlencode($meta_pixel_id) ?>&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->
<?php endif; ?>
<?php if ($structured_data !== ''): ?>
    <script type="application/ld+json"><?= $structured_data ?></script>
<?php endif; ?>
</head>
<body class="bg-blue-950 text-slate-100 font-sans antialiased selection:bg-orange-500 selection:text-white">
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5NVJVRF7"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->

    <!-- Floating Header with Glassmorphism -->
    <header class="fixed w-full top-0 z-50 bg-white shadow-md">
        <nav class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <!-- Logo -->
                <a href="index.php" class="flex items-center gap-2 group">
                    <img src="uploads/logo/logo.png" alt="DDbuildingTech Logo" class="h-8">
                </a>

                <!-- Desktop Nav -->
                <div class="hidden md:flex items-center space-x-8 font-medium text-sm uppercase tracking-widest text-blue-900">
                    <a href="index.php" class="hover:text-orange-500 transition-colors duration-300">Home</a>
                    <a href="products.php" class="hover:text-orange-500 transition-colors duration-300">Shop</a>
                    <a href="blog.php" class="hover:text-orange-500 transition-colors duration-300">Blog</a>
                    <a href="gallery.php">Gallery</a>
                    
                    <!-- Solutions Dropdown -->
                    <div class="relative group">
                        <a href="index.php#solutions" class="hover:text-orange-500 transition-colors duration-300 flex items-center gap-1">
                            Solutions
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-down"><path d="m6 9 6 6 6-6"/></svg>
                        </a>
                        <div class="absolute left-0 mt-0 hidden group-hover:block bg-white border border-blue-200 rounded-lg shadow-lg py-2 min-w-48 z-50">
                            <a href="solar-energy-solutions.php" class="block px-4 py-2 text-blue-900 hover:bg-orange-50 hover:text-orange-600 transition">Solar Energy Solutions</a>
                            <a href="cctv-solutions.php" class="block px-4 py-2 text-blue-900 hover:bg-orange-50 hover:text-orange-600 transition">CCTV Solutions</a>
                            <a href="access-control-time-attendance.php" class="block px-4 py-2 text-blue-900 hover:bg-orange-50 hover:text-orange-600 transition">Access Control & Time Attendance</a>
                            <a href="fire-alarm.php" class="block px-4 py-2 text-blue-900 hover:bg-orange-50 hover:text-orange-600 transition">Fire Alarm</a>
                            <a href="building-automation.php" class="block px-4 py-2 text-blue-900 hover:bg-orange-50 hover:text-orange-600 transition">Building Automation</a>
                        </div>
                    </div>
                    <a href="contact.php">Support</a>
                </div>

                <!-- Actions -->
                <div class="flex items-center space-x-5">
                     <button class="hidden md:block text-blue-900 hover:text-orange-500 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </button>

                    <button id="cart-button" class="group flex items-center gap-2 text-blue-900 hover:text-orange-500 transition">
                        <div class="relative">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-bag"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            <span id="cart-count" class="absolute -top-2 -right-2 bg-orange-500 text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">0</span>
                        </div>
                        <span class="hidden lg:block text-sm font-semibold group-hover:text-orange-500 transition">Cart</span>
                    </button>

                     <!-- Mobile Menu Button -->
                     <button id="mobile-menu-button" class="md:hidden text-blue-900 hover:text-orange-500">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-menu"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                    </button>
                </div>
            </div>
             <!-- Mobile Menu -->
             <div id="mobile-menu" class="hidden md:hidden mt-4 border-t border-blue-200 pt-4">
                <a href="index.php" class="block py-2 text-blue-900 hover:text-orange-500">Home</a>
                <a href="products.php" class="block py-2 text-blue-900 hover:text-orange-500">Shop</a>
                <a href="blog.php" class="block py-2 text-blue-900 hover:text-orange-500">Blog</a>
                <a href="gallery.php" class="block py-2 text-blue-900 hover:text-orange-500">Gallery</a>
                <a href="about.php" class="block py-2 text-blue-900 hover:text-orange-500">About Us</a>
                <a href="index.php#solutions" class="block py-2 text-blue-900 hover:text-orange-500">Solutions</a>
                <a href="/contact.php" class="block py-2 text-blue-900 hover:text-orange-500">Support</a>
            </div>
        </nav>
    </header>
