<?php
session_start();
require_once 'config/database.php';
include 'includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: products.php');
    exit;
}
$stmt = $conn->prepare( "SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header('Location: products.php');
    exit;
}




// Fetch related products
$related_stmt = $conn->prepare("SELECT * FROM products WHERE category = ? AND id != ? ORDER BY RAND() LIMIT 4");
$related_stmt->bind_param("si", $product['category'], $id);
$related_stmt->execute();
$related_result = $related_stmt->get_result();
$related_products = [];
while($row = $related_result->fetch_assoc()) {
    $related_products[] = $row;
}

?>

<main class="py-32 bg-slate-900">
    <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 gap-16">
            <div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                    <div>
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
                    </div>
                    <div>
                        <span class="text-amber-400 text-sm font-bold uppercase tracking-wider"><?= htmlspecialchars($product['category']) ?></span>
                        <h1 class="text-4xl lg:text-5xl font-bold text-white mt-2 mb-4"><?= htmlspecialchars($product['name']) ?></h1>
                        <p class="text-4xl font-bold text-white mb-8">NGN<?= htmlspecialchars($product['price']) ?></p>
                        <button 
                            onclick="addToCart(<?= $product['id'] ?>)"
                            class="w-full lg:w-auto bg-amber-500 hover:bg-amber-400 text-slate-900 font-bold py-4 px-8 rounded hover:scale-105 transition transform duration-200 flex items-center justify-center gap-2"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-bag"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            Add to Cart
                        </button>
                        <div class="mt-8">
                            <h2 class="text-2xl font-bold text-white mb-4">Description</h2>
                            <p class="text-slate-400 text-lg"><?= htmlspecialchars($product['description']) ?></p>
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
                                <p class="text-amber-400 font-bold">NGN<?= htmlspecialchars($related_product['price']) ?></p>
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
</script>
    </div>
</main>

<!-- Image Modal -->
<div id="image-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
    <span class="absolute top-4 right-6 text-white text-4xl cursor-pointer" onclick="closeImageModal()">&times;</span>
    <img id="modal-image" src="" alt="Enlarged product image" class="max-w-4/5 max-h-4/5">
</div>

<?php include 'includes/footer.php'; ?>
