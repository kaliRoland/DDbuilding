<?php include 'includes/header.php'; ?>

<main class="py-20 bg-blue-950">
    <div class="container mx-auto px-6">
        <article class="max-w-3xl mx-auto bg-blue-900 p-8 rounded-lg shadow-lg">
            <?php
            // Placeholder for WordPress REST API URL - User will provide this
            $wordpress_api_base_url = 'YOUR_WORDPRESS_REST_API_URL_HERE'; // Example: https://yourwordpress.com/wp-json/wp/v2/

            $post_id = $_GET['id'] ?? null;
            $post = null;
            $error = null;

            if (!$post_id) {
                $error = "No post ID provided.";
            } else {
                $wordpress_api_url = $wordpress_api_base_url . 'posts/' . $post_id . '?_embed';
                try {
                    $json_data = @file_get_contents($wordpress_api_url);
                    if ($json_data === FALSE) {
                        $error = "Could not connect to the WordPress API or post not found.";
                    } else {
                        $post = json_decode($json_data, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $error = "Error decoding JSON from WordPress API: " . json_last_error_msg();
                            $post = null;
                        }
                    }
                } catch (Exception $e) {
                    $error = "An unexpected error occurred: " . $e->getMessage();
                }
            }

            if ($error) {
                echo '<p class="text-red-400 text-center">' . htmlspecialchars($error) . '</p>';
            } elseif (!$post) {
                echo '<p class="text-slate-400 text-center">Post not found.</p>';
            } else {
                $title = htmlspecialchars($post['title']['rendered']);
                $content = $post['content']['rendered']; // Content is already HTML, no need to escape
                $date = date('F j, Y', strtotime($post['date']));
                $author = htmlspecialchars($post['_embedded']['author'][0]['name'] ?? 'Unknown Author');
                ?>
                <h1 class="text-4xl font-bold text-white mb-4" id="post-title"><?= $title ?></h1>
                <p class="text-slate-400 text-sm mb-6" id="post-meta">By <?= $author ?> on <?= $date ?></p>
                <div class="prose prose-invert max-w-none text-slate-300" id="post-content">
                    <?= $content ?>
                </div>
                <?php
            }
            ?>
        </article>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
