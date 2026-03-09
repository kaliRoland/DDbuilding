<?php
session_start();
require_once 'config/database.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: products.php');
    exit;
}
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header('Location: products.php');
    exit;
}

// Fetch reviews summary and approved reviews if table exists
$reviews = [];
$review_summary = ['avg' => 0, 'count' => 0];
$reviews_table_exists = false;
$reviews_check = $conn->query("SHOW TABLES LIKE 'product_reviews'");
if ($reviews_check && $reviews_check->num_rows > 0) {
    $reviews_table_exists = true;
    $summary_stmt = $conn->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS review_count FROM product_reviews WHERE product_id = ? AND status = 'approved'");
    if ($summary_stmt) {
        $summary_stmt->bind_param('i', $id);
        $summary_stmt->execute();
        $summary_result = $summary_stmt->get_result()->fetch_assoc();
        if ($summary_result) {
            $review_summary['avg'] = (float)($summary_result['avg_rating'] ?? 0);
            $review_summary['count'] = (int)($summary_result['review_count'] ?? 0);
        }
        $summary_stmt->close();
    }

    $reviews_stmt = $conn->prepare("SELECT name, rating, review_text, created_at FROM product_reviews WHERE product_id = ? AND status = 'approved' ORDER BY created_at DESC");
    if ($reviews_stmt) {
        $reviews_stmt->bind_param('i', $id);
        $reviews_stmt->execute();
        $reviews_result = $reviews_stmt->get_result();
        while ($row = $reviews_result->fetch_assoc()) {
            $reviews[] = $row;
        }
        $reviews_stmt->close();
    }
}

// Fetch related products (use category_id when available, include subcategories)
$related_products = [];
if (!empty($product['category_id'])) {
    // Determine parent/main id for grouping
    $cat_stmt = $conn->prepare("SELECT parent_id FROM categories WHERE id = ?");
    if ($cat_stmt) {
        $cat_stmt->bind_param('i', $product['category_id']);
        $cat_stmt->execute();
        $cat_res = $cat_stmt->get_result()->fetch_assoc();
        $cat_stmt->close();
        $parent_of_cat = $cat_res ? $cat_res['parent_id'] : null;
        $group_parent = is_null($parent_of_cat) ? (int)$product['category_id'] : (int)$parent_of_cat;

        $related_stmt = $conn->prepare("SELECT p.* FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE (p.category_id = ? OR c.parent_id = ?) AND p.id != ? ORDER BY RAND() LIMIT 4");
        if ($related_stmt) {
            $related_stmt->bind_param('iii', $product['category_id'], $group_parent, $id);
            $related_stmt->execute();
            $related_result = $related_stmt->get_result();
            while($row = $related_result->fetch_assoc()) {
                $related_products[] = $row;
            }
        }
    }
} else {
    // Fallback to legacy text-based related products
    $related_stmt = $conn->prepare("SELECT * FROM products WHERE category = ? AND id != ? ORDER BY RAND() LIMIT 4");
    if ($related_stmt) {
        $related_stmt->bind_param("si", $product['category'], $id);
        $related_stmt->execute();
        $related_result = $related_stmt->get_result();
        while($row = $related_result->fetch_assoc()) {
            $related_products[] = $row;
        }
    }
}

$avg_rating = (float)$review_summary['avg'];
$review_count = (int)$review_summary['count'];
$rounded_rating = (int)round($avg_rating);

function absoluteUrl(string $path): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    return $scheme . '://' . $host . '/' . ltrim($path, '/');
}

$product_url = absoluteUrl('product.php?id=' . $id);
$image_urls = [];
foreach (['image_main', 'image_1', 'image_2', 'image_3'] as $image_key) {
    if (!empty($product[$image_key])) {
        $image_urls[] = absoluteUrl((string)$product[$image_key]);
    }
}

