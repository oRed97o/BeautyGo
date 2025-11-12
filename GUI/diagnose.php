<?php
// Diagnostic script to check file contents and cache status
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>BeautyGo Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #FFF5E4; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔍 BeautyGo System Diagnostic</h1>
    
    <div class="section">
        <h2>1. PHP Cache Status</h2>
        <?php
        if (function_exists('opcache_get_status')) {
            $status = opcache_get_status();
            if ($status && $status['opcache_enabled']) {
                echo "<p class='warning'>⚠️ OpCache is ENABLED - This might be caching old code!</p>";
                echo "<p>Run server with: <code>php -S localhost:8000 -d opcache.enable=0</code></p>";
            } else {
                echo "<p class='success'>✅ OpCache is disabled</p>";
            }
        } else {
            echo "<p class='success'>✅ OpCache not installed</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>2. File Content Check - business-detail.php (Line 11)</h2>
        <?php
        $file = __DIR__ . '/business-detail.php';
        if (file_exists($file)) {
            $lines = file($file);
            $line11 = isset($lines[10]) ? trim($lines[10]) : 'NOT FOUND';
            
            if (strpos($line11, 'getBusinessById') !== false) {
                echo "<p class='success'>✅ CORRECT: Line 11 contains MySQL code</p>";
                echo "<pre>" . htmlspecialchars($line11) . "</pre>";
            } elseif (strpos($line11, 'readData') !== false) {
                echo "<p class='error'>❌ ERROR: Line 11 still has OLD JSON code!</p>";
                echo "<pre>" . htmlspecialchars($line11) . "</pre>";
                echo "<p><strong>The file on disk is outdated!</strong></p>";
            } else {
                echo "<p class='warning'>⚠️ Line 11 content:</p>";
                echo "<pre>" . htmlspecialchars($line11) . "</pre>";
            }
        } else {
            echo "<p class='error'>❌ File not found!</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>3. File Content Check - auth.php</h2>
        <?php
        $authFile = __DIR__ . '/auth.php';
        if (file_exists($authFile)) {
            $content = file_get_contents($authFile);
            
            if (strpos($content, 'readData') !== false) {
                echo "<p class='error'>❌ ERROR: auth.php contains readData()</p>";
                
                // Find line numbers
                $lines = file($authFile);
                foreach ($lines as $num => $line) {
                    if (strpos($line, 'readData') !== false) {
                        echo "<p>Found on line " . ($num + 1) . ": <code>" . htmlspecialchars(trim($line)) . "</code></p>";
                    }
                }
            } else {
                echo "<p class='success'>✅ CORRECT: auth.php does NOT contain readData()</p>";
            }
        } else {
            echo "<p class='error'>❌ File not found!</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>4. Database Connection Test</h2>
        <?php
        require_once 'config.php';
        
        try {
            $conn = getDbConnection();
            echo "<p class='success'>✅ Database connection successful!</p>";
            echo "<p>Database: <strong>" . DB_NAME . "</strong></p>";
            
            // Check if tables exist
            $tables = ['users', 'businesses', 'services', 'staff', 'bookings', 'reviews'];
            foreach ($tables as $table) {
                $result = $conn->query("SELECT COUNT(*) as count FROM $table");
                if ($result) {
                    $row = $result->fetch_assoc();
                    echo "<p>✅ Table <strong>$table</strong>: {$row['count']} records</p>";
                } else {
                    echo "<p class='error'>❌ Table <strong>$table</strong> not found!</p>";
                }
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>5. Functions Test</h2>
        <?php
        require_once 'functions.php';
        
        // Test if old functions exist
        if (function_exists('readData')) {
            echo "<p class='error'>❌ OLD FUNCTION readData() still exists!</p>";
        } else {
            echo "<p class='success'>✅ Old function readData() does not exist</p>";
        }
        
        // Test if new functions exist
        if (function_exists('getBusinessById')) {
            echo "<p class='success'>✅ New function getBusinessById() exists</p>";
        } else {
            echo "<p class='error'>❌ New function getBusinessById() NOT found!</p>";
        }
        
        if (function_exists('getUserByEmail')) {
            echo "<p class='success'>✅ New function getUserByEmail() exists</p>";
        } else {
            echo "<p class='error'>❌ New function getUserByEmail() NOT found!</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>6. Data Folder Check</h2>
        <?php
        $dataDir = __DIR__ . '/data';
        if (file_exists($dataDir)) {
            echo "<p class='warning'>⚠️ OLD 'data' folder still exists!</p>";
            echo "<p>This folder contains old JSON files and should be deleted.</p>";
            echo "<p>Files found:</p><ul>";
            $files = scandir($dataDir);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    echo "<li>$file</li>";
                }
            }
            echo "</ul>";
            echo "<p><strong>Solution:</strong> Delete the 'data' folder manually.</p>";
        } else {
            echo "<p class='success'>✅ No old 'data' folder found</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>7. System Info</h2>
        <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
        <p><strong>Current Directory:</strong> <?php echo __DIR__; ?></p>
        <p><strong>Config File:</strong> <?php echo file_exists('config.php') ? '✅ Found' : '❌ Not Found'; ?></p>
        <p><strong>Functions File:</strong> <?php echo file_exists('functions.php') ? '✅ Found' : '❌ Not Found'; ?></p>
        <p><strong>Database Config:</strong> <?php echo defined('DB_NAME') ? '✅ Loaded (DB: ' . DB_NAME . ')' : '❌ Not Loaded'; ?></p>
    </div>
    
    <hr>
    
    <h2>🎯 What to do next:</h2>
    
    <div class="section">
        <h3>If everything shows ✅ (green checkmarks):</h3>
        <ol>
            <li><strong>Stop your PHP server</strong> (Ctrl + C)</li>
            <li><strong>Clear browser cache</strong> or use Incognito mode</li>
            <li><strong>Restart server:</strong> <code>php -S localhost:8000 -d opcache.enable=0</code></li>
            <li><strong>Try logging in again</strong></li>
        </ol>
        
        <h3>If you see any ❌ (red X) or ⚠️ (warnings):</h3>
        <ul>
            <li>Take a screenshot of this page</li>
            <li>Send it to me so I can help fix the specific issue</li>
        </ul>
    </div>
    
    <p style="margin-top: 30px;">
        <a href="test-db.php" style="background: #850E35; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;">Test Database</a>
        <a href="index.php" style="background: #EE6983; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;">Go to Home</a>
        <a href="login.php" style="background: #FFC4C4; color: #850E35; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;">Go to Login</a>
    </p>
</body>
</html>
