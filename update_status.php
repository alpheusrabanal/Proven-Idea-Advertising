<?php
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "", "appdevdb");

$input = json_decode(file_get_contents("php://input"), true);

$id = intval($input["id"]);
$status = $input["status"];

$stmt = $conn->prepare("UPDATE advertising_requests SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $id);
$stmt->execute();

echo json_encode(["success" => true]);
?>
