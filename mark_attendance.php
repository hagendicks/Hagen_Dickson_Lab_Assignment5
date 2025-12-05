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
    if (empty($input['session_id']) || empty($input['student_id']) || empty($input['status'])) {
        throw new Exception('Missing required parameters');
    }
    
    $session_id = (int)$input['session_id'];
    $student_id = (int)$input['student_id'];
    $status = $input['status'];
    $notes = isset($input['notes']) ? trim($input['notes']) : '';
    
    $check_stmt = $conn->prepare("SELECT s.faculty_id, s.course_id FROM sessions s WHERE s.session_id = ? AND s.faculty_id = ?");
    $check_stmt->bind_param("ii", $session_id, $_SESSION['user_id']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        throw new Exception('Unauthorized to mark attendance for this session');
    }
    
    $session_info = $check_result->fetch_assoc();
    $check_stmt->close();
    
    $stmt = $conn->prepare("UPDATE attendance SET status = ?, marked_by = ?, check_in_time = NOW(), notes = ? WHERE session_id = ? AND student_id = ?");
    $stmt->bind_param("sisii", $status, $_SESSION['user_id'], $notes, $session_id, $student_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update attendance');
    }
    
    if ($stmt->affected_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO attendance (session_id, student_id, course_id, status, marked_by, check_in_time, notes) VALUES (?, ?, ?, ?, ?, NOW(), ?)");
        $stmt->bind_param("iiisis", $session_id, $student_id, $session_info['course_id'], $status, $_SESSION['user_id'], $notes);
        $stmt->execute();
    }
    
    $stmt->close();
    
    $response['success'] = true;
    $response['message'] = 'Attendance marked successfully';
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
$conn->close();
?>