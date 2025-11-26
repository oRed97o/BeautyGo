<?php
header('Content-Type: application/json');

// Include Python path configuration
include 'config.php';

try {
    // Get and decode JSON data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['image'])) {
        throw new Exception('No image data received');
    }
    
    $imageData = $data['image'];
    
    // Create uploads folder if it doesn't exist
    $folder = 'uploads/';
    if (!file_exists($folder)) {
        mkdir($folder, 0777, true);
    }
    
    // Generate unique filename
    $filename = $folder . 'temp_' . time() . '.png';
    
    // Clean and decode base64 image
    $imageData = str_replace('data:image/png;base64,', '', $imageData);
    $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
    $imageData = str_replace('data:image/jpg;base64,', '', $imageData);
    $imageData = str_replace(' ', '+', $imageData);
    
    $decodedImage = base64_decode($imageData);
    
    if ($decodedImage === false) {
        throw new Exception('Failed to decode image data');
    }
    
    // Save image
    $saved = file_put_contents($filename, $decodedImage);
    
    if ($saved === false) {
        throw new Exception('Failed to save image');
    }
    
    // Check if Python script exists
    if (!file_exists('predict.py')) {
        throw new Exception('Prediction script not found');
    }
    
    // Call Python script with proper escaping
  //  $pythonPath = __DIR__ . '\.venv\Scripts\python.exe'; // or 'python3' depending on your system
  //  $command = escapeshellcmd("$pythonPath predict.py") . ' ' . escapeshellarg($filename);

    $predictPath = __DIR__ . '/predict.py';  // full path to predict.py
    $command = escapeshellcmd("$pythonPath $predictPath") . ' ' . escapeshellarg($filename);

    
    // Execute command and capture output
    // Don't capture stderr at all
    $output = shell_exec($command);  // Remove the 2>&1 part
    $prediction = trim($output);
    
    // Clean up the temporary file
    if (file_exists($filename)) {
        unlink($filename);
    }
    
    // Check if output is valid
    if (empty($output)) {
        throw new Exception('No output from prediction script');
    }
    
    $prediction = trim($output);
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'prediction' => $prediction
    ]);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'prediction' => 'Error: ' . $e->getMessage()
    ]);
}
?>