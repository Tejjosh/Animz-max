<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "animzmax_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
<?php
session_start();
// Your database connection file

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = "Please enter both username and password.";
    } else {
        // Prepare statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, username, password_hash FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($admin_id, $admin_username, $password_hash);
            $stmt->fetch();

            if (password_verify($password, $password_hash)) {
                // Password correct, set session and redirect
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin_id;
                $_SESSION['admin_username'] = $admin_username;

                header("Location: admin_dashboard.php");
                exit;
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Login</title>
<style>
    /* Reset some default styles */
    * {
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #e0f2f1, #a5d6a7);
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
        color: #004d32;
    }

    .login-container {
        background: #ffffff;
        padding: 40px 30px;
        border-radius: 12px;
        box-shadow:
            0 8px 16px rgba(0, 0, 0, 0.1),
            0 4px 6px rgba(0, 0, 0, 0.05);
        width: 360px;
        max-width: 90vw;
        transition: transform 0.3s ease;
    }
    .login-container:hover {
        transform: translateY(-4px);
        box-shadow:
            0 12px 24px rgba(0, 0, 0, 0.15),
            0 6px 12px rgba(0, 0, 0, 0.1);
    }

    h2 {
        text-align: center;
        margin-bottom: 28px;
        font-weight: 700;
        font-size: 1.9rem;
        color: #00796b;
        letter-spacing: 1px;
    }

    label {
        display: block;
        font-weight: 600;
        margin-bottom: 6px;
        font-size: 0.95rem;
        color: #004d32;
    }

    input[type="text"],
    input[type="password"] {
        width: 100%;
        padding: 14px 18px;
        margin-bottom: 22px;
        border: 1.8px solid #b2dfdb;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
    }
    input[type="text"]:focus,
    input[type="password"]:focus {
        outline: none;
        border-color: #00796b;
        box-shadow: 0 0 6px #00796b66;
    }

    button {
        width: 100%;
        padding: 14px 0;
        background-color: #00796b;
        border: none;
        border-radius: 8px;
        font-size: 1.1rem;
        color: white;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 4px 12px #004d3266;
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
    }
    button:hover,
    button:focus {
        background-color: #004d32;
        box-shadow: 0 6px 18px #00301bcc;
        outline: none;
    }

    .error {
        color: #d32f2f;
        background: #ffebee;
        border: 1px solid #f44336;
        border-radius: 6px;
        padding: 12px 16px;
        margin-bottom: 20px;
        text-align: center;
        font-weight: 600;
        font-size: 0.95rem;
        user-select: none;
    }
</style>

</head>
<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required autocomplete="off" />

            <label for="password">Password:</label>
            <div style="position: relative;">
                <input type="password" name="password" id="password" required autocomplete="off" style="padding-right: 80px;" />
                <b<button type="button" class="toggle-password" aria-label="Toggle password visibility"  onclick="togglePassword()"
                 style="
                 position: absolute; 
                 right: 8px; 
                 top: 50%; 
                 transform: translateY(-50%);
                 background: none; 
                 border: none; 
                 color: #00796b; 
                 font-weight: 600; 
                 cursor: pointer;
                 padding: 2px 6px;
                 font-size: 0.85rem;
                 user-select: none;
                 line-height: 1;">Show
                 </button>
            </div>
            <button type="submit">Login</button>
        </form>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.toggle-password');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = 'Hide';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = 'Show';
            }
        }
    </script>
</body>

</html>