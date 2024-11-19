<?php
require 'vendor/autoload.php';
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Stripe API Key
$stripeSecretKey = $_ENV['STRIPE_SECRET_KEY'] ?? null;
if (!$stripeSecretKey) {
    die("Stripe secret key not set in the .env file.");
}

\Stripe\Stripe::setApiKey($stripeSecretKey);

// Fetch active products and prices
try {
    $products = \Stripe\Product::all(['active' => true]); // Fetch active products
    $prices = \Stripe\Price::all(['active' => true]);    // Fetch active prices
} catch (\Stripe\Exception\ApiErrorException $e) {
    die("Error fetching data: " . $e->getMessage());
}

// Map prices to product IDs
$price_map = [];
foreach ($prices->data as $price) {
    if (isset($price->product) && $price->type === 'one_time') {
        $price_map[$price->product] = $price;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Payment Link</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        form {
            max-width: 600px;
            margin: auto;
        }
        select, input, button {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            background-color: #27ae60;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #219150;
        }
    </style>
</head>
<body>
    <h1>Generate Payment Link</h1>
    <form action="generate-payment-link.php" method="POST">
        <h2>Select Products</h2>
        <?php foreach ($products->data as $product): ?>
            <?php if (isset($price_map[$product->id])): ?>
                <label>
                    <input type="checkbox" name="products[]" value="<?= htmlspecialchars($price_map[$product->id]->id) ?>">
                    <?= htmlspecialchars($product->name) ?> - 
                    $<?= number_format($price_map[$product->id]->unit_amount / 100, 2) ?>
                </label><br>
            <?php endif; ?>
        <?php endforeach; ?>

        <button type="submit">Generate Payment Link</button>
    </form>
</body>
</html>
