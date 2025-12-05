<?php
session_start();
require_once 'auth_check.php';
requireFaculty();
require_once 'db_connect.php';

header('Content-Type: application/json');
$response = array();

$stmt = $conn->prepare("SELECT course_id, course_code, course_name FROM courses WHERE faculty_id = ? ORDER BY course_name");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$courses = array();
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

$response['success'] = true;
$response['courses'] = $courses;

$stmt->close();
$conn->close();

echo json_encode($response);
?>