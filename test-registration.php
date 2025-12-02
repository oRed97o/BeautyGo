<?php
// Test script to verify registration works
require_once __DIR__ . '/db_connection/config.php';

$conn = getDbConnection();

// Test 1: Check table structure
echo "=== CUSTOMERS TABLE STRUCTURE ===\n";
$result = $conn->query("DESCRIBE customers");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . " (" . ($row['Null'] == 'NO' ? 'NOT NULL' : 'NULLABLE') . ")\n";
}

echo "\n=== RECENT REGISTRATIONS ===\n";
$result = $conn->query("SELECT customer_id, cstmr_email, ST_X(customer_location) as lng, ST_Y(customer_location) as lat FROM customers ORDER BY customer_id DESC LIMIT 3");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['customer_id'] . " | Email: " . $row['cstmr_email'] . " | Lat: " . $row['lat'] . " | Lng: " . $row['lng'] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

// Test 2: Check if coordinates are working
echo "\n=== DATABASE CONNECTION TEST ===\n";
echo "Database: " . DB_NAME . "\n";
echo "Host: " . DB_HOST . "\n";
echo "Connected successfully\n";
?>
