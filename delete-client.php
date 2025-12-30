<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

// Get client ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view-clients.php");
    exit();
}

$client_id = intval($_GET['id']);

// Delete client
$stmt = $conn->prepare("DELETE FROM clients WHERE client_id = ?");
$stmt->bind_param("i", $client_id);

if ($stmt->execute()) {
    header("Location: view-clients.php?deleted=success");
} else {
    header("Location: view-clients.php?error=delete_failed");
}

$stmt->close();
$conn->close();
exit();
?>
