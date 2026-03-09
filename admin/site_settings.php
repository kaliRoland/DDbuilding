<?php
require_once __DIR__ . '/includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security_headers.php';

// If the admin is not logged in, redirect to login page
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $store_name = $_POST['store_name'] ?? '';
    $store_address = $_POST['store_address'] ?? '';
    $contact_email = $_POST['contact_email'] ?? '';
    $contact_phone = $_POST['contact_phone'] ?? '';
    $social_facebook = $_POST['social_facebook'] ?? '';
    $social_twitter = $_POST['social_twitter'] ?? '';
    $social_instagram = $_POST['social_instagram'] ?? '';

    // For now, we'll store settings in a simple key-value table
    // In a production environment, you'd want a dedicated settings table
    $settings = [
        'store_name' => $store_name,
        'store_address' => $store_address,
        'contact_email' => $contact_email,
        'contact_phone' => $contact_phone,
        'social_facebook' => $social_facebook,
        'social_twitter' => $social_twitter,
        'social_instagram' => $social_instagram
    ];

    // Save settings (for now, we'll use a simple approach)
    // In production, you'd want to create a settings table
    foreach ($settings as $key => $value) {
        $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param("sss", $key, $value, $value);
        $stmt->execute();
        $stmt->close();
    }

    $message = "Settings updated successfully!";
    log_activity($conn, $_SESSION['admin_id'], 'update_site_settings');
}

// Fetch current settings
$settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM site_settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings</title>
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
            <h1 class="text-3xl font-bold text-white mb-6">Site Settings</h1>

            <?php if ($message): ?>
                <div class="bg-green-600 text-white p-4 rounded mb-6"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-600 text-white p-4 rounded mb-6"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" class="bg-slate-800 p-6 rounded-lg max-w-2xl">
                <h2 class="text-xl font-bold text-white mb-6">Store Information</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="store_name" class="block text-sm font-medium text-blue-200 mb-2">Store Name</label>
                        <input type="text" id="store_name" name="store_name" value="<?php echo htmlspecialchars($settings['store_name'] ?? 'DDbuilding Tech'); ?>"
                               class="w-full bg-slate-700 border border-slate-600 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-orange-500">
                    </div>
                    <div>
                        <label for="contact_email" class="block text-sm font-medium text-blue-200 mb-2">Contact Email</label>
                        <input type="email" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email'] ?? 'sales@ddbuildingtech.com'); ?>"
                               class="w-full bg-slate-700 border border-slate-600 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-orange-500">
                    </div>
                </div>

                <div class="mb-6">
                    <label for="store_address" class="block text-sm font-medium text-blue-200 mb-2">Store Address</label>
                    <textarea id="store_address" name="store_address" rows="3"
                              class="w-full bg-slate-700 border border-slate-600 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-orange-500"><?php echo htmlspecialchars($settings['store_address'] ?? '98,ogui Road,Opposite stadium gate,Enugu & Elite plaza, Behind GIGM park Unizik junction'); ?></textarea>
                </div>

                <div class="mb-6">
                    <label for="contact_phone" class="block text-sm font-medium text-blue-200 mb-2">Contact Phone</label>
                    <input type="text" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($settings['contact_phone'] ?? '+234 916 121 2301'); ?>"
                           class="w-full bg-slate-700 border border-slate-600 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-orange-500">
                </div>

                <h2 class="text-xl font-bold text-white mb-6">Social Media Links</h2>

                <div class="space-y-4">
                    <div>
                        <label for="social_facebook" class="block text-sm font-medium text-blue-200 mb-2">Facebook URL</label>
                        <input type="url" id="social_facebook" name="social_facebook" value="<?php echo htmlspecialchars($settings['social_facebook'] ?? ''); ?>"
                               placeholder="https://facebook.com/yourpage"
                               class="w-full bg-slate-700 border border-slate-600 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-orange-500">
                    </div>
                    <div>
                        <label for="social_twitter" class="block text-sm font-medium text-blue-200 mb-2">Twitter URL</label>
                        <input type="url" id="social_twitter" name="social_twitter" value="<?php echo htmlspecialchars($settings['social_twitter'] ?? ''); ?>"
                               placeholder="https://twitter.com/yourhandle"
                               class="w-full bg-slate-700 border border-slate-600 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-orange-500">
                    </div>
                    <div>
                        <label for="social_instagram" class="block text-sm font-medium text-blue-200 mb-2">Instagram URL</label>
                        <input type="url" id="social_instagram" name="social_instagram" value="<?php echo htmlspecialchars($settings['social_instagram'] ?? ''); ?>"
                               placeholder="https://instagram.com/yourhandle"
                               class="w-full bg-slate-700 border border-slate-600 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-orange-500">
                    </div>
                </div>

                <div class="flex justify-end gap-4 mt-6">
                    <button type="submit" class="bg-orange-500 hover:bg-orange-400 text-white font-bold py-2 px-4 rounded transition">
                        Save Settings
                    </button>
                </div>
            </form>
        </main>
    </div>
</body>
</html>


