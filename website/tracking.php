<?php
include './../config.php';
include './template/header.php';
?>

<style>
    .tracking-card {
        border: none;
        border-radius: 20px;
        background: white;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        transition: transform 0.3s ease;
    }

    .tracking-header {
        background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
        padding: 30px;
        color: white;
        text-align: center;
        position: relative;
    }

    .tracking-header::after {
        content: '';
        position: absolute;
        bottom: -20px;
        left: 0;
        right: 0;
        height: 40px;
        background: white;
        border-radius: 50% 50% 0 0;
    }

    .status-pill {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(5px);
        padding: 5px 15px;
        border-radius: 50px;
        font-size: 0.9rem;
        font-weight: 600;
        display: inline-block;
        margin-top: 10px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px dashed #e9ecef;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        color: #6c757d;
        font-weight: 500;
    }

    .info-value {
        color: #212529;
        font-weight: 700;
    }

    /* Status Specific Colors */
    .balance-card {
        background: #fff3cd;
        color: #856404;
        border-radius: 15px;
        padding: 15px;
        margin-top: 20px;
        border: 1px solid #ffeeba;
    }

    .paid-card {
        background: #d1e7dd;
        color: #0f5132;
        border-radius: 15px;
        padding: 15px;
        margin-top: 20px;
        border: 1px solid #badbcc;
    }
</style>

