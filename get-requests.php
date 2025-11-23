<?php
header('Content-Type: application/json');
include '../db-config.php';

try {
    $status = $_GET['status'] ?? null;
    
    if ($status) {
        $sql = "SELECT * FROM requests WHERE status = :status ORDER BY created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':status' => $status]);
    } else {
        $sql = "SELECT * FROM requests ORDER BY created_at DESC";
        $stmt = $conn->query($sql);
    }
    
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($requests);
} catch(Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>