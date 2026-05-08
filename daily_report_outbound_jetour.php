<?php

############ initialize db ###################
include 'db_connect.php';

########## set title ########################
$title = 'Daily Report (Outbound) - JeTour';

$now = date("Y-m-d");

########## get data when submit form ########
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : $now;
$to_date   = isset($_GET['to_date']) ? $_GET['to_date'] : $now;

$report_data = [];

########## totals ###########################
$total_calls = 0;
$total_connected_calls = 0;
$total_not_connected_calls = 0;

$grand_total_talk_seconds = 0;
$grand_total_hold_seconds = 0;
$grand_total_dispo_seconds = 0;
$grand_total_dead_seconds = 0;
$grand_total_hold_count = 0;

########### generate date range #############
$start = new DateTime($from_date);
$end   = new DateTime($to_date);
$end->modify('+1 day');

$date_range = new DatePeriod($start, new DateInterval('P1D'), $end);

########### time format function ############
function timeFormat($time)
{
    $time = (int) round((float)$time);
    if ($time < 0) {
        $time = 0;
    }

    $hours = floor($time / 3600);
    $mins  = floor(($time % 3600) / 60);
    $secs  = $time % 60;

    return sprintf("%02d:%02d:%02d", $hours, $mins, $secs);
}

########### only build report when submit ####
if (isset($_GET['SUBMIT'])) {

    foreach ($date_range as $date) {
        $current_date = $date->format("Y-m-d");

        ################# outbound calls #########################
        $calls_query = "SELECT COUNT(*) AS outbound_calls FROM vicidial_log WHERE DATE(call_date) = '$current_date' AND campaign_id LIKE '%JETOUR%'";
        $calls_result = $conn->query($calls_query);
        $calls = ($calls_result && $calls_result->num_rows > 0)
            ? (int)$calls_result->fetch_assoc()['outbound_calls']
            : 0;

        $total_calls += $calls;

        ############### outbound connected calls ##################
        $calls_connected_query = "SELECT COUNT(*) AS connected_calls FROM vicidial_log WHERE DATE(call_date) = '$current_date' AND campaign_id LIKE '%JETOUR%' AND status NOT IN('NA','B','DC','N')";
        $connected_result = $conn->query($calls_connected_query);
        $connected_calls = ($connected_result && $connected_result->num_rows > 0)
            ? (int)$connected_result->fetch_assoc()['connected_calls']
            : 0;

        $total_connected_calls += $connected_calls;

        ############### outbound not connected calls #############
        $not_connected_query = "SELECT COUNT(*) AS not_connected_calls FROM vicidial_log WHERE DATE(call_date) = '$current_date' AND campaign_id LIKE '%JETOUR%' AND status IN('NA','B','DC','N')";
        $not_connected_result = $conn->query($not_connected_query);
        $not_connected_calls = ($not_connected_result && $not_connected_result->num_rows > 0)
            ? (int)$not_connected_result->fetch_assoc()['not_connected_calls']
            : 0;

        $total_not_connected_calls += $not_connected_calls;

        ############### daily talk totals / avg talk time ########
        $talk_query = "SELECT SUM(length_in_sec) AS total_talk_time,AVG(length_in_sec) AS avg_talk_time FROM vicidial_log WHERE DATE(call_date) = '$current_date' AND campaign_id LIKE '%JETOUR%' AND status NOT IN('NA','B','DC','N')";
        $talk_result = $conn->query($talk_query);
        $talk_data = ($talk_result && $talk_result->num_rows > 0)
            ? $talk_result->fetch_assoc()
            : ['total_talk_time' => 0, 'avg_talk_time' => 0];

        $daily_total_talk_time = isset($talk_data['total_talk_time']) ? (float)$talk_data['total_talk_time'] : 0;
        $daily_avg_talk_time   = isset($talk_data['avg_talk_time']) ? (float)$talk_data['avg_talk_time'] : 0;

        $grand_total_talk_seconds += $daily_total_talk_time;
        $formatted_avg_talk_time = timeFormat($daily_avg_talk_time);

        ############### daily hold totals / avg hold time ########
        $avg_hold_time_query = "SELECT SUM(parked_sec) AS total_hold_time,AVG(parked_sec) AS avg_hold_time,COUNT(*) AS hold_count FROM park_log WHERE DATE(parked_time) = '$current_date' AND channel_group LIKE '%JETOUR%'";
        $avg_hold_time_result = $conn->query($avg_hold_time_query);
        $hold_data = ($avg_hold_time_result && $avg_hold_time_result->num_rows > 0)
            ? $avg_hold_time_result->fetch_assoc()
            : ['total_hold_time' => 0, 'avg_hold_time' => 0, 'hold_count' => 0];

        $daily_total_hold_time = isset($hold_data['total_hold_time']) ? (float)$hold_data['total_hold_time'] : 0;
        $daily_avg_hold_time   = isset($hold_data['avg_hold_time']) ? (float)$hold_data['avg_hold_time'] : 0;
        $daily_hold_count      = isset($hold_data['hold_count']) ? (int)$hold_data['hold_count'] : 0;

        $grand_total_hold_seconds += $daily_total_hold_time;
        $grand_total_hold_count   += $daily_hold_count;

        $formatted_avg_hold_time = timeFormat($daily_avg_hold_time);

        ####### daily dispo time #################################
        $dispo_time_query = "SELECT SUM(dispo_sec) AS total_dispo_time FROM vicidial_agent_log WHERE DATE(event_time) = '$current_date' AND comments = 'MANUAL' AND campaign_id LIKE '%JETOUR%'";
        $dispo_time_result = $conn->query($dispo_time_query);
        $total_dispo_time = ($dispo_time_result && $dispo_time_result->num_rows > 0)
            ? (float)$dispo_time_result->fetch_assoc()['total_dispo_time']
            : 0;

        $grand_total_dispo_seconds += $total_dispo_time;

        ####### daily dead time ##################################
        $dead_time_query = "SELECT SUM(dead_sec) AS total_dead_time FROM vicidial_agent_log WHERE DATE(event_time) = '$current_date' AND comments = 'MANUAL' AND campaign_id LIKE '%JETOUR%'";
        $dead_sec_result = $conn->query($dead_time_query);
        $total_dead_time = ($dead_sec_result && $dead_sec_result->num_rows > 0)
            ? (float)$dead_sec_result->fetch_assoc()['total_dead_time']
            : 0;

        $grand_total_dead_seconds += $total_dead_time;

        ####### Avg Handling Time (talk+hold+dispo+dead)/connected calls #######
        if ($connected_calls > 0) {
            $daily_avg_handle_time = (
                $daily_total_talk_time +
                $daily_total_hold_time +
                $total_dispo_time +
                $total_dead_time
            ) / $connected_calls;
        } else {
            $daily_avg_handle_time = 0;
        }

        $formatted_avg_handle_time = timeFormat($daily_avg_handle_time);

        ########## add daily data to array ##########################
        if($calls>0){
            $report_data[] = [
            'date'                               => $current_date,
            'date_time'                          => $current_date . ' 00:00:00',
            'total_outbound_calls'               => $calls,
            'total_outbound_calls_connected'     => $connected_calls,
            'total_outbound_calls_not_connected' => $not_connected_calls,
            'avg_talk_time'                      => $formatted_avg_talk_time,
            'avg_hold_time'                      => $formatted_avg_hold_time,
            'avg_handling_time'                  => $formatted_avg_handle_time
        ];
        }
        
    }

    ########## grand total row averages ##########################
    $grand_avg_talk_time = ($total_connected_calls > 0)
        ? ($grand_total_talk_seconds / $total_connected_calls)
        : 0;

    $grand_avg_hold_time = ($grand_total_hold_count > 0)
        ? ($grand_total_hold_seconds / $grand_total_hold_count)
        : 0;

    $grand_avg_handle_time = ($total_connected_calls > 0)
        ? (
            ($grand_total_talk_seconds + $grand_total_hold_seconds + $grand_total_dispo_seconds + $grand_total_dead_seconds)
            / $total_connected_calls
        )
        : 0;

    $formatted_total_avg_talk_time   = timeFormat($grand_avg_talk_time);
    $formatted_total_avg_hold_time   = timeFormat($grand_avg_hold_time);
    $formatted_total_avg_handle_time = timeFormat($grand_avg_handle_time);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">    
</head>

<body>
<div class="container-fluid">
    <h5 class="text-center mt-3 mb-3"><?php echo $title; ?></h5>

    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="get" name="vicidial_report" id="vicidial_report">
        <div class="row form-section">
            <div class="col-lg-4">
                <div class="d-flex align-items-center justify-content-end gap-2">
                    <label for="from_date" class="mb-0">From Date:</label>
                    <input type="date" name="from_date" id="from_date"
                           value="<?php echo $from_date; ?>"
                           class="form-control form-control-sm" style="width: 140px;">
                </div>
            </div>

            <div class="col-lg-4">
                <div class="d-flex align-items-center justify-content-end gap-2">
                    <label for="to_date" class="mb-0">To Date:</label>
                    <input type="date" name="to_date" id="to_date"
                           value="<?php echo $to_date; ?>"
                           class="form-control form-control-sm" style="width: 140px;">
                </div>
            </div>

            <div class="col-lg-4 d-flex justify-content-end">
                <div class="button-group d-flex gap-2">
                    <a href="/ocdial/admin.php?ADD=999999" class="btn btn-secondary btn-equal">Back</a>
                    <input type="submit" value="SUBMIT" name="SUBMIT" class="btn btn-primary btn-equal">
                    <input type="button" value="Download CSV" onclick="downloadCSV()" class="btn btn-success btn-equal">
                </div>
            </div>
        </div>
    </form>

    <?php if (isset($_GET['SUBMIT'])): ?>
        <?php if (empty($report_data)): ?>
            <div style="text-align:center; margin-top:20px; color:black;">
                No data available for the selected date range.
            </div>
        <?php else: ?>
            <table>
                <tr>
                    <th>Break down by Date</th>
                    <th>Break down by Date &amp; Time</th>
                    <th>Total Outbound Call</th>
                    <th>Total Outbound Call Connected</th>
                    <th>Total Outbound Call Not Connected</th>
                    <th>AVG Talk Time</th>
                    <th>AVG Hold Time</th>
                    <th>AVG Handling Time</th>
                </tr>

                <?php foreach ($report_data as $data): ?>
                    <tr>
                        <td><?php echo $data['date']; ?></td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($data['date_time'])); ?></td>
                        <td><?php echo $data['total_outbound_calls']; ?></td>
                        <td><?php echo $data['total_outbound_calls_connected']; ?></td>
                        <td><?php echo $data['total_outbound_calls_not_connected']; ?></td>
                        <td><?php echo $data['avg_talk_time']; ?></td>
                        <td><?php echo $data['avg_hold_time']; ?></td>
                        <td><?php echo $data['avg_handling_time']; ?></td>
                    </tr>
                <?php endforeach; ?>

                <tr style="background-color: #fab4af; font-weight: bold;">
                    <th>Total</th>
                    <th></th>
                    <th><?php echo $total_calls; ?></th>
                    <th><?php echo $total_connected_calls; ?></th>
                    <th><?php echo $total_not_connected_calls; ?></th>
                    <th><?php echo $formatted_total_avg_talk_time; ?></th>
                    <th><?php echo $formatted_total_avg_hold_time; ?></th>
                    <th><?php echo $formatted_total_avg_handle_time; ?></th>
                </tr>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 15px;
            margin-top: 20px;
        }

        td, th {
            border: 1px solid #dddddd;
            text-align: center;
            padding: 8px;
        }

        tr:nth-child(even) {
            background-color: #f4f4f4;
        }

        .button-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: center;
        }

        .form-section {
            align-items: center;
            margin-bottom: 15px;
        }

        .btn-equal {
            width: 130px;
        }
</style>

<script>
function downloadCSV() {
    var csv = '';
    var table = document.querySelector('table');

    if (!table) {
        return;
    }

    table.querySelectorAll('tr').forEach(function(row) {
        var rowData = [];
        row.querySelectorAll('th, td').forEach(function(cell) {
            rowData.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
        });
        csv += rowData.join(',') + '\n';
    });

    var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    var a = document.createElement('a');
    a.style.display = 'none';
    a.href = URL.createObjectURL(blob);
    a.download = 'JeTour_Daily_Report_Outbound.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}
</script>
</body>
</html>