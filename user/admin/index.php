<?php
include '../../config.php';

// --- PHP DATA COLLECTION FOR DASHBOARD & CHARTS ---

// date filter defaults, can be overridden by GET parameters
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-t');

// 1. Fetch All KPI Counts
$kpi_counts = [];
$kpi_queries = [
    'new_reservation' => 'SELECT COUNT(*) AS total FROM reservation_tbl WHERE status = 1',
    'booked_today' => "SELECT COUNT(*) AS total FROM reservation_tbl WHERE status = 2 AND DATE(check_in) = CURDATE()",
    'in_house' => 'SELECT COUNT(*) AS total FROM reservation_tbl WHERE status = 3',
    'total_reservation' => 'SELECT COUNT(*) AS total FROM reservation_tbl',
    'total_services' => 'SELECT COUNT(*) AS total FROM services_tbl',
    'total_rooms' => 'SELECT COUNT(*) AS total FROM rooms_tbl',
    'total_rentals' => 'SELECT COUNT(*) AS total FROM rentals_tbl',
];

foreach ($kpi_queries as $key => $sql) {
    $result = mysqli_query($conn, $sql);
    $data = mysqli_fetch_assoc($result);
    $kpi_counts[$key] = $data['total'];
}

// 2. Data for Reservation Status Breakdown (Doughnut Chart)
$status_data_query = mysqli_query($conn, "
    SELECT 
        status, 
        COUNT(*) as count 
    FROM reservation_tbl 
    GROUP BY status
");

$status_labels = [];
$status_counts = [];
$status_colors = [];

while ($row = mysqli_fetch_assoc($status_data_query)) {
    $status_code = $row['status'];
    $count = $row['count'];

    // Map status code to human-readable label and color
    $label = 'Unknown';
    $color = '#6c757d'; // Default Gray
    switch ($status_code) {
        case 1:
            $label = 'New (Pending)';
            $color = '#ffc107';
            break; // Yellow
        case 2:
            $label = 'Reserved/Confirmed';
            $color = '#28a745';
            break; // Green
        case 3:
            $label = 'Checked-In (In House)';
            $color = '#007bff';
            break; // Blue
        case 4:
            $label = 'Checked-Out/Completed';
            $color = '#6f42c1';
            break; // Purple
    }

    $status_labels[] = $label;
    $status_counts[] = $count;
    $status_colors[] = $color;
}

// 3. Data for Monthly Revenue Trend (Bar Chart) with booking count
$revenue_labels = [];
$revenue_data = [];
$booking_counts = [];
$revenue_query = mysqli_query($conn, "
    SELECT 
        DATE_FORMAT(check_in, '%b %Y') as month_label,
        COUNT(*) as reservations_count,
        SUM(total_price) as monthly_revenue
    FROM reservation_tbl
    WHERE status IN (2, 3, 4)
      AND check_in BETWEEN '$from_date' AND '$to_date'
    GROUP BY month_label, DATE_FORMAT(check_in, '%Y-%m')
    ORDER BY DATE_FORMAT(check_in, '%Y-%m') ASC
");
while ($row = mysqli_fetch_assoc($revenue_query)) {
    $revenue_labels[] = $row['month_label'];
    $booking_counts[] = (int) $row['reservations_count'];
    $revenue_data[] = round($row['monthly_revenue'], 2);
}

// Fill missing months with zero for consistency (Optional but good practice for time series)
// generate month labels between from_date and to_date
$period = new DatePeriod(
    new DateTime($from_date),
    new DateInterval('P1M'),
    (new DateTime($to_date))->modify('+1 month')
);
$all_months = [];
foreach ($period as $dt) {
    $all_months[] = $dt->format('M Y');
}

// build maps for easy lookup
$rev_map = [];
for ($i = 0; $i < count($revenue_labels); $i++) {
    $rev_map[$revenue_labels[$i]] = [
        'revenue' => $revenue_data[$i],
        'count' => $booking_counts[$i]
    ];
}
$revenue_labels = [];
$revenue_data = [];
$booking_counts = [];
foreach ($all_months as $m) {
    $revenue_labels[] = $m;
    if (isset($rev_map[$m])) {
        $revenue_data[] = $rev_map[$m]['revenue'];
        $booking_counts[] = $rev_map[$m]['count'];
    } else {
        $revenue_data[] = 0;
        $booking_counts[] = 0;
    }
}


// 4. Tourist type breakdown
$tourist_labels = [];
$tourist_local_data = [];
$tourist_foreign_data = [];
$tourist_temp = [];

$tourist_query = mysqli_query($conn, "
    SELECT DATE_FORMAT(check_in, '%b %Y') as month_label,
           tourist_type,
           COUNT(*) as cnt
    FROM reservation_tbl
    WHERE check_in BETWEEN '$from_date' AND '$to_date'
    GROUP BY month_label, tourist_type
    ORDER BY DATE_FORMAT(check_in,'%Y-%m') ASC
");
while ($row = mysqli_fetch_assoc($tourist_query)) {
    $m = $row['month_label'];
    if (!isset($tourist_temp[$m])) {
        $tourist_labels[] = $m;
        $tourist_temp[$m] = ['Local' => 0, 'Foreign' => 0];
    }
    $tourist_temp[$m][$row['tourist_type']] = $row['cnt'];
}
foreach ($tourist_labels as $m) {
    $tourist_local_data[] = $tourist_temp[$m]['Local'];
    $tourist_foreign_data[] = $tourist_temp[$m]['Foreign'];
}

// 5. Gender breakdown using total_male and total_female
$gender_labels = [];
$gender_male_data = [];
$gender_female_data = [];

$gender_query = mysqli_query($conn, "
    SELECT DATE_FORMAT(check_in, '%b %Y') as month_label,
           SUM(total_male) as male_cnt,
           SUM(total_female) as female_cnt
    FROM reservation_tbl
    WHERE check_in BETWEEN '$from_date' AND '$to_date'
    GROUP BY month_label, DATE_FORMAT(check_in,'%Y-%m')
    ORDER BY DATE_FORMAT(check_in,'%Y-%m') ASC
");
while ($row = mysqli_fetch_assoc($gender_query)) {
    $gender_labels[] = $row['month_label'];
    $gender_male_data[] = (int)$row['male_cnt'];
    $gender_female_data[] = (int)$row['female_cnt'];
}

// normalize tourist and gender using all_months
$tor_map = [];
for ($i = 0; $i < count($tourist_labels); $i++) {
    $tor_map[$tourist_labels[$i]] = [
        'Local' => $tourist_local_data[$i],
        'Foreign' => $tourist_foreign_data[$i]
    ];
}
$tourist_labels = [];
$tourist_local_data = [];
$tourist_foreign_data = [];
foreach ($all_months as $m) {
    $tourist_labels[] = $m;
    if (isset($tor_map[$m])) {
        $tourist_local_data[] = $tor_map[$m]['Local'];
        $tourist_foreign_data[] = $tor_map[$m]['Foreign'];
    } else {
        $tourist_local_data[] = 0;
        $tourist_foreign_data[] = 0;
    }
}

$gen_map = [];
for ($i = 0; $i < count($gender_labels); $i++) {
    $gen_map[$gender_labels[$i]] = [
        'Male' => $gender_male_data[$i],
        'Female' => $gender_female_data[$i]
    ];
}
$gender_labels = [];
$gender_male_data = [];
$gender_female_data = [];
foreach ($all_months as $m) {
    $gender_labels[] = $m;
    if (isset($gen_map[$m])) {
        $gender_male_data[] = $gen_map[$m]['Male'];
        $gender_female_data[] = $gen_map[$m]['Female'];
    } else {
        $gender_male_data[] = 0;
        $gender_female_data[] = 0;
    }
}

// --- END PHP DATA COLLECTION ---
?>
<!DOCTYPE html>
<html lang="en">

<?php include './../template/header.php'; ?>

<body id="page-top">
    <!-- Custom Styles for Visual Appeal -->
    <style>
        .hover-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border-radius: 0.5rem !important;
        }

        .hover-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2) !important;
        }

        .card-kpi {
            min-height: 120px;
        }

        /* Ensure chart containers have defined height */
        .chart-container {
            height: 400px;
            width: 100%;
        }
    </style>

    <div id="wrapper">
        <?php include './../template/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include './../template/navbar.php'; ?>
                <div class="container-fluid">

                    <!-- Date range filter for analytics -->
                    <form method="GET" class="mb-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="small">From</label>
                                <input type="date" name="from_date" class="form-control" value="<?= $from_date ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="small">To</label>
                                <input type="date" name="to_date" class="form-control" value="<?= $to_date ?>">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-primary w-100"><i class="fas fa-filter"></i> Apply</button>
                            </div>
                        </div>
                    </form>

                    <!-- Row 1: Key Navigation Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-9">
                            <div class="row g-4">
                                <!-- Home Card -->
                                <div class="col-md-6 mb-4">
                                    <a href="index" class="text-decoration-none">
                                        <div class="card hover-card shadow-lg border-0 card-kpi" style="background:#03045e; ">
                                            <div class="card-body text-center p-3">
                                                <h3 class="fw-bold text-white mb-0">Home 🏠</h3>
                                                <p class="text-white-50 mb-0">Return to this dashboard overview.</p>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <!-- Dashboard/Monitoring Card -->
                                <div class="col-md-6 mb-4">
                                    <a href="graph" class="text-decoration-none">
                                        <div class="card hover-card shadow-lg border-0 card-kpi">
                                            <div class="card-body text-center p-3">
                                                <h3 class="fw-bold text-success mb-0">Monitoring 📈</h3>
                                                <p class="text-muted mb-0">Manage rooms and occupancy in real-time.</p>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Real-time Clock Card -->
                        <div class="col-md-3 mb-4">
                            <div class="card shadow border-0 rounded-3 bg-dark text-white card-kpi">
                                <div class="card-body text-center py-3">
                                    <h5 class="fw-bold text-warning mb-1">📅 <span id="currentDate"></span></h5>
                                    <h3 class="fw-bold display-6 mb-0" style="font-family: 'Courier New', monospace;">
                                        ⏰ <span id="currentTime"></span>
                                    </h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: KPI STATS CARDS -->
                    <h5 class="mb-3 text-gray-800">Reservation Quick Stats</h5>
                    <div class="row">
                        <!-- New Reservation Card (Status 1) -->
                        <a href="newReservation.php" class="col-xl-3 col-md-6 mb-4 text-decoration-none">
                            <div class="card border-left-warning shadow h-100 py-2 hover-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                New Reservations (Pending)</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= $kpi_counts['new_reservation'] ?>
                                            </div>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-bell fa-2x text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </a>

                        <!-- Today's Booked Card (Status 2, Check-in Today) -->
                        <a href="reservedCustomer.php?status=2&date=today" class="col-xl-3 col-md-6 mb-4 text-decoration-none">
                            <div class="card border-left-success shadow h-100 py-2 hover-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Confirmed Check-Ins (Today)</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= $kpi_counts['booked_today'] ?>
                                            </div>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-calendar-check fa-2x text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </a>

                        <!-- In House Guest Card (Status 3) -->
                        <a href="checkInCustomer.php" class="col-xl-3 col-md-6 mb-4 text-decoration-none">
                            <div class="card border-left-primary shadow h-100 py-2 hover-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">In House Guests</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= $kpi_counts['in_house'] ?>
                                            </div>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-home fa-2x text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </a>

                        <!-- Total Reservation Card -->
                        <a href="reservationHistory.php" class="col-xl-3 col-md-6 mb-4 text-decoration-none">
                            <div class="card border-left-secondary shadow h-100 py-2 hover-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                                Total Reservations (History)</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= $kpi_counts['total_reservation'] ?>
                                            </div>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-clipboard-list fa-2x text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Row 3: DATA VISUALIZATION -->
                    <div class="row">

                        <!-- Monthly Revenue Bar Chart -->
                        <div class="col-xl-8 col-lg-7 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Monthly Revenue & Bookings</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-area chart-container">
                                        <canvas id="monthlyRevenueChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reservation Status Doughnut Chart -->
                        <div class="col-xl-4 col-lg-5 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Reservation Status Breakdown</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-pie chart-container">
                                        <canvas id="statusDoughnutChart"></canvas>
                                    </div>
                                    <div class="mt-4 text-center small">
                                        <?php
                                        // Display legend directly from PHP data
                                        for ($i = 0; $i < count($status_labels); $i++): ?>
                                            <span class="mr-2">
                                                <i class="fas fa-circle" style="color: <?= $status_colors[$i] ?>;"></i> <?= $status_labels[$i] ?> (<?= $status_counts[$i] ?>)
                                            </span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 4: Tourist & Gender Visualization -->
                    <div class="row">
                        <!-- Tourist Type Bar Chart -->
                        <div class="col-xl-6 col-lg-6 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Tourist Type (Local vs Foreign)</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-area chart-container">
                                        <canvas id="touristTypeChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Gender Bar Chart -->
                        <div class="col-xl-6 col-lg-6 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Gender Breakdown</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-area chart-container">
                                        <canvas id="genderChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 4: Inventory Totals -->
                    <h5 class="mb-3 text-gray-800">Inventory Totals</h5>
                    <div class="row">
                        <!-- Total Rooms Card -->
                        <a href="monitoring.php" class="col-xl-3 col-md-6 mb-4 text-decoration-none">
                            <div class="card border-left-info shadow h-100 py-2 hover-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Rooms</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= $kpi_counts['total_rooms'] ?>
                                            </div>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-hotel fa-2x text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </a>
                        <!-- Total Services Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-secondary shadow h-100 py-2 hover-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Total Services</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= $kpi_counts['total_services'] ?>
                                            </div>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-concierge-bell fa-2x text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Total Rentals Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-secondary shadow h-100 py-2 hover-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Total Rentals</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= $kpi_counts['total_rentals'] ?>
                                            </div>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-bicycle fa-2x text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <?php include './../template/footer.php'; ?>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

    <!-- Assuming Chart.js is loaded via script.php -->
    <?php include './../template/script.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>

    <script>
        // --- REAL TIME CLOCK ---
        function updateDateTime() {
            const now = new Date();
            const optionsDate = {
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            };
            document.getElementById("currentDate").innerText = now.toLocaleDateString('en-US', optionsDate);
            document.getElementById("currentTime").innerText = now.toLocaleTimeString('en-US');
        }
        setInterval(updateDateTime, 1000);
        updateDateTime();

        // --- CHART DATA FROM PHP ---
        const revenueLabels = <?= json_encode($revenue_labels) ?>;
        const revenueData = <?= json_encode($revenue_data) ?>;
        const bookingCounts = <?= json_encode($booking_counts) ?>;
        const statusLabels = <?= json_encode($status_labels) ?>;
        const statusCounts = <?= json_encode($status_counts) ?>;
        const statusColors = <?= json_encode($status_colors) ?>;
        const touristLabels = <?= json_encode($tourist_labels) ?>;
        const touristLocal = <?= json_encode($tourist_local_data) ?>;
        const touristForeign = <?= json_encode($tourist_foreign_data) ?>;
        const genderLabels = <?= json_encode($gender_labels) ?>;
        const genderMale = <?= json_encode($gender_male_data) ?>;
        const genderFemale = <?= json_encode($gender_female_data) ?>;

        // --- 1. MONTHLY REVENUE & BOOKINGS CHART ---
        var ctxRevenue = document.getElementById("monthlyRevenueChart");
        if (ctxRevenue) {
            new Chart(ctxRevenue, {
                type: 'bar',
                data: {
                    labels: revenueLabels,
                    datasets: [
                        {
                            label: "Revenue (₱)",
                            backgroundColor: "#4e73df",
                            hoverBackgroundColor: "#2e59d9",
                            borderColor: "#4e73df",
                            data: revenueData,
                            yAxisID: 'y-revenue'
                        },
                        {
                            label: "Bookings",
                            type: 'line',
                            borderColor: '#1cc88a',
                            backgroundColor: 'transparent',
                            data: bookingCounts,
                            yAxisID: 'y-bookings'
                        }
                    ],
                },
                options: {
                    maintainAspectRatio: false,
                    tooltips: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(tooltipItem, chart) {
                                var ds = chart.datasets[tooltipItem.datasetIndex];
                                if (ds.label === 'Revenue (₱)') {
                                    return ds.label + ': ₱' + Number(tooltipItem.yLabel).toLocaleString(undefined, {minimumFractionDigits: 2});
                                }
                                return ds.label + ': ' + tooltipItem.yLabel;
                            }
                        }
                    },
                    scales: {
                        xAxes: [{
                            ticks: {fontColor: '#858796'},
                            gridLines: {display: false}
                        }],
                        yAxes: [
                            {
                                id: 'y-revenue',
                                position: 'left',
                                ticks: {beginAtZero: true, fontColor: '#858796', callback: function(value){return '₱'+Number(value).toLocaleString();}}
                            },
                            {
                                id: 'y-bookings',
                                position: 'right',
                                ticks: {beginAtZero: true, fontColor: '#858796'}
                            }
                        ]
                    }
                }
            });
        }

        // --- 2. RESERVATION STATUS DOUGHNUT CHART ---
        var ctxStatus = document.getElementById("statusDoughnutChart");
        if (ctxStatus) {
            new Chart(ctxStatus, {
                type: 'doughnut',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusCounts,
                        backgroundColor: statusColors,
                        hoverBackgroundColor: statusColors.map(color => color + 'b3'), // Add slight transparency on hover
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    tooltips: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyFontColor: "#858796",
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: false,
                        caretPadding: 10,
                        callbacks: {
                            label: function(tooltipItem, data) {
                                let label = data.labels[tooltipItem.index];
                                let value = data.datasets[0].data[tooltipItem.index];
                                return ' ' + label + ': ' + value;
                            }
                        }
                    },
                    legend: {
                        display: false
                    },
                    cutoutPercentage: 80,
                },
            });
        }
        
        // --- 3. TOURIST TYPE BAR CHART ---
        var ctxTourist = document.getElementById("touristTypeChart");
        if (ctxTourist) {
            new Chart(ctxTourist, {
                type: 'bar',
                data: {
                    labels: touristLabels,
                    datasets: [
                        { label: 'Local', backgroundColor: '#36b9cc', data: touristLocal },
                        { label: 'Foreign', backgroundColor: '#f6c23e', data: touristForeign }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        xAxes: [{ticks:{fontColor:'#858796'},gridLines:{display:false}}],
                        yAxes: [{ticks:{beginAtZero:true,fontColor:'#858796'}}]
                    }
                }
            });
        }
        
        // --- 4. GENDER BAR CHART ---
        var ctxGender = document.getElementById("genderChart");
        if (ctxGender) {
            new Chart(ctxGender, {
                type: 'bar',
                data: {
                    labels: genderLabels,
                    datasets: [
                        { label: 'Male', backgroundColor: '#4e73df', data: genderMale },
                        { label: 'Female', backgroundColor: '#e74a3b', data: genderFemale }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        xAxes: [{ticks:{fontColor:'#858796'},gridLines:{display:false}}],
                        yAxes: [{ticks:{beginAtZero:true,fontColor:'#858796'}}]
                    }
                }
            });
        }
    </script>
</body>

</html>