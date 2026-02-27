<?php
include '../../config.php';
// ... (retain your update logic here) ...
?>

<!DOCTYPE html>
<html lang="en">
<?php include './../template/header.php' ?>

<style>
    /* Premium Interactive Room Card */
    .room-card {
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        border-left: 5px solid;
        cursor: default;
    }

    .room-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.12) !important;
    }

    .room-title {
        color: #4e73df;
        font-weight: 700;
    }

    .price-tag {
        font-size: 1.25rem;
        color: #1cc88a;
        font-weight: 800;
    }

    /* Search Bar Styling */
    .search-container {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    .filter-btn.active {
        background-color: #4e73df !important;
        color: white !important;
        box-shadow: 0 4px 15px rgba(78, 115, 223, 0.4);
    }
</style>

<body id="page-top">
    <div id="wrapper">
        <?php include './../template/sidebar.php' ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include './../template/navbar.php'; ?>
                <div class="container-fluid">

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Walk-in: Select Room</h1>
                    </div>

                    <div class="search-container mb-4">
                        <div class="row align-items-center">
                            <div class="col-lg-5 mb-3 mb-lg-0">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-primary text-white border-0"><i
                                                class="fas fa-search"></i></span>
                                    </div>
                                    <input type="text" id="roomSearch" class="form-control"
                                        placeholder="Search room name or type (e.g. Deluxe)...">
                                </div>
                            </div>
                            <div class="col-lg-7">
                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-outline-primary mr-2 filter-btn active" data-filter="all">All
                                        Rooms</button>
                                    <button class="btn btn-outline-success mr-2 filter-btn"
                                        data-filter="available">Available Only</button>
                                    <button class="btn btn-outline-danger filter-btn" data-filter="booked">Fully
                                        Booked</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row" id="roomGrid">
                        <?php
                        $room_sql = "SELECT r.*, t.room_type_name FROM rooms_tbl r
                                     LEFT JOIN room_type_tbl t ON r.room_type_id = t.room_type_id";
                        $room_result = mysqli_query($conn, $room_sql);

                        while ($room = mysqli_fetch_assoc($room_result)) {
                            $avail_count = (int) $room['available'];
                            $is_available = $avail_count > 0;
                            $status_text = $is_available ? "available" : "booked";
                            $border_class = $is_available ? "border-left-success" : "border-left-danger";
                            $room_data = strtolower($room['room_name'] . ' ' . $room['room_type_name']);
                            ?>

                            <div class="col-xl-4 col-md-6 mb-4 room-item" data-info="<?= $room_data ?>"
                                data-status="<?= $status_text ?>">
                                <div class="card shadow h-100 py-2 room-card <?= $border_class ?>">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="d-flex justify-content-between">
                                                    <div class="text-xs font-weight-bold text-uppercase mb-1">
                                                        <?= htmlspecialchars($room['room_type_name']) ?>
                                                    </div>
                                                    <span
                                                        class="badge badge-<?= $is_available ? 'success' : 'danger' ?> mb-1">
                                                        <?= $is_available ? 'Available' : 'Full' ?>
                                                    </span>
                                                </div>

                                                <div class="h5 mb-0 font-weight-bold text-gray-800 room-title">
                                                    <?= htmlspecialchars($room['room_name']) ?>
                                                </div>

                                                <div class="mt-2 price-tag">
                                                    ₱<?= number_format($room['price'], 2) ?>
                                                    <small class="text-muted" style="font-size: 0.65rem;">/ NIGHT</small>
                                                </div>

                                                <div
                                                    class="room-meta my-3 p-2 bg-gray-100 rounded d-flex justify-content-around">
                                                    <div class="text-center">
                                                        <div class="small text-muted">Capacity</div>
                                                        <div class="font-weight-bold"><i
                                                                class="fas fa-users mr-1"></i><?= $room['max_guest'] ?>
                                                        </div>
                                                    </div>
                                                    <div class="text-center">
                                                        <div class="small text-muted">Stock</div>
                                                        <div class="font-weight-bold"><i
                                                                class="fas fa-layer-group mr-1"></i><?= $avail_count ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <?php if ($is_available): ?>
                                                    <button
                                                        class="btn btn-primary btn-block btn-sm font-weight-bold shadow-sm book-now-btn"
                                                        data-room-id="<?= $room['room_id'] ?>">
                                                        SELECT ROOM <i class="fas fa-chevron-right ml-1"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-light btn-block btn-sm text-muted" disabled>
                                                        <i class="fas fa-ban mr-1"></i> UNAVAILABLE
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <?php } ?>
                    </div>

                    <div id="noResults" class="text-center py-5 d-none">
                        <i class="fas fa-search fa-3x text-gray-300 mb-3"></i>
                        <h4 class="text-gray-500">No rooms match your search...</h4>
                    </div>

                </div>
            </div>
            <?php include './../template/footer.php'; ?>
        </div>
    </div>

    <?php include './../template/script.php'; ?>

    <script>
        $(document).ready(function () {
            const $searchInput = $('#roomSearch');
            const $roomItems = $('.room-item');
            const $filterBtns = $('.filter-btn');
            const $noResults = $('#noResults');

            function filterRooms() {
                const searchTerm = $searchInput.val().toLowerCase();
                const activeFilter = $('.filter-btn.active').data('filter');
                let visibleCount = 0;

                $roomItems.each(function () {
                    const $item = $(this);
                    const roomInfo = $item.data('info');
                    const roomStatus = $item.data('status');

                    const matchesSearch = roomInfo.includes(searchTerm);
                    const matchesFilter = (activeFilter === 'all') || (roomStatus === activeFilter);

                    if (matchesSearch && matchesFilter) {
                        $item.fadeIn(200);
                        visibleCount++;
                    } else {
                        $item.fadeOut(200);
                    }
                });

                // Show/Hide no results message
                if (visibleCount === 0) {
                    $noResults.removeClass('d-none');
                } else {
                    $noResults.addClass('d-none');
                }
            }

            // Search input listener
            $searchInput.on('keyup', filterRooms);

            // Filter button listener
            $filterBtns.on('click', function () {
                $filterBtns.removeClass('active');
                $(this).addClass('active');
                filterRooms();
            });

            // Redirect to booking
            $('.book-now-btn').on('click', function () {
                const roomId = $(this).data('room-id');
                window.location.href = `book_room?room_id=${roomId}`;
            });
        });
    </script>
</body>

</html>