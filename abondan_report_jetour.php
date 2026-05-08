<?php
################  set db connection ################
include('db_connect.php');

############# define title #########################
$title = 'Abandon Report - JeTour';

############# time variables ######################
$now = date("Y-m-d");

$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : $now;
$to_date   = isset($_GET['to_date']) ? $_GET['to_date'] : $now;

$from_time = isset($_GET['from_time']) ? $_GET['from_time'] : "00:00:00";
$to_time   = isset($_GET['to_time']) ? $_GET['to_time'] : "23:59:59";

$from_date_time = $from_date . ' ' . $from_time;
$to_date_time   = $to_date . ' ' . $to_time;

$short_abandon_sec = isset($_GET['short_abandon_sec']) ? intval($_GET['short_abandon_sec']) : 10;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <title><?php echo $title; ?></title>


</head>

<body>

    <div class="container-fluid">
        <h5 class="text-center mt-3 mb-3"><?php echo $title; ?></h5>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="get" name="vicidial_report" id="vicidial_report">
            <div class="row form-section">
                <div class="col-lg-3">
                    <div class="d-flex align-items-center gap-2">
                        <label for="from_date" class="mb-0">From Date:</label><br>
                        <input type="date" name="from_date" id="from_date" value="<?php echo $from_date; ?>" class="form-control form-control-sm" style="width: 120px;">
                        <input type="text" name="from_time" id="from_time" value="<?php echo $from_time; ?>" class="form-control form-control-sm" style="width: 120px;">
                    </div>
                </div>


                <div class="col-lg-3">
                    <div class="d-flex align-items-center gap-2">
                        <label for="to_date" class="mb-0">To Date:</label><br>
                        <input type="date" name="to_date" id="to_date" value="<?php echo $to_date; ?>" class="form-control form-control-sm" style="width: 120px;">
                        <input type="text" name="to_time" id="to_time" value="<?php echo $to_time; ?>" class="form-control form-control-sm" style="width: 120px;">
                    </div>
                </div>

                <div class="col-lg-3">
                    <div class="d-flex align-items-center gap-2">
                        <label for="short_abandon_sec" class="mb-0">Short Abandon Seconds:&nbsp;</label><br>
                        <input type="number" id="short_abandon_sec" name="short_abandon_sec"
                            value="<?php echo $short_abandon_sec; ?>" style="width: 50px;" placeholder="Sec" class="form-control form-control-sm">

                    </div>
                </div>

                <div class="col-lg-3 d-flex justify-content-end">
                    <div class="button-group d-flex gap-2">
                        <a href="/ocdial/admin.php?ADD=999999" class="btn btn-secondary btn-equal">Back</a>
                        <input type="submit" value="SUBMIT" name="SUBMIT" class="btn btn-primary btn-equal">
                        <input type="button" value="Download CSV" onclick="downloadCSV()" class="btn btn-success btn-equal">
                    </div>
                </div>
            </div>
        </form>

        <!--<table>-->

        <?php
        if (isset($_GET['SUBMIT'])) {

            $query = "SELECT call_date, phone_number, status, queue_seconds
                          FROM vicidial_closer_log
                          WHERE call_date BETWEEN '$from_date_time' AND '$to_date_time'
                          AND status IN ('DROP','TIMEOT')
                          AND campaign_id LIKE '%JETOUR_%'
                          ORDER BY call_date";

            $result = $conn->query($query);

            if ($result && $result->num_rows > 0) {

                echo "    <table>
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Caller Phone No</th>
                                    <th>Status (Short Abandon <?php echo $short_abandon_sec; ?> Sec)</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                        <tbody>";

                while ($row = $result->fetch_assoc()) {

                    if ($row['queue_seconds'] < $short_abandon_sec) {
                        $status = "Short Abandon";
                    } else {
                        $status = "Abandon";
                    }

                    $hours   = floor($row['queue_seconds'] / 3600);
                    $minutes = floor(($row['queue_seconds'] % 3600) / 60);
                    $seconds = $row['queue_seconds'] % 60;

                    $duration = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);

                    echo "<tr>
                                <td>{$row['call_date']}</td>
                                <td>{$row['phone_number']}</td>
                                <td>{$status}</td>
                                <td>{$duration}</td>
                              </tr>";
                }
            } else {
                //echo '<tr><td colspan="4" style="text-align:center;">No data found for the selected date range.</td></tr>';
                echo "<div style='display:flex; justify-content: center;'>No data found for the selected date range.</div>";
            }
        }
        ?>
        </tbody>
        </table>
    </div>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 15px;
        }

        td,
        th {
            border: 1px solid #ddd;
            text-align: center;
            padding: 8px;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .form-section {
            text-align: center;
            margin-bottom: 20px;
        }

        .button-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: center;
        }

        .form-control-sm {
            padding: 2px 6px;
            border: 1px solid #DEDED1;
            width: 120px;
        }

        .btn-equal {
            width: 130px;
        }
    </style>
    <script>
        function downloadCSV() {
            var csv = '';
            var tables = document.querySelectorAll('table');

            tables.forEach(function(table, index) {
                if (index > 0) {
                    csv += '\n';
                }

                table.querySelectorAll('tr').forEach(function(row) {
                    row.querySelectorAll('th, td').forEach(function(cell, cellIndex) {
                        if (cellIndex > 0) {
                            csv += ',';
                        }
                        csv += '"' + cell.textContent.trim() + '"';
                    });
                    csv += '\n';
                });
            });

            var blob = new Blob([csv], {
                type: 'text/csv'
            });
            var a = document.createElement('a');
            a.style.display = 'none';
            a.href = window.URL.createObjectURL(blob);
            a.download = 'JeTour_Abandon_Report.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    </script>

</body>

</html>