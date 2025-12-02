<?php
// Barangay coordinates for Nasugbu, Batangas
// These are approximate center coordinates for each barangay
// Users can fine-tune by clicking on the map

$barangayCoordinates = [
    'Aga' => ['lat' => 14.0878, 'lng' => 120.6234],
    'Balaytigui' => ['lat' => 14.0756, 'lng' => 120.6312],
    'Banilad' => ['lat' => 14.0698, 'lng' => 120.6456],
    'Bilaran' => ['lat' => 14.0912, 'lng' => 120.6178],
    'Bucana' => ['lat' => 14.0680, 'lng' => 120.6300],
    'Buhay' => ['lat' => 14.0745, 'lng' => 120.6289],
    'Bulihan' => ['lat' => 14.0834, 'lng' => 120.6145],
    'Bunducan' => ['lat' => 14.0956, 'lng' => 120.6089],
    'Butucan' => ['lat' => 14.0612, 'lng' => 120.6378],
    'Calayo' => ['lat' => 14.1542, 'lng' => 120.6155],
    'Catandaan' => ['lat' => 14.0823, 'lng' => 120.6501],
    'Caybunga' => ['lat' => 14.1045, 'lng' => 120.6234],
    'Cogunan' => ['lat' => 14.0534, 'lng' => 120.6412],
    'Dayap' => ['lat' => 14.0723, 'lng' => 120.6178],
    'Kaylaway' => ['lat' => 14.1156, 'lng' => 120.6089],
    'Latag' => ['lat' => 14.0945, 'lng' => 120.6234],
    'Looc' => ['lat' => 14.0823, 'lng' => 120.6378],
    'Lumbangan' => ['lat' => 14.0748, 'lng' => 120.6302],
    'Malapad na Bato' => ['lat' => 14.0612, 'lng' => 120.6089],
    'Mataas na Pulo' => ['lat' => 14.1234, 'lng' => 120.6401],
    'Munting Indan' => ['lat' => 14.0534, 'lng' => 120.6234],
    'Natipuan' => ['lat' => 14.0823, 'lng' => 120.6089],
    'Pantalan' => ['lat' => 14.0945, 'lng' => 120.6478],
    'Papaya' => ['lat' => 14.1056, 'lng' => 120.6312],
    'Poblacion' => ['lat' => 14.0745, 'lng' => 120.6328],
    'Putat' => ['lat' => 14.0923, 'lng' => 120.6345],
    'Reparo' => ['lat' => 14.0834, 'lng' => 120.6234],
    'San Diego' => ['lat' => 14.0612, 'lng' => 120.6156],
    'San Jose' => ['lat' => 14.0745, 'lng' => 120.6145],
    'San Juan' => ['lat' => 14.0856, 'lng' => 120.6289],
    'Talangan' => ['lat' => 14.1045, 'lng' => 120.6145],
    'Tumalim' => ['lat' => 14.0534, 'lng' => 120.6501],
    'Utod' => ['lat' => 14.0923, 'lng' => 120.6089],
    'Wawa' => ['lat' => 14.0745, 'lng' => 120.6456],
];

// Return JSON if requested via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    
    if (isset($_GET['barangay'])) {
        // Single barangay lookup
        $barangay = $_GET['barangay'];
        if (isset($barangayCoordinates[$barangay])) {
            echo json_encode($barangayCoordinates[$barangay]);
        } else {
            // Default to Poblacion if not found
            echo json_encode($barangayCoordinates['Poblacion']);
        }
    } else {
        // Return all barangays (for finding nearest)
        echo json_encode($barangayCoordinates);
    }
    exit;
}

// Otherwise, return the full array for PHP use
return $barangayCoordinates;
?>
