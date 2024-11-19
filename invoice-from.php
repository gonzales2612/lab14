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

// Fetch customers and products
try {
    $customers = \Stripe\Customer::all(['limit' => 10]); // Fetch 10 customers
    $products = \Stripe\Product::all(['active' => true]); // Fetch active products
    $prices = \Stripe\Price::all(['active' => true]);    // Fetch active prices
} catch (\Stripe\Exception\ApiErrorException $e) {
    die("Error fetching data: " . $e->getMessage());
}

// Map product prices by product ID, only including 'one_time' prices
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
    <title>Create Invoice</title>
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
    <h1>Create Invoice</h1>
    <form action="generate-invoice.php" method="POST">
        <label for="customer">Select Customer</label>
        <select id="customer" name="customer_id" required>
            <option value="">-- Select Customer --</option>
            <?php foreach ($customers->data as $customer): ?>
                <option value="<?= htmlspecialchars($customer->id) ?>">
                    <?= htmlspecialchars($customer->name ?: $customer->email) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <h2>Products</h2>
        <?php foreach ($products->data as $product): ?>
            <?php if (isset($price_map[$product->id])): ?>
                <label>
                    <input type="checkbox" name="products[]" value="<?= htmlspecialchars($price_map[$product->id]->id) ?>">
                    <?= htmlspecialchars($product->name) ?> - 
                    $<?= number_format($price_map[$product->id]->unit_amount / 100, 2) ?>
                </label>
            <?php endif; ?>
        <?php endforeach; ?>

        <button type="submit">Generate Invoice</button>
    </form>
</body>
</html>
