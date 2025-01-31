<?php
require 'db_connect.php';
header('Content-Type: application/json');

$sql = "SELECT date, class, (COUNT(status) / (SELECT COUNT(*) FROM attendance WHERE date = a.date AND class = a.class)) * 100 AS percentage
        FROM attendance a
        WHERE status = 'present'
        GROUP BY date, class";

$result = $conn->query($sql);

$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'date' => $row['date'],
            'class' => $row['class'],
            'percentage' => round($row['percentage'], 2)
        ];
    }
}

echo json_encode($data);
$conn->close();
?>
