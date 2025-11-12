<?php
// Initialize BeautyGo mock data
// Run this file once to create all mock data files

echo "<!DOCTYPE html><html><head><title>BeautyGo - Data Initialization</title></head><body>";
echo "<h1>BeautyGo Data Initialization</h1>";

// Create data directory
$dataDir = __DIR__ . '/data/';
if (!file_exists($dataDir)) {
    mkdir($dataDir, 0777, true);
    echo "<p>✓ Created data directory: {$dataDir}</p>";
} else {
    echo "<p>✓ Data directory exists: {$dataDir}</p>";
}

// Mock Users
$mockUsers = [
    [
        'id' => 'user_001',
        'email' => 'maria@beautygo.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'name' => 'Maria Santos',
        'phone' => '+63 917 123 4567',
        'address' => 'Brgy. Poblacion, Nasugbu, Batangas',
        'face_shape' => 'oval',
        'skin_tone' => 'medium',
        'body_mass' => 'average',
        'desired_hair_length' => 'medium',
        'preferences' => 'Prefer natural and organic products',
        'created_at' => '2024-01-15 10:30:00'
    ],
    [
        'id' => 'user_002',
        'email' => 'john@beautygo.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'name' => 'John Reyes',
        'phone' => '+63 917 987 6543',
        'address' => 'Brgy. Wawa, Nasugbu, Batangas',
        'face_shape' => 'square',
        'skin_tone' => 'light',
        'body_mass' => 'athletic',
        'desired_hair_length' => 'short',
        'preferences' => 'Quick service, modern styles',
        'created_at' => '2024-02-10 14:20:00'
    ]
];

// Mock Businesses
$mockBusinesses = [
    [
        'id' => 'biz_001',
        'email' => 'elegance@beautygo.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'business_name' => 'Elegance Beauty Salon',
        'owner_name' => 'Anna Cruz',
        'phone' => '+63 917 555 1234',
        'address' => '123 Coastal Road, Brgy. Bucana, Nasugbu, Batangas',
        'city' => 'Nasugbu',
        'description' => 'Premier beauty salon offering hair styling, makeup, and spa treatments. We specialize in bridal packages and special occasion makeovers.',
        'business_type' => 'salon',
        'latitude' => 14.0697,
        'longitude' => 120.6328,
        'image' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=800',
        'opening_hours' => '9:00 AM - 7:00 PM',
        'created_at' => '2023-11-20 09:00:00'
    ],
    [
        'id' => 'biz_002',
        'email' => 'serenity@beautygo.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'business_name' => 'Serenity Spa & Wellness',
        'owner_name' => 'Robert Flores',
        'phone' => '+63 917 555 5678',
        'address' => '456 Beach Drive, Brgy. Poblacion, Nasugbu, Batangas',
        'city' => 'Nasugbu',
        'description' => 'Luxury spa offering massage therapy, facials, and wellness treatments. Perfect getaway for relaxation and rejuvenation.',
        'business_type' => 'spa',
        'latitude' => 14.0710,
        'longitude' => 120.6315,
        'image' => 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=800',
        'opening_hours' => '10:00 AM - 9:00 PM',
        'created_at' => '2023-12-05 11:30:00'
    ],
    [
        'id' => 'biz_003',
        'email' => 'classic@beautygo.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'business_name' => 'Classic Cuts Barbershop',
        'owner_name' => 'Miguel Santos',
        'phone' => '+63 917 555 9012',
        'address' => '789 Main Street, Brgy. Wawa, Nasugbu, Batangas',
        'city' => 'Nasugbu',
        'description' => 'Traditional barbershop with modern techniques. Specializing in classic and contemporary mens grooming services.',
        'business_type' => 'barbershop',
        'latitude' => 14.0685,
        'longitude' => 120.6340,
        'image' => 'https://images.unsplash.com/photo-1503951914875-452162b0f3f1?w=800',
        'opening_hours' => '8:00 AM - 8:00 PM',
        'created_at' => '2024-01-10 08:00:00'
    ]
];

// Mock Services
$mockServices = [
    [
        'id' => 'srv_001',
        'business_id' => 'biz_001',
        'service_name' => 'Haircut & Style',
        'description' => 'Professional haircut with styling and blow-dry',
        'price' => 500,
        'duration' => 60,
        'category' => 'Hair Services',
        'created_at' => '2023-11-20 09:00:00'
    ],
    [
        'id' => 'srv_002',
        'business_id' => 'biz_001',
        'service_name' => 'Hair Coloring',
        'description' => 'Full hair coloring with premium products',
        'price' => 2500,
        'duration' => 180,
        'category' => 'Hair Services',
        'created_at' => '2023-11-20 09:00:00'
    ],
    [
        'id' => 'srv_003',
        'business_id' => 'biz_001',
        'service_name' => 'Bridal Makeup',
        'description' => 'Complete bridal makeup package with trial',
        'price' => 5000,
        'duration' => 120,
        'category' => 'Makeup',
        'created_at' => '2023-11-20 09:00:00'
    ],
    [
        'id' => 'srv_004',
        'business_id' => 'biz_002',
        'service_name' => 'Swedish Massage',
        'description' => 'Relaxing full body massage',
        'price' => 1200,
        'duration' => 90,
        'category' => 'Massage',
        'created_at' => '2023-12-05 11:30:00'
    ],
    [
        'id' => 'srv_005',
        'business_id' => 'biz_002',
        'service_name' => 'Deep Tissue Massage',
        'description' => 'Therapeutic massage for muscle tension',
        'price' => 1500,
        'duration' => 90,
        'category' => 'Massage',
        'created_at' => '2023-12-05 11:30:00'
    ],
    [
        'id' => 'srv_006',
        'business_id' => 'biz_002',
        'service_name' => 'Facial Treatment',
        'description' => 'Deep cleansing facial with mask',
        'price' => 1000,
        'duration' => 60,
        'category' => 'Facial',
        'created_at' => '2023-12-05 11:30:00'
    ],
    [
        'id' => 'srv_007',
        'business_id' => 'biz_003',
        'service_name' => 'Classic Haircut',
        'description' => 'Traditional mens haircut',
        'price' => 200,
        'duration' => 30,
        'category' => 'Haircut',
        'created_at' => '2024-01-10 08:00:00'
    ],
    [
        'id' => 'srv_008',
        'business_id' => 'biz_003',
        'service_name' => 'Beard Trim & Shape',
        'description' => 'Professional beard grooming',
        'price' => 150,
        'duration' => 20,
        'category' => 'Grooming',
        'created_at' => '2024-01-10 08:00:00'
    ],
    [
        'id' => 'srv_009',
        'business_id' => 'biz_003',
        'service_name' => 'Hot Towel Shave',
        'description' => 'Traditional straight razor shave',
        'price' => 300,
        'duration' => 40,
        'category' => 'Shaving',
        'created_at' => '2024-01-10 08:00:00'
    ]
];

