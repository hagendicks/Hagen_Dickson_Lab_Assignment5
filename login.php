<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';
$response = array();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['success'] = false;
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}
$input = json_decode(file_get_contents("php://input"), true);
if (empty($input)) {
    $input = $_POST;
}
if (empty($input['email']) || empty($input['password'])) {
    $response['success'] = false;
    $response['message'] = 'Email and password are required';
    echo json_encode($response);
    exit;
}
$email = trim(strtolower($input['email']));
$password = $input['password'];
$remember_me = isset($input['remember_me']) && $input['remember_me'] === true;

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['success'] = false;
    $response['message'] = 'Invalid email address format';
    echo json_encode($response);
    exit;
}

$stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, password, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response['success'] = false;
    $response['message'] = 'Invalid email or password';
    $stmt->close();
    $conn->close();
    echo json_encode($response);
    exit;
}

$user = $result->fetch_assoc();

if (password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    
    if ($remember_me) {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + (30 * 24 * 60 * 60);
        
        $token_stmt = $conn->prepare("INSERT INTO remember_me_tokens (user_id, token, expires_at) VALUES (?, ?, FROM_UNIXTIME(?))");
        $token_stmt->bind_param("isi", $user['user_id'], $token, $expiry);
        $token_stmt->execute();
        $token_stmt->close();
        
        setcookie('remember_me', $token, $expiry, "/", "", false, true);
        setcookie('user_id', $user['user_id'], $expiry, "/", "", false, true);
    }
    
    $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
    $update_stmt->bind_param("i", $user['user_id']);
    $update_stmt->execute();
    $update_stmt->close();
    
    $response['success'] = true;
    $response['message'] = 'Login successful!';
    $response['username'] = $user['first_name'] . ' ' . $user['last_name'];
    $response['user_id'] = $user['user_id'];
    $response['role'] = $user['role'];
    
    error_log("User logged in: {$email}");
} else {
    $response['success'] = false;
    $response['message'] = 'Invalid email or password';
    error_log("Failed login attempt for: {$email}");
}

$stmt->close();
$conn->close();
echo json_encode($response);
?>