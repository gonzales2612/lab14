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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = $_POST['customer_id'] ?? null;
    $selectedProducts = $_POST['products'] ?? [];

    // Validate input
    if (!$customerId) {
        die("Error: No customer selected.");
    }
    if (empty($selectedProducts)) {
        die("Error: No products selected.");
    }

    try {
        // Create a draft invoice
        $invoice = \Stripe\Invoice::create([
            'customer' => $customerId,
        ]);

        foreach ($selectedProducts as $priceId) {
            // Retrieve the price object
            $price = \Stripe\Price::retrieve($priceId);

            // Check if the price type is 'one_time'
            if ($price->type !== 'one_time') {
                echo "Skipping price ID {$priceId}: Not a 'one_time' price.<br>";
                continue; // Skip this price
            }

            // Create an invoice line item
            \Stripe\InvoiceItem::create([
                'customer' => $customerId,
                'price' => $priceId,
                'invoice' => $invoice->id,
            ]);
        }

        // Finalize the invoice (use instance method, not static)
        $finalizedInvoice = $invoice->finalizeInvoice();

        // Output links to PDF and payment
        echo "<h1>Invoice Created Successfully!</h1>";
        echo "<p>Invoice ID: " . htmlspecialchars($finalizedInvoice->id) . "</p>";
        echo '<a href="' . htmlspecialchars($finalizedInvoice->invoice_pdf) . '" target="_blank">Download PDF</a><br>';
        echo '<a href="' . htmlspecialchars($finalizedInvoice->hosted_invoice_url) . '" target="_blank">Pay Invoice</a>';
    } catch (\Stripe\Exception\ApiErrorException $e) {
        die("Error creating invoice: " . $e->getMessage());
    }
} else {
    echo "Invalid request.";
}
?>
