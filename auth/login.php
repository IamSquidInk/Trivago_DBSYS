<?php
session_start();
require_once "../config/db.php";

$error = "";

if(isset($_POST['login'])){

    $email    = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $query  = "SELECT * FROM Guest WHERE Guest_Email = '$email' LIMIT 1";
    $result = $conn->query($query);

    if($result->num_rows > 0){
        $guest = $result->fetch_assoc();

        if(password_verify($password, $guest['Guest_Password'])){

            // ========================
            // SET SESSION
            // ========================
            $_SESSION['guest_id']      = $guest['Guest_Id'];
            $_SESSION['guest_name']    = $guest['Guest_Name'];
            $_SESSION['guest_email']   = $guest['Guest_Email'];
            $_SESSION['member_status'] = $guest['Guest_MemberStatus'];
            $_SESSION['role'] = ($guest['Guest_MemberStatus'] === 'admin') ? 'admin' : 'member';

            // ========================
            // REDIRECT
            // ========================
            if ($_SESSION['role'] === 'admin') {
                header("Location: /trivago/admin/admin_dashboard.php");
            } else {
                header("Location: /trivago/user/user_dashboard.php");
            }
            exit();

        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No account found with that email.";
    }
}
?>

<?php
$title = "Sign In - trivago";
include "../layout/header.php";
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card card-trivago p-4 bg-white">

                <h3 class="text-center mb-4">Sign in</h3>

                <?php if($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <button type="submit" name="login" class="btn btn-trivago w-100 mt-2">
                        Sign in
                    </button>

                </form>

                <p class="text-center text-muted mt-3 mb-0" style="font-size:14px;">
                    Don't have an account?
                    <a href="register.php" style="color:var(--trivago-blue);">Register</a>
                </p>

            </div>
        </div>
    </div>
</div>

<?php include "../layout/footer.php"; ?>