// Mock Staff
$mockStaff = [
    [
        'id' => 'staff_001',
        'business_id' => 'biz_001',
        'name' => 'Lisa Martinez',
        'specialty' => 'Hair Styling & Coloring',
        'experience_years' => 8,
        'bio' => 'Certified hair stylist specializing in modern cuts and color techniques',
        'created_at' => '2023-11-20 09:00:00'
    ],
    [
        'id' => 'staff_002',
        'business_id' => 'biz_001',
        'name' => 'Jenny Cruz',
        'specialty' => 'Makeup Artist',
        'experience_years' => 5,
        'bio' => 'Professional makeup artist for weddings and special events',
        'created_at' => '2023-11-20 09:00:00'
    ],
    [
        'id' => 'staff_003',
        'business_id' => 'biz_002',
        'name' => 'Michael Reyes',
        'specialty' => 'Massage Therapist',
        'experience_years' => 10,
        'bio' => 'Licensed massage therapist specializing in therapeutic treatments',
        'created_at' => '2023-12-05 11:30:00'
    ],
    [
        'id' => 'staff_004',
        'business_id' => 'biz_002',
        'name' => 'Sarah Gonzales',
        'specialty' => 'Facial Specialist',
        'experience_years' => 6,
        'bio' => 'Certified esthetician with expertise in skin care treatments',
        'created_at' => '2023-12-05 11:30:00'
    ],
    [
        'id' => 'staff_005',
        'business_id' => 'biz_003',
        'name' => 'David Santos',
        'specialty' => 'Master Barber',
        'experience_years' => 15,
        'bio' => 'Traditional barber with modern styling expertise',
        'created_at' => '2024-01-10 08:00:00'
    ]
];

// Mock Reviews
$mockReviews = [
    [
        'id' => 'rev_001',
        'business_id' => 'biz_001',
        'user_id' => 'user_001',
        'rating' => 5,
        'comment' => 'Amazing service! Lisa did a fantastic job with my hair color. Highly recommend!',
        'created_at' => '2024-03-15 16:30:00'
    ],
    [
        'id' => 'rev_002',
        'business_id' => 'biz_002',
        'user_id' => 'user_002',
        'rating' => 5,
        'comment' => 'Best massage I ever had. Very relaxing and professional staff.',
        'created_at' => '2024-03-18 14:20:00'
    ],
    [
        'id' => 'rev_003',
        'business_id' => 'biz_001',
        'user_id' => 'user_002',
        'rating' => 4,
        'comment' => 'Great salon with friendly staff. Will definitely come back!',
        'created_at' => '2024-03-20 11:45:00'
    ]
];

// Write files
$files = [
    'users.json' => $mockUsers,
    'businesses.json' => $mockBusinesses,
    'services.json' => $mockServices,
    'staff.json' => $mockStaff,
    'reviews.json' => $mockReviews,
    'bookings.json' => []
];

foreach ($files as $filename => $data) {
    $filepath = $dataDir . $filename;
    $result = file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));
    if ($result !== false) {
        echo "<p>✓ Created {$filename} (" . count($data) . " records)</p>";
    } else {
        echo "<p style='color:red;'>✗ Failed to create {$filename}</p>";
    }
}

echo "<hr>";
echo "<h2>Login Credentials</h2>";
echo "<p style='background: #FFF5E4; padding: 10px; border-left: 4px solid #850E35;'>";
echo "<strong>⚠️ Note:</strong> This file contains OLD/LEGACY mock data. The current database uses different credentials from <code>database-NEW-SCHEMA.sql</code>";
echo "</p>";

echo "<h3>Current Database Credentials:</h3>";
echo "<h4>Customers:</h4>";
echo "<ul>";
echo "<li><strong>maria.santos@email.com</strong> / password</li>";
echo "<li><strong>juan.delacruz@email.com</strong> / password</li>";
echo "<li><strong>ana.reyes@email.com</strong> / password</li>";
echo "</ul>";

echo "<h4>Businesses:</h4>";
echo "<ul>";
echo "<li><strong>glam.salon@email.com</strong> / password - Glam Beauty Salon</li>";
echo "<li><strong>tranquil.spa@email.com</strong> / password - Tranquil Day Spa</li>";
echo "<li><strong>classic.barber@email.com</strong> / password - Classic Cuts Barbershop</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='index.php' style='color: #850E35; font-weight: bold;'>← Go to Homepage</a></p>";
echo "<p><a href='login.php' style='color: #850E35; font-weight: bold;'>← Go to Login</a></p>";

echo "</body></html>";
?>
