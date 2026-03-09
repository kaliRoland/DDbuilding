<?php session_start(); ?>
<?php include 'includes/header.php'; ?>

<section class="py-20 mt-24 bg-blue-950">
    <div class="container mx-auto px-6 text-white">
        <h1 class="text-4xl md:text-5xl font-bold text-center mb-6">About Us</h1>
        <p class="text-lg text-blue-200 text-center mb-10 max-w-3xl mx-auto">
            This is a placeholder for the About Us page content. 
            You can add detailed information about your company's history, mission, values, and team members here.
        </p>

        <!-- Placeholder for about us pictures -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-12">
            <div>
                <img src="uploads/placeholders/placeholder_about_1.jpg" alt="About Us Image 1" class="w-full h-auto rounded-lg shadow-lg mb-4">
                <p class="text-blue-300 text-sm">
                    Description for About Us Image 1.
                    (Please replace 'uploads/placeholders/placeholder_about_1.jpg' with an actual image.)
                </p>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-white mb-4">Our Vision for a Secure & Sustainable Future</h2>
                <p class="text-blue-200 leading-relaxed mb-4">
                    At DDbuilding Tech, we are pioneers in empowering homes and businesses with robust, forward-thinking solutions. 
                    We specialize in delivering cutting-edge sustainable energy systems, primarily through advanced solar technology, 
                    ensuring reliable power and energy independence.
                </p>
                <p class="text-blue-200 leading-relaxed mb-4">
                    Beyond energy, our expertise extends to comprehensive security infrastructures. From military-grade CCTV surveillance 
                    and intelligent access control to state-of-the-art fire alarms and integrated building automation, we engineer environments
                    that are not only efficient but also supremely secure.
                </p>
                <p class="text-blue-200 leading-relaxed">
                    Our commitment is to resilience, innovation, and unparalleled expert support, helping you build a future that is
                    both powerful and protected.
                </p>
            </div>
        </div>
        <!-- End Placeholder for about us pictures -->
    </div>
</section>

<?php include 'includes/footer.php'; ?>