<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';
$response = array();

if (isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    $delete_stmt = $conn->prepare("DELETE FROM remember_me_tokens WHERE token = ?");
    $delete_stmt->bind_param("s", $token);
    $delete_stmt->execute();
    $delete_stmt->close();
}

if (isset($_SESSION['email'])) {
    error_log("User logged out: " . $_SESSION['email']);
}

session_unset();
session_destroy();

setcookie('remember_me', '', time() - 3600, "/", "", false, true);
setcookie('user_id', '', time() - 3600, "/", "", false, true);

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

$response['success'] = true;
$response['message'] = 'Logged out successfully';
echo json_encode($response);
?>