<?php
require_once 'db_connection/config.php';

// Get the last registered business
$conn = getDbConnection();
$sql = "SELECT business_id, business_name, business_email, business_address, 
        ST_X(location) as longitude, ST_Y(location) as latitude, location 
        FROM businesses 
        ORDER BY business_id DESC 
        LIMIT 5";

$result = $conn->query($sql);

echo "<h2>Last 5 Registered Businesses</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Address</th><th>Latitude</th><th>Longitude</th><th>Raw Location</th></tr>";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $lat = $row['latitude'] ?? 'NULL';
        $lng = $row['longitude'] ?? 'NULL';
        $location = $row['location'] ?? 'NULL';
        echo "<tr>";
        echo "<td>{$row['business_id']}</td>";
        echo "<td>{$row['business_name']}</td>";
        echo "<td>{$row['business_email']}</td>";
        echo "<td>{$row['business_address']}</td>";
        echo "<td>$lat</td>";
        echo "<td>$lng</td>";
        echo "<td>$location</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='7'>No businesses found</td></tr>";
}

echo "</table>";
?>
