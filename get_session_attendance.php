<?php
session_start();
require_once 'auth_check.php';
require_once 'db_connect.php';

header('Content-Type: application/json');
$response = array();

if (!isset($_GET['session_id'])) {
    $response['success'] = false;
    $response['message'] = 'Session ID required';
    echo json_encode($response);
    exit;
}

$session_id = (int)$_GET['session_id'];

if ($_SESSION['role'] === 'faculty') {
    $check_stmt = $conn->prepare("SELECT session_id FROM sessions WHERE session_id = ? AND faculty_id = ?");
    $check_stmt->bind_param("ii", $session_id, $_SESSION['user_id']);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows === 0) {
        $response['success'] = false;
        $response['message'] = 'Unauthorized';
        echo json_encode($response);
        exit;
    }
    $check_stmt->close();
}

$stmt = $conn->prepare("
    SELECT 
        a.*,
        u.first_name,
        u.last_name,
        u.email,
        s.session_name,
        s.session_date,
        s.start_time,
        c.course_name
    FROM attendance a
    JOIN users u ON a.student_id = u.user_id
    JOIN sessions s ON a.session_id = s.session_id
    JOIN courses c ON a.course_id = c.course_id
    WHERE a.session_id = ?
    ORDER BY u.last_name, u.first_name
");
$stmt->bind_param("i", $session_id);
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