<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$conn = new mysqli("localhost", "root", "", "animzmax_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);


$userId = (int)$_SESSION['user_id'];
$viewMode = $_GET['view'] ?? 'unread';

$successMsg = '';
$errorMsg = '';

// Get admin info from admin table (assuming single admin)
$adminRes = $conn->query("SELECT id, username, email FROM admins LIMIT 1");
if ($adminRes && $adminRes->num_rows > 0) {
    $adminUser = $adminRes->fetch_assoc();
} else {
    $errorMsg = "Admin user not found. Please contact support.";
    $adminUser = null;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $zip = trim($_POST['zip'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name) || empty($email)) {
        $errorMsg = "Name and Email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = "Invalid email format.";
    } else {
        $avatarPath = '';
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/avatars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileTmp = $_FILES['avatar']['tmp_name'];
            $fileName = basename($_FILES['avatar']['name']);
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($fileExt, $allowed)) {
                $errorMsg = "Invalid avatar file type. Allowed: jpg, jpeg, png, gif.";
            } else {
                $avatarPath = $uploadDir . uniqid('avatar_') . '.' . $fileExt;
                if (!move_uploaded_file($fileTmp, $avatarPath)) {
                    $errorMsg = "Failed to upload avatar.";
                }
            }
        }

        if (empty($avatarPath)) {
            // Keep existing avatar
            $res = $conn->prepare("SELECT avatar FROM users WHERE user_id = ?");
            $res->bind_param("i", $userId);
            $res->execute();
            $result = $res->get_result();
            $row = $result->fetch_assoc();
            $avatarPath = $row['avatar'] ?? '';
            $res->close();
        }

        if (!$errorMsg) {
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, avatar=?, address=?, city=?, zip=?, phone=? WHERE user_id=?");
            $stmt->bind_param("sssssssi", $name, $email, $avatarPath, $address, $city, $zip, $phone, $userId);
            if ($stmt->execute()) {
                $_SESSION['user_name'] = $name;
                $successMsg = "Profile updated successfully.";
                header("Location: loginpage.php?update=success");
                exit();
            } else {
                $errorMsg = "Update failed: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

/// Handle sending message to admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $msgText = trim($_POST['message'] ?? '');

    if (empty($msgText)) { 
        $errorMsg = "Message cannot be empty.";
    } elseif (!$adminUser) {
        $errorMsg = "Admin user not found. Cannot send message.";
    } else {
        // Correct SQL: exclude 'id' since it's AUTO_INCREMENT
        $stmt = $conn->prepare("INSERT INTO messagess (sender_id, receiver_id, message, sent_at, is_read) VALUES (?, ?, ?, NOW(), 0)");
        $stmt->bind_param("iis", $userId, $adminUser['id'], $msgText);
        
        if ($stmt->execute()) {
            $successMsg = "Message sent successfully to admin.";
            header("Location: loginpage.php?message=sent");
            exit();
        } else {
            $errorMsg = "Failed to send message: " . $stmt->error;
        }
             $stmt->close();
            }
}


// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch orders
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch unread messages count (messages from admin to user)
$stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM messagess WHERE receiver_id = ? AND sender_id = ? AND is_read = 0");
$stmt->bind_param("ii", $userId, $adminUser['id']);
$stmt->execute();
$unreadMessages = $stmt->get_result()->fetch_assoc()['unread_count'] ?? 0;
$stmt->close();

// Fetch last message time from admin
$stmt = $conn->prepare("SELECT sent_at FROM messagess WHERE receiver_id = ? AND sender_id = ? ORDER BY sent_at DESC LIMIT 1");
$stmt->bind_param("ii", $userId, $adminUser['id']);
$stmt->execute();
$lastMessageTime = $stmt->get_result()->fetch_assoc()['sent_at'] ?? null;
$stmt->close();

// Fetch last 10 messages received from admin
$stmt = $conn->prepare("SELECT m.*, a.username AS sender_name FROM messagess m JOIN admins a ON m.sender_id = a.id WHERE m.receiver_id = ? ORDER BY m.sent_at DESC LIMIT 10");
$stmt->bind_param("i", $userId);
$stmt->execute();
$receivedMessages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Mark unread messages from admin as read
$stmt = $conn->prepare("UPDATE messagess SET is_read = 1 WHERE receiver_id = ? AND sender_id = ? AND is_read = 0");
$stmt->bind_param("ii", $userId, $adminUser['id']);
$stmt->execute();
$stmt->close();

// Fetch notifications 
$sql = "SELECT content, created_at FROM notifications 
        WHERE target_type = 'all' 
           OR (target_type = 'selected' AND FIND_IN_SET(?, target_user_ids)) 
        ORDER BY created_at DESC
        LIMIT 20";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();



// Fetch user details to get email (used to fetch contact messages)
$userEmail = '';
$stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($userEmail);
$stmt->fetch();
$stmt->close();

$contactMessages = [];

if (!empty($userEmail)) {
    // Fetch contact messages for this user by email
    $stmt2 = $conn->prepare("SELECT id, name, email, message, submitted_at FROM contact_messages WHERE email = ? ORDER BY submitted_at DESC");
    $stmt2->bind_param("s", $userEmail);
    $stmt2->execute();
    $result = $stmt2->get_result();
    $contactMessages = $result->fetch_all(MYSQLI_ASSOC);
    $stmt2->close();

    // Now fetch replies for each contact message
    foreach ($contactMessages as &$contact) {
        $contactId = $contact['id'];
        $repliesStmt = $conn->prepare("SELECT id, reply_message, replied_at FROM contact_replies WHERE contact_id = ? ORDER BY replied_at ASC");
        $repliesStmt->bind_param("i", $contactId);
        $repliesStmt->execute();
        $repliesResult = $repliesStmt->get_result();
        $contact['replies'] = $repliesResult->fetch_all(MYSQLI_ASSOC);
        $repliesStmt->close();
    }
    unset($contact); // break reference
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>User Dashboard - Animz Max</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>

    /* Base Styles */
    body {
      font-family: Arial, sans-serif;
      background-color: #dad7cd;
      color: #333;
      margin: 0;
      
    }

    header {
      width: 98.5%;
      background: #00573f;
      color: white;
      padding: 1rem;
      display: flex;
      justify-content: space-between;
      position: sticky;
      top: 0;
     
    }

    header .logo a {
      color: white;
      font-weight: bold;
      font-size: 3rem;
      text-decoration: none;
    }

    header nav a {
      color: white;
      margin-left: 1rem;
      font-size: 1.4rem;
      text-decoration: none;
    }

    main {
      max-width: 900px;
      margin: 2rem auto;
    }

    /* Profile Section */
    .profile-header {
      background-color: #fff;
      border-radius: 14px;
      display: flex;
      align-items: center;
      gap: 1rem;
      border-bottom: 3px solid #ccc;
      padding: 1rem;
    }

    .avatar {
      width: 90px;
      height: 90px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #00573f;
    }

    .logout-container button {
      display: inline-flex;     
      background: #e63946;
      border: none;
      padding: 0.5rem 1rem;
      color: white;
      font-weight: bold;
      cursor: pointer;
      border-radius: 4px;
    }

    /* Dashboard Cards */
    .dashboard-summary {
      display: flex;
      gap: 1rem;
      margin-top: 2rem;
      flex-wrap: wrap;
    }

    .summary-card {
      flex: 1 1 30%;
      background: #f1f1f1;
      padding: 1rem;
      border-radius: 8px;
      box-shadow: 0 0 6px rgba(0, 0, 0, 0.1);
      min-width: 250px;
      max-height: 350px;
      overflow-y: auto;
    }

    .summary-card h3 {
      margin-top: 0;
      color: #00573f;
      text-align: center;
    }
/* Orders Table */
table.orders-table {
  border-radius:8px;
  background-color: #fff;
  width: 100%; /* or set a max-width if you want */
  border-collapse: collapse;
  margin: 0 auto;  /* centers table horizontally */
}

table.orders-table th,
table.orders-table td {
  border: 1px solid #ccc;
  padding: 8px 20px;
  text-align: center;
}

table.orders-table th {
  background: #00573f;
  color: white;
}


    /* Toggle Buttons */
   #toggleMessageForm,
   #toggleUpdateProfile {
    display: none;
  }
  .toggle-view-btn,
  label[for="toggleMessageForm"],
  label[for="toggleUpdateProfile"],
  .send-button {
  cursor: pointer;
  background: white;
  color: #00573f;
  font-weight: bold;
  padding: 8px 16px;
  border: 2px solid #00573f;
  border-radius: 8px;
  margin-top: 1rem;
  display: inline-flex;     
  transition: background 0.3s, color 0.3s;
  text-decoration: none;
}

.toggle-view-btn:hover,
label[for="toggleMessageForm"]:hover,
label[for="toggleUpdateProfile"]:hover,
.send-button:hover {
  background: #00573f;
  color: white;
}


    /* Messages */
    .message-box {
      border: 1px solid #ccc;
      padding: 10px;
      border-radius: 8px;
      margin-bottom: 10px;
      background: #f9f9f9;
      transition: all 0.3s;
    }

    .mark-read-btn {
      background: #00573f;
      color: white;
      border: none;
      padding: 4px 8px;
      border-radius: 4px;
      cursor: pointer;
      margin-left: 10px;
      transition: background 0.3s;
    }

    .mark-read-btn:hover {
      background: #003f2b;
    }

    /* Success & Error Messages */
    .success-message,
    .error-message {
      padding: 0.5rem;
      border-radius: 4px;
      margin: 1rem 0;
    }

    .success-message {
      background: #d4edda;
      border: 1px solid #c3e6cb;
      color: #155724;
    }

    .error-message {
      background: #f8d7da;
      border: 1px solid #f5c6cb;
      color: #721c24;
    }

    /* Update Profile Form */
    .update-profile-form {
      background: #f9f9f9;
      padding: 1.5rem;
      border: 1px solid #ddd;
      border-radius: 8px;
      max-width: 500px;
      margin: 1rem auto;
      display: flex;
      flex-direction: column;
      gap: 0.8rem;
    }

    .update-profile-form h3 {
      margin-top: 0;
      color: #00573f;
      text-align: center;
    }

    .update-profile-form label {
      font-weight: bold;
    }

    .update-profile-form input[type="text"],
    .update-profile-form input[type="email"],
    .update-profile-form input[type="file"] {
      padding: 0.6rem;
      border: 1px solid #ccc;
      border-radius: 4px;
      width: 95%;
    }

    .update-button {
      background-color: #00573f;
      color: white;
      border: none;
      padding: 0.8rem 1.5rem;
      cursor: pointer;
      border-radius: 4px;
      font-weight: bold;
      transition: background-color 0.3s ease;
    }

    .update-button:hover {
      background-color: #004630;
    }

    /* Send Message Form Styles */
#sendMessageForm {
  margin-top: 1.5rem;
  padding: 1.5rem;
  background-color: #fafafa;
  border: 1px solid #ddd;
  border-radius: 8px;
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}

#sendMessageForm h3 {
  margin-top: 0;
  color: #00573f;
  text-align: center;
}

