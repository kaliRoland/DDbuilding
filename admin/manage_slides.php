<?php
require_once __DIR__ . '/includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// If the admin is not logged in, redirect to login page
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_slides'])) {
        // Create uploads/slides directory if it doesn't exist
        $upload_dir = '../uploads/slides/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Check if directory is writable
        if (!is_writable($upload_dir)) {
            $error = "Upload directory is not writable. Please check permissions.";
        } else {

        // Update existing slides
        for ($i = 1; $i <= 4; $i++) {
            $title = $_POST["title_$i"] ?? '';
            $subtitle = $_POST["subtitle_$i"] ?? '';
            $current_image_path = $_POST["current_image_$i"] ?? '';

            $image_path = $current_image_path; // Default to current image

            // Handle file upload
            if (isset($_FILES["image_$i"]) && $_FILES["image_$i"]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES["image_$i"];
                $file_name = basename($file['name']);
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                // Validate file type
                $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($file_ext, $allowed_exts)) {
                    // Generate unique filename
                    $new_filename = 'slide' . $i . '_' . time() . '.' . $file_ext;
                    $target_path = $upload_dir . $new_filename;

                    if (move_uploaded_file($file['tmp_name'], $target_path)) {
                        $image_path = 'uploads/slides/' . $new_filename;
                    } else {
                        $error = "Failed to upload image for slide $i";
                    }
                } else {
                    $error = "Invalid file type for slide $i. Only JPG, PNG, GIF, and WebP are allowed.";
                }
            }

            if (!empty($title)) {
                $stmt = $conn->prepare("UPDATE hero_slides SET title = ?, subtitle = ?, image_path = ? WHERE id = ?");
                $stmt->bind_param("sssi", $title, $subtitle, $image_path, $i);
                $stmt->execute();
                $stmt->close();
            }
        }
        }
        if (empty($error)) {
            $message = "Slides updated successfully!";
        }
        log_activity($conn, $_SESSION['admin_id'], 'update_hero_slides');
    }
}

// Fetch current slides
$slides = [];
$result = $conn->query("SELECT * FROM hero_slides ORDER BY id LIMIT 4");
while ($row = $result->fetch_assoc()) {
    $slides[] = $row;
}

// Fill with empty slides if less than 4
while (count($slides) < 4) {
    $slides[] = ['id' => count($slides) + 1, 'title' => '', 'image_path' => ''];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Hero Slides</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/brand.css">
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-5NVJVRF7');</script>
    <!-- End Google Tag Manager -->
</head>
<body class="bg-slate-900 text-slate-100">
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5NVJVRF7"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->

    <div class="flex min-h-screen">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 p-10">
            <h1 class="text-3xl font-bold text-white mb-6">Manage Hero Slides</h1>

            <?php if ($message): ?>
                <div class="bg-green-600 text-white p-4 rounded mb-6"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-600 text-white p-4 rounded mb-6"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="bg-slate-800 p-6 rounded-lg">
                <p class="text-blue-300 mb-4">Upload new images or keep existing ones. Images will be stored in the uploads/slides/ folder.</p>

                <?php for ($i = 0; $i < 4; $i++): ?>
                    <div class="mb-6 p-4 border border-slate-700 rounded">
                        <h3 class="text-lg font-semibold text-white mb-3">Slide <?php echo $i + 1; ?></h3>
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-blue-200 mb-2">Title</label>
                                <input type="text" name="title_<?php echo $i + 1; ?>" value="<?php echo htmlspecialchars($slides[$i]['title'] ?? ''); ?>"
                                       class="w-full bg-slate-700 border border-slate-600 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-orange-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-blue-200 mb-2">Subtitle/Description</label>
                                <textarea name="subtitle_<?php echo $i + 1; ?>" rows="3" 
                                          class="w-full bg-slate-700 border border-slate-600 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-orange-500"
                                          placeholder="Enter subtitle or description text..."><?php echo htmlspecialchars($slides[$i]['subtitle'] ?? ''); ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-blue-200 mb-2">Upload New Image</label>
                                <input type="file" name="image_<?php echo $i + 1; ?>" accept="image/*"
                                       class="w-full bg-slate-700 border border-slate-600 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-orange-500 file:bg-orange-500 file:text-slate-900 file:border-none file:rounded file:px-2 file:py-1 file:mr-2">
                                <input type="hidden" name="current_image_<?php echo $i + 1; ?>" value="<?php echo htmlspecialchars($slides[$i]['image_path'] ?? ''); ?>">
                                <?php if (!empty($slides[$i]['image_path'])): ?>
                                    <div class="mt-2">
                                        <p class="text-sm text-blue-300 mb-1">Current Image:</p>
                                        <img src="../<?php echo htmlspecialchars($slides[$i]['image_path']); ?>" alt="Current slide" class="w-32 h-20 object-cover rounded border border-slate-600">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>

                <button type="submit" name="update_slides" class="bg-orange-500 hover:bg-orange-400 text-white font-bold py-2 px-4 rounded">
                    Update Slides
                </button>
            </form>
        </main>
    </div>
</body>
</html>


