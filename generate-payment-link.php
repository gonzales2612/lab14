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

// Fetch products and prices
try {
    $products = \Stripe\Product::all(['active' => true]); // Fetch active products
} catch (\Stripe\Exception\ApiErrorException $e) {
    die("Error fetching data: " . $e->getMessage());
}

// Prepare line items
$line_items = [];
foreach ($products->data as $product) {
    if (isset($product->default_price)) {
        array_push($line_items, [
            'price' => $product->default_price,
            'quantity' => 1
        ]);
    }
}

// Create payment link
try {
    $payment_link = \Stripe\PaymentLink::create([
        'line_items' => $line_items
    ]);
    
    // Output the payment link URL
    echo "<h1>Payment Link Created Successfully!</h1>";
    echo "<p>Click below to pay:</p>";
    echo "<a href='" . htmlspecialchars($payment_link->url) . "' target='_blank'>Pay Now</a>";
} catch (\Stripe\Exception\ApiErrorException $e) {
    die("Error creating payment link: " . $e->getMessage());
}
?>
