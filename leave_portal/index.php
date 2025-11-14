<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HVF Leave Portal</title>
<style>
body {
  margin: 0;
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(135deg,#000,#1a1a1a);
  height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
}
.container {
  background: #fff;
  border-radius: 12px;
  width: 380px;
  box-shadow: 0 0 15px rgba(255,255,255,0.1);
  overflow: hidden;
}
.header {
  display: flex;
}
.header button {
  flex: 1;
  border: none;
  padding: 15px;
  cursor: pointer;
  font-weight: bold;
  color: #fff;
  background: #001f3f;
}
.header button.active {
  background: #f1c40f;
  color: #000;
}
.form-container {
  padding: 30px;
  text-align: center;
}
h2 {
  margin-bottom: 10px;
  color: #001f3f;
}
form {
  display: none;
}
form.active {
  display: block;
}
input, select {
  width: 100%;
  padding: 10px;
  margin: 8px 0;
  border-radius: 6px;
  border: 1px solid #ccc;
}
button.submit-btn {
  width: 100%;
  background: #f1c40f;
  color: #000;
  font-weight: bold;
  border: none;
  padding: 10px;
  margin-top: 10px;
  border-radius: 6px;
  cursor: pointer;
}
p.message {
  color: #333;
  font-size: 14px;
  margin-top: 10px;
}
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <button id="signInBtn" class="active">SIGN IN</button>
    <button id="signUpBtn">SIGN UP</button>
  </div>
  <div class="form-container">
    <h2>Welcome to HVF Leave Portal</h2>
    <p style="font-size:13px;color:#777;">Manage your leaves and approvals seamlessly</p>

    <!-- Display messages -->
    <?php if(isset($_GET['message'])) {
        echo "<p class='message'>" . htmlspecialchars($_GET['message']) . "</p>";
    } ?>

    <!-- Sign In Form -->
    <form id="signInForm" class="active" method="POST" action="process.php">
      <input type="email" name="login_email" placeholder="Email" required>
      <input type="password" name="login_password" placeholder="Password" required>
      <button type="submit" name="login" class="submit-btn">SIGN IN</button>
    </form>

    <!-- Sign Up Form -->
    <form id="signUpForm" method="POST" action="process.php">
      <input type="text" name="name" placeholder="Full Name" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <select name="gender" required>
        <option value="">Select Gender</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
        <option value="Transgender">Transgender</option>
      </select>
      <select name="role" required>
        <option value="">Select Role</option>
        <option value="User">User</option>
        <option value="Admin">Admin</option>
      </select>
      <input type="text" name="department" placeholder="Department" required>
      <button type="submit" name="register" class="submit-btn">REGISTER</button>
    </form>
  </div>
</div>

<script>
const signInBtn = document.getElementById('signInBtn');
const signUpBtn = document.getElementById('signUpBtn');
const signInForm = document.getElementById('signInForm');
const signUpForm = document.getElementById('signUpForm');

function showSignIn() {
  signInBtn.classList.add('active');
  signUpBtn.classList.remove('active');
  signInForm.classList.add('active');
  signUpForm.classList.remove('active');
}

function showSignUp() {
  signUpBtn.classList.add('active');
  signInBtn.classList.remove('active');
  signUpForm.classList.add('active');
  signInForm.classList.remove('active');
}

signInBtn.onclick = showSignIn;
signUpBtn.onclick = showSignUp;

// Automatically switch based on PHP GET param
<?php if(isset($_GET['action']) && $_GET['action'] == 'signin') { ?>
  showSignIn();
<?php } elseif(isset($_GET['action']) && $_GET['action'] == 'signup') { ?>
  showSignUp();
<?php } ?>
</script>
</body>
</html>