.send-message-form textarea {
  width: 95%;
  padding: 0.8rem;
  border: 1px solid #ccc;
  border-radius: 4px;
  resize: vertical;
  margin-bottom: 1rem;
  font-family: inherit;
  font-size: 1rem;
}

.send-message-form button.send-button {
  display: block;
  margin: 0 auto;
}
/* Notifications Section Styles */
.dashboard-summary .summary-card h3 {
  margin-top: 0;
  color: #00573f;
  text-align: center;
}

.dashboard-summary .summary-card p {
  font-size: 1.1rem;
  color: #333;
  margin-bottom: 0.5rem;
}

.dashboard-summary .summary-card small {
  display: block;
  font-size: 0.9rem;
  color: #666;
  text-align: center;
}

/* Notification Box Enhancement */
.notification-box {
  background-color: #f9f9f9;
  border: 1px solid #ddd;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1rem;
  box-shadow: 0 0 6px rgba(0, 0, 0, 0.05);
}

.notification-box p {
  margin: 0 0 0.5rem 0;
  color: #333;
}

.notification-box small {
  color: #666;
  font-size: 0.9rem;
}
/* contact-message */
#userContactMessages {
  max-width: 700px;
  margin: 1.5rem auto;
  padding: 1rem 1.5rem;
  background: #f9f9f9;
  border-radius: 8px;
  box-shadow: 0 0 10px rgb(0 0 0 / 0.05);
  font-family: Arial, sans-serif;
  color: #003322;
}

