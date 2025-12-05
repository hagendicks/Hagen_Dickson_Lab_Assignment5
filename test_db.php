<?php
$conn = new mysqli("localhost", "root", "PaDaK123@$$", "attendancemanagement");

if ($conn->connect_error) {
    die("Failed: " . $conn->connect_error);
}
echo "Connected!";
?>
