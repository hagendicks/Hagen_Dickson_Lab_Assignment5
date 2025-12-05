<?php
session_start();
require_once 'auth_check.php';
requireFaculty();
require_once 'db_connect.php';

header('Content-Type: application/json');
$response = array();

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if ($course_id > 0) {
    $check_stmt = $conn->prepare("SELECT course_id FROM courses WHERE course_id = ? AND faculty_id = ?");
    $check_stmt->bind_param("ii", $course_id, $_SESSION['user_id']);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows === 0) {
        $response['success'] = false;
        $response['message'] = 'Unauthorized';
        echo json_encode($response);
        exit;
    }
    $check_stmt->close();
}

$summary_stmt = $conn->prepare("
    SELECT 
        c.course_id,
        c.course_name,
        c.course_code,
        COUNT(DISTINCT s.session_id) as total_sessions,
        COUNT(DISTINCT e.student_id) as total_students,
        AVG(CASE WHEN a.status IN ('present', 'late') THEN 1 ELSE 0 END) * 100 as avg_attendance_rate
    FROM courses c
    LEFT JOIN sessions s ON c.course_id = s.course_id
    LEFT JOIN enrollments e ON c.course_id = e.course_id AND e.status = 'approved'
    LEFT JOIN attendance a ON s.session_id = a.session_id AND e.student_id = a.student_id
    WHERE c.faculty_id = ?
    " . ($course_id > 0 ? " AND c.course_id = ?" : "") . "
    GROUP BY c.course_id, c.course_name, c.course_code
    ORDER BY c.course_name
");

if ($course_id > 0) {
    $summary_stmt->bind_param("ii", $_SESSION['user_id'], $course_id);
} else {
    $summary_stmt->bind_param("i", $_SESSION['user_id']);
}

$summary_stmt->execute();
$summary_result = $summary_stmt->get_result();

$summary = array();
while ($row = $summary_result->fetch_assoc()) {
    $summary[] = $row;
}

$response['success'] = true;
$response['summary'] = $summary;

$summary_stmt->close();

if ($course_id > 0) {
    $detail_stmt = $conn->prepare("
        SELECT 
            s.session_id,
            s.session_name,
            s.session_date,
            s.start_time,
            s.end_time,
            COUNT(a.student_id) as total_students,
            SUM(CASE WHEN a.status IN ('present', 'late') THEN 1 ELSE 0 END) as attended,
            SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent,
            SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late
        FROM sessions s
        LEFT JOIN attendance a ON s.session_id = a.session_id
        WHERE s.course_id = ? AND s.faculty_id = ?
        GROUP BY s.session_id, s.session_name, s.session_date, s.start_time, s.end_time
        ORDER BY s.session_date DESC, s.start_time
    ");
    $detail_stmt->bind_param("ii", $course_id, $_SESSION['user_id']);
    $detail_stmt->execute();
    $detail_result = $detail_stmt->get_result();
    
    $details = array();
    while ($row = $detail_result->fetch_assoc()) {
        $details[] = $row;
    }
    
    $response['details'] = $details;
    $detail_stmt->close();
}

$conn->close();

echo json_encode($response);
?>