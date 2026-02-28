<?php
session_start();

if (!isset($_SESSION['userid'])) {
    header("Location: ./../../");
    exit();
}

$userid = $_SESSION['userid'] ?? null;


$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Define grouped pages
$reservation_page = ['newReservation', 'cancelledReservation', 'event', 'checkInCustomer', 'for_cancellation', 'forPaymentReservation', 'reservedCustomer', 'todayReservation', 'reserationHistory', 'view_reservation', 'checkInCustomer', 'reservationHistory'];
$request_pages = ['pending_request', 'for_recieve', 'recieved'];
$maintenance_pages = ['room_type', 'payment_type', 'add_room', 'event_bookings', 'entrance_fee', 'services', 'boat_rental_fee', 'rental', 'equipment', 'discount'];
$report_page = ['customerLogs', 'incomeReports', 'listOfReservationReport', 'servicesListReport', 'rentalListReport', 'roomListReport', 'reschedule_report'];
$inventory_page = ['equipment_inventory', 'borrowed_equipment', 'damaged_equipment', 'all_equipment_status'];
?>

<style>
    /* 1. Main Sidebar Background - Gradient for modern look */
    #accordionSidebar {
        background: linear-gradient(180deg, #03045e 0%, #023e8a 100%) !important;
        box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    /* 2. Navigation Links Styling */
    .nav-item .nav-link {
        color: rgba(255, 255, 255, 0.8) !important;
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
        /* Prepare for active state */
    }

    /* 3. Hover Effect - Slide right and brighten */
    .nav-item .nav-link:hover {
        color: #fff !important;
        background-color: rgba(255, 255, 255, 0.1);
        transform: translateX(5px);
    }

    /* 4. Active/Current Page Styling - Distinct Highlight */
    .nav-item.active>.nav-link {
        background-color: rgba(255, 255, 255, 0.2) !important;
        color: #fff !important;
        font-weight: bold;
        border-left: 4px solid #00b4d8;
        /* Bright Cyan accent */
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    /* 5. Icons Styling */
    .nav-link i {
        color: #90e0ef;
        /* Light blue icons */
        margin-right: 10px;
    }

    .nav-item.active .nav-link i {
        color: #fff;
        /* White icons on active */
    }

    /* 6. Section Headings */
    .sidebar-heading {
        color: rgba(255, 255, 255, 0.5) !important;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 15px;
        margin-bottom: 5px;
    }

    /* 7. Dropdown/Collapse Menu Styling */
    .collapse-inner {
        background-color: #f8f9fa !important;
        border-radius: 8px !important;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        border: none;
    }

    .collapse-item {
        color: #495057 !important;
        margin: 2px 5px;
        border-radius: 5px;
        transition: 0.2s;
    }

    .collapse-item:hover {
        background-color: #e9ecef !important;
        color: #03045e !important;
        /* Dark blue text on hover */
        padding-left: 15px !important;
        /* Slight movement */
    }

    .collapse-item.active {
        background-color: #03045e !important;
        color: #fff !important;
        font-weight: 600;
    }

    /* Logo Styling */
    .sidebar-brand-icon img {
        border: 2px solid rgba(255, 255, 255, 0.7) !important;
        transition: transform 0.3s;
    }

    .sidebar-brand-icon:hover img {
        transform: scale(1.1);
        border-color: #fff !important;
    }
</style>
<ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">

    <a class="sidebar-brand d-flex align-items-center justify-content-center py-3" href="index">
        <div class="sidebar-brand-icon">
            <img src="uploads/solo_logo.jpg" width="60" height="60" style="border-radius:50%; object-fit: cover;"
                alt="Logo">
        </div>
    </a>

    <hr class="sidebar-divider my-2">

    <li class="nav-item <?= $current_page == 'index' ? 'active' : '' ?>">
        <a class="nav-link" href="index">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <hr class="sidebar-divider d-none d-md-block my-1">

    <li class="nav-item <?= $current_page == 'walkInReservation' ? 'active' : '' ?>">
        <a class="nav-link" href="walkInReservation">
            <i class="fas fa-fw fa-user-plus"></i> <span>Walk In Reservation</span>
        </a>
    </li>

    <hr class="sidebar-divider d-none d-md-block my-1">

    <li class="nav-item <?= $current_page == 'monitoring' ? 'active' : '' ?>">
        <a class="nav-link" href="monitoring">
            <i class="fas fa-fw fa-desktop"></i> <span>Room Monitoring</span>
        </a>
    </li>

    <hr class="sidebar-divider d-none d-md-block my-1">

    <div class="sidebar-heading">Manage</div>

    <li class="nav-item <?= in_array($current_page, $reservation_page) ? 'active' : '' ?>">
        <a class="nav-link <?= in_array($current_page, $reservation_page) ? '' : 'collapsed' ?>" href="#"
            data-toggle="collapse" data-target="#reservation"
            aria-expanded="<?= in_array($current_page, $reservation_page) ? 'true' : 'false' ?>"
            aria-controls="reservation">
            <i class="fas fa-fw fa-calendar-check"></i>
            <span>Reservation</span>
        </a>
        <div id="reservation" class="collapse <?= in_array($current_page, $reservation_page) ? 'show' : '' ?>"
            data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Booking Actions:</h6>
                <a class="collapse-item <?= $current_page == 'event' ? 'active' : '' ?>" href="event">Events</a>
                <a class="collapse-item <?= $current_page == 'newReservation' ? 'active' : '' ?>"
                    href="newReservation">New Reservation</a>
                <a class="collapse-item <?= $current_page == 'reservedCustomer' ? 'active' : '' ?>"
                    href="reservedCustomer">Approved Customer</a>
                <a class="collapse-item <?= $current_page == 'todayReservation' ? 'active' : '' ?>"
                    href="todayReservation">Booked Today</a>
                <a class="collapse-item <?= $current_page == 'resched_booking' ? 'active' : '' ?>"
                    href="resched_booking">Resched Booking</a>
                <a class="collapse-item <?= $current_page == 'for_cancellation' ? 'active' : '' ?>"
                    href="for_cancellation">For Cancellation</a>
                <a class="collapse-item <?= $current_page == 'cancelledReservation' ? 'active' : '' ?>"
                    href="cancelledReservation">Cancelled History</a>
                <a class="collapse-item <?= $current_page == 'checkInCustomer' ? 'active' : '' ?>"
                    href="checkInCustomer">In-House Guests</a>
                <a class="collapse-item <?= $current_page == 'reservationHistory' ? 'active' : '' ?>"
                    href="reservationHistory">All History</a>
            </div>
        </div>
    </li>

    <li class="nav-item <?= in_array($current_page, $inventory_page) ? 'active' : '' ?>">
        <a class="nav-link <?= in_array($current_page, $inventory_page) ? '' : 'collapsed' ?>" href="#"
            data-toggle="collapse" data-target="#inventory"
            aria-expanded="<?= in_array($current_page, $inventory_page) ? 'true' : 'false' ?>"
            aria-controls="reservation">
            <i class="fas fa-fw fa-boxes"></i>
            <span>Inventory</span>
        </a>
        <div id="inventory" class="collapse <?= in_array($current_page, $inventory_page) ? 'show' : '' ?>"
            data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Equipment Status:</h6>
                <a class="collapse-item <?= $current_page == 'equipment_inventory' ? 'active' : '' ?>"
                    href="equipment_inventory">Master List</a>
                <a class="collapse-item <?= $current_page == 'borrowed_equipment' ? 'active' : '' ?>"
                    href="borrowed_equipment">Borrowed</a>
                <a class="collapse-item <?= $current_page == 'damaged_equipment' ? 'active' : '' ?>"
                    href="damaged_equipment">Damaged</a>
                <a class="collapse-item <?= $current_page == 'all_equipment_status' ? 'active' : '' ?>"
                    href="all_equipment_status">Overview Status</a>
            </div>
        </div>
    </li>


    <hr class="sidebar-divider d-none d-md-block my-1">

    <div class="sidebar-heading">Analytics</div>

    <li class="nav-item <?= in_array($current_page, $report_page) ? 'active' : '' ?>">
        <a class="nav-link <?= in_array($current_page, $report_page) ? '' : 'collapsed' ?>" href="#"
            data-toggle="collapse" data-target="#reports"
            aria-expanded="<?= in_array($current_page, $report_page) ? 'true' : 'false' ?>" aria-controls="reports">
            <i class="fas fa-fw fa-chart-line"></i>
            <span>Reports</span>
        </a>
        <div id="reports" class="collapse <?= in_array($current_page, $report_page) ? 'show' : '' ?>"
            data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Generate Reports:</h6>
                <a class="collapse-item <?= $current_page == 'customerLogs' ? 'active' : '' ?>"
                    href="customerLogs">Customer Logs</a>
                <a class="collapse-item <?= $current_page == 'incomeReports' ? 'active' : '' ?>"
                    href="incomeReports">Income Report</a>
                <a class="collapse-item <?= $current_page == 'listOfReservationReport' ? 'active' : '' ?>"
                    href="listOfReservationReport">Reservation List Report</a>
                <a class="collapse-item <?= $current_page == 'reschedule_report' ? 'active' : '' ?>"
                    href="reschedule_report">Reschedule List Report</a>
                <a class="collapse-item <?= $current_page == 'roomListReport' ? 'active' : '' ?>"
                    href="roomListReport">Room List</a>
            </div>
        </div>
    </li>


    <hr class="sidebar-divider d-none d-md-block my-1">

    <div class="sidebar-heading">Settings</div>

    <li class="nav-item <?= in_array($current_page, $maintenance_pages) ? 'active' : '' ?>">
        <a class="nav-link <?= in_array($current_page, $maintenance_pages) ? '' : 'collapsed' ?>" href="#"
            data-toggle="collapse" data-target="#maintenance"
            aria-expanded="<?= in_array($current_page, $maintenance_pages) ? 'true' : 'false' ?>"
            aria-controls="maintenance">
            <i class="fas fa-fw fa-cogs"></i>
            <span>Maintenance</span>
        </a>
        <div id="maintenance" class="collapse <?= in_array($current_page, $maintenance_pages) ? 'show' : '' ?>"
            data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">System Setup:</h6>
                <a class="collapse-item <?= $current_page == 'add_room' ? 'active' : '' ?>" href="add_room">Manage
                    Rooms</a>
                <a class="collapse-item <?= $current_page == 'room_type' ? 'active' : '' ?>" href="room_type">Room
                    Types</a>
                <a class="collapse-item <?= $current_page == 'payment_type' ? 'active' : '' ?>"
                    href="payment_type">Payment Types</a>
                <a class="collapse-item <?= $current_page == 'services' ? 'active' : '' ?>" href="services">Services</a>
                <a class="collapse-item <?= $current_page == 'rental' ? 'active' : '' ?>" href="rental">Rentals</a>
                <a class="collapse-item <?= $current_page == 'boat_rental_fee' ? 'active' : '' ?>"
                    href="boat_rental_fee">Boat Fees</a>
                <a class="collapse-item <?= $current_page == 'equipment' ? 'active' : '' ?>"
                    href="equipment">Equipment</a>
                <!-- <a class="collapse-item <?= $current_page == 'event' ? 'active' : '' ?>" href="event">Events</a> -->
                <!-- <a class="collapse-item <?= $current_page == 'event_bookings' ? 'active' : '' ?>" href="event_bookings">Event Bookings</a> -->
                <a class="collapse-item <?= $current_page == 'entrance_fee' ? 'active' : '' ?>"
                    href="entrance_fee">Entrance Fees</a>
                <a class="collapse-item <?= $current_page == 'discount' ? 'active' : '' ?>" href="discount">Promos /
                    Discounts</a>
            </div>
        </div>
    </li>

    <hr class="sidebar-divider d-none d-md-block">

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>
</ul>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.getElementById('signOutLink')?.addEventListener('click', function (event) {
        event.preventDefault();
        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to sign out?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, sign out',
            cancelButtonText: 'No, stay here'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../../logout';
            }
        });
    });
</script>