<body>
    <!-- Spinner -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
    </div>

    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <?php include './template/navbar.php'; ?>

    <!-- Header -->
    <div class="container-fluid page-header py-5 mb-5 bg-primary text-white wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5 text-center">
            <h1 class="display-3 animated slideInRight">Track Reservation</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb justify-content-center mb-0 bg-transparent">
                    <li class="breadcrumb-item"><a href="#" class="text-white-50">Home</a></li>
                    <li class="breadcrumb-item active text-white" aria-current="page">Tracking</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="container py-5">
        <!-- Search Box -->
        <div class="row justify-content-center mb-5">
            <div class="col-md-6">
                <div class="input-group input-group-lg shadow-sm">
                    <input type="text" id="trackingNumberInput" class="form-control border-0 py-3 ps-4" placeholder="Enter your Tracking Number (e.g. RES123456)">
                    <button class="btn btn-primary px-4" id="trackBtn"><i class="fas fa-search me-2"></i> Track</button>
                </div>
            </div>
        </div>

        <!-- Results Container -->
        <div id="reservationDetails" style="display: none;">
            <!-- Content will be injected here by JS -->
        </div>
    </div>

    <a href="#" class="btn btn-lg btn-primary btn-lg-square rounded-circle back-to-top"><i class="bi bi-arrow-up"></i></a>
    <?php include './template/script.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Hide Spinner
        window.addEventListener('load', () => {
            const spinner = document.getElementById('spinner');
            if (spinner) spinner.classList.remove('show');
        });

        document.getElementById('trackBtn').addEventListener('click', function() {
            const trackingNumber = document.getElementById('trackingNumberInput').value.trim();
            const container = document.getElementById('reservationDetails');

            if (trackingNumber === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Oops...',
                    text: 'Please enter a tracking number!'
                });
                return;
            }

            // Show Loading
            container.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Searching...</p></div>';
            container.style.display = 'block';

            // Call the backend API
            fetch('track_reservation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        tracking_number: trackingNumber
                    })
                })
                .then(async res => {
                    if (!res.ok) throw new Error(`Server Error: ${res.status} ${res.statusText}`);
                    const text = await res.text();
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error("Server response:", text);
                        throw new Error("Invalid response from server. Check console.");
                    }
                })
                .then(data => {
                    if (data.success) {
                        const r = data.reservation;

                        // Build Lists for Services/Rentals/Boats
                        let servicesList = r.services.length ? r.services.map(s => `<div class="d-flex justify-content-between small mb-1"><span>${s.name}</span><span>₱${s.price}</span></div>`).join('') : '<small class="text-muted">None</small>';
                        let rentalsList = r.rentals.length ? r.rentals.map(x => `<div class="d-flex justify-content-between small mb-1"><span>${x.name}</span><span>₱${x.price}</span></div>`).join('') : '<small class="text-muted">None</small>';
                        let boatsList = r.boats.length ? r.boats.map(b => `<div class="d-flex justify-content-between small mb-1"><span>${b.name}</span><span>₱${b.price}</span></div>`).join('') : '<small class="text-muted">None</small>';

                        // Logic for Balance Display
                        let balanceVal = parseFloat(r.balance.toString().replace(/,/g, ''));
                        let balanceHtml = '';

                        if (balanceVal > 0) {
                            balanceHtml = `
                            <div class="balance-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-exclamation-circle me-2"></i>Balance Due (Upon Arrival)</span>
                                    <span class="h5 mb-0 fw-bold">₱${r.balance}</span>
                                </div>
                            </div>`;
                        } else {
                            balanceHtml = `
                            <div class="paid-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-check-circle me-2"></i>Fully Paid</span>
                                    <span class="h5 mb-0 fw-bold">₱0.00</span>
                                </div>
                            </div>`;
                        }

                        // --- ACTION BUTTONS LOGIC ---
                        // Show buttons only if status is Pending (1) or Confirmed (2)
                        let actionButtons = '';
                        if (r.status == 1 || r.status == 2) {
                            actionButtons = `
                            <div class="d-grid gap-2 mt-4 border-top pt-3">
                                <button class="btn btn-warning btn-sm text-white py-2" id="reschedBtn">
                                    <i class="fas fa-calendar-alt me-2"></i>Request Reschedule
                                </button>
                                <button class="btn btn-danger btn-sm py-2" id="cancelBtn">
                                    <i class="fas fa-ban me-2"></i>Request Cancellation
                                </button>
                            </div>
                        `;
                        }

                        // Render HTML Layout
                        container.innerHTML = `
                        <div class="row g-4 justify-content-center">
                            <!-- LEFT: Main Details Card -->
                            <div class="col-lg-5">
                                <div class="tracking-card h-100">
                                    <div class="tracking-header">
                                        <h4 class="mb-0 fw-bold">${r.guest_name}</h4>
                                        <div class="status-pill">${r.status_text}</div>
                                    </div>
                                    <div class="p-4 pt-5">
                                        <div class="text-center mb-4">
                                            <img src="./../user/admin/${r.room_image}" class="img-fluid rounded-3 shadow-sm" style="max-height:180px; width:100%; object-fit:cover;">
                                            <h5 class="mt-3 fw-bold text-primary">${r.room_name}</h5>
                                            <small class="text-muted text-uppercase">${r.room_type}</small>
                                        </div>
                                        
                                        <div class="info-row">
                                            <span class="info-label">Check-In</span>
                                            <span class="info-value">${r.check_in}</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Check-Out</span>
                                            <span class="info-value">${r.check_out}</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Guests</span>
                                            <span class="info-value">${r.guests} Person(s)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- RIGHT: Financials Card -->
                            <div class="col-lg-5">
                                <div class="tracking-card h-100">
                                    <div class="p-4">
                                        <h5 class="fw-bold text-secondary mb-4"><i class="fas fa-receipt me-2"></i>Booking Breakdown</h5>
                                        
                                        <h6 class="text-primary border-bottom pb-2 mb-3">Inclusions</h6>
                                        <div class="mb-3">
                                            <p class="fw-bold small mb-1">Extra Services:</p>
                                            ${servicesList}
                                        </div>
                                        <div class="mb-3">
                                            <p class="fw-bold small mb-1">Rentals:</p>
                                            ${rentalsList}
                                        </div>
                                        <div class="mb-4">
                                            <p class="fw-bold small mb-1">Boat Activities:</p>
                                            ${boatsList}
                                        </div>

                                        <h6 class="text-primary border-bottom pb-2 mb-3">Payment Summary</h6>
                                        <div class="info-row">
                                            <span class="info-label">Total Cost</span>
                                            <span class="info-value fs-5">₱${r.total_price}</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Option Selected</span>
                                            <span class="info-value"><span class="badge bg-info text-dark">${r.payment_option}</span></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Amount Paid</span>
                                            <span class="info-value text-success">₱${r.amount_paid}</span>
                                        </div>

                                        ${balanceHtml}
                                        
                                        <div class="text-center mt-4">
                                            <small class="text-muted d-block mb-2">Ref No: <strong>${r.reference_number || 'N/A'}</strong></small>
                                            <button class="btn btn-outline-primary btn-sm rounded-pill" onclick="window.print()"><i class="fas fa-print me-2"></i>Print Details</button>
                                        </div>

                                        <!-- ACTION BUTTONS INJECTED HERE -->
                                        ${actionButtons}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                        // Attach Listeners for Resched & Cancel if they exist
                        const reschedBtn = document.getElementById("reschedBtn");
                        const cancelBtn = document.getElementById("cancelBtn");

                        if (reschedBtn) {
                            reschedBtn.addEventListener("click", function() {
                                // Initialize current dates for the date picker default
                                const currentCheckIn = new Date(r.check_in).toISOString().split('T')[0];
                                const currentCheckOut = new Date(r.check_out).toISOString().split('T')[0];

                                Swal.fire({
                                    title: "Request Reschedule",
                                    html: `
                                    <div class="text-start">
                                        <label class="form-label small fw-bold">New Check-In</label>
                                        <input type="date" id="newCheckIn" class="form-control mb-2" min="${new Date().toISOString().split('T')[0]}">
                                        <label class="form-label small fw-bold">New Check-Out</label>
                                        <input type="date" id="newCheckOut" class="form-control" min="${new Date().toISOString().split('T')[0]}">
                                    </div>
                                `,
                                    showCancelButton: true,
                                    confirmButtonText: "Submit Request",
                                    preConfirm: () => {
                                        const newCheckIn = document.getElementById("newCheckIn").value;
                                        const newCheckOut = document.getElementById("newCheckOut").value;
                                        if (!newCheckIn || !newCheckOut) {
                                            Swal.showValidationMessage("Please select both dates");
                                        }
                                        return {
                                            newCheckIn,
                                            newCheckOut
                                        };
                                    }
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Call backend to update status to 8 (Request Resched)
                                        updateStatus(trackingNumber, 8, result.value);
                                    }
                                });
                            });
                        }

                        if (cancelBtn) {
                            cancelBtn.addEventListener("click", function() {
                                Swal.fire({
                                    title: "Request Cancellation?",
                                    text: "This will be sent to admin for approval.",
                                    icon: "warning",
                                    showCancelButton: true,
                                    confirmButtonColor: "#dc3545",
                                    confirmButtonText: "Yes, Cancel it"
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Call backend to update status to 7 (Request Cancellation)
                                        updateStatus(trackingNumber, 7);
                                    }
                                });
                            });
                        }

                    } else {
                        container.innerHTML = `
                        <div class="text-center py-5">
                            <img src="https://cdn-icons-png.flaticon.com/512/2748/2748558.png" width="100" class="mb-3 opacity-50">
                            <h4 class="text-secondary">Reservation Not Found</h4>
                            <p class="text-muted">We couldn't find any booking with tracking number <strong>${trackingNumber}</strong>.</p>
                        </div>
                    `;
                    }
                })
                .catch(err => {
                    console.error(err);
                    let msg = err.message;
                    if (msg.includes("404")) msg = "File 'track_reservation.php' not found.";
                    container.innerHTML = `<div class="alert alert-danger text-center"><h4>Error</h4><p>${msg}</p></div>`;
                });
        });

        // Helper to send updates to backend
        function updateStatus(tracking, status, dates = null) {
            Swal.fire({
                title: 'Processing...',
                didOpen: () => Swal.showLoading()
            });

            fetch('update_reservation_request.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        tracking_number: tracking,
                        status: status,
                        dates: dates
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Success', 'Request submitted successfully.', 'success')
                            .then(() => document.getElementById('trackBtn').click()); // Refresh view
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(err => Swal.fire('Error', 'Request failed.', 'error'));
        }
    </script>
</body>