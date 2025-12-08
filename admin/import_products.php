<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// If the admin is not logged in, redirect to login page
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// If the admin is not a super user, redirect to dashboard (assuming only super admins can import)
// if ($_SESSION['admin_role'] !== 'super') {
//     header('Location: index.php');
//     exit;
// }

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];

    // Check for file upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = "File upload error: " . $file['error'];
        $message_type = 'error';
    } elseif ($file['type'] !== 'text/csv' && $file['type'] !== 'application/vnd.ms-excel') {
        $message = "Invalid file type. Please upload a CSV file.";
        $message_type = 'error';
    } else {
        $handle = fopen($file['tmp_name'], "r");
        if ($handle === FALSE) {
            $message = "Could not open CSV file.";
            $message_type = 'error';
        } else {
            // Skip the header row
            fgetcsv($handle);

            $imported_count = 0;
            $errors_found = 0;

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($data) != 8) { // Assuming 8 columns: name, category, price, image_main, image_1, image_2, image_3, description
                    $errors_found++;
                    continue;
                }

                $name = $data[0];
                $category = $data[1];
                $price = (float)$data[2];
                $image_main = $data[3];
                $image_1 = $data[4];
                $image_2 = $data[5];
                $image_3 = $data[6];
                $description = $data[7];

                $stmt = $conn->prepare("INSERT INTO products (name, category, price, image_main, image_1, image_2, image_3, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if (!$stmt) {
                    $errors_found++;
                    log_activity($conn, $_SESSION['admin_id'], 'csv_import_error: Prepare failed - ' . $conn->error);
                    continue;
                }
                $stmt->bind_param("ssdsssss", $name, $category, $price, $image_main, $image_1, $image_2, $image_3, $description);

                if ($stmt->execute()) {
                    $imported_count++;
                } else {
                    $errors_found++;
                    log_activity($conn, $_SESSION['admin_id'], 'csv_import_error: Execute failed for product ' . $name . ' - ' . $stmt->error);
                }
                $stmt->close();
            }
            fclose($handle);

            if ($imported_count > 0) {
                log_activity($conn, $_SESSION['admin_id'], 'csv_import: Imported ' . $imported_count . ' products');
                $message = "Successfully imported $imported_count products.";
                $message_type = 'success';
            }
            if ($errors_found > 0) {
                $message .= " $errors_found rows had errors.";
                $message_type = $message_type === 'success' ? 'warning' : 'error';
            }
            if ($imported_count == 0 && $errors_found == 0) {
                $message = "No products found in the CSV file or file was empty.";
                $message_type = 'warning';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Products</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-slate-100">

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-800 p-6">
            <img src="../uploads/logo/logo.png" alt="DDbuildingTech Logo" class="h-8 mb-8">
            <nav>
                <a href="index.php" class="block py-2 px-4 rounded hover:bg-slate-700">Dashboard</a>
                <a href="products.php" class="block py-2 px-4 rounded bg-amber-500 text-slate-900">Products</a>
                <?php if ($_SESSION['admin_role'] === 'super'): ?>
                    <a href="manage_users.php" class="block py-2 px-4 rounded hover:bg-slate-700">Manage Users</a>
                <?php endif; ?>
                <a href="logout.php" class="block py-2 px-4 rounded hover:bg-slate-700 mt-4">Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-10">
            <h1 class="text-3xl font-bold text-white mb-6">Import Products from CSV</h1>

            <div class="bg-slate-800 p-8 rounded-lg max-w-2xl mx-auto">
                <?php if ($message): ?>
                    <div class="p-3 rounded mb-4 
                        <?php 
                            if ($message_type === 'success') echo 'bg-green-500 text-white';
                            elseif ($message_type === 'error') echo 'bg-red-500 text-white';
                            elseif ($message_type === 'warning') echo 'bg-yellow-500 text-slate-900';
                        ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form action="import_products.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label for="csv_file" class="block text-slate-400 mb-2">Upload CSV File</label>
                        <input type="file" id="csv_file" name="csv_file" accept=".csv" required class="w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                    </div>
                    <p class="text-slate-500 text-sm mb-6">
                        CSV format: <code class="bg-slate-700 p-1 rounded">name,category,price,image_main,image_1,image_2,image_3,description</code>
                    </p>
                    <div class="flex justify-end gap-4">
                        <a href="products.php" class="bg-slate-600 hover:bg-slate-500 text-white font-bold py-2 px-4 rounded transition">
                            Cancel
                        </a>
                        <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold py-2 px-4 rounded transition">
                            Import Products
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
