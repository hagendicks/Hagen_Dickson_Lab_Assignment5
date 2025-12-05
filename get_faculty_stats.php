<?php
session_start();
require_once 'auth_check.php';
requireFaculty();
require_once 'db_connect.php';

header('Content-Type: application/json');
$response = array();

$stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT c.course_id) as total_courses,
        COUNT(DISTINCT CASE WHEN s.status = 'active' THEN s.session_id END) as active_sessions,
        COUNT(DISTINCT e.student_id) as total_students,
        COALESCE(AVG(CASE WHEN a.status IN ('present', 'late') THEN 1 ELSE 0 END) * 100, 0) as avg_attendance
    FROM courses c
    LEFT JOIN sessions s ON c.course_id = s.course_id
    LEFT JOIN enrollments e ON c.course_id = e.course_id AND e.status = 'approved'
    LEFT JOIN attendance a ON s.session_id = a.session_id AND e.student_id = a.student_id
    WHERE c.faculty_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $stats = $result->fetch_assoc();
    $response['success'] = true;
    $response['stats'] = array(
        'total_courses' => (int)$stats['total_courses'],
        'active_sessions' => (int)$stats['active_sessions'],
        'total_students' => (int)$stats['total_students'],
        'avg_attendance' => round($stats['avg_attendance'], 2)
    );
} else {
    $response['success'] = false;
    $response['message'] = 'No statistics available';
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>