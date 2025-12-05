<?php
ob_start();
session_start();

header('Content-Type: application/json');
require_once 'db_connect.php';

$response = array();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $input = json_decode(file_get_contents("php://input"), true);
    if (empty($input)) {
        $input = $_POST;
    }
    
    $required_fields = ['first_name', 'last_name', 'email', 'password'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }
    
    $first_name = trim($input['first_name']);
    $last_name = trim($input['last_name']);
    $email = trim(strtolower($input['email']));
    $password = $input['password'];
    $role = isset($input['role']) ? $input['role'] : 'student';
    $dob = isset($input['dob']) ? $input['dob'] : null;

    if (strlen($first_name) < 2 || !preg_match("/^[a-zA-Z\s]+$/", $first_name)) {
        throw new Exception('Invalid first name. Use only letters and spaces (minimum 2 characters)');
    }
    
    if (strlen($last_name) < 2 || !preg_match("/^[a-zA-Z\s]+$/", $last_name)) {
        throw new Exception('Invalid last name. Use only letters and spaces (minimum 2 characters)');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address format');
    }
    
    if (strlen($password) < 6) {
        throw new Exception('Password must be at least 6 characters long');
    }
    
    $valid_roles = ['student', 'faculty', 'admin'];
    if (!in_array($role, $valid_roles)) {
        throw new Exception('Invalid role selected');
    }

    $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $check_stmt->close();
        throw new Exception('Email address already registered');
    }
    $check_stmt->close();

    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    if ($dob) {
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, dob) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $first_name, $last_name, $email, $password_hash, $role, $dob);
    } else {
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $first_name, $last_name, $email, $password_hash, $role);
    }

    if (!$stmt->execute()) {
        throw new Exception('Registration failed. Please try again.');
    }
    
    $user_id = $conn->insert_id;

    if ($role === 'student') {
        $student_stmt = $conn->prepare("INSERT INTO students (student_id) VALUES (?)");
        $student_stmt->bind_param("i", $user_id);
        $student_stmt->execute();
        $student_stmt->close();
    }
    
    if ($role === 'faculty') {
        $faculty_stmt = $conn->prepare("INSERT INTO faculty (faculty_id) VALUES (?)");
        $faculty_stmt->bind_param("i", $user_id);
        $faculty_stmt->execute();
        $faculty_stmt->close();
    }

    $stmt->close();
    $conn->close();

    $response['success'] = true;
    $response['message'] = 'Account created successfully!';
    $response['user_id'] = $user_id;

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

ob_end_clean();
echo json_encode($response);
exit;
?>
