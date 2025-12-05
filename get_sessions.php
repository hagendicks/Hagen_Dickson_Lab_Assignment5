<?php
session_start();
require_once 'auth_check.php';
requireFaculty();
require_once 'db_connect.php';

header('Content-Type: application/json');
$response = array();

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if ($course_id > 0) {
    $stmt = $conn->prepare("SELECT s.*, c.course_name FROM sessions s JOIN courses c ON s.course_id = c.course_id WHERE s.course_id = ? AND s.faculty_id = ? ORDER BY s.session_date DESC, s.start_time");
    $stmt->bind_param("ii", $course_id, $_SESSION['user_id']);
} else {
    $stmt = $conn->prepare("SELECT s.*, c.course_name FROM sessions s JOIN courses c ON s.course_id = c.course_id WHERE s.faculty_id = ? ORDER BY s.session_date DESC, s.start_time");
    $stmt->bind_param("i", $_SESSION['user_id']);
}

$stmt->execute();
$result = $stmt->get_result();

$sessions = array();
while ($row = $result->fetch_assoc()) {
    $sessions[] = $row;
}

$response['success'] = true;
$response['sessions'] = $sessions;

$stmt->close();
$conn->close();

echo json_encode($response);
?>