#userContactMessages h2 {
  font-size: 1.8rem;
  margin-bottom: 1rem;
  border-bottom: 2px solid #00573f;
  padding-bottom: 0.3rem;
}

.no-messages {
  font-style: italic;
  color: #777;
  text-align: center;
  margin: 2rem 0;
}

.message-box {
  background: #ffffff;
  border: 1px solid #c2d6d6;
  border-radius: 6px;
  padding: 1rem 1.2rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 2px 4px rgb(0 0 0 / 0.05);
}

.message-header {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #004d40;
  font-size: 0.9rem;
}

.message-date {
  font-weight: 400;
  font-size: 0.85rem;
  color: #666;
  font-style: italic;
}

.message-content {
  font-size: 1rem;
  line-height: 1.5;
  white-space: pre-wrap;
  margin-bottom: 0.7rem;
  color: #222;
}

.replies {
  border-top: 1px solid #c2d6d6;
  padding-top: 0.7rem;
  margin-top: 0.7rem;
  font-size: 0.9rem;
  color: #00573f;
}

.reply {
  background: #e9f5f3;
  border-radius: 5px;
  padding: 0.6rem 1rem;
  margin-top: 0.6rem;
  box-shadow: inset 0 0 5px rgb(0 87 63 / 0.1);
}

