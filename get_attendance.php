<?php
session_start();
require_once 'auth_check.php';
require_once 'db_connect.php';

header('Content-Type: application/json');

$response = array();

$stmt = $conn->prepare("
    SELECT c.course_name, a.date, a.status, s.session_name 
    FROM attendance a
    JOIN courses c ON a.course_id = c.course_id
    LEFT JOIN sessions s ON a.session_id = s.session_id
    WHERE a.student_id = ?
    ORDER BY a.date DESC
    LIMIT 20
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$attendance = array();
while ($row = $result->fetch_assoc()) {
    $attendance[] = $row;
}

$response['success'] = true;
$response['attendance'] = $attendance;

$stmt->close();
$conn->close();

echo json_encode($response);
?>