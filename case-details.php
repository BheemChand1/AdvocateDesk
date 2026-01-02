<?php
error_reporting(E_ALL); ini_set('display_errors', 1);
ob_start();
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

// Get case ID from URL
$case_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$case_id) {
    header("Location: view-cases.php");
    exit();
}

// Fetch case type
$query = "SELECT case_type FROM cases WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $case_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$case_data = mysqli_fetch_assoc($result);

if (!$case_data) {
    header("Location: view-cases.php");
    exit();
}

// Redirect to the appropriate case-type specific page (robust mapping)
$basePath = '/clients/';
$case_type = strtolower(trim($case_data['case_type'] ?? ''));
$target = '';

switch ($case_type) {
    case 'ni_passa':
        $target = $basePath . 'case-details-ni-passa.php?id=' . $case_id;
        break;
    case 'criminal':
        $target = $basePath . 'case-details-criminal.php?id=' . $case_id;
        break;
    case 'consumer_civil':
        $target = $basePath . 'case-details-consumer-civil.php?id=' . $case_id;
        break;
    case 'ep_arbitration':
        $target = $basePath . 'case-details-ep-arbitration.php?id=' . $case_id;
        break;
    case 'arbitration_other':
        $target = $basePath . 'case-details-arbitration-other.php?id=' . $case_id;
        break;
}

// Fallback pattern-based routing for unexpected case_type values
if ($target === '') {
    if (strpos($case_type, 'ni') !== false || strpos($case_type, 'passa') !== false) {
        $target = $basePath . 'case-details-ni-passa.php?id=' . $case_id;
    } elseif (strpos($case_type, 'criminal') !== false) {
        $target = $basePath . 'case-details-criminal.php?id=' . $case_id;
    } elseif (strpos($case_type, 'consumer') !== false || strpos($case_type, 'civil') !== false) {
        $target = $basePath . 'case-details-consumer-civil.php?id=' . $case_id;
    } elseif (
        strpos($case_type, 'ep') !== false ||
        strpos($case_type, 'execution') !== false ||
        (strpos($case_type, 'arbitration') !== false && strpos($case_type, 'other') === false)
    ) {
        $target = $basePath . 'case-details-ep-arbitration.php?id=' . $case_id;
    } elseif (strpos($case_type, 'arbitration') !== false && strpos($case_type, 'other') !== false) {
        $target = $basePath . 'case-details-arbitration-other.php?id=' . $case_id;
    }
}

// Final fallback to cases list
if ($target === '') {
    $target = $basePath . 'view-cases.php';
}

header('Location: ' . $target);
ob_end_clean();
exit();