.reply-date {
  font-size: 0.8rem;
  color: #004d40;
  font-style: italic;
  margin-top: 0.4rem;
}

.no-replies {
  font-style: italic;
  color: #999;
  margin-top: 0.5rem;
}

    /* Footer */
    .footer {
      width: auto;
      background-color: #00573f;
      color: white;
      padding: 40px 20px 10px;
    }

    .footer-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      gap: 30px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .footer-logo {
      font-weight: bold;
      color: white;
      font-size: 3rem;
      text-decoration: none;
    }

    .footer-links ul {
      list-style: none;
      padding: 0;
    }

    .footer-links ul li {
      margin-bottom: 8px;
    }

    .footer-links ul li a {
      color: white;
      text-decoration: none;
    }

    .footer-links ul li a:hover {
      text-decoration: underline;
    }

    .footer-social .social-icons a {
      color: white;
      margin-right: 10px;
      font-size: 20px;
    }

    .footer-bottom {
      text-align: center;
      padding: 15px 0 0;
      font-size: 14px;
      border-top: 1px solid rgba(255, 255, 255, 0.2);
    }
  </style>
</head>

<body>
    <header>
        <div class="logo"><a href="index.html">Animz Max</a></div>
        <nav>
            <a href="search.php" title="Search">🔍</a>
            <a href="product.php" title="Products">🧥</a>
            <a href="cart.php" title="Cart">🛒</a>
            </nav>
    </header>

    <main>
        <section class="profile-header">
            <img src="<?php echo htmlspecialchars($user['avatar'] ?: 'default-avatar.png'); ?>" alt="Avatar" class="avatar" />
            <div>
                <h1><?php echo htmlspecialchars($user['name']); ?></h1>
                <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
                <p>Member since: <?php echo date("F j, Y", strtotime($user['created_at'])); ?></p>
            </div>
            <div class="logout-container">
                <form action="logout.php" method="POST" onsubmit="return confirm('Are you sure you want to logout?');">
                    <button type="submit">Logout</button>
                </form>
            </div>
            <input type="checkbox" id="toggleUpdateProfile" />
            <label for="toggleUpdateProfile">Update Profile</label>
        </section>

        <?php if (!empty($successMsg)): ?>
            <div class="success-message">✅ <?php echo htmlspecialchars($successMsg); ?></div>
        <?php elseif (!empty($errorMsg)): ?>
            <div class="error-message">⚠️ <?php echo htmlspecialchars($errorMsg); ?></div>
        <?php elseif (isset($_GET['update']) && $_GET['update'] === 'success'): ?>
            <div class="success-message">✅ Profile updated successfully.</div>
        <?php elseif (isset($_GET['message']) && $_GET['message'] === 'sent'): ?>
            <div class="success-message">✅ Message sent successfully.</div>
        <?php endif; ?>

        <section id="updateProfileForm" style="display:none;">
            <form method="POST" enctype="multipart/form-data" novalidate class="update-profile-form">
                <h3>Update Profile Information</h3>
                <label>Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required placeholder="Name" />
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required placeholder="Email" />
                <label>Avatar</label>
                <input type="file" name="avatar" accept="image/*" />
                <label>Address</label>
                <input type="text" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" placeholder="Address" />
                <label>City</label>
                <input type="text" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" placeholder="City" />
                <label>ZIP</label>
                <input type="text" name="zip" value="<?php echo htmlspecialchars($user['zip'] ?? ''); ?>" placeholder="ZIP" />
                <label>Phone</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Phone" />
                <button type="submit" name="update_profile" class="update-button">Update Profile</button>
            </form>
        </section>

        <section class="dashboard-summary">
            <div class="summary-card">
                <h3>📩 Messages</h3>
                <p>You have <strong><?php echo (int)$unreadMessages; ?> unread message(s)</strong>.</p>
                <?php if ($viewMode === 'unread'): ?>
                    <a href="loginpage.php?view=all" class="toggle-view-btn">View Previous Messages</a>
                <?php else: ?>
                    <a href="loginpage.php?view=unread" class="toggle-view-btn">Hide Previous Messages</a>
                <?php endif; ?>

                <ul style="list-style:none; padding:0; max-height:150px; overflow:auto;">
                    <?php if (count($receivedMessages) > 0): ?>
                        <?php foreach ($receivedMessages as $msg): ?>
                            <?php if ($viewMode === 'all' || $msg['is_read'] == 0): ?>
                                <li class="message-box" id="message-<?php echo $msg['id']; ?>">
                                    <strong><?php echo htmlspecialchars($msg['sender_name']); ?></strong>
                                    <em>(<?php echo date("M j, Y H:i", strtotime($msg['sent_at'])); ?>)</em>:
                                    <span><?php echo nl2br(htmlspecialchars(mb_strimwidth($msg['message'], 0, 40, "..."))); ?></span>
                                    <?php if ($msg['is_read'] == 0): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="mark_read_id" value="<?php echo $msg['id']; ?>">
                                            <button type="submit" class="mark-read-btn">Mark as Read</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: green; font-size: 0.9em;">(Read)</span>
                                    <?php endif; ?>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>No messages received yet.</li>
                    <?php endif; ?>
                </ul>

                <input type="checkbox" id="toggleMessageForm" />
                <label for="toggleMessageForm">Send New Message</label>
            </div>
        </section>

        <section id="sendMessageForm" style="display:none; margin-top:1rem;" class="send-message-to-admin">
            <h3>Send a Message to Animz Max</h3>
            <?php if (!$adminUser): ?>
                <p style="color:red;">Cannot send messages: Admin user not found.</p>
            <?php else: ?>
                <form method="POST" class="send-message-form">
                    <textarea name="message" rows="4" placeholder="Write your message here..." required></textarea><br />
                    <button type="submit" name="send_message" class="send-button">Send Message</button>
                </form>
            <?php endif; ?>
        </section>
