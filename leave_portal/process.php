<?php
session_start();
include 'E:/xampp/htdocs/leave_portal/includes/db_connect.php';

// Handle Registration & Login
$message = "";

// Handle Registration
if (isset($_POST['register'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $conn->real_escape_string($_POST['password']);
    $role = $conn->real_escape_string($_POST['role']);
    $gender = $conn->real_escape_string($_POST['gender']);
    $department = $conn->real_escape_string($_POST['department']);

    // Check if email exists
    $check = $conn->query("SELECT * FROM employee WHERE email='$email'");
    if ($check->num_rows > 0) {
        // Redirect back with message, stay on Sign Up
        header("Location: index.php?message=" . urlencode("❌ Email already registered!") . "&action=signup");
        exit;
    } else {
        $insert = "INSERT INTO employee (name,email,password,role,gender,department)
                   VALUES ('$name','$email','$password','$role','$gender','$department')";
        if ($conn->query($insert)) {
            $emp_id = $conn->insert_id;

            // Assign leave balances
            if ($gender == 'Male') {
                $conn->query("
                    INSERT INTO leave_balance (emp_id, leave_type_id, remaining_days)
                    SELECT $emp_id, leave_type_id,
                    CASE WHEN name='Maternity Leave' OR name='Child Care Leave' THEN 0 ELSE max_days END
                    FROM leave_type
                ");
            } elseif ($gender == 'Female') {
                $conn->query("
                    INSERT INTO leave_balance (emp_id, leave_type_id, remaining_days)
                    SELECT $emp_id, leave_type_id,
                    CASE WHEN name='Paternity Leave' THEN 0 ELSE max_days END
                    FROM leave_type
                ");
            } else {
                $conn->query("
                    INSERT INTO leave_balance (emp_id, leave_type_id, remaining_days)
                    SELECT $emp_id, leave_type_id, max_days FROM leave_type
                ");
            }

            // Redirect back with message, switch to Sign In
            header("Location: index.php?message=" . urlencode("✅ Registration successful! You can now log in.") . "&action=signin");
            exit;
        } else {
            header("Location: index.php?message=" . urlencode("❌ Error: " . $conn->error) . "&action=signup");
            exit;
        }
    }
}

// Handle Login
if (isset($_POST['login'])) {
    $email = $conn->real_escape_string($_POST['login_email']);
    $password = $conn->real_escape_string($_POST['login_password']);

    $result = $conn->query("SELECT * FROM employee WHERE email='$email' AND password='$password'");
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $_SESSION['emp_id'] = $row['emp_id'];
        $_SESSION['role'] = $row['role'];
        $_SESSION['name'] = $row['name'];

        header("Location: dashboard.php");
        exit;
    } else {
        header("Location: index.php?message=" . urlencode("⚠️ Invalid Email or Password!") . "&action=signin");
        exit;
    }
}
?>
