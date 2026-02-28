<?php
include '../../config.php';

// --- 1. FILTER LOGIC (Sir Rob's Requirements) ---
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'monthly';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-t');

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

// Function para sa currency formatting
function formatCurrency($amount)
{
    return '₱' . number_format($amount, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include './../template/header.php'; ?>

<style>
    @media print {
        .d-print-none {
            display: none !important;
        }
    }

    .filter-active {
        background-color: #1cc88a !important;
        color: white !important;
        border-color: #1cc88a !important;
    }

    .btn-filter {
        transition: all 0.2s;
        font-weight: 600;
    }

    .info-label {
        font-size: 0.75rem;
        font-weight: bold;
        text-transform: uppercase;
        color: #858796;
    }
</style>

<body id="page-top">
    <div id="wrapper">
        <?php include './../template/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include './../template/navbar.php'; ?>
                <div class="container-fluid">

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Daily Income Summary</h1>
                    </div>

                    <div class="mb-4 d-print-none">
                        <div class="btn-group shadow-sm">
                            <a href="?filter=weekly"
                                class="btn btn-white border <?= $filter == 'weekly' ? 'filter-active' : '' ?> btn-filter">Weekly</a>
                            <a href="?filter=monthly"
                                class="btn btn-white border <?= $filter == 'monthly' ? 'filter-active' : '' ?> btn-filter">Monthly</a>
                            <a href="?filter=yearly"
                                class="btn btn-white border <?= $filter == 'yearly' ? 'filter-active' : '' ?> btn-filter">Yearly</a>
                            <a href="incomeReports"
                                class="btn btn-white border <?= $filter == 'custom' ? 'filter-active' : '' ?> btn-filter text-primary">Custom
                                Range</a>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div
                            class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-success text-white">
                            <h6 class="m-0 font-weight-bold"><i class="fas fa-coins mr-2"></i> Revenue Report
                                (<?= strtoupper($filter) ?>)</h6>
                            <a href="./report/print_income_report.php?from_date=<?= $from_date ?>&to_date=<?= $to_date ?>"
                                target="_blank" class="btn btn-light btn-sm text-success font-weight-bold shadow-sm">
                                <i class="fas fa-print fa-sm"></i> Print Official Report
                            </a>
                        </div>
                        <div class="card-body">

                            <form method="GET" class="mb-4 p-3 bg-light rounded border d-print-none">
                                <input type="hidden" name="filter" value="custom">
                                <div class="form-row align-items-end">
                                    <div class="col-md-4 mb-2">
                                        <label class="info-label">Start Date</label>
                                        <input type="date" name="from_date" class="form-control"
                                            value="<?= $from_date ?>">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="info-label">End Date</label>
                                        <input type="date" name="to_date" class="form-control" value="<?= $to_date ?>">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <button type="submit" class="btn btn-success btn-block shadow-sm">Generate
                                            Custom Report</button>
                                    </div>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="bg-gray-100 text-center">
                                        <tr>
                                            <th>Date</th>
                                            <th>No. of Transactions</th>
                                            <th class="text-right">Daily Income</th>
                                            <th class="text-right text-danger d-print-none">Remaining Balance
                                                (Receivables)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $grand_total_income = 0;
                                        $grand_total_balance = 0;

                                        // --- UPDATED SQL: Group by Date ---
                                        $sql = "SELECT 
                                                    DATE(p.created_at) as trans_date, 
                                                    COUNT(p.payment_id) as trans_count,
                                                    SUM(p.amount) as daily_total_income
                                                FROM reservation_payments_tbl p
                                                WHERE DATE(p.created_at) BETWEEN '$from_date' AND '$to_date'
                                                GROUP BY DATE(p.created_at)
                                                ORDER BY trans_date DESC";

                                        $result = mysqli_query($conn, $sql);

                                        if (mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $current_date = $row['trans_date'];
                                                $daily_income = (float) $row['daily_total_income'];
                                                $grand_total_income += $daily_income;

                                                // --- LOGIC PARA SA BALANCE ---
                                                // Kunin natin ang total price minus total paid para sa lahat ng reservations na nagbayad sa araw na ito
                                                $bal_sql = "SELECT 
                                                                SUM(r.total_price) as total_expected,
                                                                (SELECT SUM(amount) FROM reservation_payments_tbl WHERE tracking_number IN (
                                                                    SELECT tracking_number FROM reservation_payments_tbl WHERE DATE(created_at) = '$current_date'
                                                                )) as total_actually_paid
                                                            FROM reservation_tbl r
                                                            WHERE r.tracking_number IN (
                                                                SELECT DISTINCT tracking_number FROM reservation_payments_tbl WHERE DATE(created_at) = '$current_date'
                                                            )";

                                                $bal_res = mysqli_query($conn, $bal_sql);
                                                $bal_data = mysqli_fetch_assoc($bal_res);

                                                // Computing balance for that specific group of customers
                                                $daily_balance = (float) $bal_data['total_expected'] - (float) $bal_data['total_actually_paid'];
                                                if ($daily_balance < 0)
                                                    $daily_balance = 0;
                                                $grand_total_balance += $daily_balance;

                                                echo "<tr>
                                                        <td class='text-center font-weight-bold'>" . date("M d, Y", strtotime($current_date)) . "</td>
                                                        <td class='text-center'>{$row['trans_count']} Payments</td>
                                                        <td class='text-right text-dark font-weight-bold'>" . formatCurrency($daily_income) . "</td>
                                                        <td class='text-right text-danger d-print-none font-italic'>" . formatCurrency($daily_balance) . "</td>
                                                      </tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='4' class='text-center text-muted py-4'>No transaction records found.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                    <tfoot class="bg-gray-200">
                                        <tr>
                                            <td colspan="2" class="text-right font-weight-bold text-uppercase">Total for
                                                this Period:</td>
                                            <td class="text-right font-weight-bold text-success h5">
                                                <?= formatCurrency($grand_total_income) ?>
                                            </td>
                                            <td class="text-right text-danger d-print-none font-weight-bold">
                                                <?= formatCurrency($grand_total_balance) ?>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <?php include './../template/footer.php'; ?>
        </div>
    </div>
    <?php include './../template/script.php'; ?>
</body>

</html>