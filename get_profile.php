<?php
session_start();
require_once 'auth_check.php';
require_once 'db_connect.php';

header('Content-Type: application/json');

$response = array();

$stmt = $conn->prepare("SELECT first_name, last_name, email, role, dob, created_at, last_login FROM users WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $response['success'] = true;
    $response['user'] = $user;
} else {
    $response['success'] = false;
    $response['message'] = 'User not found';
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>