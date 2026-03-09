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
$message_type = '';

function handle_image_upload($file_input_name, $existing_path = '') {
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $image_name = basename($_FILES[$file_input_name]["name"]);
        $image_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
        $unique_name = uniqid('gallery_') . '.' . $image_extension;
        $target_file = $target_dir . $unique_name;

        $check = getimagesize($_FILES[$file_input_name]["tmp_name"]);
        if ($check === false) {
            return ['error' => "File is not an image."];
        }
        if (!in_array($image_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            return ['error' => "Sorry, only JPG, JPEG, PNG & GIF files are allowed."];
        }

        if (move_uploaded_file($_FILES[$file_input_name]["tmp_name"], $target_file)) {
            // Delete old image if a new one is uploaded
            if (!empty($existing_path) && file_exists("../" . $existing_path)) {
                unlink("../" . $existing_path);
            }
            return ['path' => "uploads/" . $unique_name];
        } else {
            return ['error' => "Sorry, there was an error uploading your file."];
        }
    }
    return ['path' => $existing_path]; // Return existing path if no new file is uploaded
}

// Handle Add/Edit Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $youtube_url = trim($_POST['youtube_url']);

    if ($_POST['action'] === 'add_item') {
        $image_paths = [];
        $has_error = false;
        for ($i = 1; $i <= 5; $i++) {
            $upload_result = handle_image_upload('image_' . $i);
            if (isset($upload_result['error'])) {
                $message = $upload_result['error'];
                $message_type = "error";
                $has_error = true;
                break;
            }
            $image_paths[] = $upload_result['path'];
        }

        if (!$has_error) {
            $stmt = $conn->prepare("INSERT INTO gallery_items (title, description, youtube_url, image_path_1, image_path_2, image_path_3, image_path_4, image_path_5) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $title, $description, $youtube_url, ...$image_paths);
            if ($stmt->execute()) {
                $message = "Gallery item added successfully.";
                $message_type = "success";
            } else {
                $message = "Error adding gallery item: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    } elseif ($_POST['action'] === 'edit_item') {
        $item_id = (int)$_POST['item_id'];
        $image_paths = [];
        $has_error = false;

        for ($i = 1; $i <= 5; $i++) {
            $existing_path = $_POST['current_image_path_' . $i] ?? '';
            $upload_result = handle_image_upload('image_' . $i, $existing_path);
            if (isset($upload_result['error'])) {
                $message = $upload_result['error'];
                $message_type = "error";
                $has_error = true;
                break;
            }
            $image_paths[] = $upload_result['path'];
        }

        if (!$has_error) {
            $stmt = $conn->prepare("UPDATE gallery_items SET title = ?, description = ?, youtube_url = ?, image_path_1 = ?, image_path_2 = ?, image_path_3 = ?, image_path_4 = ?, image_path_5 = ? WHERE id = ?");
            $image_paths[] = $item_id;
            $stmt->bind_param("ssssssssi", $title, $description, $youtube_url, ...$image_paths);
            if ($stmt->execute()) {
                $message = "Gallery item updated successfully.";
                $message_type = "success";
            } else {
                $message = "Error updating gallery item: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    }
}


// Handle Delete Action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $item_id = (int)$_GET['id'];

    $stmt = $conn->prepare("SELECT * FROM gallery_items WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item_to_delete = $result->fetch_assoc();
    $stmt->close();

    if ($item_to_delete) {
        $stmt = $conn->prepare("DELETE FROM gallery_items WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        if ($stmt->execute()) {
            for ($i = 1; $i <= 5; $i++) {
                $img_path = $item_to_delete['image_path_' . $i];
                if (!empty($img_path) && file_exists("../" . $img_path)) {
                    unlink("../" . $img_path);
                }
            }
            $message = "Gallery item deleted successfully.";
            $message_type = "success";
        } else {
            $message = "Error deleting gallery item: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    } else {
        $message = "Gallery item not found.";
        $message_type = "error";
    }
}


// Fetch all gallery items for display
$gallery_items = [];
$result = $conn->query("SELECT * FROM gallery_items ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    $gallery_items[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Gallery</title>
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
            <h1 class="text-3xl font-bold text-white mb-6">Manage Gallery</h1>

            <?php if ($message): ?>
                <div class="mb-4 p-3 rounded <?php echo $message_type === 'success' ? 'bg-green-500' : 'bg-red-500'; ?> text-white">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Add New Gallery Item Form -->
            <div class="bg-slate-800 p-6 rounded-lg shadow-lg mb-8">
                <h2 class="text-xl font-bold text-white mb-4">Add New Gallery Item</h2>
                <form action="gallery.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="add_item">
                    
                    <div>
                        <label for="title" class="block text-sm font-medium text-slate-300">Title</label>
                        <input type="text" name="title" id="title" class="mt-1 block w-full bg-slate-700 border border-slate-600 rounded-md shadow-sm text-white focus:ring-amber-500 focus:border-amber-500 sm:text-sm p-2" required>
                    </div>
                    
                    <div>
                        <label for="description" class="block text-sm font-medium text-slate-300">Description</label>
                        <textarea name="description" id="description" rows="3" class="mt-1 block w-full bg-slate-700 border border-slate-600 rounded-md shadow-sm text-white focus:ring-amber-500 focus:border-amber-500 sm:text-sm p-2"></textarea>
                    </div>
                    
                    <div>
                        <label for="youtube_url" class="block text-sm font-medium text-slate-300">YouTube Video URL</label>
                        <input type="url" name="youtube_url" id="youtube_url" class="mt-1 block w-full bg-slate-700 border border-slate-600 rounded-md shadow-sm text-white focus:ring-amber-500 focus:border-amber-500 sm:text-sm p-2">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <div>
                            <label for="image_<?php echo $i; ?>" class="block text-sm font-medium text-slate-300">Image <?php echo $i; ?></label>
                            <input type="file" name="image_<?php echo $i; ?>" id="image_<?php echo $i; ?>" class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                        </div>
                        <?php endfor; ?>
                    </div>
                    
                    <button type="submit" class="px-5 py-2 bg-amber-500 hover:bg-amber-400 text-slate-900 font-semibold rounded-md transition duration-200">Add Item</button>
                </form>
            </div>

            <!-- Existing Gallery Items -->
            <div class="bg-slate-800 p-6 rounded-lg shadow-lg">
                <h2 class="text-xl font-bold text-white mb-4">Existing Gallery Items</h2>
                <?php if (empty($gallery_items)): ?>
                    <p class="text-slate-400">No gallery items found. Add some above!</p>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($gallery_items as $item): ?>
                            <div class="bg-slate-700 rounded-lg overflow-hidden shadow-md p-4 flex gap-4">
                                <div class="flex-shrink-0">
                                    <img src="../<?php echo htmlspecialchars($item['image_path_1'] ?? 'https://placehold.co/150x150/1e293b/ffffff?text=No+Image'); ?>" alt="Primary Image" class="w-32 h-32 object-cover rounded-md">
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-white font-semibold mb-2"><?php echo htmlspecialchars($item['title']); ?></h3>
                                    <p class="text-slate-300 text-sm mb-2 clamp-2"><?php echo htmlspecialchars($item['description']); ?></p>
                                    <?php if(!empty($item['youtube_url'])): ?>
                                        <a href="<?php echo htmlspecialchars($item['youtube_url']); ?>" target="_blank" class="text-blue-400 text-xs hover:underline">YouTube Link</a>
                                    <?php endif; ?>
                                    <div class="flex space-x-2 mt-4">
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($item)); ?>)" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded transition">Edit</button>
                                        <a href="gallery.php?action=delete&id=<?php echo $item['id']; ?>" onclick="return confirm('Are you sure?');" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-sm rounded text-center transition">Delete</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Edit Modal -->
            <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                <div class="bg-slate-800 p-8 rounded-lg shadow-lg w-full max-w-2xl mx-auto max-h-screen overflow-y-auto">
                    <h2 class="text-xl font-bold text-white mb-4">Edit Gallery Item</h2>
                    <form action="gallery.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="action" value="edit_item">
                        <input type="hidden" name="item_id" id="edit-item-id">
                        
                        <div>
                            <label for="edit-title" class="block text-sm font-medium text-slate-300">Title</label>
                            <input type="text" name="title" id="edit-title" class="mt-1 block w-full bg-slate-700 border border-slate-600 rounded-md shadow-sm text-white focus:ring-amber-500 focus:border-amber-500 sm:text-sm p-2" required>
                        </div>
                        
                        <div>
                            <label for="edit-description" class="block text-sm font-medium text-slate-300">Description</label>
                            <textarea name="description" id="edit-description" rows="3" class="mt-1 block w-full bg-slate-700 border border-slate-600 rounded-md shadow-sm text-white focus:ring-amber-500 focus:border-amber-500 sm:text-sm p-2"></textarea>
                        </div>
                        <div>
                            <label for="edit-youtube_url" class="block text-sm font-medium text-slate-300">YouTube Video URL</label>
                            <input type="url" name="youtube_url" id="edit-youtube_url" class="mt-1 block w-full bg-slate-700 border border-slate-600 rounded-md shadow-sm text-white focus:ring-amber-500 focus:border-amber-500 sm:text-sm p-2">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <div>
                                <label for="edit-image_<?php echo $i; ?>" class="block text-sm font-medium text-slate-300">Image <?php echo $i; ?> (Optional)</label>
                                <input type="file" name="image_<?php echo $i; ?>" id="edit-image_<?php echo $i; ?>" class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                                <input type="hidden" name="current_image_path_<?php echo $i; ?>" id="edit-current-image-path_<?php echo $i; ?>">
                                <img id="edit-image-preview_<?php echo $i; ?>" src="" alt="Current Image <?php echo $i; ?>" class="mt-2 h-20 w-full object-cover rounded hidden">
                            </div>
                            <?php endfor; ?>
                        </div>

                        <div class="flex justify-end space-x-2">
                            <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-slate-600 hover:bg-slate-500 text-white font-semibold rounded-md transition duration-200">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-900 font-semibold rounded-md transition duration-200">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

        </main>
    </div>

    <script>
        function openEditModal(item) {
            document.getElementById('edit-item-id').value = item.id;
            document.getElementById('edit-title').value = item.title;
            document.getElementById('edit-description').value = item.description;
            document.getElementById('edit-youtube_url').value = item.youtube_url;

            for (let i = 1; i <= 5; i++) {
                const imgPath = item['image_path_' + i];
                const preview = document.getElementById('edit-image-preview_' + i);
                const currentPathInput = document.getElementById('edit-current-image-path_' + i);

                currentPathInput.value = imgPath || '';
                
                if (imgPath) {
                    preview.src = '../' + imgPath;
                    preview.classList.remove('hidden');
                } else {
                    preview.classList.add('hidden');
                }
            }
            
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
    </script>

</body>
</html>


