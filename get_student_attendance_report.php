<?php
session_start();
require_once 'auth_check.php';
requireStudent();
require_once 'db_connect.php';

header('Content-Type: application/json');
$response = array();

$student_id = $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if ($course_id > 0) {
    $enrollment_stmt = $conn->prepare("SELECT enrollment_id FROM enrollments WHERE course_id = ? AND student_id = ? AND status = 'approved'");
    $enrollment_stmt->bind_param("ii", $course_id, $student_id);
    $enrollment_stmt->execute();
    
    if ($enrollment_stmt->get_result()->num_rows === 0) {
        $response['success'] = false;
        $response['message'] = 'Not enrolled in this course';
        echo json_encode($response);
        exit;
    }
    $enrollment_stmt->close();
    
    $stmt = $conn->prepare("
        SELECT 
            a.*,
            s.session_name,
            s.session_date,
            s.start_time,
            s.end_time,
            c.course_name,
            c.course_code
        FROM attendance a
        JOIN sessions s ON a.session_id = s.session_id
        JOIN courses c ON a.course_id = c.course_id
        WHERE a.student_id = ? AND a.course_id = ?
        ORDER BY s.session_date DESC
    ");
    $stmt->bind_param("ii", $student_id, $course_id);
} else {
    $stmt = $conn->prepare("
        SELECT 
            a.*,
            s.session_name,
            s.session_date,
            s.start_time,
            s.end_time,
            c.course_name,
            c.course_code
        FROM attendance a
        JOIN sessions s ON a.session_id = s.session_id
        JOIN courses c ON a.course_id = c.course_id
        WHERE a.student_id = ?
        ORDER BY s.session_date DESC
        LIMIT 50
    ");
    $stmt->bind_param("i", $student_id);
}

$stmt->execute();
$result = $stmt->get_result();

$attendance = array();
$stats = array(
    'total_sessions' => 0,
    'present' => 0,
    'absent' => 0,
    'late' => 0,
    'attendance_rate' => 0
);

while ($row = $result->fetch_assoc()) {
    $attendance[] = $row;
    
    if (!isset($stats['courses'][$row['course_id']])) {
        $stats['courses'][$row['course_id']] = array(
            'course_name' => $row['course_name'],
            'course_code' => $row['course_code'],
            'total' => 0,
            'present' => 0,
            'absent' => 0,
            'late' => 0
        );
    }
    
    $stats['courses'][$row['course_id']]['total']++;
    $stats['total_sessions']++;
    
    if ($row['status'] === 'present') {
        $stats['courses'][$row['course_id']]['present']++;
        $stats['present']++;
    } elseif ($row['status'] === 'absent') {
        $stats['courses'][$row['course_id']]['absent']++;
        $stats['absent']++;
    } elseif ($row['status'] === 'late') {
        $stats['courses'][$row['course_id']]['late']++;
        $stats['late']++;
    }
}

if ($stats['total_sessions'] > 0) {
    $stats['attendance_rate'] = round((($stats['present'] + $stats['late']) / $stats['total_sessions']) * 100, 2);
}

foreach ($stats['courses'] as &$course) {
    if ($course['total'] > 0) {
        $course['rate'] = round((($course['present'] + $course['late']) / $course['total']) * 100, 2);
    } else {
        $course['rate'] = 0;
    }
}

$response['success'] = true;
$response['attendance'] = $attendance;
$response['stats'] = $stats;

$stmt->close();
$conn->close();

echo json_encode($response);
?>