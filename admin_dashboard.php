<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: admin_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "animzmax_db");
if ($conn->connect_error) {
    die("Connection failed: " . htmlspecialchars($conn->connect_error));
}

// Fetch Admin ID
$adminEmail = 'admin@animzmax.com';
$adminId = 0;
$stmtAdmin = $conn->prepare("SELECT id FROM admins WHERE email = ?");
if (!$stmtAdmin) {
    die("Prepare failed: " . htmlspecialchars($conn->error));
}
$stmtAdmin->bind_param("s", $adminEmail);
$stmtAdmin->execute();
$stmtAdmin->bind_result($adminId);
$stmtAdmin->fetch();
$stmtAdmin->close();

if ($adminId == 0) {
    die("Admin user not found or admin email is incorrect.");
}

// 1. Total Users
$totalUsers = 0;
$res = $conn->query("SELECT COUNT(*) AS cnt FROM users");
if ($res) {
    $row = $res->fetch_assoc();
    $totalUsers = (int)$row['cnt'];
    $res->free();
}

// 2. Total Orders
$totalOrders = 0;
$res = $conn->query("SELECT COUNT(*) AS cnt FROM orders");
if ($res) {
    $row = $res->fetch_assoc();
    $totalOrders = (int)$row['cnt'];
    $res->free();
}

// 3. Total Revenue (sum of order totals, excluding cancelled)
$totalRevenue = 0.0;
$res = $conn->query("SELECT IFNULL(SUM(order_total), 0) AS total_revenue FROM orders WHERE status != 'Cancelled'");
if ($res) {
    $row = $res->fetch_assoc();
    $totalRevenue = (float)$row['total_revenue'];
    $res->free();
}

// 4. New Orders Today
$newOrdersToday = 0;
$res = $conn->query("SELECT COUNT(*) AS cnt FROM orders WHERE DATE(created_at) = CURDATE()");
if ($res) {
    $row = $res->fetch_assoc();
    $newOrdersToday = (int)$row['cnt'];
    $res->free();
}

// 5. Pending Orders
$pendingOrders = 0;
$res = $conn->query("SELECT COUNT(*) AS cnt FROM orders WHERE status = 'Pending'");
if ($res) {
    $row = $res->fetch_assoc();
    $pendingOrders = (int)$row['cnt'];
    $res->free();
}

// 6. Total Products
$totalProducts = 0;
$res = $conn->query("SELECT COUNT(*) AS cnt FROM products");
if ($res) {
    $row = $res->fetch_assoc();
    $totalProducts = (int)$row['cnt'];
    $res->free();
}

// 7. Messages / Notifications Count (unread messages)
$messagesCount = 0;
$res = $conn->query("SELECT COUNT(*) AS cnt FROM messagess WHERE is_read = 0");
if ($res) {
    $row = $res->fetch_assoc();
    $messagesCount = (int)$row['cnt'];
    $res->free();
}

// 8. Sales Chart Data - daily sales last 7 days
$salesChartData = [];
$res = $conn->query("
    SELECT DATE(created_at) AS sale_date, IFNULL(SUM(order_total),0) AS daily_total
    FROM orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND status != 'Cancelled'
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at)
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $salesChartData[$row['sale_date']] = (float)$row['daily_total'];
    }
    $res->free();
}
// Fill missing dates with 0
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    if (!isset($salesChartData[$date])) {
        $salesChartData[$date] = 0;
    }
}

// ======================= PRODUCT UPDATE INSERT/DELETE =======================
// ===== Fetch Existing Categories =====
// ===== Add Product =====
// ===== Fetch Existing Categories =====
// Fetch categories for dropdown
$categories = [];
$categoryResult = $conn->query("SELECT DISTINCT category FROM products ORDER BY category ASC");
if ($categoryResult) {
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

$productSuccess = '';
$productError = '';

// ===== DELETE PRODUCT =====
if (isset($_GET['delete_product'])) {
    $deleteProductId = $_GET['delete_product'];
    $deleteProductId = $conn->real_escape_string($deleteProductId);

    // Get image URL for deletion
    $imgStmt = $conn->prepare("SELECT image_url FROM products WHERE product_id = ?");
    $imgStmt->bind_param("s", $deleteProductId);
    $imgStmt->execute();
    $imgStmt->bind_result($imageUrlToDelete);
    $imgStmt->fetch();
    $imgStmt->close();

    // Delete product from DB
    $deleteStmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    $deleteStmt->bind_param("s", $deleteProductId);
    if ($deleteStmt->execute()) {
        // Delete image file if exists
        if ($imageUrlToDelete && file_exists($imageUrlToDelete)) {
            unlink($imageUrlToDelete);
        }
        $deleteStmt->close();
        // Redirect to same page without query string to prevent re-delete on refresh
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
        exit();
    } else {
        $productError = "Error deleting product: " . htmlspecialchars($deleteStmt->error);
        $deleteStmt->close();
    }
}

// ===== ADD PRODUCT =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    if (!isset($_SESSION['product_token']) || $_SESSION['product_token'] !== $_POST['product_token']) {
        $_SESSION['product_token'] = $_POST['product_token'];

        $productName = trim($_POST['product_name']);
        $price = floatval($_POST['price']);
        $description = trim($_POST['description']);
        $slug = trim($_POST['slug']);
        $category = trim($_POST['category']);
        $newCategory = trim($_POST['new_category']);

        if ($newCategory !== '') {
            $category = $newCategory;
        }

        if ($productName === '' || $price <= 0 || $category === '' || $slug === '') {
            $productError = "Product name, price (> 0), category, and slug are required.";
        } elseif (!isset($_FILES['image_file']) || $_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
            $productError = "Please select a valid image.";
        } else {
            // Check duplicate slug
            $slugCheckStmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE slug = ?");
            $slugCheckStmt->bind_param("s", $slug);
            $slugCheckStmt->execute();
            $slugCheckStmt->bind_result($slugCount);
            $slugCheckStmt->fetch();
            $slugCheckStmt->close();

            if ($slugCount > 0) {
                $productError = "Slug already exists. Please choose a different one.";
            } else {
                // Generate product ID with loop to avoid duplicates
                $words = preg_split('/\s+/', $category);
                $prefix = '';
                foreach ($words as $w) {
                    if (strlen($w) > 0) {
                        $prefix .= strtoupper($w[0]);
                    }
                }

                $number = 1;
                do {
                    $productId = $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);

                    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE product_id = ?");
                    $checkStmt->bind_param("s", $productId);
                    $checkStmt->execute();
                    $checkStmt->bind_result($count);
                    $checkStmt->fetch();
                    $checkStmt->close();

                    $number++;
                } while ($count > 0);
                $number--;
                $productId = $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);

                // Image upload
                $uploadDir = 'uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $fileExt = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
                $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($fileExt, $allowedExts)) {
                    $productError = "Invalid image file type.";
                } else {
                    $fileName = uniqid('img_', true) . '.' . $fileExt;
                    $uploadPath = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadPath)) {
                        $imageUrl = $uploadPath;

                        $stmt = $conn->prepare("INSERT INTO products (product_id, product_name, price, description, image_url, category, slug) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        if ($stmt) {
                            $stmt->bind_param("ssdssss", $productId, $productName, $price, $description, $imageUrl, $category, $slug);
                            if ($stmt->execute()) {
                                $productSuccess = "Product added successfully with ID: $productId";
                            } else {
                                $productError = "Error adding product: " . htmlspecialchars($stmt->error);
                            }
                            $stmt->close();
                        } else {
                            $productError = "Prepare failed: " . htmlspecialchars($conn->error);
                        }
                    } else {
                        $productError = "Failed to move uploaded image.";
                    }
                }
            }
        }
    }
}

