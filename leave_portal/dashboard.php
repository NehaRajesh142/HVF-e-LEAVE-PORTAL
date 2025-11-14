<?php
session_start();
include 'E:/xampp/htdocs/leave_portal/includes/db_connect.php';

if (!isset($_SESSION['emp_id'])) {
    header("Location: index.php");
    exit;
}

$emp_id = (int)$_SESSION['emp_id'];
$name = $_SESSION['name'];
$role = $_SESSION['role'];

// === Fetch leave balances ===
$balances = [];     
$leave_types = [];  
$max_days_arr = [];
$result = $conn->query("
    SELECT lt.leave_type_id, lt.name, lt.max_days, COALESCE(lb.remaining_days,0) AS remaining_days
    FROM leave_type lt
    LEFT JOIN leave_balance lb 
      ON lb.leave_type_id = lt.leave_type_id 
     AND lb.emp_id = $emp_id
    ORDER BY lt.leave_type_id
");
while ($row = $result->fetch_assoc()) {
    $id = (int)$row['leave_type_id'];
    $balances[$id] = (int)$row['remaining_days'];
    $leave_types[$id] = $row['name'];
    $max_days_arr[$id] = (int)$row['max_days'];
}

// prepare JSON for charts
$chart_labels = json_encode(array_values($leave_types));
$chart_data   = json_encode(array_values($balances));
$chart_max    = json_encode(array_values($max_days_arr));

// === Leave status list for this user ===
$leave_statuses = [];
$status_result = $conn->query("
    SELECT la.leave_id, lt.name AS leave_type, la.start_date, la.end_date, la.total_days, la.status, la.remarks
    FROM leave_application la
    JOIN leave_type lt ON la.leave_type_id = lt.leave_type_id
    WHERE la.emp_id = $emp_id
    ORDER BY la.created_at DESC
");
while ($r = $status_result->fetch_assoc()) {
    $leave_statuses[] = $r;
}

// === Pending leaves for admin ===
$pending_leaves = [];
if (strtolower($role) === 'admin' || strtolower($role) === 'administrator') {
    $pending_q = "
        SELECT la.leave_id, e.name AS emp_name, lt.name AS leave_type, la.start_date, la.end_date, la.total_days, la.reason, la.certificate
        FROM leave_application la
        JOIN leave_type lt ON la.leave_type_id = lt.leave_type_id
        JOIN employee e ON la.emp_id = e.emp_id
        WHERE la.status = 'Pending'
        ORDER BY la.created_at ASC
    ";
    $pending_res = $conn->query($pending_q);
    while ($p = $pending_res->fetch_assoc()) {
        $pending_leaves[] = $p;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>User Dashboard</title>
<script src="js/chart.js"></script>
<style>
body { font-family:'Poppins', sans-serif; margin:0; padding:0; background:#f0f2f5; color:#0b0b0b; }
.container { max-width:1200px; margin:30px auto; padding:0 20px; }
h2 { color:#001f3f; margin-bottom:12px; font-weight:600; }
.dashboard-section { background:#fff; padding:20px; border-radius:12px; margin-bottom:18px; box-shadow:0 6px 18px rgba(10,10,10,0.04); }
.card-row { display:flex; flex-wrap:wrap; gap:20px; }
.month-selector { display:flex; gap:10px; margin-bottom:18px; }
.month-selector button { padding:8px 14px; border:none; border-radius:999px; cursor:pointer; background:#e8eaed; font-weight:600; color:#333; }
.month-selector button.active { background:#001f3f; color:#fff; }
table { width:100%; border-collapse:collapse; margin-bottom:8px; font-size:14px; }
th, td { padding:10px; text-align:left; border-bottom:1px solid #eee; vertical-align:middle; }
th { background:#fafafa; color:#333; font-weight:700; }
button { cursor:pointer; border:none; border-radius:8px; font-weight:700; }
.btn-primary { background:#001f3f; color:#fff; padding:8px 14px; }
.btn-yellow { background:#f1c40f; color:#000; padding:8px 14px; }
.btn-certificate { background:#3498db; color:#fff; padding:4px 10px; font-size:13px; }
.chart-container { width:100%; max-width:700px; margin:0 auto; position:relative; }
.donut-center { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); font-size:16px; font-weight:700; color:#001f3f; text-align:center; }
.legend { display:flex; flex-wrap:wrap; gap:10px; margin-top:10px; }
.legend-item { display:flex; align-items:center; gap:8px; font-weight:600; color:#333; }
.legend-color { width:14px; height:14px; border-radius:4px; display:inline-block; }
.badge { display:inline-block; padding:6px 10px; border-radius:999px; font-weight:700; font-size:13px; color:#fff; }
.badge-accepted { background:#2ecc71; }
.badge-pending  { background:#f39c12; }
.badge-rejected { background:#e74c3c; }
@media (max-width:900px){
  .card-row { flex-direction:column; }
  .chart-container { max-width:100%; }
}
</style>
</head>
<body>
<div class="container">

<h2>Welcome, <?php echo htmlspecialchars($name); ?>!</h2>

<div class="month-selector">
<?php
$months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
foreach($months as $i => $m) {
    $active = ($i+1)==date('n') ? 'active' : '';
    echo "<button class='$active'>$m</button>";
}
?>
</div>

<div class="card-row">
<div class="dashboard-section" style="flex:1 1 360px;">
<h3>Leave Balances</h3>
<table>
<thead><tr><th>Leave Type</th><th>Remaining</th><th>Max Days</th></tr></thead>
<tbody>
<?php foreach($balances as $id => $remaining): ?>
<tr>
<td><?php echo htmlspecialchars($leave_types[$id]); ?></td>
<td><?php echo htmlspecialchars($remaining); ?></td>
<td><?php echo htmlspecialchars($max_days_arr[$id]); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<button class="btn-yellow" onclick="window.location.href='apply_leave.php'">Apply for Leave</button>
</div>

<div class="dashboard-section chart-container" style="position:relative; flex:1 1 560px;">
<h3>Leave Distribution</h3>
<canvas id="donutChart"></canvas>
<div class="donut-center"><?php echo array_sum($balances); ?><div style="font-size:12px;font-weight:600;color:#666;">Total Days Left</div></div>
<div class="legend" aria-hidden="true">
<?php
$legend_codes = ['EL','HPL','COM','LND','EOL','ML','PL','AL','CCL','SL','SPL'];
$colors = ['#f1c40f','#1abc9c','#3498db','#e74c3c','#9b59b6','#34495e','#2ecc71','#e67e22','#16a085','#7f8c8d','#c0392b'];
for ($i=0; $i<count($legend_codes); $i++) {
    $code = $legend_codes[$i];
    $color = $colors[$i] ?? '#999';
    echo "<div class='legend-item'><span class='legend-color' style='background:{$color}'></span>{$code}</div>";
}
?>
</div>
</div>
</div>

<div class="card-row">
<div class="dashboard-section chart-container" style="flex:1 1 100%;"><h3>Total Leaves Left</h3><canvas id="barChart1"></canvas></div>
</div>

<div class="dashboard-section">
<h3>Your Leave Status</h3>
<table id="dashboard-leave-status">
<thead>
<tr><th>Leave Type</th><th>Start</th><th>End</th><th>Total Days</th><th>Status</th><th>Remarks</th></tr>
</thead>
<tbody>
<?php if (count($leave_statuses)===0): ?>
<tr><td colspan="6" style="text-align:center;color:#666;padding:16px;">No leave applications yet.</td></tr>
<?php else: ?>
<?php foreach($leave_statuses as $l): 
    $status = $l['status'] ?? 'Pending';
    $badgeClass = 'badge-pending';
    if(strtolower($status)==='accepted') $badgeClass='badge-accepted';
    if(strtolower($status)==='rejected') $badgeClass='badge-rejected';
?>
<tr>
<td><?php echo htmlspecialchars($l['leave_type']); ?></td>
<td><?php echo htmlspecialchars($l['start_date']); ?></td>
<td><?php echo htmlspecialchars($l['end_date']); ?></td>
<td><?php echo htmlspecialchars($l['total_days']); ?></td>
<td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span></td>
<td><?php echo htmlspecialchars($l['remarks']??''); ?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>

<!-- Admin Pending Requests Panel -->
<?php if (strtolower($role) === 'admin' || strtolower($role) === 'administrator'): ?>
<div class="dashboard-section">
<h3>Pending Leave Requests</h3>
<?php if (count($pending_leaves)===0): ?>
<p style="color:#666;">No pending leave requests.</p>
<?php else: ?>
<table>
<thead>
<tr>
<th>Employee</th>
<th>Leave Type</th>
<th>Start</th>
<th>End</th>
<th>Total Days</th>
<th>Reason</th>
<th>Certificate</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach($pending_leaves as $pl): ?>
<tr>
<td><?php echo htmlspecialchars($pl['emp_name']); ?></td>
<td><?php echo htmlspecialchars($pl['leave_type']); ?></td>
<td><?php echo htmlspecialchars($pl['start_date']); ?></td>
<td><?php echo htmlspecialchars($pl['end_date']); ?></td>
<td><?php echo htmlspecialchars($pl['total_days']); ?></td>
<td><?php echo htmlspecialchars($pl['reason']); ?></td>
<td>
<?php 
$cert_path = __DIR__ . '/uploads/' . ($pl['certificate'] ?? '');
if (!empty($pl['certificate']) && file_exists($cert_path)): ?>
<a href="download_certificate.php?leave_id=<?php echo (int)$pl['leave_id']; ?>" target="_blank" class="btn-certificate">View</a>
<?php else: ?>
    N/A
<?php endif; ?>
</td>
<td>
<form method="post" action="admin_leave_action.php" style="display:flex;gap:8px;align-items:center;">
<input type="hidden" name="leave_id" value="<?php echo (int)$pl['leave_id']; ?>">

<select name="action" required style="padding:6px;border-radius:6px;border:1px solid #ddd;">
    <option value="" disabled selected>Select</option>
    <option value="Accepted">Accept</option>
    <option value="Rejected">Reject</option>
</select>

<input type="text" name="remarks" placeholder="Remarks (required if reject)" style="padding:6px;border-radius:6px;border:1px solid #ddd;">

<button type="submit" class="btn-primary">Submit</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
<?php endif; ?>

<div style="margin:18px 0;">
<button class="btn-primary" onclick="window.location.href='logout.php'">Logout</button>
</div>
</div>

<script>
// --- Donut Chart ---
const donutCtx = document.getElementById('donutChart').getContext('2d');
const donutLabels = <?php echo $chart_labels; ?>;
const donutData   = <?php echo $chart_data; ?>;
const donutColors = ['#f1c40f','#1abc9c','#3498db','#e74c3c','#9b59b6','#34495e','#2ecc71','#e67e22','#16a085','#7f8c8d','#c0392b','#2980b9','#f39c12','#8e44ad'];
const donutBg = donutColors.slice(0, donutLabels.length);
while (donutBg.length < donutLabels.length) donutBg.push('#cccccc');

new Chart(donutCtx, {
    type:'doughnut',
    data:{ labels:donutLabels, datasets:[{ data:donutData, backgroundColor:donutBg }] },
    options:{ cutout:'70%', responsive:true, plugins:{legend:{display:false}, tooltip:{callbacks:{label: ctx=>ctx.label+': '+(ctx.parsed||0)+' days'}}} }
});

// --- Bar Chart ---
const barCtx1 = document.getElementById('barChart1').getContext('2d');
new Chart(barCtx1, {
    type:'bar',
    data:{ labels:donutLabels, datasets:[{ label:'Remaining Leaves', data:donutData, backgroundColor:'#3498db' }] },
    options:{ responsive:true, scales:{ y:{ beginAtZero:true, ticks:{ precision:0 } } }, plugins:{ tooltip:{ callbacks:{ label: ctx=>'Remaining Leaves: '+(ctx.parsed.y||ctx.parsed)+' days' } } } }
});
</script>
</body>
</html>