<!-- notifications sections -->
   <section class="dashboard-summary">
    <div class="summary-card">
        <h3>🔔 Notifications</h3>
        <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-box">
                    <p><?php echo htmlspecialchars($notification['content']); ?></p>
                    <?php if (!empty($notification['time'])): ?>
                        <small>On <?php echo date("M j, Y H:i", strtotime($notification['time'])); ?></small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No notifications available.</p>
        <?php endif; ?>
    </div>
</section>

        <div id="userContactMessages" class="section">
            <h2>My Contact Messages</h2>
            <?php if (count($contactMessages) === 0): ?>
                <p class="no-messages">You have not submitted any contact messages.</p>
            <?php else: ?>
                <?php foreach ($contactMessages as $msg): ?>
                    <div class="message-box" role="region">
                        <div class="message-header">
                            <strong>Message ID:</strong> <?= htmlspecialchars($msg['id']) ?>
                            <span class="message-date">(Submitted: <?= htmlspecialchars($msg['submitted_at']) ?>)</span>
                        </div>
                        <div class="message-content"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>

                        <?php if (!empty($msg['replies'])): ?>
                            <div class="replies">
                                <strong>Replies:</strong>
                                <?php foreach ($msg['replies'] as $reply): ?>
                                    <div class="reply">
                                        <div class="message-content"><?= nl2br(htmlspecialchars($reply['reply_message'])) ?></div>
                                        <div class="reply-date">Replied at: <?= htmlspecialchars($reply['replied_at']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="no-replies"><em>No replies yet.</em></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <section class="ordersSection">
            <div class="order-list">
                <h3>🛒 Orders</h3>
                <?php if (count($orders) > 0): ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>User ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>City</th>
                                <th>ZIP</th>
                                <th>Payment Method</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                    <td><?php echo htmlspecialchars($order['user_id']); ?></td>
                                    <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['email']); ?></td>
                                    <td><?php echo htmlspecialchars($order['address']); ?></td>
                                    <td><?php echo htmlspecialchars($order['city']); ?></td>
                                    <td><?php echo htmlspecialchars($order['zip']); ?></td>
                                    <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                                    <td>₹<?php echo number_format($order['order_total'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($order['status']); ?></td>
                                    <td><?php echo date("M j, Y", strtotime($order['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>You have no orders yet.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>


<script>
    // Toggle Update Profile form visibility
    const toggleCheckbox = document.getElementById('toggleUpdateProfile');
    const updateForm = document.getElementById('updateProfileForm');

    function toggleUpdateForm() {
      updateForm.style.display = toggleCheckbox.checked ? 'block' : 'none';
    }

    toggleCheckbox.addEventListener('change', toggleUpdateForm);
    toggleUpdateForm();

    // Toggle Send Message form visibility
    const messageToggle = document.getElementById('toggleMessageForm');
    const messageForm = document.getElementById('sendMessageForm');

    function toggleMessageForm() {
      messageForm.style.display = messageToggle.checked ? 'block' : 'none';
    }

    messageToggle.addEventListener('change', toggleMessageForm);
    toggleMessageForm();

    // Hide message after marking as read
    document.querySelectorAll('.mark-read-btn').forEach(button => {
      button.addEventListener('click', function (e) {
        e.preventDefault();
        let form = this.closest('form');
        let messageBox = this.closest('.message-box');
        let formData = new FormData(form);

        fetch(window.location.href, { method: 'POST', body: formData })
          .then(response => {
            if (response.ok) {
              messageBox.style.display = 'none';
            }
          });
      });
    });
  </script>
</body>
    <footer class="footer">
  <div class="footer-container">
    <div class="footer-logo">
      <a href="index.html" class="footer-logo">Animz Max</a>
    </div>
    <div class="footer-links">

      <ul>
        <li><a href="index.html">Home</a></li>
        <li><a href="product.php">Products</a></li>
        <li><a href="contact.php">Contact</a></li>
        <li><a href="privacy.html">Privacy Policy</a></li>
          <li><a href="terms.html">Terms of Use</a></li>
          <li><a href="returns.html">Returns</a></li>
        <ul>
        </div>

    <div class="footer-social">
      <h4>Follow Us</h4>
      <div class="social-icons">
        <a href="#"><i class="fab fa-instagram"></i></a>
        <a href="#"><i class="fab fa-x-twitter"></i></a>
        <a href="#"><i class="fab fa-facebook"></i></a>
      </div>
    </div>
  </div>

  <div class="footer-bottom">
    <p>&copy; 2025 Animz Max. All rights reserved.</p>
  </div>
</footer>
</html>