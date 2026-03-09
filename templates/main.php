<?php require_once 'config/database.php'; ?>
<?php
// Fetch hero slides from database
$slides_result = $conn->query("SELECT * FROM hero_slides ORDER BY id LIMIT 4");
$hero_slides = [];
while ($row = $slides_result->fetch_assoc()) {
    $hero_slides[] = $row;
}
?>
    <!-- Hero Section - Image Slider -->
    <section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden bg-blue-950">
        <div id="hero-slider" class="absolute inset-0">
            <?php foreach ($hero_slides as $index => $slide): ?>
            <div class="slide absolute inset-0 transition-opacity duration-1000 ease-in-out <?php echo $index === 0 ? 'opacity-100' : 'opacity-0'; ?>">
                <img src="<?php echo htmlspecialchars($slide['image_path']); ?>" alt="<?php echo htmlspecialchars($slide['title']); ?>" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-blue-950/70"></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="container mx-auto px-6 text-center relative z-10 min-h-[calc(100vh-100px)] flex flex-col justify-end pb-20">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-900 border border-blue-700 text-orange-400 text-xs font-bold uppercase tracking-wider mb-6 mx-auto">
                <span class="w-2 h-2 rounded-full bg-orange-500 animate-pulse"></span>
                New 2025 Series Available
            </div>
            <h1 id="hero-title" class="text-4xl md:text-6xl lg:text-7xl font-bold text-white mb-6 tracking-tight leading-tight">
                <?php echo htmlspecialchars($hero_slides[0]['title'] ?? 'Power the Grid. Secure the Perimeter.'); ?>
            </h1>
            <p id="hero-subtitle" class="text-lg text-blue-200 mb-10 max-w-2xl mx-auto">
                <?php echo htmlspecialchars($hero_slides[0]['subtitle'] ?? 'Industrial-grade solar components and high-definition surveillance systems designed for resilience in any environment.'); ?>
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
        
        <!-- Slider Navigation Dots -->
        <div id="slider-dots" class="absolute bottom-4 left-1/2 transform -translate-x-1/2 z-20 flex space-x-2">
            <button class="dot w-3 h-3 bg-white rounded-full opacity-50"></button>
            <button class="dot w-3 h-3 bg-white rounded-full opacity-50"></button>
            <button class="dot w-3 h-3 bg-white rounded-full opacity-50"></button>
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

    <!-- Services Section -->
    <section id="solutions" class="py-20 bg-blue-950">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-bold text-white text-center mb-12">Our Services</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8">
                <!-- Service 1 -->
                <a href="solar-energy-solutions.php" class="relative p-8 rounded-lg text-center border border-blue-800 hover:border-orange-500/50 transition duration-300 bg-cover bg-center group">
                    <div class="absolute inset-0 bg-blue-900/80 group-hover:bg-blue-900/70 transition-colors duration-300 rounded-lg" style="background-image: url('uploads/services/solar solution.jpg'); background-blend-mode: multiply; background-size: cover; background-position: center;"></div>
                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sun mx-auto text-orange-400 mb-4"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                        <h3 class="text-xl font-bold text-white mb-2">Solar Energy Solutions</h3>
                        <p class="text-blue-300 text-sm">Harness the power of the sun with cutting-edge solar infrastructure and renewable energy systems.</p>
                    </div>
                </a>
                <!-- Service 2 -->
                <a href="cctv-solutions.php" class="relative p-8 rounded-lg text-center border border-blue-800 hover:border-orange-500/50 transition duration-300 bg-cover bg-center group">
                    <div class="absolute inset-0 bg-blue-900/80 group-hover:bg-blue-900/70 transition-colors duration-300 rounded-lg" style="background-image: url('uploads/services/cctv.jpg'); background-blend-mode: multiply; background-size: cover; background-position: center;"></div>
                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-camera mx-auto text-orange-400 mb-4"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                        <h3 class="text-xl font-bold text-white mb-2">CCTV Solutions</h3>
                        <p class="text-blue-300 text-sm">Advanced surveillance systems and high-definition monitoring for complete perimeter security.</p>
                    </div>
                </a>
                <!-- Service 3 -->
                <a href="access-control-time-attendance.php" class="relative p-8 rounded-lg text-center border border-blue-800 hover:border-orange-500/50 transition duration-300 bg-cover bg-center group">
                    <div class="absolute inset-0 bg-blue-900/80 group-hover:bg-blue-900/70 transition-colors duration-300 rounded-lg" style="background-image: url('uploads/services/time attendance.jpg'); background-blend-mode: multiply; background-size: cover; background-position: center;"></div>
                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lock mx-auto text-orange-400 mb-4"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <h3 class="text-xl font-bold text-white mb-2">Access Control & Time Attendance</h3>
                        <p class="text-blue-300 text-sm">Intelligent access control systems and biometric attendance tracking for enhanced security.</p>
                    </div>
                </a>
                <!-- Service 4 -->
                <a href="fire-alarm.php" class="relative p-8 rounded-lg text-center border border-blue-800 hover:border-orange-500/50 transition duration-300 bg-cover bg-center group">
                    <div class="absolute inset-0 bg-blue-900/80 group-hover:bg-blue-900/70 transition-colors duration-300 rounded-lg" style="background-image: url('uploads/services/fire alarm.jpg'); background-blend-mode: multiply; background-size: cover; background-position: center;"></div>
                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bell mx-auto text-orange-400 mb-4"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21h3.4a1 1 0 0 0 .98-.88l.11-1.42a1 1 0 0 0-.98-1.1h-4.5a1 1 0 0 0-.98 1.1l.11 1.42a1 1 0 0 0 .98.88z"/></svg>
                        <h3 class="text-xl font-bold text-white mb-2">Fire Alarm</h3>
                        <p class="text-blue-300 text-sm">State-of-the-art fire detection and alarm systems for rapid emergency response and protection.</p>
                    </div>
                </a>
                <!-- Service 5 -->
                <a href="building-automation.php" class="relative p-8 rounded-lg text-center border border-blue-800 hover:border-orange-500/50 transition duration-300 bg-cover bg-center group">
                    <div class="absolute inset-0 bg-blue-900/80 group-hover:bg-blue-900/70 transition-colors duration-300 rounded-lg" style="background-image: url('uploads/services/building automation.jpg'); background-blend-mode: multiply; background-size: cover; background-position: center;"></div>
                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings mx-auto text-orange-400 mb-4"><circle cx="12" cy="12" r="3"/><path d="M12 1v6m0 6v6M4.22 4.22l4.24 4.24m2.12 2.12l4.24 4.24M1 12h6m6 0h6m-16.78 7.78l4.24-4.24m2.12-2.12l4.24-4.24"/></svg>
                        <h3 class="text-xl font-bold text-white mb-2">Building Automation</h3>
                        <p class="text-blue-300 text-sm">Integrated smart building systems for energy efficiency, comfort, and intelligent facility management.</p>
                    </div>
                </a>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.dot');
    const heroTitle = document.getElementById('hero-title');
    const heroSubtitle = document.getElementById('hero-subtitle');
    const slideTitles = <?php echo json_encode(array_column($hero_slides, 'title')); ?>;
    const slideSubtitles = <?php echo json_encode(array_column($hero_slides, 'subtitle')); ?>;
    let currentSlide = 0;
    let slideInterval;

    function showSlide(index) {
        // Hide all slides
        slides.forEach(slide => slide.classList.remove('opacity-100'));
        slides.forEach(slide => slide.classList.add('opacity-0'));

        // Show current slide
        slides[index].classList.remove('opacity-0');
        slides[index].classList.add('opacity-100');

        // Update dots
        dots.forEach((dot, i) => {
            if (i === index) {
                dot.classList.add('bg-orange-500');
                dot.classList.remove('bg-white');
            } else {
                dot.classList.remove('bg-orange-500');
                dot.classList.add('bg-white');
            }
        });

        // Update title
        if (heroTitle && slideTitles[index]) {
            heroTitle.innerHTML = slideTitles[index].replace(/\n/g, '<br/>');
        }

        // Update subtitle
        if (heroSubtitle && slideSubtitles[index]) {
            heroSubtitle.innerHTML = slideSubtitles[index].replace(/\n/g, '<br/>');
        }
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }

    function startSlideshow() {
        slideInterval = setInterval(nextSlide, 5000); // Change slide every 5 seconds
    }

    function stopSlideshow() {
        clearInterval(slideInterval);
    }

    // Initialize
    if (slides.length > 0) {
        showSlide(0);
        startSlideshow();

        // Add click handlers to dots
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                currentSlide = index;
                showSlide(currentSlide);
                stopSlideshow();
                startSlideshow();
            });
        });

        // Pause on hover
        const heroSection = document.querySelector('section.relative');
        if (heroSection) {
            heroSection.addEventListener('mouseenter', stopSlideshow);
            heroSection.addEventListener('mouseleave', startSlideshow);
        }
    }
});
</script>

    
