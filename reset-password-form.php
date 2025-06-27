<?php
session_start();

// DB Connection
$host = 'localhost';
$db = 'animzmax_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get token from URL
if (!isset($_GET['token'])) {
    die('Invalid or missing reset token.');
}

$token = $_GET['token'];

// Check if token is valid
$stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ?");
$stmt->execute([$token]);
$userData = $stmt->fetch();

if (!$userData) {
    die('Invalid or expired token.');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($email) || empty($new_password) || empty($confirm_password)) {
        $error = "Please fill all fields.";
    } elseif ($email !== $userData['email']) {
        $error = "Email does not match the account.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        // Update password and clear the token
        $updateStmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL WHERE user_id = ?");
        $updateStmt->execute([$hashed_password, $userData['user_id']]);

        $success = "Password successfully reset. You can now <a href='login.php'>login</a>.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <style>
        /* Full Page Centering */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        /* Form Container */
        form {
            background-color: #fff;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        /* Heading */
        h2 {
            text-align: center;
            color: #00573f;
            margin-bottom: 10px;
        }

        /* Labels */
        label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* Inputs */
        input[type="password"],
        input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        /* Button */
        button {
            padding: 10px;
            background-color: #00573f;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #004730;
        }

        /* Messages */
        p {
            text-align: center;
        }

        p[style*="color: red"] {
            color: red;
            font-weight: bold;
        }

        p[style*="color: green"] {
            color: green;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <?php if (isset($error)) : ?>
        <p style="color: red;"><?= $error ?></p>
    <?php elseif (isset($success)) : ?>
        <p style="color: green;"><?= $success ?></p>
    <?php else : ?>
        <form method="POST">
            <h2>Reset Password</h2>
            <div>
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>

            <div>
                <label>New Password:</label>
                <input type="password" name="new_password" required>
            </div>

            <div>
                <label>Confirm Password:</label>
                <input type="password" name="confirm_password" required>
            </div>

            <button type="submit">Reset Password</button>
        </form>
    <?php endif; ?>
</body>

</html>
