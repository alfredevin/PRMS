<?php
include '../../config.php';

// Fetch rooms with reservation status
// Note: Logic adjusted to prioritize 'Stay-in' (3) over 'Reserved' (2) if multiple bookings exist for a room.
// Using GROUP BY to ensure one card per room, prioritizing active statuses.
$sql = "
    SELECT 
        r.room_id, 
        r.room_name, 
        r.image,
        r.quantity,
        rtb.room_type_name,
        (SELECT COUNT(*) FROM reservation_tbl res WHERE res.room_id = r.room_id AND res.status = 2) as reserved_count,
        (SELECT COUNT(*) FROM reservation_tbl res WHERE res.room_id = r.room_id AND res.status = 3) as stayin_count,
        (
            SELECT guest_name FROM reservation_tbl res 
            WHERE res.room_id = r.room_id AND res.status IN (2,3) 
            ORDER BY res.status DESC, res.created_at DESC LIMIT 1
        ) as current_guest
    FROM rooms_tbl r
    INNER JOIN room_type_tbl rtb ON rtb.room_type_id = r.room_type_id 
    ORDER BY r.room_name ASC
";
$result = mysqli_query($conn, $sql);

// Initialize Counters
$totalAvailable = 0;
$totalReserved = 0;
$totalStayin = 0;
$rooms_data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $reserved = $row['reserved_count'];
    $stayin = $row['stayin_count'];
    $available = $row['quantity'] - ($reserved + $stayin);

    // Aggregate totals
    $totalAvailable += max(0, $available); // Prevent negative
    $totalReserved += $reserved;
    $totalStayin += $stayin;

    // Store for display
    $row['available_count'] = max(0, $available);
    $rooms_data[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include './../template/header.php' ?>

<style>
    .room-card {
        transition: all 0.3s ease;
        border: none;
        border-radius: 15px;
        overflow: hidden;
    }

    .room-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15) !important;
    }

    .room-img-wrapper {
        height: 160px;
        overflow: hidden;
        position: relative;
    }

    .room-img-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s;
    }

    .room-card:hover .room-img-wrapper img {
        transform: scale(1.1);
    }

    .status-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 0.75rem;
        padding: 5px 12px;
        border-radius: 20px;
        font-weight: 700;
        text-transform: uppercase;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }

    .filter-btn {
        border-radius: 50px;
        padding: 8px 20px;
        font-weight: 600;
        transition: all 0.3s;
        border: 2px solid transparent;
    }

    .filter-btn:hover,
    .filter-btn.active {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .filter-btn.active {
        border-color: transparent;
    }

    /* Status Specific Colors */
    .bg-available {
        background-color: #1cc88a;
        color: white;
    }

    .bg-reserved {
        background-color: #f6c23e;
        color: white;
    }

    .bg-stayin {
        background-color: #e74a3b;
        color: white;
    }

    .progress-thin {
        height: 6px;
        border-radius: 3px;
        background-color: #eaecf4;
        overflow: hidden;
    }

    .guest-avatar {
        width: 30px;
        height: 30px;
        background-color: #4e73df;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: bold;
        margin-right: 8px;
    }
</style>

<body id="page-top">
    <div id="wrapper">
        <?php include './../template/sidebar.php' ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include './../template/navbar.php'; ?>
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Room Monitoring Dashboard</h1>
                        <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" onclick="location.reload()">
                            <i class="fas fa-sync-alt fa-sm text-white-50 mr-1"></i> Refresh Data
                        </a>
                    </div>

                    <!-- Summary Cards Row -->
                    <div class="row mb-4">
                        <!-- Available Card -->
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2 cursor-pointer filter-card" onclick="filterRooms('Available')">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Available Rooms</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalAvailable ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-door-open fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reserved Card -->
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2 cursor-pointer filter-card" onclick="filterRooms('Reserved')">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Reserved Rooms</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalReserved ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Stay-in Card -->
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-danger shadow h-100 py-2 cursor-pointer filter-card" onclick="filterRooms('Stay-in')">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Occupied / Stay-in</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalStayin ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-bed fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Buttons -->
                    <div class="d-flex justify-content-center mb-4">
                        <div class="btn-group shadow-sm rounded-pill" role="group">
                            <button type="button" class="btn btn-light filter-btn active" onclick="filterRooms('All', this)" id="btn-all">All Rooms</button>
                            <button type="button" class="btn btn-light filter-btn" onclick="filterRooms('Available', this)">Available</button>
                            <button type="button" class="btn btn-light filter-btn" onclick="filterRooms('Reserved', this)">Reserved</button>
                            <button type="button" class="btn btn-light filter-btn" onclick="filterRooms('Stay-in', this)">Stay-in</button>
                        </div>
                    </div>

                    <!-- Rooms Grid -->
                    <div class="row" id="roomContainer">
                        <?php foreach ($rooms_data as $room):
                            // Determine primary status of the room card based on availability vs occupancy
                            $total = (int)$room['quantity'];
                            $avail = (int)$room['available_count'];
                            $resvd = (int)$room['reserved_count'];
                            $occup = (int)$room['stayin_count'];

                            // Logic: If fully occupied/reserved, show that status. If partially available, show available.
                            // Priority: Stay-in > Reserved > Available
                            if ($avail == $total) {
                                $statusTag = "Available";
                                $badgeColor = "bg-available";
                                $icon = "fa-check-circle";
                            } elseif ($occup > 0) {
                                $statusTag = "Stay-in";
                                $badgeColor = "bg-stayin";
                                $icon = "fa-bed";
                            } elseif ($resvd > 0) {
                                $statusTag = "Reserved";
                                $badgeColor = "bg-reserved";
                                $icon = "fa-clock";
                            } else {
                                $statusTag = "Available"; // Fallback
                                $badgeColor = "bg-available";
                                $icon = "fa-check-circle";
                            }

                            // Calculate progress width for visual bar
                            $occupancyRate = (($total - $avail) / $total) * 100;
                            $progressBarColor = ($occupancyRate >= 100) ? 'bg-danger' : (($occupancyRate > 50) ? 'bg-warning' : 'bg-success');
                        ?>
                            <!-- Room Card -->
                            <div class="col-xl-3 col-md-6 mb-4 room-item"
                                data-status="<?= $statusTag ?>"
                                data-avail="<?= ($avail > 0) ? 'Available' : 'Full' ?>"
                                data-resvd="<?= ($resvd > 0) ? 'Reserved' : '' ?>"
                                data-stayin="<?= ($occup > 0) ? 'Stay-in' : '' ?>">

                                <div class="card shadow room-card h-100">
                                    <!-- Image & Badge -->
                                    <div class="room-img-wrapper">
                                        <img src="<?= $room['image'] ?>" alt="<?= $room['room_name'] ?>">
                                        <div class="status-badge <?= $badgeColor ?>">
                                            <i class="fas <?= $icon ?> mr-1"></i> <?= $statusTag ?>
                                        </div>
                                    </div>

                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1"><?= $room['room_type_name'] ?></div>
                                                <h5 class="font-weight-bold text-gray-800 mb-0"><?= $room['room_name'] ?></h5>
                                            </div>
                                            <div class="text-right">
                                                <h6 class="font-weight-bold text-gray-700"><?= $avail ?> <small class="text-muted">/ <?= $total ?></small></h6>
                                                <small class="text-xs text-muted">Available</small>
                                            </div>
                                        </div>

                                        <!-- Occupancy Bar -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between text-xs mb-1">
                                                <span class="text-gray-500">Occupancy</span>
                                                <span class="font-weight-bold text-gray-700"><?= round($occupancyRate) ?>%</span>
                                            </div>
                                            <div class="progress progress-thin">
                                                <div class="progress-bar <?= $progressBarColor ?>" role="progressbar" style="width: <?= $occupancyRate ?>%"></div>
                                            </div>
                                        </div>

                                        <hr class="my-2">

                                        <!-- Current Status Details -->
                                        <div class="small">
                                            <?php if ($occup > 0): ?>
                                                <div class="d-flex align-items-center text-danger mb-1">
                                                    <div class="guest-avatar bg-danger"><i class="fas fa-user"></i></div>
                                                    <div>
                                                        <span class="d-block font-weight-bold">Stay-in Guest</span>
                                                        <span class="text-xs"><?= $room['current_guest'] ?></span>
                                                    </div>
                                                </div>
                                            <?php elseif ($resvd > 0): ?>
                                                <div class="d-flex align-items-center text-warning mb-1">
                                                    <div class="guest-avatar bg-warning"><i class="fas fa-clock"></i></div>
                                                    <div>
                                                        <span class="d-block font-weight-bold">Reserved For</span>
                                                        <span class="text-xs"><?= $room['current_guest'] ?></span>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="d-flex align-items-center text-success mb-1">
                                                    <div class="guest-avatar bg-success"><i class="fas fa-check"></i></div>
                                                    <div>
                                                        <span class="d-block font-weight-bold">Ready for Occupancy</span>
                                                        <span class="text-xs">Clean & Available</span>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Optional Action Button (e.g. View Details) -->
                                        <!-- <a href="room_details.php?id=<?= $room['room_id'] ?>" class="btn btn-outline-primary btn-sm btn-block mt-3 rounded-pill">View Details</a> -->
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Empty State (Hidden by default) -->
                    <div id="noRoomsFound" class="text-center py-5" style="display: none;">
                        <img src="../assets/img/no-data.svg" style="width: 150px; opacity: 0.5;" class="mb-3">
                        <h5 class="text-gray-500">No rooms found for this status.</h5>
                    </div>

                </div>
            </div>
            <?php include './../template/footer.php'; ?>
        </div>
    </div>

    <?php include './../template/script.php'; ?>

    <script>
        function filterRooms(status, btnElement) {
            // 1. Update Active Button State
            if (btnElement) {
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.remove('active', 'btn-primary');
                    btn.classList.add('btn-light');
                });
                btnElement.classList.remove('btn-light');
                btnElement.classList.add('active', 'btn-primary');
            } else {
                // If called from card click, find matching button
                const btnMap = {
                    'Available': 1,
                    'Reserved': 2,
                    'Stay-in': 3
                };
                // Assuming buttons are in order All, Available, Reserved, Stay-in
                const buttons = document.querySelectorAll('.filter-btn');
                buttons.forEach(b => b.classList.remove('active', 'btn-primary'));
                buttons.forEach(b => b.classList.add('btn-light'));

                // Manually set active based on status string (simple mapping)
                for (let b of buttons) {
                    if (b.textContent.includes(status)) {
                        b.classList.add('active', 'btn-primary');
                        b.classList.remove('btn-light');
                        break;
                    }
                }
            }

            // 2. Filter Logic
            const rooms = document.querySelectorAll('.room-item');
            let visibleCount = 0;

            rooms.forEach(room => {
                const roomStatus = room.getAttribute('data-status'); // Primary status
                const isAvail = room.getAttribute('data-avail') === 'Available';
                const isResvd = room.getAttribute('data-resvd') === 'Reserved';
                const isStayin = room.getAttribute('data-stayin') === 'Stay-in';

                let show = false;

                if (status === 'All') {
                    show = true;
                } else if (status === 'Available') {
                    // Show if it has ANY available slots
                    if (isAvail) show = true;
                } else if (status === 'Reserved') {
                    // Show if it has ANY reservations
                    if (isResvd) show = true;
                } else if (status === 'Stay-in') {
                    // Show if it has ANY stay-ins
                    if (isStayin) show = true;
                }

                if (show) {
                    room.style.display = 'block';
                    room.classList.add('animate__animated', 'animate__fadeIn'); // Add animation if using Animate.css
                    visibleCount++;
                } else {
                    room.style.display = 'none';
                }
            });

            // 3. Show Empty State
            const noDataEl = document.getElementById('noRoomsFound');
            if (visibleCount === 0) {
                noDataEl.style.display = 'block';
            } else {
                noDataEl.style.display = 'none';
            }
        }
    </script>
</body>

</html>