// ===== UPDATE PRODUCT =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $productId = trim($_POST['product_id']);
    $productName = trim($_POST['product_name']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    $slug = trim($_POST['slug']);
    $category = trim($_POST['category']);
    $newCategory = trim($_POST['new_category']);

    if ($newCategory !== '') {
        $category = $newCategory;
    }

    if ($productName === '' || $price <= 0 || $category === '' || $slug === '') {
        $productError = "Product name, price (> 0), category, and slug are required.";
    } else {
        // Check duplicate slug except current product
        $slugCheckStmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE slug = ? AND product_id != ?");
        $slugCheckStmt->bind_param("ss", $slug, $productId);
        $slugCheckStmt->execute();
        $slugCheckStmt->bind_result($slugCount);
        $slugCheckStmt->fetch();
        $slugCheckStmt->close();

        if ($slugCount > 0) {
            $productError = "Slug already exists. Please choose a different one.";
        } else {
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $fileExt = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
                $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($fileExt, $allowedExts)) {
                    $productError = "Invalid image file type.";
                } else {
                    $fileName = uniqid('img_', true) . '.' . $fileExt;
                    $uploadPath = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadPath)) {
                        // Delete old image
                        $oldImgStmt = $conn->prepare("SELECT image_url FROM products WHERE product_id = ?");
                        if ($oldImgStmt) {
                            $oldImgStmt->bind_param("s", $productId);
                            $oldImgStmt->execute();
                            $oldImgStmt->bind_result($oldImageUrl);
                            $oldImgStmt->fetch();
                            $oldImgStmt->close();

                            if ($oldImageUrl && file_exists($oldImageUrl)) {
                                unlink($oldImageUrl);
                            }
                        }

                        $stmt = $conn->prepare("UPDATE products SET product_name = ?, price = ?, description = ?, image_url = ?, category = ?, slug = ? WHERE product_id = ?");
                        $stmt->bind_param("sdsssss", $productName, $price, $description, $uploadPath, $category, $slug, $productId);
                    } else {
                        $productError = "Failed to move uploaded image.";
                    }
                }
            } else {
                $stmt = $conn->prepare("UPDATE products SET product_name = ?, price = ?, description = ?, category = ?, slug = ? WHERE product_id = ?");
                $stmt->bind_param("sdssss", $productName, $price, $description, $category, $slug, $productId);
            }

            if (empty($productError) && $stmt) {
                if ($stmt->execute()) {
                    $productSuccess = "Product updated successfully!";
                } else {
                    $productError = "Error updating product: " . htmlspecialchars($stmt->error);
                }
                $stmt->close();
            }
        }
    }
}

// ===== FETCH ALL PRODUCTS =====
$products = [];
$productsResult = $conn->query("SELECT * FROM products ORDER BY product_id ASC");
if ($productsResult) {
    $products = $productsResult->fetch_all(MYSQLI_ASSOC);
}


// ======================= ORDER STATUS UPDATE =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status']) && !isset($_POST['send_message'])) {
    header('Content-Type: application/json');

    $orderId = (int)$_POST['order_id'];
    $status = trim($_POST['status']);
    $validStatuses = ['Pending', 'Packed', 'Shipped', 'Delivered', 'Cancelled'];

    if ($orderId > 0 && in_array($status, $validStatuses, true)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . htmlspecialchars($conn->error)]);
            exit;
        }
        $stmt->bind_param("si", $status, $orderId);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }
}

// MESSAGE READ HANDLER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read_id'])) {
    header('Content-Type: application/json');
    $messageId = (int)$_POST['mark_read_id'];

    if ($messageId > 0) {
        $stmt = $conn->prepare("UPDATE messagess SET is_read = 1 WHERE id = ?");
        $stmt->bind_param("i", $messageId);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
        exit;
    }
    echo json_encode(['success' => false]);
    exit;
}
// Fetch messages with sender and receiver names
$messages = [];
$msgQuery = "
    SELECT m.*, 
           sender.name AS sender_name, 
           receiver.name AS receiver_name
    FROM messagess m
    LEFT JOIN users sender ON m.sender_id = sender.user_id
    LEFT JOIN users receiver ON m.receiver_id = receiver.user_id
    ORDER BY m.sent_at DESC
";
$msgResult = $conn->query($msgQuery);
if ($msgResult && $msgResult->num_rows > 0) {
    $messages = $msgResult->fetch_all(MYSQLI_ASSOC);
}

