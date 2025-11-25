<?php
header('Content-Type: application/json');
include '../db-config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $sql = "INSERT INTO requests (
            client_name, email, contact_number, company_name, 
            service_type, size_dimensions, other_service, 
            project_description, location, upload_files, status
        ) VALUES (
            :client_name, :email, :contact_number, :company_name,
            :service_type, :size_dimensions, :other_service,
            :project_description, :location, :upload_files, 'New'
        )";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':client_name' => $data['client_name'],
            ':email' => $data['email'],
            ':contact_number' => $data['contact_number'],
            ':company_name' => $data['company_name'],
            ':service_type' => $data['service_type'],
            ':size_dimensions' => $data['size_dimensions'] ?? null,
            ':other_service' => $data['other_service'] ?? null,
            ':project_description' => $data['project_description'],
            ':location' => $data['location'],
            ':upload_files' => $data['upload_files'] ?? null
        ]);
        
        echo json_encode(['success' => true, 'id' => $conn->lastInsertId()]);
    } catch(Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>