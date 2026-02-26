<?php
include '../../config.php';

if (isset($_POST['submit'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];
    $id = $_POST['id'];
    $sql = "SELECT userid, password FROM user_tbl WHERE userid = $id";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    if (password_verify($old_password, $row['password'])) {
        if ($new_password === $confirm_new_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE user_tbl SET password = '$hashed_password' WHERE userid = '$id'";
            if (mysqli_query($conn, $update_sql)) {
                echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    // Show the toast notification first
                    const Toast = Swal.mixin({
                        toast: true,
                        position: "top-end", 
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener("mouseenter", Swal.stopTimer);
                            toast.addEventListener("mouseleave", Swal.resumeTimer);
                        }
                    });
        
                    // First toast message for successful update
                    Toast.fire({
                        icon: "success",
                        title: "Successfully Updated!!!"
                    }).then(function() {
                        // Show modal to confirm logging out
                        Swal.fire({
                            title: "You need to log out",
                            text: "Please log out to apply the changes.",
                            icon: "info",
                            showCancelButton: true,
                            confirmButtonText: "Logout",
                            cancelButtonText: "Stay logged in"
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = "../../logout";
                            }
                        });
                    });
                });
            </script>';

            } else {
                echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end", 
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer)
                        toast.addEventListener("mouseleave", Swal.resumeTimer)
                    }
                })

                Toast.fire({
                    icon: "error",
                    title: "Update Failed!!!"
                }) 
            });
        </script>';

            }
        } else {
            echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end", 
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer)
                        toast.addEventListener("mouseleave", Swal.resumeTimer)
                    }
                })

                Toast.fire({
                    icon: "error",
                    title: "Mismatch Password!!!"
                }) 
            });
        </script>';

        }
    } else {
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const Toast = Swal.mixin({
                toast: true,
                position: "top-end", 
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener("mouseenter", Swal.stopTimer)
                    toast.addEventListener("mouseleave", Swal.resumeTimer)
                }
            })

            Toast.fire({
                icon: "error",
                title: "Incorrect old Password!!!"
            }) 
        });
    </script>';

    }
}

?>

<!DOCTYPE html>
<html lang="en">
<?php include './../template/header.php' ?>

<body id="page-top">
    <div id="wrapper">
        <?php include './../template/sidebar.php' ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include './../template/navbar.php'; ?>
                <div class="container-fluid"> 
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Change Password</h6>
                        </div>
                        <div class="card-body">
                            <p>Change password Information.</p>
                            <form action="" method="POST">
                                <div class="col-12 mb-1">
                                    <input type="hidden" name="id" value="<?php echo $userid; ?>">
                                    <label for="oldPassword">Old Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="old_password" id="oldPassword"
                                            required>
                                        <div class="input-group-append">
                                            <span class="input-group-text" id="showHidePassword"
                                                onclick="togglePassword('oldPassword')">
                                                <i class="bi bi-eye"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 mb-1">
                                    <label for="newPassword">New Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="new_password" id="newPassword"
                                            required>
                                        <div class="input-group-append">
                                            <span class="input-group-text" id="showHidePassword"
                                                onclick="togglePassword('newPassword')">
                                                <i class="bi bi-eye"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 mb-1">
                                    <label for="confirmPassword">Confirm New Password <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="confirm_new_password"
                                            id="confirmPassword" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text" id="showHidePassword"
                                                onclick="togglePassword('confirmPassword')">
                                                <i class="bi bi-eye"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" name="submit" class="btn btn-primary">Update
                                    Password</button>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
            <?php include './../template/footer.php'; ?>
        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    <?php include './../template/script.php'; ?>
    <script>
        function togglePassword(inputId) {
            var passwordField = document.getElementById(inputId);
            var type = passwordField.getAttribute('type');
            if (type === 'password') {
                passwordField.setAttribute('type', 'text');
            } else {
                passwordField.setAttribute('type', 'password');
            }
        }
    </script>
</body>

</html>