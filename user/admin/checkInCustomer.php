<?php
include '../../config.php';

// --- PHP Data Fetching ---

// 1. Fetch ALL available equipment for the Loan Modal
// NOTE: equipment_price must be a valid DECIMAL field in equipment_tbl
$equipment_list_res = mysqli_query($conn, "SELECT equipment_id, equipment_name, equipment_price, equipment_quantity FROM equipment_tbl WHERE equipment_quantity > 0 ORDER BY equipment_name ASC");
$equipment_list = mysqli_fetch_all($equipment_list_res, MYSQLI_ASSOC);


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

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">In-House Guests</h1>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-bed"></i> Current Stay List</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Status</th>
                                            <th>Tracking No.</th>
                                            <th>Guest Name</th>
                                            <th>Room / Rate</th>
                                            <th>Check In / Out</th>
                                            <th>Balance Due</th>
                                            <th style="min-width: 330px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $counter = 1;
                                        $today = date('Y-m-d');

                                        // 2. Fetch Guests currently in-house (status = 3)
                                        $sql = "SELECT r.*, rm.room_name, rm.price as room_rate,
            r.guest_name,
            (SELECT SUM(amount) FROM reservation_payments_tbl WHERE tracking_number = r.tracking_number) as total_paid
          FROM reservation_tbl r
          JOIN rooms_tbl rm ON r.room_id = rm.room_id
          WHERE r.status = 3 ORDER BY r.check_out ASC";
                                        $result = mysqli_query($conn, $sql);
                                        while ($res = mysqli_fetch_assoc($result)) {
                                            $checkIn = date("M d, Y", strtotime($res['check_in']));
                                            $checkOutRaw = $res['check_out'];
                                            $checkOut = date("M d, Y", strtotime($res['check_out']));

                                            // Balance Calculation
                                            $total_due = floatval($res['total_price']);
                                            $total_paid = floatval($res['total_paid'] ?? 0);
                                            $balance = $total_due - $total_paid;

                                            $isTodayCheckout = (date("Y-m-d", strtotime($res['check_out'])) == $today);
                                            $balance_class = ($balance > 0) ? 'text-danger fw-bold' : 'text-success';
                                        ?>
                                            <tr>
                                                <td><?= $counter++; ?></td>
                                                <td>
                                                    <?php if ($isTodayCheckout): ?>
                                                        <span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i> Due Today</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-success">Stay-in</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($res['tracking_number']) ?></td>
                                                <td><?= htmlspecialchars($res['guest_name']) ?></td>
                                                <td>
                                                    <?= htmlspecialchars($res['room_name']) ?> <br>
                                                    <small class="text-muted">₱<?= number_format($res['room_rate'], 2) ?>/night</small>
                                                </td>
                                                <td><?= $checkIn ?> to <?= $checkOut ?></td>
                                                <td class="<?= $balance_class ?>">
                                                    ₱<?= number_format($balance, 2) ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <!-- View Loans Button (NEW) -->
                                                        <button type="button" class="btn btn-secondary btn-sm view-loans-btn"
                                                            data-id="<?= $res['reservation_id'] ?>"
                                                            data-name="<?= htmlspecialchars($res['guest_name']) ?>"
                                                            data-balance="<?= $balance ?>">
                                                            <i class="fas fa-list-alt"></i> View Loans
                                                        </button>

                                                        <button type="button" class="btn btn-info btn-sm manage-btn"
                                                            data-id="<?= $res['reservation_id'] ?>"
                                                            data-checkout="<?= $checkOutRaw ?>"
                                                            data-rate="<?= $res['room_rate'] ?>"
                                                            data-total="<?= $res['total_price'] ?>"
                                                            data-balance="<?= $balance ?>">
                                                            <i class="fas fa-calendar-alt"></i> Stay
                                                        </button>

                                                        <!-- LOAN ITEM BUTTON -->
                                                        <button type="button" class="btn btn-primary btn-sm loan-btn"
                                                            data-id="<?= $res['reservation_id'] ?>"
                                                            data-name="<?= htmlspecialchars($res['guest_name']) ?>"
                                                            data-balance="<?= $balance ?>">
                                                            <i class="fas fa-tools"></i> Loan Item
                                                        </button>

                                                        <button class="btn btn-danger btn-sm checkout-btn" data-id="<?= $res['reservation_id'] ?>">
                                                            <i class="fas fa-sign-out-alt"></i> Out
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>
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

    <!-- MANAGE STAY MODAL (Existing) -->
    <div class="modal fade" id="manageStayModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Manage Guest Stay</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <form id="extendForm">
                    <div class="modal-body">
                        <input type="hidden" name="reservation_id" id="manage_res_id">
                        <input type="hidden" id="room_rate">
                        <input type="hidden" id="current_total">

                        <div class="alert alert-primary">
                            <small>Current Balance:</small>
                            <h4 class="fw-bold" id="current_balance_display">₱0.00</h4>
                        </div>

                        <div class="form-group">
                            <label class="fw-bold">Current Check-out Date</label>
                            <input type="text" class="form-control" id="current_checkout" readonly>
                        </div>

                        <div class="form-group">
                            <label class="fw-bold text-primary">New Check-out Date (Extend/Shorten)</label>
                            <input type="date" class="form-control" name="new_checkout" id="new_checkout" required>
                            <small class="text-muted">Select a new date to calculate charges.</small>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center">
                            <span>Additional Nights:</span>
                            <strong id="added_nights">0</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span>Additional Amount:</span>
                            <strong class="text-success" id="added_amount">+ ₱0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 border-top pt-2">
                            <span>New Total Balance:</span>
                            <strong class="text-danger fs-5" id="new_balance">₱0.00</strong>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-info">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- NEW: MANAGE LOAN EQUIPMENT MODAL -->
    <div class="modal fade" id="loanEquipmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-tools"></i> Equipment Loan for <span id="loan_guest_name" class="font-weight-bold"></span></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <form id="loanEquipmentForm">
                    <div class="modal-body">
                        <input type="hidden" name="reservation_id" id="loan_res_id">
                        <input type="hidden" name="current_balance" id="loan_current_balance">
                        <input type="hidden" name="loan_total_cost" id="loan_total_cost" value="0.00">

                        <div class="alert alert-info py-2">
                            <small>Initial Balance:</small>
                            <h5 class="fw-bold mb-0" id="loan_initial_balance_display">₱0.00</h5>
                        </div>

                        <p class="font-weight-bold text-primary">Select Items to Loan (Damage/Replacement Cost):</p>
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-center">Price/Unit (₱)</th>
                                        <th class="text-center">Available</th>
                                        <th class="text-center">Qty to Loan</th>
                                    </tr>
                                </thead>
                                <tbody id="equipmentTableBody">
                                    <?php if (empty($equipment_list)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No equipment currently in inventory.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($equipment_list as $eq):
                                            $price_val = floatval($eq['equipment_price'] ?? 0.00);
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($eq['equipment_name']) ?></td>
                                                <td class="text-center">₱<?= number_format($price_val, 2) ?></td>
                                                <td class="text-center"><span class="badge badge-primary"><?= $eq['equipment_quantity'] ?></span></td>
                                                <td class="text-center">
                                                    <input type="number"
                                                        name="equipment_loan[<?= $eq['equipment_id'] ?>]"
                                                        data-price="<?= $price_val ?>"
                                                        data-max="<?= $eq['equipment_quantity'] ?>"
                                                        class="form-control form-control-sm equipment-qty-input"
                                                        value="0" min="0" max="<?= $eq['equipment_quantity'] ?>"
                                                        style="width: 80px; display: inline-block;">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="fw-bold">Loan Item Cost:</span>
                            <strong class="text-danger" id="loan_cost_display">₱0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 border-top pt-2">
                            <span class="h5">New Final Balance:</span>
                            <strong class="h4 text-danger" id="loan_new_balance_display">₱0.00</strong>
                            <input type="hidden" name="new_final_balance" id="loan_new_final_balance">
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Loan & Update Balance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- NEW: LOAN HISTORY MODAL -->
    <div class="modal fade" id="viewLoanHistoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title"><i class="fas fa-clipboard-list"></i> Loan History for <span id="history_guest_name" class="font-weight-bold"></span></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Below is the list of items loaned and charged to this reservation.</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-center">Price/Unit</th>
                                    <th class="text-center">Total Charge</th>
                                    <th>Loan Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="loanHistoryTableBody">
                                <!-- Data will be loaded here via AJAX -->
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Loading history...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <?php include './../template/script.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- DYNAMIC PHP/AJAX ENDPOINT DECLARATION -->
    <script>
        const LOAN_ENDPOINT = "update_loan_equipment.php";
        const HISTORY_ENDPOINT = "fetch_loan_history.php"; // NEW ENDPOINT
    </script>

    <script>
        // Function to format currency
        function formatCurrency(amount) {
            return '₱' + parseFloat(amount).toLocaleString(undefined, {
                minimumFractionDigits: 2
            });
        }

        // --- 1. MANAGE STAY MODAL LOGIC (Existing logic updated for clarity) ---

        $('.manage-btn').on('click', function() {
            const id = $(this).data('id');
            const checkout = $(this).data('checkout');
            const rate = parseFloat($(this).data('rate'));
            const total = parseFloat($(this).data('total'));
            const balance = parseFloat($(this).data('balance'));

            $('#manage_res_id').val(id);
            $('#current_checkout').val(checkout);
            $('#room_rate').val(rate);
            $('#current_total').val(total);

            // Set display values
            $('#current_balance_display').text(formatCurrency(balance));
            $('#manageStayModal').data('initialBalance', balance);

            $('#new_checkout').attr('min', checkout);
            $('#new_checkout').val(checkout);

            // Reset Calcs
            $('#added_nights').text(0);
            $('#added_amount').text(formatCurrency(0)).removeClass('text-danger').addClass('text-success');
            $('#new_balance').text(formatCurrency(balance));

            $('#manageStayModal').modal('show');
        });

        // 2. Calculate Cost on Date Change (Stay Modal)
        $('#new_checkout').on('change', function() {
            const oldDate = new Date($('#current_checkout').val());
            const newDate = new Date($(this).val());
            const rate = parseFloat($('#room_rate').val());
            const initialBalance = parseFloat($('#manageStayModal').data('initialBalance'));

            if (newDate) {
                const diffTime = newDate - oldDate;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                const addedCost = diffDays * rate;
                const newBalance = initialBalance + addedCost;

                // Update UI
                $('#added_nights').text(diffDays);
                $('#added_amount').text(formatCurrency(addedCost));
                $('#new_balance').text(formatCurrency(newBalance));

                $('#added_amount').toggleClass('text-success', addedCost >= 0).toggleClass('text-danger', addedCost < 0);
                $('#new_balance').toggleClass('text-danger', newBalance > 0).toggleClass('text-success', newBalance <= 0);
            }
        });

        // 3. Submit Extension (Existing)
        $('#extendForm').on('submit', function(e) {
            e.preventDefault();

            Swal.fire({
                title: 'Update Stay?',
                text: "This will update the checkout date and adjust the total balance.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Update',
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = $(this).serialize();

                    $.ajax({
                        url: 'process_extension.php',
                        type: 'POST',
                        data: formData,
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire('Success', response.message, 'success').then(() => location.reload());
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Server error occurred during extension processing.', 'error');
                        }
                    });
                }
            });
        });

        // --- 4. LOAN EQUIPMENT MODAL LOGIC ---

        let loan_initial_balance = 0;

        $('.loan-btn').on('click', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const balance = parseFloat($(this).data('balance'));

            loan_initial_balance = balance;

            $('#loan_res_id').val(id);
            $('#loan_guest_name').text(name);
            $('#loan_current_balance').val(balance);
            $('#loan_initial_balance_display').text(formatCurrency(balance));

            // Reset quantities and trigger calculation reset
            $('.equipment-qty-input').val(0);
            calculateLoanCost();

            $('#loanEquipmentModal').modal('show');
        });

        // Calculation logic for equipment loan
        function calculateLoanCost() {
            let totalLoanCost = 0;

            $('.equipment-qty-input').each(function() {
                const qty = parseInt($(this).val()) || 0;
                const price = parseFloat($(this).data('price'));
                const max_qty = parseInt($(this).data('max'));

                // Client-side validation: ensure positive quantity
                if (qty < 0) {
                    $(this).val(0);
                    return;
                }

                let actual_qty = qty;

                if (qty > max_qty) {
                    $(this).val(max_qty);
                    actual_qty = max_qty;
                }

                if (actual_qty > 0 && !isNaN(price)) {
                    totalLoanCost += (actual_qty * price);
                }
            });

            const newFinalBalance = loan_initial_balance + totalLoanCost;

            // Update displays
            $('#loan_total_cost').val(totalLoanCost.toFixed(2));
            $('#loan_cost_display').text(formatCurrency(totalLoanCost));
            $('#loan_new_balance_display').text(formatCurrency(newFinalBalance));
            $('#loan_new_final_balance').val(newFinalBalance.toFixed(2));

            // Highlight balance based on amount owed
            $('#loan_new_balance_display').toggleClass('text-danger', newFinalBalance > 0).toggleClass('text-success', newFinalBalance <= 0);
        }

        // Listener for quantity changes
        $('#loanEquipmentModal').on('input', '.equipment-qty-input', calculateLoanCost);

        // 5. Submit Loan Equipment Form
        $('#loanEquipmentForm').on('submit', function(e) {
            e.preventDefault();

            const totalLoanCost = parseFloat($('#loan_total_cost').val());
            if (totalLoanCost <= 0) {
                Swal.fire('Error', 'Please select at least one item to loan.', 'error');
                return;
            }

            Swal.fire({
                title: 'Confirm Equipment Loan?',
                html: `This will charge <strong>${formatCurrency(totalLoanCost)}</strong> to the guest's balance.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Save Loan',
                confirmButtonColor: '#007bff',
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = $(this).serialize();

                    $.ajax({
                        url: LOAN_ENDPOINT,
                        type: 'POST',
                        data: formData,
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire('Success', response.message, 'success').then(() => location.reload());
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
                            Swal.fire('Error', 'Server error occurred during loan processing. (Check network tab)', 'error');
                        }
                    });
                }
            });
        });

        // --- 6. LOAN HISTORY LOGIC (NEW) ---
        $('.view-loans-btn').on('click', function() {
            const reservationId = $(this).data('id');
            const guestName = $(this).data('name');

            $('#history_guest_name').text(guestName);
            $('#loanHistoryTableBody').html('<tr><td colspan="6" class="text-center text-muted">Loading history...</td></tr>');
            $('#viewLoanHistoryModal').modal('show');

            $.ajax({
                url: HISTORY_ENDPOINT,
                type: 'GET',
                data: {
                    reservation_id: reservationId
                },
                dataType: 'json',
                success: function(response) {
                    let tableRows = '';
                    if (response.status === 'success' && response.data.length > 0) {
                        response.data.forEach(item => {
                            const totalCharge = item.quantity_loaned * item.unit_price;
                            tableRows += `
                                <tr>
                                    <td>${item.equipment_name}</td>
                                    <td class="text-center">${item.quantity_loaned}</td>
                                    <td class="text-center">${formatCurrency(item.unit_price)}</td>
                                    <td class="text-center fw-bold text-danger">${formatCurrency(totalCharge)}</td>
                                    <td>${item.loan_date}</td>
                                    <td><span class="badge badge-secondary">${item.loan_status}</span></td>
                                </tr>
                            `;
                        });
                    } else {
                        tableRows = '<tr><td colspan="6" class="text-center text-muted">No equipment loan history found for this guest.</td></tr>';
                    }
                    $('#loanHistoryTableBody').html(tableRows);
                },
                error: function() {
                    $('#loanHistoryTableBody').html('<tr><td colspan="6" class="text-center text-danger">Failed to load loan history.</td></tr>');
                }
            });
        });


        // 7. Checkout Logic (Existing, unchanged)
        $('.checkout-btn').on('click', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Check-out Guest?',
                text: "Ensure all balances are settled before proceeding.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Check-out',
                confirmButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Sending email receipt, please wait...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false, // Itago ang OK button habang loading
                        didOpen: () => {
                            Swal.showLoading(); // Ito ang magpapaikot ng loader
                        }
                    });
                    $.ajax({
                        url: 'checkOutCustomer.php',
                        type: 'POST',
                        data: {
                            reservation_id: id,
                            status: 4
                        }, // 4 = Completed/Checked-out
                        success: function(response) {
                            Swal.fire('Checked Out', 'Guest has been checked out.', 'success').then(() => location.reload());
                        },
                        error: function() {
                            Swal.fire('Error', 'Failed to check out.', 'error');
                        }
                    });
                }
            });
        });
    </script>
</body>

</html>