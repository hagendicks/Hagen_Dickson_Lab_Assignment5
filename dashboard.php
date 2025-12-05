<?php
require_once 'auth_check.php';
session_start();
$username = $_SESSION['username'];
$user_role = $_SESSION['role'];
redirectBasedOnRole();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Attendance Management</title>
    <link rel="stylesheet" href="stylesheet.css">
</head>
<body>
    <header>
        <h1>Attendance Management System</h1>
        <div>
            Welcome, <?php echo htmlspecialchars($username); ?> (<?php echo htmlspecialchars($user_role); ?>)
            <button onclick="logout()" style="margin-left: 20px;">Logout</button>
        </div>
    </header>
    <div class="container">
        <nav class="sidebar">
            <ul>
                <li class="active"><a href="dashboard.php">Dashboard</a></li>
                <li><a href="courses.php">Courses</a></li>
                <li><a href="sessions.php">Sessions</a></li>
                <li><a href="attendance.php">Attendance</a></li>
                <li><a href="profile.php">Profile</a></li>
            </ul>
        </nav>
        <main>
            <div class="card">
                <h2>Welcome to Your Dashboard</h2>
                <p>You are logged in as: <strong><?php echo htmlspecialchars($username); ?></strong></p>
                <p>Your role: <strong><?php echo htmlspecialchars($user_role); ?></strong></p>
                <p>User ID: <strong><?php echo htmlspecialchars($_SESSION['user_id']); ?></strong></p>
            </div>
            <div class="card">
                <h2>Quick Stats</h2>
                <p>This is a protected page. Only logged-in users can see this content.</p>
            </div>
        </main>
    </div>
    <footer>
        2025 Attendance Management System
    </footer>
    <script src="script.js"></script>
</body>
</html>