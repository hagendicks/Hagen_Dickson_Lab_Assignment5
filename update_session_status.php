<?php
session_start();
require_once 'auth_check.php';
requireFaculty();
require_once 'db_connect.php';

header('Content-Type: application/json');
$response = array();

$input = json_decode(file_get_contents("php://input"), true);
if (empty($input)) $input = $_POST;

try {
    if (empty($input['session_id']) || empty($input['status'])) {
        throw new Exception('Missing required parameters');
    }
    
    $session_id = (int)$input['session_id'];
    $status = $input['status'];
    
    $valid_statuses = ['upcoming', 'active', 'completed', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception('Invalid status');
    }
    
    $stmt = $conn->prepare("UPDATE sessions SET status = ? WHERE session_id = ? AND faculty_id = ?");
    $stmt->bind_param("sii", $status, $session_id, $_SESSION['user_id']);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update session status');
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Session not found or unauthorized');
    }
    
    $stmt->close();
    
    $response['success'] = true;
    $response['message'] = 'Session status updated successfully';
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
$conn->close();
?>