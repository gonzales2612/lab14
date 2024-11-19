<?php
// Include Stripe PHP SDK and Dotenv
require 'vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Set Stripe Secret Key from .env
$stripeSecretKey = $_ENV['STRIPE_SECRET_KEY'] ?? null;
if (!$stripeSecretKey) {
    die("Stripe secret key not set in the .env file.");
}

\Stripe\Stripe::setApiKey($stripeSecretKey);

// Fetch products from Stripe
try {
    $products = \Stripe\Product::all(['active' => true]); // Fetch active products
    $prices = \Stripe\Price::all(['active' => true]);    // Fetch active prices
    
    // Map product prices by product ID
    $price_map = [];
    foreach ($prices->data as $price) {
        if (isset($price->product)) {
            $price_map[$price->product] = $price;
        }
    }
} catch (\Stripe\Exception\ApiErrorException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .product {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        .product img {
            max-width: 100px;
            margin-right: 20px;
            border-radius: 5px;
        }
        .product-details {
            flex: 1;
        }
        .product-price {
            font-weight: bold;
            color: #27ae60;
        }
    </style>
</head>
<body>
    <h1>Product List</h1>

    <?php foreach ($products->data as $product): ?>
        <?php 
        // Skip products without associated prices
        if (!isset($price_map[$product->id])) {
            continue;
        }

        // Get price information
        $price = $price_map[$product->id];
        $formatted_price = number_format($price->unit_amount / 100, 2);
        ?>
        <div class="product">
            <img src="<?= htmlspecialchars($product->images[0] ?? 'https://via.placeholder.com/100') ?>" alt="<?= htmlspecialchars($product->name) ?>">
            <div class="product-details">
                <h2><?= htmlspecialchars($product->name) ?></h2>
                <p><?= htmlspecialchars($product->description ?? 'No description available.') ?></p>
                <p class="product-price">$<?= $formatted_price ?></p>
            </div>
        </div>
    <?php endforeach; ?>
</body>
</html>
