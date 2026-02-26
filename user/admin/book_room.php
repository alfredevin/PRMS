<?php
include '../../config.php';

// Get the room_id from GET
if (!isset($_GET['room_id'])) {
    echo "<script>alert('No room selected.'); window.location.href='index.php';</script>";
    exit;
}

$room_id = mysqli_real_escape_string($conn, $_GET['room_id']);

// 1. FETCH ROOM + DISCOUNT LOGIC (Same as Online)
$sql = "SELECT r.*, t.room_type_name, 
                d.discount_name, d.discount_percent, d.start_date, d.end_date 
        FROM rooms_tbl r
        LEFT JOIN room_type_tbl t ON r.room_type_id = t.room_type_id
        LEFT JOIN discount_tbl d ON r.discount_id = d.discount_id
        WHERE r.room_id = '$room_id'";

$result = mysqli_query($conn, $sql);
$room = mysqli_fetch_assoc($result);

if (!$room) {
    die("Room not found.");
}

// 2. VALIDATE DISCOUNT DATE
$today = date('Y-m-d');
$active_discount = 0;
$promo_name = "";

if (!empty($room['discount_percent'])) {
    if ($today >= $room['start_date'] && $today <= $room['end_date']) {
        $active_discount = $room['discount_percent'];
        $promo_name = $room['discount_name'];
    }
}

// Calculate Prices for Display
$orig_price = $room['price'];
$final_price = $orig_price;
if ($active_discount > 0) {
    $final_price = $orig_price - ($orig_price * ($active_discount / 100));
}

// ... (pagkatapos ng Room fetch logic)

// 3. FETCH ENTRANCE FEE (Kukunin ang presyo sa database)
$fee_sql = "SELECT entrance_fee_amount FROM entrance_fee_tbl LIMIT 1";
$fee_res = mysqli_query($conn, $fee_sql);
$fee_data = mysqli_fetch_assoc($fee_res);
$entrance_rate = $fee_data['entrance_fee_amount'] ?? 0; // Default to 0 if walang laman
?>

<!DOCTYPE html>
<html lang="en">
<?php include './../template/header.php' ?>

<style>
    .step {
        flex: 1;
        text-align: center;
        padding: 10px;
        border-bottom: 3px solid #e9ecef;
        color: #aaa;
        font-weight: 500;
        cursor: default;
    }

    .step.active {
        border-color: #4e73df;
        color: #4e73df;
        font-weight: bold;
    }

    /* Admin Primary Color */
    .step.completed {
        border-color: #1cc88a;
        color: #1cc88a;
    }

    .room-card-sticky {
        position: sticky;
        top: 90px;
        /* Adjusted for Admin Navbar */
        z-index: 10;
        transition: top 0.3s;
    }

    .room-img-container {
        position: relative;
        height: 250px;
        overflow: hidden;
        border-top-left-radius: 0.35rem;
        border-top-right-radius: 0.35rem;
    }

    .room-img-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .room-img-container:hover img {
        transform: scale(1.05);
    }

    .promo-badge {
        position: absolute;
        top: 15px;
        left: 0;
        background: linear-gradient(45deg, #e74a3b, #e74a3b);
        color: white;
        padding: 5px 15px;
        font-weight: bold;
        font-size: 0.85rem;
        border-top-right-radius: 20px;
        border-bottom-right-radius: 20px;
        box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2);
    }

    .card {
        border: none;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        border-radius: 0.35rem;
    }

    /* Flatpickr z-index fix for admin template */
    .flatpickr-calendar {
        z-index: 9999 !important;
    }
