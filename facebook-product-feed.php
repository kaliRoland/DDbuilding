<?php
/**
 * Facebook Meta Product Catalogue Feed
 * Catalogue ID: 2636724799992876613
 * 
 * This file generates an XML feed of all products for Facebook Commerce Manager
 * URL: https://yourdomain.com/facebook-product-feed.php
 */

require_once 'config/database.php';

// Set the content type to XML
header('Content-Type: application/xml; charset=utf-8');

// Your website base URL - UPDATE THIS TO YOUR LIVE DOMAIN
$base_url = 'https://ddbuildingtech.com';

// Fetch all products from the database
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.price > 0";
$result = $conn->query($query);

// Start XML output
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:g="http://base.google.com/ns/1.0">
    <title>DD Building Technology Solutions - Product Catalogue</title>
    <link rel="self" href="<?= htmlspecialchars($base_url) ?>/facebook-product-feed.php"/>
    <updated><?= date('c') ?></updated>
    
<?php while ($product = $result->fetch_assoc()): 
    // Build the product URL
    $product_url = $base_url . '/product.php?id=' . $product['id'];
    
    // Build the image URL (ensure it's absolute)
    $image_url = $product['image_main'];
    if (!preg_match('/^https?:\/\//', $image_url)) {
        $image_url = $base_url . '/' . ltrim($image_url, '/');
    }
    
    // Clean description - remove HTML tags and limit length
    $description = strip_tags($product['description'] ?? '');
    $description = preg_replace('/\s+/', ' ', $description); // Remove extra whitespace
    $description = mb_substr($description, 0, 5000); // Facebook limit
    
    // Determine availability
    $availability = 'in stock'; // You can modify this based on your inventory system
    
    // Get category name
    $category = !empty($product['category_name']) ? $product['category_name'] : ($product['category'] ?? 'Electronics');
    
    // Get brand
    $brand = !empty($product['brand']) ? $product['brand'] : 'DD Building Tech';
?>
    <entry>
        <g:id><?= htmlspecialchars($product['id']) ?></g:id>
        <g:title><?= htmlspecialchars($product['name']) ?></g:title>
        <g:description><?= htmlspecialchars($description) ?></g:description>
        <g:link><?= htmlspecialchars($product_url) ?></g:link>
        <g:image_link><?= htmlspecialchars($image_url) ?></g:image_link>
<?php 
    // Add additional images if available
    if (!empty($product['image_1'])): 
        $img1 = preg_match('/^https?:\/\//', $product['image_1']) ? $product['image_1'] : $base_url . '/' . ltrim($product['image_1'], '/');
?>
        <g:additional_image_link><?= htmlspecialchars($img1) ?></g:additional_image_link>
<?php endif; 
    if (!empty($product['image_2'])): 
        $img2 = preg_match('/^https?:\/\//', $product['image_2']) ? $product['image_2'] : $base_url . '/' . ltrim($product['image_2'], '/');
?>
        <g:additional_image_link><?= htmlspecialchars($img2) ?></g:additional_image_link>
<?php endif; 
    if (!empty($product['image_3'])): 
        $img3 = preg_match('/^https?:\/\//', $product['image_3']) ? $product['image_3'] : $base_url . '/' . ltrim($product['image_3'], '/');
?>
        <g:additional_image_link><?= htmlspecialchars($img3) ?></g:additional_image_link>
<?php endif; ?>
        <g:availability><?= $availability ?></g:availability>
        <g:price><?= number_format($product['price'], 2, '.', '') ?> NGN</g:price>
        <g:brand><?= htmlspecialchars($brand) ?></g:brand>
        <g:condition>new</g:condition>
        <g:product_type><?= htmlspecialchars($category) ?></g:product_type>
        <g:google_product_category>Electronics</g:google_product_category>
    </entry>
<?php endwhile; ?>
</feed>
<?php
$conn->close();
?>
