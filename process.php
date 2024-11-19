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

// Check if form data is set
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $line1 = $_POST['line1'];
    $line2 = $_POST['line2'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $country = $_POST['country'];
    $postal_code = $_POST['postal_code'];

    try {
        // Create a customer in Stripe
        $customer = \Stripe\Customer::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => [
                'line1' => $line1,
                'line2' => $line2,
                'city' => $city,
                'state' => $state,
                'country' => $country,
                'postal_code' => $postal_code,
            ],
        ]);

        echo "<h1>Customer Created Successfully!</h1>";
        echo "<p>Name: " . htmlspecialchars($customer->name) . "</p>";
        echo "<p>Email: " . htmlspecialchars($customer->email) . "</p>";
        echo "<p>Phone: " . htmlspecialchars($customer->phone) . "</p>";
        echo "<p>Customer ID: " . htmlspecialchars($customer->id) . "</p>";
    } catch (\Stripe\Exception\ApiErrorException $e) {
        echo "Error creating customer: " . $e->getMessage();
    }
} else {
    echo "Invalid request.";
}
?>
