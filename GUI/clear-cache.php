<?php
// Clear all PHP cache and session data
session_start();

// Clear session
session_unset();
session_destroy();

// Clear opcode cache if available
if (function_exists('opcache_reset')) {
    opcache_reset();
}

// Clear any other caches
if (function_exists('apc_clear_cache')) {
    apc_clear_cache();
}

echo "<!DOCTYPE html><html><head><title>Cache Cleared</title></head><body>";
echo "<h1>✅ Cache Cleared Successfully!</h1>";
echo "<p>All caches have been cleared. You can now test the application.</p>";
echo "<hr>";
echo "<p><a href='test-db.php' style='background: #850E35; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>Test Database Connection</a></p>";
echo "<p><a href='index.php' style='background: #EE6983; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>Go to Home Page</a></p>";
echo "<p><a href='login.php' style='background: #FFC4C4; color: #850E35; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>Go to Login Page</a></p>";
echo "</body></html>";
?>
