<?php
session_start();
require_once "../config/db.php";

if(isset($_POST['register'])){

    $name     = $conn->real_escape_string($_POST['guest_name']);
    $email    = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    // Check passwords match
    if($password !== $confirm){
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: register.php");
        exit();
    }

    // Check if email already exists
    $check = $conn->prepare("SELECT Guest_Id FROM Guest WHERE Guest_Email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if($check->num_rows > 0){
        $_SESSION['error'] = "Email is already registered.";
        header("Location: register.php");
        exit();
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $createdDate    = date('Y-m-d');

    // Insert guest
    $stmt = $conn->prepare("INSERT INTO Guest 
        (Guest_Email, Guest_Password, Guest_Name, Guest_MemberStatus, Guest_VerifiedEmail, Guest_CreatedDate) 
        VALUES (?, ?, ?, 'Guest', FALSE, ?)");
    $stmt->bind_param("ssss", $email, $hashedPassword, $name, $createdDate);
    $stmt->execute();

    $_SESSION['success'] = "Account registered successfully! You can now sign in.";
    header("Location: register.php");
    exit();
}

$title = "Register - trivago";
include "../layout/header.php";
?>

<!-- SUCCESS TOAST -->
<?php if(isset($_SESSION['success'])): ?>
<div class="toast-container position-fixed top-0 end-0 p-3" style="margin-top:70px;">
    <div id="successToast" class="toast text-white bg-success border-0">
        <div class="toast-body">
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
    </div>
</div>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>

<!-- ERROR TOAST -->
<?php if(isset($_SESSION['error'])): ?>
<div class="toast-container position-fixed top-0 end-0 p-3" style="margin-top:70px;">
    <div id="errorToast" class="toast text-white bg-danger border-0">
        <div class="toast-body">
            <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
    </div>
</div>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card card-trivago p-4 bg-white">

                <h3 class="text-center mb-4">Create an Account</h3>

                <form method="POST">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name</label>
                        <input type="text" name="guest_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>

                    <button type="submit" name="register" class="btn btn-trivago w-100 mt-2">
                        Register
                    </button>

                </form>

                <p class="text-center text-muted mt-3 mb-0" style="font-size:14px;">
                    Already have an account?
                    <a href="login.php" style="color:var(--trivago-blue);">Sign in</a>
                </p>

            </div>
        </div>
    </div>
</div>

<?php include "../layout/footer.php"; ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const success = document.getElementById('successToast');
    const error   = document.getElementById('errorToast');

    if(success) new bootstrap.Toast(success, { delay: 3000 }).show();
    if(error)   new bootstrap.Toast(error,   { delay: 3000 }).show();
});
</script>