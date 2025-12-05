<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';

$response = array();

function validateRememberMeToken($conn) {
    if (isset($_COOKIE['remember_me']) && isset($_COOKIE['user_id'])) {
        $token = $_COOKIE['remember_me'];
        $user_id = (int)$_COOKIE['user_id'];
        
        $clean_stmt = $conn->prepare("DELETE FROM remember_me_tokens WHERE expires_at < NOW()");
        $clean_stmt->execute();
        $clean_stmt->close();
        
        $stmt = $conn->prepare("SELECT u.user_id, u.first_name, u.last_name, u.email, u.role 
                               FROM remember_me_tokens rmt 
                               JOIN users u ON rmt.user_id = u.user_id 
                               WHERE rmt.token = ? AND rmt.user_id = ? AND rmt.expires_at > NOW()");
        $stmt->bind_param("si", $token, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            $new_token = bin2hex(random_bytes(32));
            $expiry = time() + (30 * 24 * 60 * 60);
            
            $update_stmt = $conn->prepare("UPDATE remember_me_tokens SET token = ?, expires_at = FROM_UNIXTIME(?) WHERE token = ?");
            $update_stmt->bind_param("sis", $new_token, $expiry, $token);
            $update_stmt->execute();
            $update_stmt->close();
            
            setcookie('remember_me', $new_token, $expiry, "/", "", false, true);
            setcookie('user_id', $user['user_id'], $expiry, "/", "", false, true);
            
            $stmt->close();
            return true;
        }
        $stmt->close();
    }
    return false;
}

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $response['logged_in'] = true;
    $response['username'] = $_SESSION['username'];
    $response['user_id'] = $_SESSION['user_id'];
    $response['email'] = isset($_SESSION['email']) ? $_SESSION['email'] : '';
    $response['role'] = $_SESSION['role'];
} elseif (validateRememberMeToken($conn)) {
    $response['logged_in'] = true;
    $response['username'] = $_SESSION['username'];
    $response['user_id'] = $_SESSION['user_id'];
    $response['email'] = $_SESSION['email'];
    $response['role'] = $_SESSION['role'];
} else {
    $response['logged_in'] = false;
}

$conn->close();
echo json_encode($response);
?>