$desc_text = trim((string)$product['description']);
$desc_text = preg_replace('/\s+/', ' ', $desc_text ?? '') ?: '';
$meta_description = $desc_text !== '' ? $desc_text : ('Buy ' . (string)$product['name'] . ' at DDbuilding Tech.');
if (strlen($meta_description) > 155) {
    $meta_description = substr($meta_description, 0, 152) . '...';
}

$page_title = trim((string)$product['name']) . ' | DDbuilding Tech';
$canonical_url = $product_url;

$schema_product = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => (string)$product['name'],
    'description' => $desc_text,
    'image' => $image_urls,
    'category' => (string)($product['category'] ?? ''),
    'sku' => (string)$product['id'],
    'url' => $product_url,
    'offers' => [
        '@type' => 'Offer',
        'priceCurrency' => 'NGN',
        'price' => number_format((float)$product['price'], 2, '.', ''),
        'availability' => 'https://schema.org/InStock',
        'url' => $product_url,
        'itemCondition' => 'https://schema.org/NewCondition'
    ]
];
if (!empty($product['brand'])) {
    $schema_product['brand'] = ['@type' => 'Brand', 'name' => (string)$product['brand']];
}
if ($review_count > 0 && $avg_rating > 0) {
    $schema_product['aggregateRating'] = [
        '@type' => 'AggregateRating',
        'ratingValue' => number_format($avg_rating, 1, '.', ''),
        'reviewCount' => $review_count
    ];
}
$structured_data = json_encode($schema_product, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

include 'includes/header.php';

?>

<main class="py-32 bg-slate-900">
    <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 gap-16">
            <div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                    <div class="flex flex-col">
                        <img id="main-product-image" src="<?= htmlspecialchars($product['image_main']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="rounded-lg shadow-lg w-full">
                        <!-- Thumbnails -->
                        <div class="grid grid-cols-3 gap-4 mt-4">
                            <?php if (!empty($product['image_1'])): ?>
                                <div>
                                    <img src="<?= htmlspecialchars($product['image_1']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="rounded-lg shadow-md w-full h-24 object-cover cursor-pointer hover:opacity-75 transition" onclick="changeMainImage('<?= htmlspecialchars($product['image_1']) ?>')">
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($product['image_2'])): ?>
                                <div>
                                    <img src="<?= htmlspecialchars($product['image_2']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="rounded-lg shadow-md w-full h-24 object-cover cursor-pointer hover:opacity-75 transition" onclick="changeMainImage('<?= htmlspecialchars($product['image_2']) ?>')">
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($product['image_3'])): ?>
                                <div>
                                    <img src="<?= htmlspecialchars($product['image_3']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="rounded-lg shadow-md w-full h-24 object-cover cursor-pointer hover:opacity-75 transition" onclick="changeMainImage('<?= htmlspecialchars($product['image_3']) ?>')">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-10">
                            <h2 class="text-2xl font-bold text-white mb-4">Customer Reviews</h2>
                            <?php if ($review_count > 0): ?>
                                <div class="space-y-4">
                                    <?php foreach ($reviews as $review): ?>
                                        <div class="bg-slate-800 p-4 rounded-lg border border-slate-700">
                                            <div class="flex items-center justify-between">
                                                <div class="font-semibold text-white"><?= htmlspecialchars($review['name']) ?></div>
                                                <div class="text-xs text-slate-400"><?= date('M d, Y', strtotime($review['created_at'])) ?></div>
                                            </div>
                                            <div class="text-amber-400 text-sm tracking-wide mt-1">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <span><?php echo $i <= (int)$review['rating'] ? '&#9733;' : '&#9734;'; ?></span>
                                                <?php endfor; ?>
                                            </div>
                                            <p class="text-slate-300 mt-2"><?= nl2br(htmlspecialchars($review['review_text'])) ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-slate-400">No reviews yet. Be the first to review this product.</p>
                            <?php endif; ?>

                            <div class="mt-8 bg-slate-800 p-6 rounded-lg border border-slate-700">
                                <h3 class="text-xl font-bold text-white mb-4">Write a Review</h3>
                                <p class="text-slate-400 text-sm mb-4">Reviews are moderated and will appear after approval.</p>
                                <form id="review-form" class="space-y-4">
                                    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                    <div>
                                        <label class="block text-slate-400 mb-2">Your Name</label>
                                        <input type="text" name="name" required class="w-full bg-slate-900 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-slate-400 mb-2">Rating</label>
                                        <select name="rating" required class="w-full bg-slate-900 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                                            <option value="">Select rating</option>
                                            <option value="5">5 - Excellent</option>
                                            <option value="4">4 - Good</option>
                                            <option value="3">3 - Average</option>
                                            <option value="2">2 - Poor</option>
                                            <option value="1">1 - Bad</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-slate-400 mb-2">Review</label>
                                        <textarea name="review_text" rows="4" required class="w-full bg-slate-900 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent"></textarea>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-slate-900 font-bold py-2 px-6 rounded transition">Submit Review</button>
                                        <span id="review-message" class="text-sm text-slate-400"></span>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div>
                        <span class="text-amber-400 text-sm font-bold uppercase tracking-wider"><?= htmlspecialchars($product['category']) ?></span>
                        <h1 class="text-4xl lg:text-5xl font-bold text-white mt-2 mb-1"><?= htmlspecialchars($product['name']) ?></h1>
                        <div class="flex items-center gap-2 mb-3">
                            <div class="text-amber-400 text-lg tracking-wide">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span><?php echo $i <= $rounded_rating ? '&#9733;' : '&#9734;'; ?></span>
                                <?php endfor; ?>
                            </div>
                            <div class="text-slate-400 text-sm">
                                <?php if ($review_count > 0): ?>
                                    <?php echo number_format($avg_rating, 1); ?> (<?php echo $review_count; ?> reviews)
                                <?php else: ?>
                                    No reviews yet
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($product['brand'])): ?>
                            <div class="text-slate-400 mb-2">Brand: <?= htmlspecialchars($product['brand']) ?></div>
                        <?php endif; ?>
                        <p class="text-4xl font-bold text-white mb-4">NGN<?= number_format(htmlspecialchars($product['price'])) ?></p>
                        <div class="flex gap-3 mb-6">
                            <a id="share-whatsapp" href="#" target="_blank" class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2 px-4 rounded transition">WhatsApp</a>
                            <a id="share-facebook" href="#" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition">Facebook</a>
                            <a id="share-twitter" href="#" target="_blank" class="bg-sky-500 hover:bg-sky-600 text-white font-bold py-2 px-4 rounded transition">Twitter</a>
                            <a id="share-linkedin" href="#" target="_blank" class="bg-slate-700 hover:bg-slate-600 text-white font-bold py-2 px-4 rounded transition">LinkedIn</a>
                        </div>
                        <button 
                            onclick="addToCart(<?= $product['id'] ?>)"
                            class="w-full lg:w-auto bg-amber-500 hover:bg-amber-400 text-slate-900 font-bold py-4 px-8 rounded hover:scale-105 transition transform duration-200 flex items-center justify-center gap-2"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-bag"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            Add to Cart
                        </button>
                        <div class="mt-8">
                            <h2 class="text-2xl font-bold text-white mb-4">Description</h2>
                            <div class="text-slate-400 text-lg space-y-4">
                                <?php 
                                    $descriptions = explode("\n", $product['description']);
                                    foreach ($descriptions as $desc) {
                                        if (trim($desc)) {
                                            echo '<p>' . nl2br(htmlspecialchars(trim($desc))) . '</p>';
                                        }
                                    }
                                ?>
                            </div>

                            <?php
                            $specifications = json_decode($product['specifications'] ?? '[]', true);
                            if (!empty($specifications)):
                            ?>
                            <h2 class="text-2xl font-bold text-white mb-4 mt-6">Specifications</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                                <?php foreach ($specifications as $spec): ?>
                                <div class="bg-slate-800 p-4 rounded-lg">
                                    <div class="text-amber-400 font-semibold text-sm uppercase tracking-wider mb-1"><?php echo htmlspecialchars($spec['title']); ?></div>
                                    <div class="text-slate-300"><?php echo htmlspecialchars($spec['detail']); ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-white mb-4">Related Products</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <?php foreach($related_products as $related_product): ?>
                        <div class="bg-slate-800 rounded-lg shadow-lg overflow-hidden">
                            <a href="product.php?id=<?= $related_product['id'] ?>">
                                <img src="<?= htmlspecialchars($related_product['image_main']) ?>" alt="<?= htmlspecialchars($related_product['name']) ?>" class="w-full h-32 object-cover">
                            </a>
                            <div class="p-4">
                                <h3 class="text-base font-bold text-white mb-2"><?= htmlspecialchars($related_product['name']) ?></h3>
                                <p class="text-amber-400 font-bold">NGN<?= number_format(htmlspecialchars($related_product['price'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
<script>
function changeMainImage(newImageSrc) {
    document.getElementById('main-product-image').src = newImageSrc;
}

function openImageModal(imageSrc) {
    document.getElementById('modal-image').src = imageSrc;
    document.getElementById('image-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    document.getElementById('image-modal').classList.add('hidden');
    document.body.style.overflow = '';
}

// Add click listener to main image to open modal as well
document.getElementById('main-product-image').addEventListener('click', () => {
    openImageModal(document.getElementById('main-product-image').src);
});

// Setup share links
document.addEventListener('DOMContentLoaded', function() {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent(document.title);
    const wa = document.getElementById('share-whatsapp');
    const fb = document.getElementById('share-facebook');
    const tw = document.getElementById('share-twitter');
    const li = document.getElementById('share-linkedin');
    if (wa) wa.href = `https://api.whatsapp.com/send?text=${title}%20${url}`;
    if (fb) fb.href = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
    if (tw) tw.href = `https://twitter.com/intent/tweet?url=${url}&text=${title}`;
    if (li) li.href = `https://www.linkedin.com/shareArticle?mini=true&url=${url}&title=${title}`;

    const reviewForm = document.getElementById('review-form');
    const reviewMessage = document.getElementById('review-message');
    if (reviewForm) {
        reviewForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (reviewMessage) {
                reviewMessage.textContent = 'Submitting...';
                reviewMessage.className = 'text-sm text-slate-400';
            }
            try {
                const formData = new FormData(reviewForm);
                const response = await fetch('api/reviews.php?action=submit', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.status === 'success') {
                    reviewForm.reset();
                    if (reviewMessage) {
                        reviewMessage.textContent = data.message || 'Review submitted and pending approval.';
                        reviewMessage.className = 'text-sm text-emerald-400';
                    }
                } else {
                    if (reviewMessage) {
                        reviewMessage.textContent = data.message || 'Failed to submit review.';
                        reviewMessage.className = 'text-sm text-red-400';
                    }
                }
            } catch (err) {
                if (reviewMessage) {
                    reviewMessage.textContent = 'Failed to submit review. Please try again.';
                    reviewMessage.className = 'text-sm text-red-400';
                }
            }
        });
    }

    if (typeof fbq === 'function') {
        fbq('track', 'ViewContent', {
            content_ids: [<?= json_encode((string)$product['id']) ?>],
            content_type: 'product',
            content_name: <?= json_encode($product['name']) ?>,
            value: <?= json_encode((float)$product['price']) ?>,
            currency: 'NGN'
        });
    }
});
</script>
    </div>
</main>

<!-- Image Modal -->
<div id="image-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
    <span class="absolute top-4 right-6 text-white text-4xl cursor-pointer" onclick="closeImageModal()">&times;</span>
    <img id="modal-image" src="" alt="Enlarged product image" class="max-w-4/5 max-h-4/5">
</div>

<?php include 'includes/footer.php'; ?>