// ======================= SEND ADMIN MESSAGE =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $senderId = $adminId;
    $receiverId = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    if ($senderId > 0 && $receiverId > 0 && $message !== '') {
        $stmt = $conn->prepare("INSERT INTO messagess (sender_id, receiver_id, message, sent_at, is_read) VALUES (?, ?, ?, NOW(), 0)");
        if ($stmt) {
            $stmt->bind_param("iis", $senderId, $receiverId, $message);
            $stmt->execute();
            $stmt->close();
            header("Location: admin_dashboard.php");
            exit();
        }
    }
}

// ======================= FETCH USERS =======================
$users = [];
$sql = "
    SELECT 
        u.user_id, u.name, u.email, u.phone, u.address, u.city, u.zip, u.avatar, u.created_at,
        COUNT(o.order_id) AS order_count
    FROM users u
    LEFT JOIN orders o ON u.user_id = o.user_id
    GROUP BY u.user_id
    ORDER BY u.name ASC
";
$usersStmt = $conn->prepare($sql);
if ($usersStmt) {
    $usersStmt->execute();
    $usersResult = $usersStmt->get_result();
    $users = $usersResult->fetch_all(MYSQLI_ASSOC);
    $usersStmt->close();
}
// ======================= FETCH ORDERS WITH ITEM DETAILS =======================
$orders = [];
$ordersResult = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
if ($ordersResult) {
    while ($order = $ordersResult->fetch_assoc()) {
        $orderId = (int)$order['order_id'];

        // Fetch order items with product_id and category
        $itemsQuery = "
            SELECT oi.product_id, oi.product_name, oi.quantity, oi.price, p.category 
            FROM order_items oi
            JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = $orderId
        ";
        $itemsResult = $conn->query($itemsQuery);
        $items = [];

        if ($itemsResult && $itemsResult->num_rows > 0) {
            $items = $itemsResult->fetch_all(MYSQLI_ASSOC);
        }

        // Add items array to order
        $order['items'] = $items;

        // order_total is already in $order as per your query SELECT *
        // But just ensure it's numeric and add it explicitly if you want:
        $order['order_total'] = isset($order['order_total']) ? (float)$order['order_total'] : 0;

        $orders[] = $order;
    }
}

// ======================= FETCH CONTACT MESSAGES WITH REPLIES =======================
$contacts = [];
$contactsResult = $conn->query("SELECT * FROM contact_messages ORDER BY submitted_at DESC");
if ($contactsResult) {
    while ($contact = $contactsResult->fetch_assoc()) {
        $contactId = $contact['id'];
        $repliesResult = $conn->query("SELECT * FROM contact_replies WHERE contact_id = $contactId ORDER BY replied_at ASC");
        $replies = [];

        if ($repliesResult && $repliesResult->num_rows > 0) {
            $replies = $repliesResult->fetch_all(MYSQLI_ASSOC);
        }

        $contact['replies'] = $replies;
        $contacts[] = $contact;
    }
}

// ======================= HANDLE CONTACT MESSAGE REPLY =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_contact'])) {
    $contactId = (int)$_POST['contact_id'];
    $replyMessage = trim($_POST['reply_message']);

    if ($contactId > 0 && $replyMessage !== '') {
        $stmt = $conn->prepare("INSERT INTO contact_replies (contact_id, reply_message, replied_at) VALUES (?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("is", $contactId, $replyMessage);
            if ($stmt->execute()) {
                header("Location: admin_dashboard.php");
                exit();
            }
            $stmt->close();
        }
    }
}

// ======================= NOTIFICATION HANDLER =======================
$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_text'])) {
    $text = trim($_POST['notification_text']);
    $targetType = $_POST['target_type'];
    $targetUserIds = ($targetType === 'selected' && !empty($_POST['target_user_ids'])) ? implode(',', $_POST['target_user_ids']) : null;

    if ($text !== '') {
        $checkStmt = $conn->prepare("SELECT id FROM notifications WHERE content = ? AND target_type = ? AND (target_user_ids = ? OR target_type = 'all')");
        $checkStmt->bind_param('sss', $text, $targetType, $targetUserIds);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows == 0) { // Only insert if no duplicate
            $checkStmt->close();

            $stmt = $conn->prepare("INSERT INTO notifications (user_id, content, target_type, target_user_ids) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('isss', $adminId, $text, $targetType, $targetUserIds);
                $stmt->execute();
                $stmt->close();
                $success = "Notification sent successfully!";
            } else {
                $error = "Prepare failed: " . htmlspecialchars($conn->error);
            }
        } else {
            $checkStmt->close(); // Duplicate found, ignore
        }
    }
}

