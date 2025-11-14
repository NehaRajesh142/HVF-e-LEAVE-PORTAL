<?php
session_start();
include 'E:/xampp/htdocs/leave_portal/includes/db_connect.php';

if (!isset($_SESSION['emp_id'])) {
    echo json_encode([
        'leave_statuses'=>[], 
        'calendar_leaves'=>[],
        'balances'=>[],
        'labels'=>[]
    ]);
    exit;
}

$emp_id = (int)$_SESSION['emp_id'];

// --- Leave balances ---
$balances = [];
$labels = [];
$balance_sql = "
    SELECT lt.name AS leave_type, COALESCE(lb.remaining_days,0) AS remaining_days
    FROM leave_type lt
    LEFT JOIN leave_balance lb ON lb.leave_type_id = lt.leave_type_id AND lb.emp_id = $emp_id
    ORDER BY lt.leave_type_id
";
$res = $conn->query($balance_sql);
while($row = $res->fetch_assoc()){
    $labels[] = $row['leave_type'];
    $balances[] = (int)$row['remaining_days'];
}

// --- Leave statuses ---
$leave_statuses = [];
$status_sql = "
    SELECT la.leave_id, lt.name AS leave_type, la.start_date, la.end_date, 
           la.total_days, la.status, la.remarks
    FROM leave_application la
    JOIN leave_type lt ON la.leave_type_id = lt.leave_type_id
    WHERE la.emp_id = $emp_id
    ORDER BY la.created_at DESC
";
$res = $conn->query($status_sql);
while($row = $res->fetch_assoc()){
    $leave_statuses[] = [
        'leave_id' => (int)$row['leave_id'],
        'leave_type' => $row['leave_type'],
        'start_date' => $row['start_date'],
        'end_date' => $row['end_date'],
        'total_days' => (int)$row['total_days'],
        'status' => $row['status'] ?? 'pending',
        'remarks' => $row['remarks']
    ];
}

// --- Approved leaves for calendar ---
$calendar_leaves = [];
$cal_sql = "
    SELECT lt.name AS leave_type, la.start_date, la.end_date
    FROM leave_application la
    JOIN leave_type lt ON la.leave_type_id = lt.leave_type_id
    WHERE la.emp_id = $emp_id AND LOWER(la.status)='accepted'
";
$cal_res = $conn->query($cal_sql);
while($row = $cal_res->fetch_assoc()){
    $calendar_leaves[] = [
        'title' => $row['leave_type'],
        'start' => $row['start_date'],
        'end'   => date('Y-m-d', strtotime($row['end_date'].' +1 day'))
    ];
}

// --- Return JSON ---
echo json_encode([
    'leave_statuses' => $leave_statuses,
    'calendar_leaves' => $calendar_leaves,
    'balances' => $balances,
    'labels' => $labels
]);
?>
