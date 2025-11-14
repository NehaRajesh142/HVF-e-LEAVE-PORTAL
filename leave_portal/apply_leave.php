<?php
session_start();
include 'E:/xampp/htdocs/leave_portal/includes/db_connect.php';

if (!isset($_SESSION['emp_id'])) {
    header("Location: login.php");
    exit();
}

$emp_id = $_SESSION['emp_id'];

// Get gender safely
$emp_query = mysqli_query($conn, "SELECT gender FROM employee WHERE emp_id='$emp_id'");
$emp = mysqli_fetch_assoc($emp_query);
$gender = $emp['gender'] ?? 'None';

// Fetch leave types
$leave_types = mysqli_query($conn, "SELECT * FROM leave_type");

// Fetch leave balances for this employee
$leave_balances = [];
mysqli_data_seek($leave_types, 0);
while ($row = mysqli_fetch_assoc($leave_types)) {
    $bal_query = mysqli_query($conn, "SELECT remaining_days FROM leave_balance WHERE emp_id='$emp_id' AND leave_type_id='{$row['leave_type_id']}'");
    $bal_row = mysqli_fetch_assoc($bal_query);
    $leave_balances[$row['leave_type_id']] = $bal_row['remaining_days'] ?? 0;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $leave_type_id = $_POST['leave_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = $_POST['reason'];

    // Fetch leave type details
    $type_query = mysqli_query($conn, "SELECT * FROM leave_type WHERE leave_type_id='$leave_type_id'");
    $type = mysqli_fetch_assoc($type_query);

    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $diff = $end->diff($start)->days + 1;

    // Gender restriction check
    if ($type['gender_restriction'] != 'None' && strtolower($type['gender_restriction']) != strtolower($gender)) {
        echo "<script>alert('You are not eligible to apply for this leave type.');</script>";
    } 
    // Leave balance check
    elseif ($diff > ($leave_balances[$leave_type_id] ?? 0)) {
        $available = $leave_balances[$leave_type_id] ?? 0;
        echo "<script>alert('Insufficient leave balance. You have only $available days left for this leave type.');</script>";
    } else {
        $certificate = null;

        // ✅ Handle certificate upload
        if ($type['requires_certificate'] == 1 && isset($_FILES['certificate']) && $_FILES['certificate']['name'] != '') {
            $upload_dir = "uploads/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true); // create folder if it doesn't exist
            $file_name = time() . "_" . basename($_FILES['certificate']['name']);
            $file_tmp = $_FILES['certificate']['tmp_name'];
            if (move_uploaded_file($file_tmp, $upload_dir . $file_name)) {
                $certificate = $file_name;
            }
        }

        $query = "INSERT INTO leave_application 
                  (emp_id, leave_type_id, start_date, end_date, total_days, reason, certificate, status, created_at)
                  VALUES ('$emp_id', '$leave_type_id', '$start_date', '$end_date', '$diff', '$reason',
                          " . ($certificate ? "'$certificate'" : "NULL") . ", 'Pending', NOW())";

        if (mysqli_query($conn, $query)) {
            // ⚠️ Leave deduction removed. Admin will deduct on approval.
            echo "<script>alert('Leave application submitted successfully! Pending admin approval.'); window.location='dashboard.php';</script>";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Apply for Leave</title>
<style>
/* === KEEP ALL YOUR EXISTING STYLES === */
body { font-family: "Poppins", sans-serif; background: linear-gradient(135deg, #eef2f3, #d9e4f5); margin:0; padding:0; display:flex; justify-content:center; align-items:center; min-height:100vh; }
.container { background:#fff; border-radius:20px; box-shadow:0 10px 25px rgba(0,0,0,0.1); padding:30px; width:450px; }
h2 { text-align:center; color:#4a6fa5; margin-bottom:25px; }
label { font-weight:600; color:#333; margin-bottom:6px; display:block; }
select, input, textarea { width:100%; padding:10px 12px; border:1px solid #ccc; border-radius:8px; margin-bottom:15px; font-size:15px; }
select:focus, input:focus, textarea:focus { outline:none; border-color:#4a6fa5; box-shadow:0 0 4px #4a6fa5; }
button { width:48%; padding:10px; border:none; border-radius:8px; background-color:#4a6fa5; color:white; font-size:16px; cursor:pointer; transition:0.3s; }
button:hover { background-color:#3c5a85; }
button:disabled { cursor:not-allowed; background-color:#aaa; }
.btn-row { display:flex; justify-content:space-between; }
#certificateDiv { display:none; }
#previewSection { background-color:#f8f9ff; border-left:4px solid #4a6fa5; margin-top:20px; padding:15px; border-radius:10px; display:none; }
</style>
<script>
const leaveBalances = <?php echo json_encode($leave_balances); ?>;
const leaveRequiresCert = <?php
    $req = [];
    mysqli_data_seek($leave_types, 0);
    while ($row = mysqli_fetch_assoc($leave_types)) {
        $req[$row['leave_type_id']] = $row['requires_certificate'];
    }
    echo json_encode($req);
?>;

window.onload = function() {
    let today = new Date().toISOString().split("T")[0];
    document.getElementById("start_date").setAttribute("min", today);
    document.getElementById("end_date").setAttribute("min", today);
}

function updateCertificateField() {
    const selected = document.getElementById("leave_type").value;
    const certDiv = document.getElementById("certificateDiv");
    certDiv.style.display = leaveRequiresCert[selected] == 1 ? 'block' : 'none';
    updateBalanceAndValidate();
}

function calculateDays() {
    const start = new Date(document.getElementById("start_date").value);
    const end = new Date(document.getElementById("end_date").value);
    if (start && end && end >= start) {
        const diff = Math.floor((end - start)/(1000*60*60*24)) + 1;
        document.getElementById("total_days").value = diff;
    }
    updateBalanceAndValidate();
}

function updateBalanceAndValidate() {
    const selected = document.getElementById("leave_type").value;
    const totalDays = parseInt(document.getElementById("total_days").value || 0);
    const available = leaveBalances[selected] || 0;
    document.getElementById("availableDays").innerText = available;

    const submitBtn = document.querySelector('button[type="submit"]');
    submitBtn.disabled = totalDays === 0 || totalDays > available;
}

function previewForm() {
    const type = document.getElementById("leave_type").options[document.getElementById("leave_type").selectedIndex].text;
    const start = document.getElementById("start_date").value;
    const end = document.getElementById("end_date").value;
    const days = document.getElementById("total_days").value;
    const reason = document.getElementById("reason").value;

    let preview = `
        <strong>Leave Type:</strong> ${type}<br>
        <strong>Start Date:</strong> ${start}<br>
        <strong>End Date:</strong> ${end}<br>
        <strong>Total Days:</strong> ${days}<br>
        <strong>Reason:</strong> ${reason}<br>
    `;
    document.getElementById("previewBox").innerHTML = preview;
    document.getElementById("previewSection").style.display = 'block';
}
</script>
</head>
<body>
<div class="container">
    <h2>Apply Leave</h2>
    <form method="POST" enctype="multipart/form-data">
        <label>Leave Type</label>
        <select name="leave_type" id="leave_type" onchange="updateCertificateField()" required>
            <option value="">-- Select Leave Type --</option>
            <?php
            mysqli_data_seek($leave_types, 0);
            while ($row = mysqli_fetch_assoc($leave_types)) {
                echo "<option value='{$row['leave_type_id']}'>{$row['name']}</option>";
            }
            ?>
        </select>

        <label>Available Days: <span id="availableDays">0</span></label>

        <label>Start Date</label>
        <input type="date" name="start_date" id="start_date" onchange="calculateDays()" required>

        <label>End Date</label>
        <input type="date" name="end_date" id="end_date" onchange="calculateDays()" required>

        <label>Total Days</label>
        <input type="number" name="total_days" id="total_days" readonly>

        <label>Reason</label>
        <textarea name="reason" id="reason" rows="4" required></textarea>

        <div id="certificateDiv">
            <label>Upload Certificate</label>
            <input type="file" name="certificate" accept=".pdf,.jpg,.png">
        </div>

        <div class="btn-row">
            <button type="button" onclick="previewForm()">Preview</button>
            <button type="submit" disabled>Submit</button>
        </div>
    </form>

    <div id="previewSection">
        <h3>Preview</h3>
        <div id="previewBox"></div>
    </div>
</div>
</body>
</html>