// ======================= FETCH NOTIFICATIONS =======================
$sql = "SELECT notifications.*, ? AS admin_name FROM notifications ORDER BY notifications.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $adminEmail);
$stmt->execute();
$result = $stmt->get_result();
$notifications = [];
if ($result && $result->num_rows > 0) {
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {font-family: Arial, sans-serif; margin: 0; padding: 0; background: #dad7cd;}
        .main {margin-left: 220px; padding: 2rem;}
        .section {display: none;}
        table {width: 100%; border-collapse: collapse; margin-top: 1rem; background: #ffffff;}
        th, td {border: 1px solid #ccc; padding: 8px;}
        th {background: #eee;}
        .alert {margin: 1rem 0; padding: 1rem; border-radius: 5px;}
        .success {background: #d4edda; color: #155724;}
        .error {background: #f8d7da; color: #721c24;}
        .unread {font-weight: bold; background-color: #f9f9f9;}
        header {
      position: fixed;
      top: 0;
      width: 97%;
      justify-content: space-between;
      display: flex;
      align-items: center;
      background: #00573f;
      color: white;
      padding: 15px 30px;
        z-index: 1000;
    }

    header .logo {
      font-size: 24px;
      font-weight: bold;
    }

    header nav a {
      color: white;
      margin-left: 20px;
      text-decoration: none;
      font-size: 18px;
    }


        .sidebar {width: 220px; background: #f4f4f4; height: 100vh; position: fixed; top: 0; left: 0;  margin-top: 60px;}
        .sidebar button {
  width: 100%;
  padding: 1rem;
  border: none;
  background: none;
  text-align: left;
  cursor: pointer;
  font-size: 16px;
  color: #333;
  transition: background-color 0.3s ease, color 0.3s ease, padding-left 0.3s ease;
  border-radius: 8px;
}

.sidebar button:hover {
  background-color: #00573f;
  color: #fff;
  padding-left: 1.5rem; /* subtle slide effect on hover */
}

.sidebar button.active {
  background-color: #00452f;
  color: #fff;
  font-weight: bold;
}

.sidebar button:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(0, 87, 63, 0.5);
}

.sidebar button i {
  margin-right: 10px; /* optional if using icons */
}
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: bold;
            color: white;
            display: inline-block;
        }
        .badge-pending { background-color: #6c757d; }
        .badge-packed { background-color: #17a2b8; }
        .badge-shipped { background-color: #ffc107; color: #212529; }
        .badge-delivered { background-color: #28a745; }
        .badge-cancelled { background-color: #dc3545; }

    /* Send Message Form Styling */
    .send-message-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    max-width: 500px;
    background-color:rgb(255, 255, 255);
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.send-message-form label {
    font-weight: bold;
    color: #333;
}

.send-message-form textarea {
    min-height: 120px;
    padding: 0.5rem;
    resize: vertical;
    font-family: Arial, sans-serif;
    font-size: 1rem;
    border-radius: 8px;
    border: 1px solid #ccc;
    background-color: #fefefe;
}

.send-message-form button , .mark-read-btn {
    background-color: #00573f;
    color: white;
    border: none;
    padding: 0.7rem 1.5rem;
    cursor: pointer;
    border-radius: 8px;
    font-size: 1rem;
}

.send-message-form button:hover {
    background-color:rgb(73, 199, 159);
}

.contact-message {
    background:#ffffff;
    margin-bottom: 2rem;
    padding: 1rem;
    border: 1px solid #ccc;
    border-radius: 8px;
}

.contact-message h4 {
    margin-top: 1rem;
    margin-bottom: 0.5rem;
}

.reply-box {
     width: 50%;
    height: 60px;
    background:rgb(212, 212, 212);
    padding: 0.8rem;
    border-left: 4px solid #00573f;
    margin-bottom: 0.5rem;
    border-radius: 4px;
}

.reply-form textarea {
    background:rgb(255, 255, 255);
    width: 60%;
    height: 100px;
    margin-bottom: 0.5rem;
    border-radius: 4px;
    padding: 0.5rem;
    border: 1px solid #ccc;
}

.reply-form button {
    padding: 0.5rem 1rem;
    background: #00573f;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
#adminNotifications {
  max-width: 700px;
  margin: 1.5rem auto;
  background: #f1f8f7;
  padding: 1.2rem 1.5rem;
  border-radius: 8px;
  box-shadow: 0 0 8px rgba(0, 87, 63, 0.1);
  font-family: Arial, sans-serif;
  color: #004d40;
}

#adminNotifications h2 {
  border-bottom: 2px solid #00573f;
  padding-bottom: 0.4rem;
  margin-bottom: 1rem;
}

.notification-form label {
  font-weight: 600;
  font-size: 1rem;
  margin-bottom: 0.3rem;
  display: block;
  color: #004d40;
}

.notification-form textarea {
  width: 100%;
  font-size: 1rem;
  padding: 0.6rem;
  border: 1px solid #a3d0ca;
  border-radius: 5px;
  resize: vertical;
  margin-bottom: 0.8rem;
}

.notification-form button {
  background-color: #00573f;
  color: white;
  padding: 0.5rem 1.2rem;
  font-size: 1rem;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.notification-form button:hover {
  background-color: #004d40;
}

.notification-list {
  list-style: none;
  padding-left: 0;
  margin-top: 0;
}

.notification-list li {
  background: #e0f0ec;
  border-left: 4px solid #00573f;
  padding: 0.8rem 1rem;
  margin-bottom: 1rem;
  border-radius: 5px;
}

.notification-text {
  font-size: 1rem;
  line-height: 1.4;
  color: #003322;
}

.notification-date {
  font-size: 0.85rem;
  color: #006644;
  margin-top: 0.3rem;
  font-style: italic;
}

.success-msg {
  color: #007700;
  font-weight: 600;
  margin-bottom: 0.8rem;
}

.error-msg {
  color: #aa0000;
  font-weight: 600;
  margin-bottom: 0.8rem;
}
/* Dropdown Styling */
/* Dropdown Styling */
.status-select {
    padding: 6px 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    background-color: #f9f9f9;
    font-size: 14px;
    cursor: pointer;
    transition: border-color 0.3s ease;
    margin-bottom: 0.8rem;
}

.status-select:hover {
    border-color: #888;
}

.status-select:focus {
    border-color: #00573f;
    outline: none;
}

/* Badge Styling */
.badge {
    padding: 4px 8px;
    border-radius: 12px;
    color: white;
    font-size: 13px;
    display: inline-block;
}

/* Color Variations for Each Status */
.badge-pending {
    background-color: #f0ad4e; /* Orange */
}

.badge-packed {
    background-color: #5bc0de; /* Light Blue */
}

.badge-shipped {
    background-color: #0275d8; /* Blue */
}

.badge-delivered {
    background-color: #5cb85c; /* Green */
}

.badge-cancelled {
    background-color: #d9534f; /* Red */
}

  /* product Basic styling */
  .product-form, .product-list {
    max-width: 900px;
    margin: 20px auto;
    padding: 15px;
    border: 1px solid #ccc;
    background: #f9f9f9;
  }

  label {
    display: block;
    margin-top: 10px;
    font-weight: bold;
  }

  input[type=text], input[type=number], textarea, select {
    width: 100%;
    padding: 7px;
    margin-top: 5px;
    box-sizing: border-box;
  }

  button {
    margin-top: 15px;
    padding: 10px 20px;
    cursor: pointer;
  }

  .success-msg {
    color: green;
  }

  .error-msg {
    color: red;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 30px;
  }

  th, td {
    border: 1px solid #aaa;
    padding: 8px;
    text-align: left;
  }

  th {
    background: #eee;
  }

  img.product-thumb {
    width: 50px;
    height: 50px;
    object-fit: cover;
  }

  .edit-section {
    margin-top: 20px;
    padding: 10px;
    background: #e9ecef;
    border: 1px solid #bbb;
  }

  .hidden {
    display: none;
  }

  .new-category-input {
    margin-top: 5px;
    font-style: italic;
    color: #555;
  }
  /* ========== Add Product Form Styles ========== */
.add-product-form {
    max-width: 500px;
    margin: 40px auto;
    padding: 20px;
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    font-family: 'Inter', sans-serif;
    color: #333;
}

.add-product-form h2 {
    text-align: center;
    margin-bottom: 20px;
    color: #00573f;
}

.add-product-form label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
}

.add-product-form input[type="text"],
.add-product-form input[type="number"],
.add-product-form input[type="image_file"],
.add-product-form textarea,
.add-product-form select {
    width: 100%;
    padding: 10px 12px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s;
}

.add-product-form input:focus,
.add-product-form textarea:focus,
.add-product-form select:focus {
    border-color: #00573f;
    outline: none;
}

.add-product-form button {
    width: 100%;
    padding: 12px;
    background-color: #00573f;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.add-product-form button:hover {
    background-color:rgb(158, 158, 158);
    color: white;
}

.add-product-form .success-message {
    color: #2e7d32;
    background: #d0f0d8;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
    text-align: center;
}

.add-product-form .error-message {
    color: #c62828;
    background: #f8d7da;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
    text-align: center;
}
/* ========== Product List Table Styles ========== */
.product-list-section {
      margin: top 20px; 
    max-width: 100%;
    padding: 0 0;
    font-family: 'Inter', sans-serif;
}

.product-list-title {
    text-align: center;
    color: #00573f;
    margin: top 20px; 
    margin-bottom: 20px;
}

.no-products {
    text-align: center;
    color: #888;
    font-size: 16px;
}

.product-table-wrapper {
    overflow-x: auto;
}

.product-table {
    width: 100%;
    border-collapse: collapse;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-radius: 12px;
    overflow: hidden;
}

.product-table th, .product-table td {
    padding: 12px 15px;
    text-align: center;
    border-bottom: 1px solid #ddd;
}

.product-table th {
    background-color: #00573f;
    color: white;
}

.product-table tr:hover {
    background-color: #f1f1f1;
}

.product-image {
    height: 200px;
    width: auto;
    border-radius: 6px;
}

/* ========== Action Buttons (Edit/Delete) ========== */
.action-buttons {
    display: flex;
    gap: 10px; 
    justify-content: center;
    align-items: center; 
}

.edit-btn, .delete-btn {
    margin:5px;
    width: 80px;             
    height: 35px;            
    display: inline-flex;     
    align-items: center;      
    justify-content: center; 
    padding: 0;               
    border: none;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
    color: white;
    text-decoration: none;    
    transition: background-color 0.3s, color 0.3s;
}

.edit-btn {
    background-color: #00573f;
}

.edit-btn:hover {
    background-color: rgb(177, 177, 177);
    color: #00573f;
}

.delete-btn {
    background-color: rgb(255, 0, 25);
}

.delete-btn:hover {
    background-color: rgb(177, 177, 177);
    color: rgb(255, 0, 25);
}

/* ========== Inline Edit Form Styles ========== */
.edit-product-form {
    background-color: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    display: grid;
    gap: 10px;
}

.edit-product-form input[type="text"],
.edit-product-form input[type="number"],
.edit-product-form input[type="file"],
.edit-product-form textarea,
.edit-product-form select {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
}

.edit-product-form button {
    padding: 10px 15px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
}

.form-buttons {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

.update-btn {
    background-color: #00573f;
    color: white;
}

.update-btn:hover {
    background-color: rgb(177, 177, 177);
    color:white;
}

.cancel-btn {
    background-color: #6c757d;
    color: white;
}

.cancel-btn:hover {
    background-color:rgb(255, 0, 0);
    color:white;
}
/* Dashboard Summary Container */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 40px;
}

.stat-card {
  background: white;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
  text-align: center;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
}

.stat-card h3 {
  margin-bottom: 10px;
  color: #00573f;
  font-size: 18px;
}

.stat-card p {
  font-size: 24px;
  font-weight: bold;
  color: #2c3e50;
  margin: 0;
}

h2, h3 {
  color: #00573f;
  margin-bottom: 20px;
}

/* Sales Chart Styling */
#salesChart {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
  padding: 20px;
  display: block;
  max-width: 100%;
}

  </style>
</head>
<body>
<header>
    <div class="logo">Admin Dashboard</div>
    <a href="admin_logout.php" style="color:white;">Logout</a>
</header>
<div class="sidebar">
    <!-- Existing menu items -->
    <button onclick="showSection('dashboard')">Dashboard</button>
    <button onclick="showSection('addProductSection')">Add Product</button>
    <button onclick="showSection('productListSection')">Product List</button>
    <button onclick="showSection('usersSection')">User Table</button>
    <button onclick="showSection('ordersSection')">🧾Orders</button>
    <button onclick="showSection('messagesSection')">📨 Inbox</button>
    <button onclick="showSection('sendMessageSection')">✉️ Send Message</button>
    <button onclick="showSection('contactSection')">📬 Contact</button>
    <button onclick="showSection('adminNotifications')">Notifications (Ads)</button>
</div>

<div class="main">
    <div class="main">
<div id="dashboard" class="content section">

    <h2>Dashboard Summary</h2>

    <div class="stats-grid">
      <div class="stat-card">
        <h3>Total Users</h3>
        <p><?= htmlspecialchars($totalUsers) ?></p>
      </div>
      <div class="stat-card">
        <h3>Total Orders</h3>
        <p><?= htmlspecialchars($totalOrders) ?></p>
      </div>
      <div class="stat-card">
        <h3>Total Revenue</h3>
        <p>₹ <?= number_format($totalRevenue, 2) ?></p>
      </div>
      <div class="stat-card">
        <h3>New Orders Today</h3>
        <p><?= htmlspecialchars($newOrdersToday) ?></p>
      </div>
      <div class="stat-card">
        <h3>Pending Orders</h3>
        <p><?= htmlspecialchars($pendingOrders) ?></p>
      </div>
      <div class="stat-card">
        <h3>Total Products</h3>
        <p><?= htmlspecialchars($totalProducts) ?></p>
      </div>
      <div class="stat-card">
        <h3>Unread Messages</h3>
        <p><?= htmlspecialchars($messagesCount) ?></p>
      </div>
    </div>

    <h3>Sales Chart (Last 7 Days)</h3>
    <canvas id="salesChart" width="800" height="300"></canvas>

  </div>
</div>

<!-- ======================= ADD PRODUCT SECTION ======================= -->
<div id="addProductSection" class="section">

    <form method="POST" enctype="multipart/form-data" class="add-product-form" autocomplete="off">
        <h2>Add Product</h2>

        <?php if ($productSuccess): ?>
            <div class="success-message"><?php echo $productSuccess; ?></div>
        <?php elseif ($productError): ?>
            <div class="error-message"><?php echo $productError; ?></div>
        <?php endif; ?>

        <input type="hidden" name="product_token" value="<?= bin2hex(random_bytes(16)) ?>">

        <label for="product_name">Product Name *</label>
        <input type="text" id="product_name" name="product_name" required maxlength="255">

        <label for="slug">Slug *</label>
        <input type="text" id="slug" name="slug" required maxlength="255" placeholder="example-slug">

        <label for="price">Price (₹) *</label>
        <input type="number" id="price" name="price" required min="0" step="1">

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="3"></textarea>

        <label for="category">Select Category *</label>
        <select name="category" id="category" required onchange="toggleNewCategory(this.value)">
            <option value="" disabled selected>-- Choose Category --</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
            <option value="__new">-- Add New Category --</option>
        </select>

        <div id="newCategoryDiv" style="display:none;">
            <input type="text" name="new_category" id="new_category" placeholder="Enter new category">
        </div>

        <label for="image_file">Product Image *</label>
        <input type="file" id="image_file" name="image_file" accept="image/*" required>

        <button type="submit" name="add_product">Add Product</button>
    </form>

    <?php if ($productSuccess): ?>
        <script>
            // Auto-hide the success message and refresh page after 2 seconds
            setTimeout(function() {
                window.location.reload();
            }, 2000);
        </script>
    <?php endif; ?>
</div>

<!-- ======================= PRODUCT LISTING ======================= -->
<div id="productListSection" class="section product-list-section">
    <section class="search-section">
  <input type="text" id="searchInput" placeholder="Search products...">
<p id="searchHint">Start typing to search for products...</p>
</section>
    <h2 class="product-list-title">Products Listing</h2>

    <?php if (empty($products)) : ?>
        <p class="no-products">No products found.</p>
    <?php else: ?>
        <div class="product-table-wrapper">
            <table class="product-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product ID</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Price (₹)</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="Image" class="product-image">
                            </td>
                            <td><?= htmlspecialchars($product['product_id']) ?></td>
                            <td><?= htmlspecialchars($product['product_name']) ?></td>
                            <td><?= htmlspecialchars($product['slug']) ?></td>
                            <td><?= number_format($product['price'], 2) ?></td>
                            <td><?= nl2br(htmlspecialchars($product['description'])) ?></td>
                            <td><?= htmlspecialchars($product['category']) ?></td>
                            <td id="action-buttons">
                                <button type="button" class="edit-btn" onclick="showEditForm('<?= addslashes($product['product_id']) ?>')">Edit</button>
                                <a href="?delete_product=<?= urlencode($product['product_id']) ?>" onclick="return confirm('Are you sure you want to delete this product?');" class="delete-btn">Delete</a>
                            </td>
                        </tr>

                        <!-- ================= EDIT PRODUCT FORM (INLINE) ================= -->
                        <tr id="edit-<?= htmlspecialchars($product['product_id']) ?>" class="edit-section" style="display:none;">
                            <td colspan="8">
                                <form method="post" enctype="multipart/form-data" autocomplete="off" class="edit-product-form">

                                    <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['product_id']) ?>">

                                    <label>Product Name *</label>
                                    <input type="text" name="product_name" required maxlength="255" value="<?= htmlspecialchars($product['product_name']) ?>">

                                    <label>Slug *</label>
                                    <input type="text" name="slug" required maxlength="255" value="<?= htmlspecialchars($product['slug']) ?>">

                                    <label>Price (₹) *</label>
                                    <input type="number" name="price" required min="0" step="0.01" value="<?= htmlspecialchars($product['price']) ?>">

                                    <label>Description</label>
                                    <textarea name="description" rows="3"><?= htmlspecialchars($product['description']) ?></textarea>

                                    <label>Select Category *</label>
                                    <select name="category" required onchange="toggleNewCategoryEdit(this.value, '<?= htmlspecialchars($product['product_id']) ?>')">
                                        <option value="" disabled>-- Choose Category --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= htmlspecialchars($cat) ?>" <?= $product['category'] === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                                        <?php endforeach; ?>
                                        <option value="__new">-- Add New Category --</option>
                                    </select>

                                    <div id="edit_new_category_<?= htmlspecialchars($product['product_id']) ?>" class="new-category-input" style="display:none;">
                                        <input type="text" name="new_category" placeholder="Enter new category">
                                    </div>

                                    <label>Change Image (leave empty to keep current)</label>
                                    <input type="file" name="image_file" accept="image/*">

                                    <div class="form-buttons">
                                        <button type="submit" name="update_product" class="update-btn">Update Product</button>
                                        <button type="button" onclick="hideEditForm('<?= addslashes($product['product_id']) ?>')" class="cancel-btn">Cancel</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>




<div id="usersSection" class="section" style="display:none;">
    <h2>All Users</h2>
    <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th>Avatar</th>
                <th>User ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Address</th>
                <th>City</th>
                <th>ZIP</th>
                <th>Order Count</th>
                <th>Joined On</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td style="text-align:center;">
                            <?php if (!empty($user['avatar'])): ?>
                                <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar" style="width:40px; height:40px; border-radius:50%;">
                            <?php else: ?>
                                <span>No Avatar</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($user['user_id']) ?></td>
                        <td><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($user['address'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($user['city'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($user['zip'] ?? 'N/A') ?></td>
                        <td style="text-align:center;"><?= (int)$user['order_count'] ?></td>
                        <td><?= htmlspecialchars($user['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="10" style="text-align:center;">No users found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="ordersSection" class="section">
    <h2>All Orders</h2>

    <?php if (empty($orders)): ?>
        <p>No orders found.</p>
    <?php else: ?>
        <table border="1" cellpadding="10" cellspacing="0" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>User ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>City</th>
                    <th>ZIP</th>
                    <th>Phone</th>
                    <th>Payment Method</th>
                    <th>Order Total (₹)</th>
                    <th>Items</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= htmlspecialchars($order['order_id']) ?></td>
                        <td><?= htmlspecialchars($order['user_id']) ?></td>
                        <td><?= htmlspecialchars($order['full_name']) ?></td>
                        <td><?= htmlspecialchars($order['email']) ?></td>
                        <td><?= htmlspecialchars($order['address']) ?></td>
                        <td><?= htmlspecialchars($order['city']) ?></td>
                        <td><?= htmlspecialchars($order['zip']) ?></td>
                        <td><?= htmlspecialchars($order['phone']) ?></td>
                        <td><?= htmlspecialchars($order['payment_method']) ?></td>
                        <td><strong>₹<?= htmlspecialchars($order['order_total']) ?></strong></td>

                        <td>
                            <?php if (!empty($order['items'])): ?>
                                <?php foreach ($order['items'] as $item): ?>
                                    <div style="margin-bottom: 6px;">
                                        <strong><?= htmlspecialchars($item['product_name']) ?></strong> <br>
                                        Product ID: <?= htmlspecialchars($item['product_id']) ?> <br>
                                        Quantity: <?= (int)$item['quantity'] ?> <br>
                                        Category: <em><?= htmlspecialchars($item['category']) ?></em>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                No items found
                            <?php endif; ?>
                        </td>

                        <td>
                            <select data-order-id="<?= $order['order_id'] ?>" class="status-select">
                                <?php
                                $statuses = ['Pending', 'Packed', 'Shipped', 'Delivered', 'Cancelled'];
                                foreach ($statuses as $status) {
                                    $selected = ($status === $order['status']) ? 'selected' : '';
                                    echo "<option value='$status' $selected>$status</option>";
                                }
                                ?>
                            </select>
                            <span class="badge badge-<?= strtolower($order['status']) ?> status-badge-<?= $order['order_id'] ?>" style="margin-left: 8px;">
                                <?= htmlspecialchars($order['status']) ?>
                            </span>
                        </td>

                        <td><?= htmlspecialchars($order['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div id="messagesSection" class="section">
    <h2>All Messages</h2>
    <table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Sender</th>
                <th>Receiver</th>
                <th>Message</th>
                <th>Time</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($messages)): ?>
                <?php foreach ($messages as $msg): ?>
                    <tr class="<?= $msg['is_read'] == 0 ? 'unread' : '' ?>">
                        <td><?= htmlspecialchars($msg['id']) ?></td>
                        <td><?= htmlspecialchars($msg['sender_name'] ?? 'Animz max') ?></td>
                        <td><?= htmlspecialchars($msg['receiver_name'] ?? 'Animz max') ?></td>
                        <td><?= nl2br(htmlspecialchars($msg['message'])) ?></td>
                        <td><?= htmlspecialchars($msg['sent_at']) ?></td>
                        <td>
                            <?php if ($msg['is_read']): ?>
                                Read
                            <?php else: ?>
                                <button class="mark-read-btn" data-message-id="<?= $msg['id'] ?>">Mark as Read</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center;">No messages found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

    <div id="sendMessageSection" class="section">
        <h2>Send Message</h2>
        <?php if ($success): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" class="send-message-form" novalidate>
            <label for="receiver_id">Select User:</label>
            <select name="receiver_id" id="receiver_id" required>
                <option value="">-- Select --</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= htmlspecialchars($user['user_id']) ?>"><?= htmlspecialchars($user['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="message">Message:</label>
            <textarea name="message" id="message" required></textarea>
            <button type="submit" name="send_message">Send</button>
        </form>
    </div>

    <div id="contactSection" class="section">
    <h2>Contact Messages</h2>
    <?php foreach ($contacts as $c): ?>
        <div class="contact-message">
            <p><strong>Name:</strong> <?= htmlspecialchars($c['name']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($c['email']) ?></p>
            <p><strong>Message:</strong> <?= nl2br(htmlspecialchars($c['message'])) ?></p>
            <p><strong>Time:</strong> <?= htmlspecialchars($c['submitted_at']) ?></p>

            <?php if (!empty($c['replies'])): ?>
                <div class="replies">
                    <h4>Replies:</h4>
                    <?php foreach ($c['replies'] as $reply): ?>
                        <div class="reply-box">
                            <p><?= nl2br(htmlspecialchars($reply['reply_message'])) ?></p>
                            <small><?= htmlspecialchars($reply['replied_at']) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="reply-form">
                <input type="hidden" name="contact_id" value="<?= $c['id'] ?>">
                <textarea name="reply_message" placeholder="Type your reply..." required></textarea><br>
                <button type="submit" name="reply_contact">Send Reply</button>
            </form>
            <hr>
        </div>
    <?php endforeach; ?>
</div>

<div id="adminNotifications" class="section">
    <h2>Admin Notifications (Ads)</h2>

    <?php if (!empty($success)): ?>
        <p class="success-msg"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <p class="error-msg"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <h2>Send Notification / Ad</h2>
    <form method="POST" class="notification-form">
        <label for="notification_text">Notification / Ad:</label><br>
        <textarea id="notification_text" name="notification_text" rows="3" placeholder="Write your ad or notification here..." required></textarea><br><br>

        <label for="target_type">Send To:</label><br>
        <select name="target_type" id="target_type" onchange="toggleUserSelection()" required>
            <option value="all">All Users</option>
            <option value="selected">Selected Users</option>
        </select><br><br>

        <div id="userSelection" style="display:none;">
            <label for="target_user_ids">Select Users:</label><br>
            <select name="target_user_ids[]" id="target_user_ids" multiple size="5">
                <?php foreach ($users as $user): ?>
                    <option value="<?= $user['user_id'] ?>">ID: <?= $user['user_id'] ?> - <?= htmlspecialchars($user['name']) ?></option>
                <?php endforeach; ?>
            </select><br><br>
        </div>

        <button type="submit">Send Notification</button>
    </form>
    <hr>
    <h3>Notifications Sent by Animz max</h3>
    <?php if (count($notifications) === 0): ?>
        <p>No notifications found.</p>
    <?php else: ?>
        <ul class="notification-list">
            <?php foreach ($notifications as $note): ?>
                <li>
                    <div class="notification-admin"><strong>Posted By:</strong> <?= htmlspecialchars($note['admin_name']) ?></div>
                    <div class="notification-target">
                        <strong>Target:</strong>
                        <?= ($note['target_type'] === 'all') ? 'All Users' : 'User IDs: ' . htmlspecialchars($note['target_user_ids']) ?>
                    </div>
                    <div class="notification-text"><?= nl2br(htmlspecialchars($note['content'])) ?></div>
                    <div class="notification-date">Created: <?= htmlspecialchars($note['created_at']) ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

</div>
<script>
    //side bar actuve state
    document.querySelectorAll('.sidebar button').forEach(button => {
  button.addEventListener('click', () => {
    document.querySelectorAll('.sidebar button').forEach(btn => btn.classList.remove('active'));
    button.classList.add('active');
  });
});

  // Set the default section
  const defaultSection = 'dashboard';

  // Show only the selected section
  function showSection(id) {
    document.querySelectorAll('.section').forEach(sec => {
      sec.style.display = 'none';
    });

    const activeSection = document.getElementById(id);
    if (activeSection) activeSection.style.display = 'block';

    localStorage.setItem('admin_open_section', id);
  }

  // Initialize the sales chart
  function initSalesChart() {
    if (!document.getElementById('salesChart')) return; // Skip if no chart is present

    const salesLabels = <?= json_encode(array_keys($salesChartData)) ?>;
    const salesData = <?= json_encode(array_values($salesChartData)) ?>;

    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: salesLabels,
        datasets: [{
          label: 'Sales (₹)',
          data: salesData,
          borderColor: '#00573f',
          backgroundColor: 'rgba(0, 87, 63, 0.2)',
          fill: true,
          tension: 0.3,
          pointRadius: 5,
        }]
      },
      options: {
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: value => '₹' + value.toFixed(2)
            }
          }
        },
        responsive: true,
        plugins: {
          legend: { display: true, position: 'top' }
        }
      }
    });
  }

  // Attach event handlers for order status change
  function attachStatusChangeHandlers() {
    document.querySelectorAll('.status-select').forEach(select => {
      select.addEventListener('change', function () {
        const orderId = this.getAttribute('data-order-id');
        const newStatus = this.value;

        fetch('admin_dashboard.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `order_id=${encodeURIComponent(orderId)}&status=${encodeURIComponent(newStatus)}`
        })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              const badge = document.querySelector(`.status-badge-${orderId}`);
              if (badge) {
                badge.textContent = newStatus;
                badge.className = `badge badge-${newStatus.toLowerCase()} status-badge-${orderId}`;
              }
            } else {
              alert("Update failed: " + (data.message || "Unknown error"));
            }
          })
          .catch(err => {
            alert("AJAX error: " + err);
          });
      });
    });
  }

  // Attach event handlers for marking messages as read
  function attachMarkReadHandlers() {
    document.querySelectorAll('.mark-read-btn').forEach(button => {
      button.addEventListener('click', function () {
        const messageId = this.getAttribute('data-message-id');
        const row = this.closest('tr');

        fetch('admin_dashboard.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `mark_read_id=${encodeURIComponent(messageId)}`
        })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              row.classList.remove('unread');
              this.parentElement.textContent = 'Read';
            } else {
              alert("Failed to mark as read.");
            }
          })
          .catch(err => {
            alert("AJAX error: " + err);
          });
      });
    });
  }

  // Toggle user selection based on notification target
  function toggleUserSelection() {
    const targetTypeElement = document.getElementById('target_type');
    const userSelection = document.getElementById('userSelection');

    if (targetTypeElement && userSelection) {
      userSelection.style.display = (targetTypeElement.value === 'selected') ? 'block' : 'none';
    }
  }

  // Toggle new category input in Add Product form
  function toggleNewCategory(value) {
    const newCatInput = document.getElementById('new_category');
    if (newCatInput) {
      newCatInput.style.display = (value === '__new') ? 'block' : 'none';
      if (value !== '__new') newCatInput.value = '';
    }
  }

  // Toggle new category input in Edit Product form
  function toggleNewCategoryEdit(value, id) {
    const newCatInput = document.getElementById('edit_new_category_' + id);
    if (newCatInput) {
      newCatInput.style.display = (value === '__new') ? 'block' : 'none';
      if (value !== '__new') newCatInput.value = '';
    }
  }

  // Show edit form for a product
  function showEditForm(productId) {
    const row = document.getElementById('edit-' + productId);
    if (row) row.style.display = 'table-row';
  }

  // Hide edit form for a product
  function hideEditForm(productId) {
    const row = document.getElementById('edit-' + productId);
    if (row) row.style.display = 'none';
  }

  // Initialize on page load
  document.addEventListener('DOMContentLoaded', () => {
    const savedSection = localStorage.getItem('admin_open_section') || defaultSection;
    showSection(savedSection);

    initSalesChart();
    attachStatusChangeHandlers();
    attachMarkReadHandlers();
    toggleUserSelection();
  });
  
document.getElementById('searchInput').addEventListener('keyup', function () {
    const filters = this.value.trim().toLowerCase().split(/\s+/); // Split input into words
    const rows = document.querySelectorAll('.product-table tbody tr');
    let found = false;

    rows.forEach(row => {
        const rowText = (
            row.cells[1].textContent + ' ' +
            row.cells[2].textContent + ' ' +
            row.cells[3].textContent + ' ' +
            row.cells[6].textContent
        ).toLowerCase();

        const matchAll = filters.every(filter => rowText.includes(filter));

        if (matchAll) {
            row.style.display = '';
            found = true;
        } else {
            row.style.display = 'none';
        }
    });

    document.getElementById('searchHint').textContent = found ? '' : 'No matching products found.';
});

</script>

</body>
</html>