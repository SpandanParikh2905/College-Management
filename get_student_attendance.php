<?php
session_start();
require 'db_connect.php';
header('Content-Type: application/json');

// Assuming student ID is stored in session
$student_id = $_SESSION['user']['id'];

$sql = "SELECT date, status FROM attendance WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'date' => $row['date'],
            'status' => ucfirst($row['status']) // Capitalize status
        ];
    }
}

echo json_encode($data);
$stmt->close();
$conn->close();
?>
