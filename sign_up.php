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
    <title>Sign Up | Attendance Management</title>
    <link rel="stylesheet" href="stylesheet.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="auth-body">
    <header class="auth-header">
        <h1>Attendance Management System</h1>
    </header>
    <div class="auth-container">
        <h2>Create Account</h2>
        <form onsubmit="return validateSignup(event)">
            <label for="first_name">First Name</label>
            <input type="text" id="first_name" placeholder="Enter your first name" required>
            <label for="last_name">Last Name</label>
            <input type="text" id="last_name" placeholder="Enter your last name" required>
            <label for="email">Email Address</label>
            <input type="email" id="email" placeholder="Enter your email" required>
            <label for="password">Password</label>
            <input type="password" id="password" placeholder="Create a password (min. 6 characters)" required>
            <label for="role">Role</label>
            <select id="role" required>
                <option value="student">Student</option>
                <option value="faculty">Faculty</option>
                <option value="admin">Admin</option>
            </select>
            <label for="dob">Date of Birth (Optional)</label>
            <input type="date" id="dob">
            <button type="submit">Sign Up</button>
            <p class="switch-auth">
                Already have an account? <a href="Loogin.php">Login here</a>
            </p>
        </form>
    </div>
    <footer class="auth-footer">
        2025 Attendance Management System
    </footer>
    <script src="script.js"></script>
</body>
</html>