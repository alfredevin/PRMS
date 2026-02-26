<style>
    /* Bell Animation */
    @keyframes ring {
        0% {
            transform: rotate(0);
        }

        1% {
            transform: rotate(30deg);
        }

        3% {
            transform: rotate(-28deg);
        }

        5% {
            transform: rotate(34deg);
        }

        7% {
            transform: rotate(-32deg);
        }

        9% {
            transform: rotate(30deg);
        }

        11% {
            transform: rotate(-28deg);
        }

        13% {
            transform: rotate(26deg);
        }

        15% {
            transform: rotate(-24deg);
        }

        17% {
            transform: rotate(22deg);
        }

        19% {
            transform: rotate(-20deg);
        }

        21% {
            transform: rotate(18deg);
        }

        23% {
            transform: rotate(-16deg);
        }

        25% {
            transform: rotate(14deg);
        }

        27% {
            transform: rotate(-12deg);
        }

        29% {
            transform: rotate(10deg);
        }

        31% {
            transform: rotate(-8deg);
        }

        33% {
            transform: rotate(6deg);
        }

        35% {
            transform: rotate(-4deg);
        }

        37% {
            transform: rotate(2deg);
        }

        39% {
            transform: rotate(-1deg);
        }

        41% {
            transform: rotate(1deg);
        }

        43% {
            transform: rotate(0);
        }

        100% {
            transform: rotate(0);
        }
    }

    .bell-active {
        animation: ring 4s .7s ease-in-out infinite;
        transform-origin: 50% 4px;
        color: #4e73df;
        /* Primary color when active */
    }

    /* Dropdown Styling */
    .dropdown-list {
        width: 20rem !important;
    }

    .dropdown-header {
        background-color: #4e73df;
        border: 1px solid #4e73df;
        padding-top: 0.75rem;
        padding-bottom: 0.75rem;
        color: #fff;
        font-weight: 800;
        text-transform: uppercase;
        border-top-left-radius: calc(.35rem - 1px);
        border-top-right-radius: calc(.35rem - 1px);
    }

    .dropdown-item {
        white-space: normal;
        padding-top: .5rem;
        padding-bottom: .5rem;
        border-left: 1px solid #e3e6f0;
        border-right: 1px solid #e3e6f0;
        border-bottom: 1px solid #e3e6f0;
        line-height: 1.3rem;
    }

    .dropdown-list-image {
        position: relative;
        height: 2.5rem;
        width: 2.5rem;
    }

    .dropdown-list-image img {
        height: 2.5rem;
        width: 2.5rem;
    }

    .status-indicator {
        background-color: #eaecf4;
        height: 0.75rem;
        width: 0.75rem;
        border-radius: 100%;
        position: absolute;
        bottom: 0;
        right: 0;
        border: .125rem solid #fff;
    }

    .bg-success {
        background-color: #1cc88a !important;
    }

    /* Badge Pulse */
    .badge-pulse {
        box-shadow: 0 0 0 rgba(231, 74, 59, 0.4);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(231, 74, 59, 0.4);
        }

        70% {
            box-shadow: 0 0 0 10px rgba(231, 74, 59, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(231, 74, 59, 0);
        }
    }
</style>

