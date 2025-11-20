<?php
// ============================================================
// SERVICE FUNCTIONS
// ============================================================

// Get services for a business
function getBusinessServices($businessId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM services WHERE business_id = ? ORDER BY service_id DESC");
    $stmt->bind_param("i", $businessId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

// Get all services
function getAllServices() {
    $conn = getDbConnection();
    $result = $conn->query("SELECT s.*, b.business_name, b.city FROM services s JOIN businesses b ON s.business_id = b.business_id ORDER BY s.service_id DESC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get service by ID
function getServiceById($id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM services WHERE service_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data;
}

// Create service
function createService($data) {
    $conn = getDbConnection();

    $duration = is_numeric($data['duration']) ? intval($data['duration']) : 0;
    
    $businessId = $data['business_id'];
    $serviceName = $data['service_name'];
    $serviceType = $data['service_type'] ?? '';
    $serviceDesc = $data['service_desc'] ?? '';
    $cost = $data['cost'] ?? 0;

    $stmt = $conn->prepare("
        INSERT INTO services 
        (business_id, service_name, service_type, service_desc, cost, duration)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssdi",
        $businessId,
        $serviceName,
        $serviceType,
        $serviceDesc,
        $cost,
        $duration
    );

    if ($stmt->execute()) {
        $serviceId = $conn->insert_id;
        $stmt->close();
        return $serviceId;
    }

    error_log("Service creation failed: " . $stmt->error);
    $stmt->close();
    return false;
}

// Update service
function updateService($id, $data) {
    $conn = getDbConnection();
    
    $duration = is_numeric($data['duration']) ? intval($data['duration']) : 0;
    
    $serviceName = $data['service_name'];
    $serviceType = $data['service_type'] ?? '';
    $serviceDesc = $data['service_desc'] ?? '';
    $cost = $data['cost'] ?? 0;
    
    $stmt = $conn->prepare("UPDATE services SET service_name = ?, service_type = ?, service_desc = ?, cost = ?, duration = ? WHERE service_id = ?");
    $stmt->bind_param("sssdii",
        $serviceName,
        $serviceType,
        $serviceDesc,
        $cost,
        $duration,
        $id
    );
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// Delete service
function deleteService($id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("DELETE FROM services WHERE service_id = ?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}
?>