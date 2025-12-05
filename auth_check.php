<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please login to access this page']);
    exit;
}

function requireFaculty() {
    if ($_SESSION['role'] !== 'faculty') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Access denied. Faculty only.']);
        exit;
    }
}

function requireStudent() {
    if ($_SESSION['role'] !== 'student') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Access denied. Students only.']);
        exit;
    }
}

function redirectBasedOnRole() {
    if ($_SESSION['role'] === 'faculty') {
        header('Location: faculty_dashboard.php');
        exit;
    } elseif ($_SESSION['role'] === 'student') {
        header('Location: student_dashboard.php');
        exit;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please login to access this page']);
    exit;
}

if (!function_exists('requireFaculty')) {
    function requireFaculty() {
        if ($_SESSION['role'] !== 'faculty') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Access denied. Faculty only.']);
            exit;
        }
    }
}

if (!function_exists('requireStudent')) {
    function requireStudent() {
        if ($_SESSION['role'] !== 'student') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Access denied. Students only.']);
            exit;
        }
    }
}

if (!function_exists('redirectBasedOnRole')) {
    function redirectBasedOnRole() {
        if (basename($_SERVER['PHP_SELF']) === 'dashboard.php') {
            if ($_SESSION['role'] === 'faculty') {
                header('Location: faculty_dashboard.php');
                exit;
            } elseif ($_SESSION['role'] === 'student') {
                header('Location: student_dashboard.php');
                exit;
            }
        }
    }
}
    error_log("Session check - logged_in: " . (isset($_SESSION['logged_in']) ? $_SESSION['logged_in'] : 'not set'));
error_log("Session check - user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please login to access this page']);
    exit;
}
?>


