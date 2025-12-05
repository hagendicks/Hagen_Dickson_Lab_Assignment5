<?php
session_start();
require_once 'auth_check.php';
requireFaculty();
require_once 'db_connect.php';

header('Content-Type: application/json');
$response = array();

$input = json_decode(file_get_contents("php://input"), true);
if (empty($input)) $input = $_POST;

try {
    if (empty($input['course_id']) || empty($input['session_name']) || empty($input['session_date']) || empty($input['start_time']) || empty($input['end_time'])) {
        throw new Exception('All required fields must be filled');
    }
    
    $course_id = (int)$input['course_id'];
    $session_name = trim($input['session_name']);
    $session_date = $input['session_date'];
    $start_time = $input['start_time'];
    $end_time = $input['end_time'];
    $location = isset($input['location']) ? trim($input['location']) : '';
    
    $session_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
    
    $stmt = $conn->prepare("INSERT INTO sessions (course_id, faculty_id, session_name, session_date, start_time, end_time, location, session_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssss", $course_id, $_SESSION['user_id'], $session_name, $session_date, $start_time, $end_time, $location, $session_code);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create session');
    }
    
    $session_id = $conn->insert_id;
    
    $enrolled_stmt = $conn->prepare("SELECT student_id FROM enrollments WHERE course_id = ? AND status = 'approved'");
    $enrolled_stmt->bind_param("i", $course_id);
    $enrolled_stmt->execute();
    $result = $enrolled_stmt->get_result();
    
    $attendance_stmt = $conn->prepare("INSERT INTO attendance (session_id, student_id, course_id, status) VALUES (?, ?, ?, 'absent')");
    
    while ($student = $result->fetch_assoc()) {
        $attendance_stmt->bind_param("iii", $session_id, $student['student_id'], $course_id);
        $attendance_stmt->execute();
    }
    
    $attendance_stmt->close();
    $enrolled_stmt->close();
    $stmt->close();
    
    $response['success'] = true;
    $response['message'] = 'Session created successfully';
    $response['session_code'] = $session_code;
    $response['session_id'] = $session_id;
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
$conn->close();
?>