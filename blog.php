<?php
session_start();
include 'includes/header.php';
?>

<main class="py-32 bg-blue-950">
    <div class="container mx-auto px-6">
        <h1 class="text-4xl lg:text-5xl font-bold text-white mb-8 text-center">Blog</h1>
        
        <div id="all-blog-posts-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- All blog posts will be injected here by JavaScript -->
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>