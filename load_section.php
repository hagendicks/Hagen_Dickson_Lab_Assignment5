<?php
session_start();
require_once 'auth_check.php';
require_once 'db_connect.php';

header('Content-Type: application/json');

$response = array();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $response['success'] = false;
    $response['message'] = 'Please login to access this content';
    echo json_encode($response);
    exit;
}

if (!isset($_GET['section'])) {
    $response['success'] = false;
    $response['message'] = 'No section specified';
    echo json_encode($response);
    exit;
}

$section = $_GET['section'];

switch($section) {
    case 'dashboard':
        $response['content'] = '
            <div class="card">
                <h2>Welcome to Student Dashboard</h2>
                <p>You are logged in as: <strong>' . htmlspecialchars($_SESSION['username']) . '</strong></p>
                <p>Your role: <strong>Student</strong></p>
                <p>User ID: <strong>' . htmlspecialchars($_SESSION['user_id']) . '</strong></p>
            </div>
            <div class="card">
                <h2>Quick Stats</h2>
                <div id="student-stats">
                    <p>Loading statistics...</p>
                </div>
            </div>
        ';
        break;
        
    case 'my-courses':
        $response['content'] = '
            <div class="card">
                <h2>My Enrolled Courses</h2>
                <button onclick="loadMyCourses()" class="btn-primary">Refresh Courses</button>
                <div id="enrolled-courses-list">
                    <p>Loading courses...</p>
                </div>
            </div>
        ';
        break;
        
    case 'available-courses':
        $response['content'] = '
            <div class="card">
                <h2>Available Courses</h2>
                <button onclick="loadAvailableCourses()" class="btn-primary">Refresh Courses</button>
                <div id="available-courses-list">
                    <p>Loading available courses...</p>
                </div>
            </div>
        ';
        break;
        
    case 'my-requests':
        $response['content'] = '
            <div class="card">
                <h2>My Enrollment Requests</h2>
                <button onclick="loadMyRequests()" class="btn-primary">Refresh Requests</button>
                <div id="my-requests-list">
                    <p>Loading requests...</p>
                </div>
            </div>
        ';
        break;
        
    case 'profile':
        $response['content'] = '
            <div class="card">
                <h2>My Profile</h2>
                <div id="profile-section">
                    <p>Loading profile...</p>
                </div>
            </div>
        ';
        break;
        
    case 'attendance':
        $response['content'] = '
            <div class="card">
                <h2>My Attendance</h2>
                <div id="attendance-section">
                    <p>Loading attendance records...</p>
                </div>
            </div>
        ';
        break;
        
    default:
        $response['success'] = false;
        $response['message'] = 'Invalid section requested';
        echo json_encode($response);
        exit;
}

$response['success'] = true;
echo json_encode($response);
?>