<?php
header("Content-Type: application/json");

// Database connection
$conn = new mysqli("localhost", "root", "", "appdevdb");

if ($conn->connect_error) {
    die(json_encode(["success" => false, "error" => $conn->connect_error]));
}

// Handle file uploads
$uploadedFiles = [];

if (!empty($_FILES['uploaded_files'])) {
    foreach ($_FILES['uploaded_files']['name'] as $i => $name) {
        $tmp = $_FILES['uploaded_files']['tmp_name'][$i];
        $dest = "uploads/" . time() . "_" . basename($name);
        if (!is_dir("uploads")) mkdir("uploads", 0777, true);
        if (move_uploaded_file($tmp, $dest)) {
            $uploadedFiles[] = $dest;
        }
    }
}

$uploadedList = implode(",", $uploadedFiles);

// Prepare SQL
$stmt = $conn->prepare("
INSERT INTO advertising_requests 
(client_name, email, contact_number, company_name, service_type, size_dimensions, other_service, location, date_needed, budget, project_description, uploaded_files, status)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'New')
");

$stmt->bind_param(
    "ssssssssssss",
    $_POST["client_name"],
    $_POST["email"],
    $_POST["contact_number"],
    $_POST["company_name"],
    $_POST["service_type"],
    $_POST["size_dimensions"],
    $_POST["other_service"],
    $_POST["location"],
    $_POST["date_needed"],
    $_POST["budget"],
    $_POST["project_description"],
    $uploadedList
);

$stmt->execute();

echo json_encode(["success" => true, "message" => "Saved to database successfully"]);
?>