<nav class="navbar navbar-expand navbar-light topbar mb-4 static-top shadow" style="background:white;">

    <!-- Sidebar Toggle (Topbar) -->
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <!-- Topbar Navbar -->
    <ul class="navbar-nav ml-auto">

        <!-- Nav Item - Search Dropdown (Visible Only XS) -->
        <li class="nav-item dropdown no-arrow d-sm-none">
            <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-search fa-fw"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in" aria-labelledby="searchDropdown">
                <form class="form-inline mr-auto w-100 navbar-search">
                    <div class="input-group">
                        <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..." aria-label="Search" aria-describedby="basic-addon2">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="button">
                                <i class="fas fa-search fa-sm"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </li>

        <!-- Nav Item - Alerts -->
        <li class="nav-item dropdown no-arrow mx-1">
            <?php
            // Function to get time elapsed
            function time_elapsed_string($datetime, $full = false)
            {
                $now = new DateTime;
                $ago = new DateTime($datetime);
                $diff = $now->diff($ago);
                $diff->w = floor($diff->d / 7);
                $diff->d -= $diff->w * 7;
                $string = array(
                    'y' => 'year',
                    'm' => 'month',
                    'w' => 'week',
                    'd' => 'day',
                    'h' => 'hour',
                    'i' => 'minute',
                    's' => 'second',
                );
                foreach ($string as $k => &$v) {
                    if ($diff->$k) {
                        $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
                    } else {
                        unset($string[$k]);
                    }
                }
                if (!$full) $string = array_slice($string, 0, 1);
                return $string ? implode(', ', $string) . ' ago' : 'just now';
            }

            $resCountQuery = "SELECT COUNT(*) as total FROM reservation_tbl WHERE status = 1";
            $resCountResult = mysqli_query($conn, $resCountQuery);
            $resCount = mysqli_fetch_assoc($resCountResult)['total'];

            // Add animation class if there are notifications
            $bellClass = ($resCount > 0) ? 'bell-active' : '';
            $badgeClass = ($resCount > 0) ? 'badge-pulse' : '';
            ?>

            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-bell fa-fw <?= $bellClass ?>"></i>
                <!-- Counter - Alerts -->
                <?php if ($resCount > 0): ?>
                    <span class="badge badge-danger badge-counter <?= $badgeClass ?>"><?= $resCount ?></span>
                <?php endif; ?>
            </a>

            <!-- Dropdown - Alerts -->
            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="alertsDropdown">
                <h6 class="dropdown-header">
                    <i class="fas fa-bell mr-2"></i> Reservation Alerts
                </h6>

                <div style="max-height: 300px; overflow-y: auto;">
                    <?php
                    $query = "SELECT guest_name, tracking_number, created_at FROM reservation_tbl WHERE status = 1 ORDER BY created_at DESC LIMIT 5";
                    $result = mysqli_query($conn, $query);

                    if (mysqli_num_rows($result) > 0) {
                        while ($res = mysqli_fetch_assoc($result)) {
                            $timeAgo = time_elapsed_string($res['created_at']);
                            // Dynamic avatar based on first letter
                            $firstLetter = strtoupper(substr($res['guest_name'], 0, 1));
                            $bgColors = ['bg-primary', 'bg-success', 'bg-info', 'bg-warning', 'bg-danger'];
                            $randomColor = $bgColors[array_rand($bgColors)];

                            echo '
                        <a class="dropdown-item d-flex align-items-center" href="view_reservation?tracking=' . $res['tracking_number'] . '">
                            <div class="mr-3">
                                <div class="icon-circle ' . $randomColor . '">
                                    <i class="fas fa-user text-white"></i>
                                </div>
                            </div>
                            <div>
                                <div class="small text-gray-500">' . $timeAgo . '</div>
                                <span class="font-weight-bold">New booking from ' . htmlspecialchars($res['guest_name']) . '!</span>
                                <div class="small text-gray-500">Ref: ' . $res['tracking_number'] . '</div>
                            </div>
                        </a>';
                        }
                    } else {
                        echo '
                    <a class="dropdown-item d-flex align-items-center" href="#">
                        <div class="mr-3">
                            <div class="icon-circle bg-secondary">
                                <i class="fas fa-info text-white"></i>
                            </div>
                        </div>
                        <div>
                            <span class="font-weight-bold">No new reservations</span>
                        </div>
                    </a>';
                    }
                    ?>
                </div>

                <a class="dropdown-item text-center small text-gray-500 bg-light" href="newReservation">Show All Alerts</a>
            </div>
        </li>

        <div class="topbar-divider d-none d-sm-block"></div>

        <!-- Nav Item - User Information -->
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                    <?php
                    if (isset($userid)) {
                        $sql = "SELECT * FROM user_tbl WHERE userid = '$userid'";
                        $resultsql = mysqli_query($conn, $sql);
                        if ($res = mysqli_fetch_assoc($resultsql)) {
                            echo htmlspecialchars($res['fullname']);
                        }
                    } else {
                        echo "Admin";
                    }
                    ?>
                </span>
                <img class="img-profile rounded-circle" src="../img/undraw_profile.svg">
            </a>
            <!-- Dropdown - User Information -->
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                <a class="dropdown-item" href="profile">
                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                    Profile
                </a>
                <a class="dropdown-item" href="changepassword">
                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                    Change Password
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" id="signOutLinkNav">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                    Logout
                </a>
            </div>
        </li>

    </ul>

</nav>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.getElementById('signOutLinkNav').addEventListener('click', function(event) {
        event.preventDefault();

        Swal.fire({
            title: 'Ready to Leave?',
            text: "Select 'Logout' below if you are ready to end your current session.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Logout',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../../logout';
            }
        });
    });
</script>