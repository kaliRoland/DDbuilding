<?php
function log_activity($conn, $user_id, $action) {
    try {
        if ($user_id === null) {
            // For guests, use a numeric representation of the session ID.
            $user_id = hexdec(substr(session_id(), 0, 8));
        }

        $stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, action) VALUES (?, ?)");
        if ($stmt === false) {
            $error_message = date('[Y-m-d H:i:s] ') . "Error preparing activity log insert: " . $conn->error . "\n";
            file_put_contents(__DIR__ . '/../activity_log_errors.log', $error_message, FILE_APPEND);
            return;
        }

        $stmt->bind_param("is", $user_id, $action);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        // Activity logging must never break core user flows (e.g., login).
        $error_message = date('[Y-m-d H:i:s] ') . "Error logging activity: " . $e->getMessage() . "\n";
        file_put_contents(__DIR__ . '/../activity_log_errors.log', $error_message, FILE_APPEND);
    }
}

function generatePaystackReference() {
    return 'PS_' . uniqid() . mt_rand(1000, 9999);
}

function createOrder($pdo, $order_details) {
    try {
        $pdo->beginTransaction();

        $user_id = $order_details['user_id'];
        $reference = $order_details['reference'];
        $amount = $order_details['amount'];
        $currency = $order_details['currency'];
        $payment_status = $order_details['payment_status'];
        $customer_email = $order_details['customer_email'];
        $customer_name = $order_details['customer_name'];
        $customer_phone = $order_details['customer_phone'];
        $customer_address = $order_details['customer_address'];
        $cart_items_json = json_encode($order_details['cart_items']);

        $stmt = $pdo->prepare("INSERT INTO orders (user_id, reference, amount, currency, payment_status, customer_email, customer_name, customer_phone, customer_address, products_json, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $reference, $amount, $currency, $payment_status, $customer_email, $customer_name, $customer_phone, $customer_address, $cart_items_json]);
        $order_id = $pdo->lastInsertId();

        // Assuming products_json is sufficient for storing order items for now.
        // If a separate order_items table is needed, this logic would be expanded.

        $pdo->commit();
        return $order_id;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error creating order: " . $e->getMessage());
        return false;
    }
}

function clearCart() {
    if (isset($_SESSION['cart'])) {
        unset($_SESSION['cart']);
    }
}
?>
