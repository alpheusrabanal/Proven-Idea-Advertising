<?php
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "", "appdevdb");

$result = $conn->query("SELECT * FROM advertising_requests ORDER BY id DESC");

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>
