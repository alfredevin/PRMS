<?php
include '../../config.php';

if (isset($_POST["update"])) {
    $fullname = $_POST['fullname'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $userid = $_POST['userid'];
    $sql = "UPDATE user_tbl SET fullname = '$fullname', email = '$email', username = '$username'
    WHERE userid = '$userid'";
    $result = mysqli_query($conn, $sql);
    if ($result) {
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
                icon: "success",
                title: "Successfully Update!!!"
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
                            <h6 class="m-0 font-weight-bold text-primary">Select Room</h6>
                        </div>
                        <div class="card-body">

                            <div class="container my-4">
                                <div class="row gy-4">
                                    <style>
                                        .product-item .card {
                                            height: 100%;
                                            /* pantay ang height */
                                            display: flex;
                                            flex-direction: column;
                                        }

                                        .product-item .card-img-top {
                                            height: 200px;
                                            /* adjust depende sa gusto mong size */
                                            object-fit: cover;
                                            /* para hindi ma-distort yung image */
                                        }

                                        .product-item .card-body {
                                            flex-grow: 1;
                                            /* para yung content sa loob pantay din */
                                        }
                                    </style>
                                    <?php
                                    $room_sql = "SELECT r.*, t.room_type_name FROM rooms_tbl r
                     LEFT JOIN room_type_tbl t ON r.room_type_id = t.room_type_id";
                                    $room_result = mysqli_query($conn, $room_sql);
                                    while ($room = mysqli_fetch_assoc($room_result)) {
                                        $room_slug = strtolower(str_replace(' ', '-', $room['room_type_name']));
                                    ?>
                                        <div class="col-md-6 col-lg-4 product-item" data-category="<?= $room_slug ?>">
                                            <div class="card shadow-sm border-0 rounded-4 position-relative">
                                                <img src="<?= $room['image'] ?>" class="card-img-top rounded-top-4" alt="<?= $room['room_type_name'] ?>">
                                                <div class="card-body text-center">
                                                    <h5 class="card-title text-primary fw-bold"><?= $room['room_name'] ?></h5>
                                                    <h6 class="card-title text-dark fw-bold"><?= $room['room_type_name'] ?></h6>
                                                    <p class="fw-bold text-dark mb-1">₱<?= number_format($room['price'], 2) ?> / night</p>
                                                    <p class="text-muted mb-3"><?= $room['available'] ?> rooms available</p>
                                                    <p class="text-muted mb-3"><?= $room['room_description'] ?> </p>
                                                    <?php if ((int)$room['available'] > 0): ?>
                                                  <button class="btn btn-success book-now-btn"
                                                    data-room-id="<?= htmlspecialchars($room['room_id']) ?>">
                                                    <i class="fas fa-bed me-2"></i>Book Now
                                                        </button>
                                                    <?php else: ?>  
                                                        <button class="btn btn-danger" disabled>
                                                            <i class="fas fa-ban me-2"></i>Room Not Available
                                                        </button>
                                                    <?php endif; ?>


                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
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
        document.querySelectorAll('.book-now-btn').forEach(button => {
            button.addEventListener('click', () => {
                const roomId = button.getAttribute('data-room-id');
                window.location.href = `book_room?room_id=${roomId}`;
            });
        });
    </script>
</body>

</html>