</style>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<body id="page-top">
    <div id="wrapper">
        <?php include './../template/sidebar.php' ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include './../template/navbar.php'; ?>
                <div class="container-fluid">

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Walk-in Reservation</h1>
                        <a href="index.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Rooms</a>
                    </div>

                    <div class="row">

                        <div class="col-lg-4 d-none d-lg-block">
                            <div class="card room-card-sticky mb-4">
                                <div class="room-img-container">
                                    <img src="<?= $room['image'] ?>" alt="Room Image">

                                    <?php if ($active_discount > 0): ?>
                                        <div class="promo-badge">
                                            <i class="fas fa-tags mr-1"></i> <?= $active_discount ?>% OFF
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="card-body">
                                    <h4 class="font-weight-bold text-primary mb-1"><?= htmlspecialchars($room['room_name']) ?></h4>
                                    <div class="text-uppercase text-xs font-weight-bold text-muted mb-3"><?= htmlspecialchars($room['room_type_name']) ?></div>

                                    <div class="mb-3 p-3 bg-gray-100 rounded">
                                        <?php if ($active_discount > 0): ?>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="text-muted" style="text-decoration: line-through;">₱<?= number_format($orig_price, 2) ?></span>
                                                <span class="badge badge-danger">Save <?= $active_discount ?>%</span>
                                            </div>
                                            <div class="h4 font-weight-bold text-success mb-0">₱<?= number_format($final_price, 2) ?></div>
                                            <small class="text-danger font-weight-bold"><i class="fas fa-fire mr-1"></i> <?= htmlspecialchars($promo_name) ?></small>
                                        <?php else: ?>
                                            <div class="h4 font-weight-bold text-dark mb-0">₱<?= number_format($orig_price, 2) ?></div>
                                            <small class="text-muted">per night</small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="d-flex justify-content-between mb-2 small">
                                        <span><i class="fas fa-users mr-2"></i>Max Guests:</span>
                                        <span class="font-weight-bold"><?= $room['max_guest'] ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-3 small">
                                        <span><i class="fas fa-door-open mr-2"></i>Available:</span>
                                        <span class="font-weight-bold"><?= $room['available'] ?></span>
                                    </div>

                                    <hr class="sidebar-divider">
                                    <p class="small text-muted mb-0">
                                        <?= htmlspecialchars($room['room_description']) ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="card mb-4">
                                <div class="card-body p-4">

                                    <div class="d-flex mb-4 pb-2 border-bottom overflow-auto">
                                        <div class="step active">1. Info</div>
                                        <div class="step">2. Boat</div>
                                        <div class="step">3. Services</div>
                                        <div class="step">4. Rental</div>
                                        <div class="step">5. Payment</div>
                                        <div class="step">6. Confirm</div>
                                    </div>

                                    <form id="multiStepForm" enctype="multipart/form-data" method="POST">
                                        <input type="hidden" name="room_id" value="<?= $room_id ?>">
                                        <input type="hidden" name="is_walkin" value="1">
                                        <div class="form-step">
                                            <h5 class="font-weight-bold text-primary mb-3">Guest Information</h5>

                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label class="font-weight-bold small">Full Name</label>
                                                    <input type="text" class="form-control required" name="guest_name" oninput="this.value = this.value.toUpperCase();" placeholder="Enter Name">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label class="font-weight-bold small">Contact Number</label>
                                                    <input
                                                        type="text"
                                                        class="form-control required"
                                                        name="guest_phone"
                                                        placeholder="09123456789"
                                                        maxlength="11"
                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)">
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="font-weight-bold small">Email Address</label>
                                                <input type="email" class="form-control required" name="guest_email" placeholder="email@example.com">
                                            </div>

                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label class="font-weight-bold small">Check-in - Check-out</label>
                                                    <input type="text" id="date_range" class="form-control bg-white" placeholder="Select Dates" readonly>
                                                    <input type="hidden" name="checkin" id="checkin">
                                                    <input type="hidden" name="checkout" id="checkout">
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label class="font-weight-bold small">Guests</label>
                                                    <div class="dropdown">
                                                        <button class="btn btn-outline-secondary btn-block text-left d-flex justify-content-between align-items-center" type="button" id="guestDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                            <span id="guestSummary">1 Adult, 0 Children</span>
                                                            <i class="fas fa-chevron-down"></i>
                                                        </button>
                                                        <div class="dropdown-menu p-3 w-100 shadow" aria-labelledby="guestDropdown">
                                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                                <div>
                                                                    <h6 class="m-0 font-weight-bold">Adults</h6><small>Ages 13+</small>
                                                                </div>
                                                                <div>
                                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle" onclick="updateCount('adult', -1)">-</button>
                                                                    <span class="mx-2 font-weight-bold" id="adultCount">1</span>
                                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle" onclick="updateCount('adult', 1)">+</button>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                                <div>
                                                                    <h6 class="m-0 font-weight-bold">Children</h6><small>Ages 0-12</small>
                                                                </div>
                                                                <div>
                                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle" onclick="updateCount('child', -1)">-</button>
                                                                    <span class="mx-2 font-weight-bold" id="childCount">0</span>
                                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle" onclick="updateCount('child', 1)">+</button>
                                                                </div>
                                                            </div>
                                                            <div id="childAgesContainer" class="border-top pt-2 d-none">
                                                                <small class="text-muted d-block mb-2">Child Ages:</small>
                                                            </div>
                                                            <input type="hidden" name="adults" id="inputAdults" value="1">
                                                            <input type="hidden" name="children" id="inputChildren" value="0">
                                                            <input type="hidden" id="totalGuests" name="guests" value="1">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="alert alert-primary d-flex justify-content-between align-items-center mt-2">
                                                <div>
                                                    <span class="small">Duration:</span> <strong id="nights_display">0</strong> nights
                                                </div>
                                                <div class="text-right">
                                                    <span class="small">Room Cost:</span>
                                                    <input type="text" class="font-weight-bold h5 mb-0 bg-transparent border-0 text-right text-primary" style="width: 150px;" id="room_cost" name="room_cost_display" readonly placeholder="₱0.00">
                                                    <input type="hidden" id="nights" name="totalNights">
                                                    <input type="hidden" id="room_cost_value" name="room_fee_actual">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-step d-none">
                                            <h5 class="font-weight-bold text-primary mb-3">Boat Rental</h5>
                                            <div class="table-responsive">
                                                <table class="table table-hover text-center">
                                                    <thead class="bg-light">
                                                        <tr>
                                                            <th>Select</th>
                                                            <th>Dest</th>
                                                            <th>Guests</th>
                                                            <th>Fee</th>
                                                            <th>Island Hop</th>
                                                            <th>Add Hop?</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $boats = mysqli_query($conn, "SELECT * FROM boat_rental_fee_tbl");
                                                        while ($b = mysqli_fetch_assoc($boats)) { ?>
                                                            <tr>
                                                                <td><input class="boat-check" type="checkbox" value="<?= $b['rental_id'] ?>" data-boat="<?= $b['amount'] ?>" data-island="<?= $b['island_hopping_amount'] ?>"></td>
                                                                <td><?= $b['destination'] ?></td>
                                                                <td><?= $b['min_guest'] . '-' . $b['max_guest'] ?></td>
                                                                <td>₱<?= number_format($b['amount']) ?></td>
                                                                <td>₱<?= number_format($b['island_hopping_amount']) ?></td>
                                                                <td><input type="checkbox" class="include-island"></td>
                                                            </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="text-right font-weight-bold text-success">Total Boat Fee: ₱<span id="totalAmount">0.00</span></div>
                                        </div>

                                        <div class="form-step d-none">
                                            <h5 class="font-weight-bold text-primary mb-3">Additional Services</h5>
                                            <div class="row">
                                                <?php
                                                $services = mysqli_query($conn, "SELECT * FROM services_tbl");
                                                while ($s = mysqli_fetch_assoc($services)) { ?>
                                                    <div class="col-md-6 mb-3">
                                                        <div class="card border p-3 flex-row align-items-center h-100">
                                                            <div class="mr-3"><input class="service-check" type="checkbox" name="services[]" value="<?= $s['service_id'] ?>" data-price="<?= $s['service_price'] ?>"></div>
                                                            <img src="uploads/<?= $s['service_image'] ?>" style="width:50px;height:50px;object-fit:cover;border-radius:5px;" class="mr-3">
                                                            <div>
                                                                <div class="font-weight-bold"><?= $s['service_name'] ?></div>
                                                                <small class="text-muted">₱<?= number_format($s['service_price'], 2) ?></small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        </div>

                                        <div class="form-step d-none">
                                            <h5 class="font-weight-bold text-primary mb-3">Equipment Rentals</h5>
                                            <div class="row">
                                                <?php
                                                $rentals = mysqli_query($conn, "SELECT * FROM rentals_tbl");
                                                while ($r = mysqli_fetch_assoc($rentals)) { ?>
                                                    <div class="col-md-6 mb-3">
                                                        <div class="card border p-3 flex-row align-items-center h-100">
                                                            <div class="mr-3"><input class="rental-check" type="checkbox" name="rentals[]" value="<?= $r['rental_id'] ?>" data-price="<?= $r['rental_price'] ?>"></div>
                                                            <img src="uploads/rentals/<?= $r['rental_image'] ?>" style="width:50px;height:50px;object-fit:cover;border-radius:5px;" class="mr-3">
                                                            <div>
                                                                <div class="font-weight-bold"><?= $r['rental_name'] ?></div>
                                                                <small class="text-muted">₱<?= number_format($r['rental_price'], 2) ?> / <?= $r['hours'] ?>hr</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        </div>

                                        <div class="form-step d-none">
                                            <h5 class="font-weight-bold text-primary mb-3">Payment Details</h5>
                                            <div class="bg-gray-100 p-3 rounded mb-4">
                                                <div class="d-flex justify-content-between mb-1"><span>Room Fee</span> <span class="font-weight-bold text-dark" id="room_payment">₱0.00</span></div>

                                                <div class="d-flex justify-content-between mb-1 text-primary">
                                                    <span>Entrance Fee (<span id="adult_summary_count">1</span> Adults)</span>
                                                    <span class="font-weight-bold" id="entrance_payment">₱0.00</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-1"><span>Boat Fee</span> <span id="boat_rentals_payment">₱0.00</span></div>
                                                <div class="d-flex justify-content-between mb-1"><span>Services Fee</span> <span id="services_payment">₱0.00</span></div>
                                                <div class="d-flex justify-content-between mb-1"><span>Rentals Fee</span> <span id="rentals_payment">₱0.00</span></div>
                                                <hr>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="h5 font-weight-bold">Total Amount</span>
                                                    <input type="text" name="total_payments_display" class="h4 font-weight-bold text-success text-right border-0 bg-transparent" id="total_payment" readonly value="₱0.00">
                                                    <input type="hidden" name="total_payments" id="total_payments_value">

                                                    <input type="hidden" name="total_entrance_fee" id="total_entrance_fee_value">
                                                </div>
                                            </div>

                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label class="small font-weight-bold">Payment Method</label>
                                                    <select class="form-control required" id="payment_type" name="payment_type">
                                                        <option value="">Select Method</option>
                                                        <?php
                                                        $pay = mysqli_query($conn, "SELECT * FROM payment_type_tbl");
                                                        while ($p = mysqli_fetch_assoc($pay)) {
                                                            echo "<option value='{$p['payment_type_id']}'>{$p['payment_type_name']}</option>";
                                                        }
                                                        ?>
                                                        <option value="CASH">Cash Payment</option>
                                                    </select>
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label class="small font-weight-bold">Payment Option</label>
                                                    <select class="form-control border-primary" id="payment_option" name="payment_option">
                                                        <option value="full">Full Payment</option>
                                                        <option value="downpayment">50% Downpayment</option>
                                                    </select>
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label class="small font-weight-bold">Amount to Collect</label>
                                                    <input type="text" class="form-control font-weight-bold text-success" id="amount_to_pay_display" readonly value="₱0.00">
                                                    <input type="hidden" name="final_payable" id="final_payable">
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label class="small font-weight-bold">Reference No. / Note</label>
                                                    <input type="text" class="form-control" name="reference_number" placeholder="Ref# or 'Cash'">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-step d-none text-center py-4">
                                            <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                                            <h3 class="mt-3 font-weight-bold">Confirm Walk-in?</h3>
                                            <p class="text-muted">Please review details before proceeding.</p>
                                            <button type="button" id="confirmBookingBtn" class="btn btn-success btn-lg px-5 shadow mt-3">Process Booking</button>
                                        </div>

                                        <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                                            <button type="button" id="prevBtn" class="btn btn-secondary" disabled>Back</button>
                                            <button type="button" id="nextBtn" class="btn btn-primary shadow-sm">Next</button>
                                        </div>

                                    </form>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <?php include './../template/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
        <?php include './../template/script.php'; ?>
        <script>
        $(document).ready(function() {
            $('#guestDropdown').parent().on('hide.bs.dropdown', function(e) {
                // Check if the click originated from within the dropdown menu
                if ($(e.clickEvent.target).closest('.dropdown-menu').length) {
                    e.preventDefault();
                }
            });
        });

        // 1. GUEST LOGIC
        let adults = 1;
        let children = 0;
        const maxGuests = <?= $room['max_guest'] ?>;

        function updateCount(type, change) {
            const total = adults + children;
            if (type === 'adult') {
                if (change === 1 && total < maxGuests) adults++;
                if (change === -1 && adults > 1) adults--;
            } else if (type === 'child') {
                if (change === 1 && total < maxGuests) {
                    children++;
                    addChildAgeSelect(children);
                }
                if (change === -1 && children > 0) {
                    children--;
                    removeChildAgeSelect();
                }
            }
            document.getElementById('adultCount').innerText = adults;
            document.getElementById('childCount').innerText = children;
            document.getElementById('inputAdults').value = adults;
            document.getElementById('inputChildren').value = children;
            document.getElementById('totalGuests').value = adults + children;
            document.getElementById('guestSummary').innerText = `${adults} Adult${adults>1?'s':''}, ${children} Child${children>1?'ren':''}`;
            const ac = document.getElementById('childAgesContainer');
            children > 0 ? ac.classList.remove('d-none') : ac.classList.add('d-none');
        }

        function addChildAgeSelect(index) {
            const d = document.createElement('div');
            d.className = 'd-flex justify-content-between align-items-center mb-2 small';
            let ops = '';
            for (let i = 0; i <= 12; i++) ops += `<option value="${i}">${i} yrs</option>`;
            d.innerHTML = `<span>Child ${index} Age</span><select class="form-control form-control-sm w-50" name="child_ages[]">${ops}</select>`;
            document.getElementById('childAgesContainer').appendChild(d);
        }

        function removeChildAgeSelect() {
            const c = document.getElementById('childAgesContainer');
            if (c.lastChild) c.removeChild(c.lastChild);
        }

        // 2. COST LOGIC
        const roomPrice = <?= $room['price'] ?>;
        const discountPercent = <?= $active_discount ?>;
        const entranceRate = <?= $entrance_rate ?>; // Galing sa PHP

        function calculateRoomCost() {
            let d1 = document.getElementById("checkin").value;
            let d2 = document.getElementById("checkout").value;
            if (d1 && d2) {
                let diff = (new Date(d2) - new Date(d1)) / (1000 * 3600 * 24);
                if (diff > 0) {
                    document.getElementById("nights").value = diff;
                    document.getElementById("nights_display").textContent = diff;
                    let total = diff * roomPrice;
                    if (discountPercent > 0) total -= (total * (discountPercent / 100));

                    document.getElementById("room_cost").value = "₱" + total.toLocaleString(undefined, {
                        minimumFractionDigits: 2
                    });
                    document.getElementById("room_cost_value").value = total.toFixed(2);

                    if (typeof calculateSummary === "function") calculateSummary();
                }
            } else {
                document.getElementById("room_cost_value").value = 0;
            }
        }

        flatpickr("#date_range", {
            mode: "range",
            minDate: "today",
            dateFormat: "Y-m-d",
            onChange: function(dates) {
                if (dates.length === 2) {
                    document.getElementById("checkin").value = flatpickr.formatDate(dates[0], "Y-m-d");
                    document.getElementById("checkout").value = flatpickr.formatDate(dates[1], "Y-m-d");
                    calculateRoomCost();
                }
            }
        });

        // 3. STEP NAV
        const formSteps = document.querySelectorAll(".form-step");
        const steps = document.querySelectorAll(".step");
        let currentStep = 0;

        function showStep(n) {
            formSteps.forEach((s, i) => s.classList.toggle("d-none", i !== n));
            steps.forEach((s, i) => {
                s.classList.remove("active", "completed");
                if (i < n) s.classList.add("completed");
                if (i === n) s.classList.add("active");
            });
            document.getElementById("prevBtn").disabled = n === 0;
            document.getElementById("nextBtn").textContent = n === formSteps.length - 1 ? "Confirm" : "Next";
            if (n === 4) calculateSummary(); // Recalculate summary every time we land on the payment step
            document.getElementById("nextBtn").classList.toggle("d-none", n === formSteps.length - 1);
            document.getElementById("prevBtn").classList.toggle("d-none", n === formSteps.length - 1);
        }

        document.getElementById("nextBtn").addEventListener("click", () => {
            // Basic validation
            const inputs = formSteps[currentStep].querySelectorAll(".required");
            let valid = true;
            inputs.forEach(i => {
                if (!i.value) {
                    i.classList.add("is-invalid");
                    valid = false;
                } else i.classList.remove("is-invalid");
            });

            // Additional validation for Step 1 (Dates and Guests)
            if (currentStep === 0) {
                if (!document.getElementById("checkin").value || !document.getElementById("checkout").value) {
                    document.getElementById("date_range").classList.add("is-invalid");
                    Swal.fire('Error', 'Please select check-in and check-out dates.', 'error');
                    valid = false;
                } else {
                    document.getElementById("date_range").classList.remove("is-invalid");
                }
            }

            if (valid && currentStep < formSteps.length - 1) {
                currentStep++;
                showStep(currentStep);
            }
        });

        document.getElementById("prevBtn").addEventListener("click", () => {
            if (currentStep > 0) {
                currentStep--;
                showStep(currentStep);
            }
        });

        showStep(0); // Initialize first step

        function calculateSummary() {
            let room = parseFloat(document.getElementById("room_cost_value").value) || 0;

            // CALCULATION NG ENTRANCE FEE (Adults lang)
            // Note: ang variable na 'adults' ay global variable na nadeclare sa taas ng script mo
            let totalEntrance = adults * entranceRate;

            // I-update ang Display ng Entrance Fee
            document.getElementById("adult_summary_count").textContent = adults;
            document.getElementById("entrance_payment").textContent = "₱" + totalEntrance.toLocaleString(undefined, {
                minimumFractionDigits: 2
            });
            document.getElementById("total_entrance_fee_value").value = totalEntrance; // Para sa form submit

            // Iba pang calculations (Existing)
            let serv = 0;
            document.querySelectorAll(".service-check:checked").forEach(c => serv += parseFloat(c.dataset.price));

            let rent = 0;
            document.querySelectorAll(".rental-check:checked").forEach(c => rent += parseFloat(c.dataset.price));

            let boat = 0;
            document.querySelectorAll(".boat-check:checked").forEach(c => {
                let p = parseFloat(c.dataset.boat);
                let add = c.closest('tr').querySelector('.include-island').checked ? parseFloat(c.dataset.island) : 0;
                boat += p + add;
            });
            document.getElementById("totalAmount").textContent = boat.toLocaleString(undefined, {
                minimumFractionDigits: 2
            });

            // STEP 5 DISPLAY UPDATES
            document.getElementById("room_payment").textContent = "₱" + room.toLocaleString(undefined, {
                minimumFractionDigits: 2
            });
            document.getElementById("services_payment").textContent = "₱" + serv.toLocaleString(undefined, {
                minimumFractionDigits: 2
            });
            document.getElementById("rentals_payment").textContent = "₱" + rent.toLocaleString(undefined, {
                minimumFractionDigits: 2
            });
            document.getElementById("boat_rentals_payment").textContent = "₱" + boat.toLocaleString(undefined, {
                minimumFractionDigits: 2
            });

            // TOTAL CALCULATION (Isinama na ang totalEntrance)
            let total = room + serv + rent + boat + totalEntrance;

            // Set grand total value
            document.getElementById("total_payment").value = "₱" + total.toLocaleString(undefined, {
                minimumFractionDigits: 2
            });
            document.getElementById("total_payments_value").value = total.toFixed(2);

            // Payment Option Logic (Downpayment)
            const opt = document.getElementById("payment_option").value;
            let pay = total;
            if (opt === 'downpayment') pay = total * 0.5;

            document.getElementById("amount_to_pay_display").value = "₱" + pay.toLocaleString(undefined, {
                minimumFractionDigits: 2
            });
            document.getElementById("final_payable").value = pay.toFixed(2);
        }

        // Listeners
        document.querySelectorAll("input[type=checkbox]").forEach(c => c.addEventListener('change', calculateSummary));
        document.getElementById("payment_option").addEventListener("change", calculateSummary);

        // Ensure initial calculation runs
        calculateRoomCost();

        // Confirm Walk-in
        document.getElementById("confirmBookingBtn").addEventListener("click", function() {
            // Validation for Payment Step
            if (!document.getElementById("payment_type").value) {
                document.getElementById("payment_type").classList.add("is-invalid");
                Swal.fire('Error', 'Please select a Payment Method.', 'error');
                return;
            } else {
                document.getElementById("payment_type").classList.remove("is-invalid");
            }

            Swal.fire({
                title: "Confirm Walk-in?",
                text: "Total Payable: " + document.getElementById("amount_to_pay_display").value + ". This will secure the booking immediately.",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Yes, Process"
            }).then((result) => {
                if (result.isConfirmed) {

                    let form = document.getElementById("multiStepForm");
                    let formData = new FormData(form);

                    // Manual boat data append
                    formData.delete('boat_rentals[]');
                    document.querySelectorAll('.boat-check:checked').forEach(cb => {
                        const rid = cb.value;
                        const amt = parseFloat(cb.dataset.boat);
                        const isl = parseFloat(cb.dataset.island);
                        const inc = cb.closest('tr').querySelector('.include-island').checked ? 1 : 0;
                        const tot = inc ? (amt + isl).toFixed(2) : amt.toFixed(2);
                        formData.append('boat_rentals[]', `${rid}:${inc}:${tot}`);
                    });

                    Swal.fire({
                        title: 'Processing...',
                        didOpen: () => Swal.showLoading()
                    });

                    // Point to confirm_booking.php (or your m_firm_booking.php file)
                    fetch("confirm_booking.php", {
                            method: "POST",
                            body: formData
                        })
                        .then(r => r.json())
                        .then(d => {
                            if (d.status === 'success') Swal.fire('Success', 'Walk-in Booked!', 'success').then(() => window.location.href = 'reservedCustomer');
                            else Swal.fire('Error', d.message, 'error');
                        })
                        .catch(error => {
                            Swal.fire('Network Error', 'An error occurred during submission. (Check PHP logs)', 'error');
                            console.error('Fetch error:', error);
                        });
                }
            });
        });
    </script>
</body>

</html>