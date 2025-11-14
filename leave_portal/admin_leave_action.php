<?php
session_start();
include 'E:/xampp/htdocs/leave_portal/includes/db_connect.php';

// --- Validate admin session ---
if (!isset($_SESSION['emp_id']) || 
    (strtolower($_SESSION['role']) !== 'admin' && strtolower($_SESSION['role']) !== 'administrator')) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // --- Fetch POST data ---
    $leave_id   = intval($_POST['leave_id'] ?? 0);
    $action     = trim($_POST['action'] ?? '');
    $action     = ucfirst(strtolower($action)); // ensure proper casing: Accepted / Rejected
    $remarks    = trim($_POST['remarks'] ?? '');
    $approver_id = $_SESSION['emp_id'];

    // --- Validate input ---
    if ($leave_id <= 0 || $action === '') {
        die("⚠️ Invalid leave action request.");
    }

    if ($action === 'Rejected' && $remarks === '') {
        die("❗ Remarks required when rejecting a leave.");
    }

    // --- Fetch leave details first ---
    $details_sql = "SELECT emp_id, leave_type_id, total_days FROM leave_application WHERE leave_id = ?";
    $details_stmt = $conn->prepare($details_sql);
    if (!$details_stmt) die("Prepare failed: " . $conn->error);
    $details_stmt->bind_param("i", $leave_id);
    $details_stmt->execute();
    $result = $details_stmt->get_result();
    $details = $result->fetch_assoc();
    $details_stmt->close();

    if (!$details) {
        die("❌ Leave record not found.");
    }

    // --- Update leave_application with admin decision ---
    $update_sql = "UPDATE leave_application 
                   SET status = ?, remarks = ?, approver_id = ? 
                   WHERE leave_id = ?";
    $stmt = $conn->prepare($update_sql);
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param("ssii", $action, $remarks, $approver_id, $leave_id);
    if (!$stmt->execute()) die("❌ Failed to update leave status: " . $stmt->error);
    $stmt->close();

    // --- Only deduct balance if Approved ---
    if ($action === 'Accepted') {
        $emp_id = $details['emp_id'];
        $leave_type_id = $details['leave_type_id'];
        $days = (int)$details['total_days'];

        $update_bal_sql = "UPDATE leave_balance 
                           SET remaining_days = GREATEST(remaining_days - ?, 0)
                           WHERE emp_id = ? AND leave_type_id = ?";
        $bal_stmt = $conn->prepare($update_bal_sql);
        if (!$bal_stmt) die("Prepare failed: " . $conn->error);
        $bal_stmt->bind_param("iii", $days, $emp_id, $leave_type_id);
        if (!$bal_stmt->execute()) die("❌ Failed to update leave balance: " . $bal_stmt->error);
        $bal_stmt->close();
    }

    $_SESSION['msg'] = "✅ Leave #$leave_id successfully marked as '$action'.";
    header("Location: dashboard.php");
    exit;

} else {
    header("Location: dashboard.php");
    exit;
}
?>
