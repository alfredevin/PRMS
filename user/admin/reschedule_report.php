<?php
include '../../config.php';

// --- 1. FILTER LOGIC ---
$filter = $_GET['filter'] ?? 'monthly';
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-t');

// Logic para sa Quick Filters (Weekly, Monthly, Yearly)
if (isset($_GET['filter'])) {
    if ($_GET['filter'] == 'weekly') {
        $from_date = date('Y-m-d', strtotime('monday this week'));
        $to_date = date('Y-m-d', strtotime('sunday this week'));
    } elseif ($_GET['filter'] == 'monthly') {
        $from_date = date('Y-m-01');
        $to_date = date('Y-m-t');
    } elseif ($_GET['filter'] == 'yearly') {
        $from_date = date('Y-01-01');
        $to_date = date('Y-12-31');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include './../template/header.php' ?>

<style>
    .filter-active {
        background-color: #4e73df !important;
        color: white !important;
        border-color: #4e73df !important;
    }

    .btn-filter {
        transition: all 0.3s;
        font-weight: 600;
    }

    .date-label {
        font-size: 0.75rem;
        font-weight: bold;
        text-transform: uppercase;
        color: #858796;
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
                        <h1 class="h3 mb-0 text-gray-800">Reschedule Requests Report</h1>
                    </div>

                    <div class="mb-4">
                        <div class="btn-group shadow-sm">
                            <a href="?filter=weekly"
                                class="btn btn-white border <?= $filter == 'weekly' ? 'filter-active' : '' ?> btn-filter">Weekly</a>
                            <a href="?filter=monthly"
                                class="btn btn-white border <?= $filter == 'monthly' ? 'filter-active' : '' ?> btn-filter">Monthly</a>
                            <a href="?filter=yearly"
                                class="btn btn-white border <?= $filter == 'yearly' ? 'filter-active' : '' ?> btn-filter">Yearly</a>
                            <a href="rescheduleReports.php"
                                class="btn btn-white border <?= $filter == 'custom' ? 'filter-active' : '' ?> btn-filter text-primary">Custom
                                Range</a>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div
                            class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-primary text-white">
                            <h6 class="m-0 font-weight-bold"><i class="fas fa-calendar-alt mr-2"></i> Pending Reschedule
                                Requests</h6>
                            <span class="badge badge-light text-primary"><?= strtoupper($filter) ?></span>
                        </div>
                        <div class="card-body">

                            <form method="GET" class="mb-4 p-3 bg-light rounded border">
                                <input type="hidden" name="filter" value="custom">
                                <div class="form-row align-items-end">
                                    <div class="col-md-4 mb-2">
                                        <label class="date-label">Old Check-In From:</label>
                                        <input type="date" name="from_date" class="form-control"
                                            value="<?= $from_date ?>">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="date-label">Old Check-In To:</label>
                                        <input type="date" name="to_date" class="form-control" value="<?= $to_date ?>">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <button type="submit" class="btn btn-primary btn-block shadow-sm">
                                            <i class="fas fa-filter fa-sm"></i> Filter Records
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="dataTable" width="100%"
                                    cellspacing="0">
                                    <thead class="bg-gray-100 text-center">
                                        <tr>
                                            <th>#</th>
                                            <th>Tracking No.</th>
                                            <th>Guest Name</th>
                                            <th>Current Schedule</th>
                                            <th>Proposed New Schedule</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $counter = 1;
                                        // UPDATED SQL: Filtered by check_in dates and status 8
                                        $sql = "SELECT r.*, re.new_check_in, re.new_check_out
                                                FROM reservation_tbl r
                                                LEFT JOIN resched_tbl re ON r.tracking_number = re.tracking_number
                                                WHERE r.status = 8 
                                                AND DATE(r.check_in) BETWEEN '$from_date' AND '$to_date'
                                                ORDER BY r.check_in ASC";

                                        $result = mysqli_query($conn, $sql);

                                        if (mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $old_dates = date("M d", strtotime($row['check_in'])) . " - " . date("M d, Y", strtotime($row['check_out']));

                                                $newCheckIn = $row['new_check_in'] ? date("M d", strtotime($row['new_check_in'])) : '---';
                                                $newCheckOut = $row['new_check_out'] ? date("M d, Y", strtotime($row['new_check_out'])) : '---';
                                                $new_dates = $newCheckIn . " - " . $newCheckOut;
                                                ?>
                                                <tr class="text-center">
                                                    <td><?= $counter++; ?></td>
                                                    <td><span
                                                            class="badge badge-secondary"><?= $row['tracking_number'] ?></span>
                                                    </td>
                                                    <td class="font-weight-bold text-dark"><?= $row['guest_name'] ?></td>
                                                    <td><small class="text-muted d-block">Check-in/out:</small>
                                                        <?= $old_dates ?></td>
                                                    <td class="bg-light">
                                                        <small class="text-success font-weight-bold d-block">Requested:</small>
                                                        <span class="text-success font-weight-bold"><?= $new_dates ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="view_reservation?tracking=<?= $row['tracking_number'] ?>"
                                                                class="btn btn-info btn-sm" title="View Details"><i
                                                                    class="fas fa-eye"></i></a>
                                                            <button class="btn btn-success btn-sm confirm-resched-btn"
                                                                data-tracking="<?= $row['tracking_number'] ?>"
                                                                title="Confirm Reschedule">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php
                                            }
                                        } else {
                                            echo "<tr><td colspan='6' class='text-center py-4 text-muted'>No reschedule requests found for this period.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include './../template/footer.php'; ?>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

    <?php include './../template/script.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function () {
            // Confirm Reschedule AJAX Logic
            $('.confirm-resched-btn').on('click', function (e) {
                e.preventDefault();
                const trackingNumber = $(this).data('tracking');

                Swal.fire({
                    title: 'Approve Reschedule?',
                    text: 'This will update the reservation dates to the new requested schedule.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Approve',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#28a745',
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({ title: 'Updating...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

                        $.ajax({
                            url: 'confirm_reschedule.php',
                            type: 'POST',
                            data: { tracking: trackingNumber },
                            success: function (response) {
                                if (response.trim() === 'success') {
                                    Swal.fire('Approved!', 'Reservation has been rescheduled.', 'success')
                                        .then(() => { location.reload(); });
                                } else {
                                    Swal.fire('Error', 'Failed to update: ' + response, 'error');
                                }
                            },
                            error: function () {
                                Swal.fire('Error', 'Connection failed.', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>

</html>