<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';
$response = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    exit;
}

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Attendance Management</title>
    <link rel="stylesheet" href="stylesheet.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="auth-body">
    <header class="auth-header">
        <h1>Attendance Management System</h1>
    </header>
    <div class="auth-container">
        <h2>Login</h2>
        <?php
        if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
            echo '<div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                    Your session has expired. Please login again.
                  </div>';
        }
        ?>
        <form id="loginForm">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="Enter your email" required>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
            
            <div style="margin: 15px 0;">
                <input type="checkbox" id="remember_me" name="remember_me">
                <label for="remember_me" style="display: inline; margin-left: 5px;">Remember Me</label>
            </div>
            
            <button type="submit" id="loginBtn">Login</button>
            <div id="loginMessage" style="margin-top: 15px;"></div>
            <p class="switch-auth">
                Don't have an account? <a href="sign_up.php">Sign up here</a>  <!-- Link to sign_up.php -->
            </p>
        </form>
    </div>
    <footer class="auth-footer">
        2025 Attendance Management System
    </footer>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            loginUser();
        });

        function loginUser() {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const rememberMe = document.getElementById('remember_me').checked;
            const loginBtn = document.getElementById('loginBtn');
            const messageDiv = document.getElementById('loginMessage');

            if (!email || !password) {
                showMessage('Please fill in all fields', 'error');
                return;
            }

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                showMessage('Please enter a valid email address', 'error');
                return;
            }

            loginBtn.disabled = true;
            loginBtn.textContent = 'Logging in...';
            messageDiv.innerHTML = '';

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'Loogin.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    loginBtn.disabled = false;
                    loginBtn.textContent = 'Login';

                    try {
                        const response = JSON.parse(xhr.responseText);
                        
                        if (xhr.status === 200) {
                            if (response.success) {
                                showMessage('Login successful! Redirecting...', 'success');
                                
                                if (response.username) {
                                    sessionStorage.setItem('username', response.username);
                                }
                                if (response.user_id) {
                                    sessionStorage.setItem('user_id', response.user_id);
                                }
                                if (response.role) {
                                    sessionStorage.setItem('role', response.role);
                                }
                                
                                setTimeout(() => {
                                    window.location.href = 'dashboard.php';
                                }, 1000);
                            } else {
                                showMessage(response.message || 'Login failed', 'error');
                            }
                        } else {
                            showMessage('Server error: ' + xhr.status, 'error');
                        }
                    } catch (e) {
                        showMessage('Error parsing server response', 'error');
                        console.error('Parse error:', e);
                    }
                }
            };

            xhr.onerror = function() {
                loginBtn.disabled = false;
                loginBtn.textContent = 'Login';
                showMessage('Network error. Please check your connection.', 'error');
            };

            const data = JSON.stringify({
                email: email,
                password: password,
                remember_me: rememberMe
            });

            xhr.send(data);
        }

        function showMessage(message, type) {
            const messageDiv = document.getElementById('loginMessage');
            const color = type === 'success' ? '#28a745' : '#dc3545';
            messageDiv.innerHTML = `<div style="color: ${color}; padding: 10px; border-radius: 5px; background-color: ${type === 'success' ? '#d4edda' : '#f8d7da'}; border: 1px solid ${type === 'success' ? '#c3e6cb' : '#f5c6cb'};">${message}</div>`;
        }

        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                loginUser();
            }
        });
    </script>
</body>
</html>
