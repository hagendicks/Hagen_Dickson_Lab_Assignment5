<?php
session_start();
require_once 'auth_check.php';
requireStudent();
require_once 'db_connect.php';

header('Content-Type: application/json');
$response = array();

$input = json_decode(file_get_contents("php://input"), true);
if (empty($input)) $input = $_POST;

try {
    if (empty($input['session_code'])) {
        throw new Exception('Session code is required');
    }
    
    $session_code = trim($input['session_code']);
    $student_id = $_SESSION['user_id'];
    
    $session_stmt = $conn->prepare("
        SELECT s.session_id, s.course_id, s.session_date, s.start_time, s.end_time 
        FROM sessions s 
        WHERE s.session_code = ? 
        AND s.status = 'active' 
        AND DATE(s.session_date) = CURDATE()
        AND TIME(NOW()) BETWEEN s.start_time AND ADDTIME(s.end_time, '00:15:00')
    ");
    $session_stmt->bind_param("s", $session_code);
    $session_stmt->execute();
    $session_result = $session_stmt->get_result();
    
    if ($session_result->num_rows === 0) {
        throw new Exception('Invalid or expired session code');
    }
    
    $session = $session_result->fetch_assoc();
    $session_stmt->close();
    
    $enrollment_stmt = $conn->prepare("SELECT enrollment_id FROM enrollments WHERE course_id = ? AND student_id = ? AND status = 'approved'");
    $enrollment_stmt->bind_param("ii", $session['course_id'], $student_id);
    $enrollment_stmt->execute();
    
    if ($enrollment_stmt->get_result()->num_rows === 0) {
        throw new Exception('You are not enrolled in this course');
    }
    $enrollment_stmt->close();
    
    $attendance_stmt = $conn->prepare("SELECT attendance_id FROM attendance WHERE session_id = ? AND student_id = ?");
    $attendance_stmt->bind_param("ii", $session['session_id'], $student_id);
    $attendance_stmt->execute();
    $attendance_result = $attendance_stmt->get_result();
    
    $status = 'present';
    $check_time = new DateTime();
    $start_time = new DateTime($session['start_time']);
    $end_time = new DateTime($session['end_time']);
    
    if ($check_time > $start_time->add(new DateInterval('PT15M'))) {
        $status = 'late';
    }
    
    if ($attendance_result->num_rows > 0) {
        $update_stmt = $conn->prepare("UPDATE attendance SET status = ?, check_in_time = NOW() WHERE session_id = ? AND student_id = ?");
        $update_stmt->bind_param("sii", $status, $session['session_id'], $student_id);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        $insert_stmt = $conn->prepare("INSERT INTO attendance (session_id, student_id, course_id, status, check_in_time) VALUES (?, ?, ?, ?, NOW())");
        $insert_stmt->bind_param("iiis", $session['session_id'], $student_id, $session['course_id'], $status);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
    
    $attendance_stmt->close();
    
    $response['success'] = true;
    $response['message'] = 'Attendance marked successfully';
    $response['status'] = $status;
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
$conn->close();
?>