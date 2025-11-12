<?php
// Test database connection and verify data
require_once 'config.php';

echo "<h2>BeautyGo Database Connection Test</h2>";

// Test connection
try {
    $conn = getDbConnection();
    echo "✅ <strong style='color: green;'>Database connection successful!</strong><br><br>";
    
    // Test users table
    echo "<h3>Users (Customers):</h3>";
    $result = $conn->query("SELECT id, email, name FROM users");
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Email</th><th>Name</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['id']}</td><td>{$row['email']}</td><td>{$row['name']}</td></tr>";
        }
        echo "</table><br>";
    } else {
        echo "❌ <span style='color: red;'>No users found. Please run the database.sql file!</span><br><br>";
    }
    
    // Test businesses table
    echo "<h3>Businesses:</h3>";
    $result = $conn->query("SELECT id, email, business_name FROM businesses");
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Email</th><th>Business Name</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['id']}</td><td>{$row['email']}</td><td>{$row['business_name']}</td></tr>";
        }
        echo "</table><br>";
    } else {
        echo "❌ <span style='color: red;'>No businesses found. Please run the database.sql file!</span><br><br>";
    }
    
    // Test services table
    echo "<h3>Services:</h3>";
    $result = $conn->query("SELECT COUNT(*) as count FROM services");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Total services: <strong>{$row['count']}</strong><br><br>";
    }
    
    // Test staff table
    echo "<h3>Staff:</h3>";
    $result = $conn->query("SELECT COUNT(*) as count FROM staff");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Total staff members: <strong>{$row['count']}</strong><br><br>";
    }
    
    // Test password verification
    echo "<h3>Password Test:</h3>";
    $testUser = getUserByEmail('maria@beautygo.com');
    if ($testUser) {
        $passwordWorks = password_verify('password123', $testUser['password']);
        if ($passwordWorks) {
            echo "✅ <strong style='color: green;'>Password verification working!</strong><br>";
            echo "You can login with: maria@beautygo.com / password123<br><br>";
        } else {
            echo "❌ <strong style='color: red;'>Password verification failed!</strong><br><br>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Login Credentials:</h3>";
    echo "<strong>Customers:</strong><br>";
    echo "• maria@beautygo.com / password123<br>";
    echo "• john@beautygo.com / password123<br><br>";
    echo "<strong>Businesses:</strong><br>";
    echo "• elegance@beautygo.com / password123<br>";
    echo "• serenity@beautygo.com / password123<br>";
    echo "• classic@beautygo.com / password123<br><br>";
    
    echo "<a href='index.php' style='display: inline-block; background: #850E35; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Home Page</a>";
    echo " ";
    echo "<a href='login.php' style='display: inline-block; background: #EE6983; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login</a>";
    
} catch (Exception $e) {
    echo "❌ <strong style='color: red;'>Connection failed: " . $e->getMessage() . "</strong><br>";
    echo "<br><strong>Troubleshooting:</strong><br>";
    echo "1. Make sure MySQL is running<br>";
    echo "2. Check your database credentials in config.php<br>";
    echo "3. Run the database.sql file to create tables and insert data<br>";
}
?>
