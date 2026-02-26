<?php
include '../../config.php';
include './dashboard/graph.php';
?>
<!DOCTYPE html>
<html lang="en">

<?php include './../template/header.php'; ?>

<body id="page-top">
    <div id="wrapper">
        <?php include './../template/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include './../template/navbar.php'; ?>
                <div class="container-fluid">
                    <div class="mb-4">
                        <div class="row g-4">
                            <div class="col-md-9">
                                <div class="card shadow-lg border-0 rounded-3">
                                    <div class="row g-4">

                                        <!-- Home Card -->
                                        <div class="col-md-6">
                                            <a href="index" class="text-decoration-none">
                                                <div class="card shadow-lg border-0 rounded-3 hover-card">
                                                    <div class="card-body text-center">
                                                        <h3 class="fw-bold text-primary">Home 🏠</h3>
                                                        <p class="text-muted mb-2">Go to the main homepage.</p>
                                                        <p class="text-secondary">
                                                            Track real-time activities, system updates, and reports with ease.
                                                        </p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>

                                        <!-- Dashboard Card -->
                                        <div class="col-md-6">
                                            <a href="graph" class="text-decoration-none">
                                                <div class="card shadow-lg border-0 rounded-3 hover-card" style="background:#03045e;">
                                                    <div class="card-body text-center">
                                                        <h3 class="fw-bold text-white">Dashboard 📊</h3>
                                                        <p class="text-white mb-2">Access your monitoring dashboard.</p>
                                                        <p class="text-white">
                                                            Visualize reports, track reservations, payments,services in one place.
                                                        </p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </div>

                                    <style>
                                        /* Hover effect for cards */
                                        .hover-card {
                                            transition: transform 0.3s, box-shadow 0.3s;
                                        }

                                        .hover-card:hover {
                                            transform: translateY(-5px);
                                            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
                                        }
                                    </style>

                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card shadow border-0 rounded-3 bg-dark text-white">
                                    <div class="card-body text-center">
                                        <h5 class="fw-bold text-warning">📅 <span id="currentDate"></span></h5>
                                        <h3 class="fw-bold display-6" style="font-family: 'Courier New', monospace;">
                                            ⏰ <span id="currentTime"></span>
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <script>
                            function updateDateTime() {
                                const now = new Date();
                                document.getElementById("currentDate").innerText = now.toLocaleDateString();
                                document.getElementById("currentTime").innerText = now.toLocaleTimeString();
                            }
                            setInterval(updateDateTime, 1000);
                            updateDateTime();
                        </script>
                    </div>
                    <hr>
                    <div class="row">
                         
                        <div class="col-xl-6 col-md-6 mb-4">
                            <div class="card shadow h-100 py-2">
                                <div class="card-body">
                                    <form method="get" style="margin-bottom:20px;">
                                        <label>Select Year:</label>
                                        <select name="year" onchange="this.form.submit()">
                                            <?php
                                            $currentYear = date('Y');
                                            for ($y = $currentYear - 5; $y <= $currentYear + 1; $y++) {
                                                $selected = ($y == $year) ? 'selected' : '';
                                                echo "<option value='$y' $selected>$y</option>";
                                            }
                                            ?>
                                        </select>
                                    </form>
                                    <canvas id="reservationChart" width="800" height="400"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-6 mb-4">
                            <div class="card shadow h-100 py-2">
                                <div class="card-body">
                                    <form method="get" style="margin-bottom:20px;">
                                        <label>Select Year:</label>
                                        <select name="year_income" onchange="this.form.submit()">
                                            <?php
                                            $currentYear = date('Y');
                                            for ($y = $currentYear - 5; $y <= $currentYear + 1; $y++) {
                                                $selected = ($y == $year) ? 'selected' : '';
                                                echo "<option value='$y' $selected>$y</option>";
                                            }
                                            ?>
                                        </select>
                                    </form>

                                    <canvas id="incomeChart" width="800" height="400"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-6 mb-4">
                            <div class="card shadow h-100 py-2">
                                <div class="card-body">
                                    <canvas id="roomChart" width="800" height="400"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-6 mb-4">
                            <div class="card shadow h-100 py-2">
                                <div class="card-body">
                                    <canvas id="serviceChart" width="800" height="400"></canvas>
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
        <?php
        include './dashboard/graphScript.php';
        include './../template/script.php'; ?>
</body>

</html>