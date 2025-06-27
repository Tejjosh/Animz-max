<?php
session_start();
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "animzmax_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize cart
if (!isset($_SESSION['cart'])) {
  $_SESSION['cart'] = [];
}

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
  $product_id = $_POST['product_id'];
  $product_name = $_POST['product_name'];
  $price = floatval($_POST['price']);

  if (isset($_SESSION['cart'][$product_id])) {
    $_SESSION['cart'][$product_id]['quantity'] += 1;
  } else {
    $_SESSION['cart'][$product_id] = [
      'product_id' => $product_id,
      'product_name' => $product_name,
      'price' => $price,
      'quantity' => 1
    ];
  }
  header("Location: search.php");
  exit();
}

// Fetch products from database
$sql = "SELECT * FROM products";
$result = $conn->query($sql);

$products = [];
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $products[] = $row;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Search - Animz Max</title>
  <link rel="stylesheet" href="search.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
<header>
  <div class="logo">Animz Max</div>
  <nav>
    <a href="index.html">Home</a>
    <a href="product.php">🧥</a>
    <a href="cart.php" title="Cart">🛒<span id="cart-count"></span></a>
    <a href="loginpage.php" title="Login">👤</a>

  </nav>
</header>

<section class="search-section">
  <input type="text" id="searchInput" placeholder="Search products...">
<p id="searchHint">Start typing to search for products...</p>
</section>

<div class="product-section">
<h1>Our Products</h1>
<div class="product-grid" id="productGrid">
    <?php
    foreach ($products as $product) {
      echo '<div class="product-card" style="display: none;" data-name="' . htmlspecialchars(strtolower($product['product_name'])) . '" data-category="' . htmlspecialchars(strtolower($product['category'])) . '">';
      echo '<img src="' . htmlspecialchars($product['image_url']) . '" alt="' . htmlspecialchars($product['product_name']) . '">';
      echo '<h2>' . htmlspecialchars($product['product_name']) . '</h2>';
      echo '<p>' . htmlspecialchars($product['description']) . '</p>';
      echo '<p class="price">₹' . htmlspecialchars($product['price']) . '</p>';
      echo '<form method="POST" action="">';
      echo '<input type="hidden" name="product_id" value="' . htmlspecialchars($product['product_id']) . '">';
      echo '<input type="hidden" name="product_name" value="' . htmlspecialchars($product['product_name']) . '">';
      echo '<input type="hidden" name="price" value="' . htmlspecialchars($product['price']) . '">';
      echo '<button type="submit" class="add-to-cart">Add to Cart</button>';
      echo '</form>';
      echo '</div>';
    }
    ?>
  </div>
</div>

<script>
  document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById("searchInput");
    const productCards = document.querySelectorAll(".product-card");

    function filterProducts() {
      const query = searchInput.value.trim().toLowerCase();

      productCards.forEach(card => {
        const name = card.getAttribute("data-name") || "";
        const category = card.getAttribute("data-category") || "";

        const matches = query && (
          name.includes(query) ||
          category.includes(query) ||
          name.startsWith(query) ||
          category.startsWith(query)
        );

        card.style.display = matches ? "block" : "none";
      });
    }

    searchInput.addEventListener("input", filterProducts);
  });
</script>

<footer class="footer">
  <div class="footer-container">
    <div class="logo">
      <a href="index.html" class="logo">Animz Max</a>
    </div>
    <div class="footer-links">
      <ul>
        <li><a href="index.html">Home</a></li>
        <li><a href="product.php">Products</a></li>
        <li><a href="contact.php">Contact</a></li>
        <li><a href="privacy.html">Privacy Policy</a></li>
        <li><a href="terms.html">Terms of Use</a></li>
        <li><a href="returns.html">Returns</a></li>
      </ul>
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
</body>
</html>