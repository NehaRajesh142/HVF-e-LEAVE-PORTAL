<?php
// download_certificate.php
session_start();
include 'E:/xampp/htdocs/leave_portal/includes/db_connect.php';

if (!isset($_SESSION['emp_id'])) {
    header("Location: index.php");
    exit;
}

$leave_id = isset($_GET['leave_id']) ? (int)$_GET['leave_id'] : 0;
if (!$leave_id) exit('Invalid request');

// Fetch certificate filename from DB
$query = $conn->prepare("SELECT certificate FROM leave_application WHERE leave_id=?");
$query->bind_param("i", $leave_id);
$query->execute();
$result = $query->get_result();
$row = $result->fetch_assoc();

if (!$row || empty($row['certificate'])) {
    exit('No certificate found.');
}

$file = __DIR__ . '/uploads/' . $row['certificate'];
if (!file_exists($file)) exit('File not found.');

// Serve the file
header('Content-Description: File Transfer');
header('Content-Type: application/pdf'); // forces browser to handle as PDF
header('Content-Disposition: inline; filename="' . basename($file) . '"');
header('Content-Length: ' . filesize($file));
readfile($file);
exit;
