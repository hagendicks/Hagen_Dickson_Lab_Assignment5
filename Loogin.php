<?php
session_start();
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
                Don't have an account? <a href="signup.php">Sign up here</a>
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
            xhr.open('POST', 'login.php', true);
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