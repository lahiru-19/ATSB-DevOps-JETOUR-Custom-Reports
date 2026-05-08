<?php

######### initialize DB #########
include 'db_connect.php';

######## title for the report ###
$title = "Hourly Interval Report-Jetour";

###### set time #################
$now = Date('Y-m-d');

$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : $now;
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : $now;
//$from_time=isset($_GET['from_time']) ? $_GET['from_time'] : $now;
//$to_time=isset($_GET['to_time']) ? $_GET['to_time'] : $now;

$answered_sec = isset($_GET['answered_sec']) ? (int)$_GET['answered_sec'] : 20;
$short_abandon_sec = isset($_GET['short_abandon_sec']) ? (int)$_GET['short_abandon_sec'] : 10;

######### declaration of array #########
$report_data = [];

######## funtion for format time #######
function timeFormat($time)
{
    $time = (int)round($time);
    $hrs = floor($time / 3600);
    $mins = floor(($time % 3600) / 60);
    $secs = $time % 60;

    return sprintf("%02d:%02d:%02d", $hrs, $mins, $secs);
}

$start = new DateTime($from_date);
$end = new DateTime($to_date);
$end->modify('+1 day');

$date_range = new DatePeriod($start, new DateInterval('P1D'), $end);
 
if (isset($_GET['SUBMIT'])) {
    foreach ($date_range as $date) {

        $current_date = $date->format('Y-m-d');

        for ($hour = 0; $hour < 24; $hour++) {
            $hour_start = sprintf('%s %02d:00:00', $current_date, $hour);
            $hour_end = sprintf('%s %02d:59:59', $current_date, $hour);

            if ($hour == 23) {
                $hour_label = sprintf('%02d:00:00-23:59:59', $hour);
            } else {
                $hour_label = sprintf('%02d:00:00-%02d:00:00', $hour, $hour + 1);
            }

            ######### total offered calls ######################
            $offered_query = "SELECT COUNT(*) AS total_calls FROM vicidial_closer_log WHERE call_date BETWEEN '$hour_start' AND '$hour_end' AND campaign_id LIKE '%JETOUR_%' ";
            $offered_result = $conn->query($offered_query);
            $total_calls = $offered_result ? $offered_result->fetch_assoc()['total_calls'] : 0;

            ######### total answered calls ####################
            $answered_query = "SELECT COUNT(*) AS answered_calls FROM vicidial_closer_log WHERE call_date BETWEEN '$hour_start' AND '$hour_end' AND campaign_id LIKE '%JETOUR_%' AND status NOT IN('DROP','TIMEOT') ";
            $answered_result = $conn->query($answered_query);
            $answered_calls = $answered_result ? $answered_result->fetch_assoc()['answered_calls'] : 0;

            ######### answer within (SLA) 30s #################
            $answered_sla_query = "SELECT COUNT(*) AS answered_sla_calls FROM vicidial_closer_log WHERE call_date BETWEEN '$hour_start' AND '$hour_end' AND campaign_id LIKE '%JETOUR_%' AND queue_seconds<=$answered_sec AND status NOT IN('DROP','TIMEOT') ";
            $answered_sla_result = $conn->query($answered_sla_query);
            $answered_sla_calls = $answered_sla_result ? $answered_sla_result->fetch_assoc()['answered_sla_calls'] : 0;

            ######### total abandon calls ###################
            $abandon_query = "SELECT COUNT(*) AS abandon_calls FROM vicidial_closer_log WHERE call_date BETWEEN '$hour_start' AND '$hour_end' AND campaign_id LIKE '%JETOUR_%' AND status ='DROP' ";
            $abandon_result = $conn->query($abandon_query);
            $abandon_calls = $abandon_result ? $abandon_result->fetch_assoc()['abandon_calls'] : 0;

            ######## total missed calls #####################
            $missed_call_query = "SELECT COUNT(*) AS missed_calls FROM vicidial_closer_log WHERE call_date BETWEEN '$hour_start' AND '$hour_end' AND campaign_id LIKE '%JETOUR_%' AND status='TIMEOT' ";
            $missed_call_result = $conn->query($missed_call_query);
            $missed_calls = $missed_call_result ? $missed_call_result->fetch_assoc()['missed_calls'] : 0;

            ####### total short abandon calls ##############
            $short_abandon_query = "SELECT COUNT(*) AS short_abandon_calls FROM vicidial_closer_log WHERE call_date BETWEEN '$hour_start' AND '$hour_end' AND campaign_id LIKE '%JETOUR_%' AND status IN('DROP','TIMEOT') AND queue_seconds<=$short_abandon_sec";
            $short_abandon_result = $conn->query($short_abandon_query);
            $short_abandon_calls = $short_abandon_result ? $short_abandon_result->fetch_assoc()['short_abandon_calls'] : 0;

            ####### avg wait time ##########################
            $avg_wait_query = "SELECT SUM(queue_seconds) AS total_wait_time,COUNT(*) AS total_wait_calls FROM vicidial_closer_log WHERE call_date BETWEEN '$hour_start' AND '$hour_end' AND campaign_id LIKE '%JETOUR_%' AND status NOT IN('DROP','TIMEOT') ";
            $avg_wait_result = $conn->query($avg_wait_query);
            $avg_wait_row = $avg_wait_result ? $avg_wait_result->fetch_assoc() : ['total_wait_time' => 0, 'total_wait_calls' => 0];

            $wait_sum = $avg_wait_row['total_wait_time'] ? $avg_wait_row['total_wait_time'] : 0;
            $wait_count = $avg_wait_row['total_wait_calls'] ? $avg_wait_row['total_wait_calls'] : 0;

            $avg_wait_time = $wait_count > 0 ? $wait_sum / $wait_count : 0;

            ########## max wait time ######################
            $max_wait_query = "SELECT MAX(queue_seconds) AS max_wait_time FROM vicidial_closer_log WHERE call_date BETWEEN '$hour_start' AND '$hour_end' AND campaign_id LIKE '%JETOUR_%' AND status NOT IN('DROP','TIMEOT') ";
            $max_wait_result = $conn->query($max_wait_query);
            $max_wait_time = $max_wait_result ? $max_wait_result->fetch_assoc()['max_wait_time'] : 0;

            ######### avg talk time #######################
            $avg_talk_query = "SELECT SUM(length_in_sec) AS total_talk_time,COUNT(*) AS total_talk_calls FROM vicidial_closer_log WHERE call_date BETWEEN '$hour_start' AND '$hour_end' AND campaign_id LIKE '%JETOUR_%' AND status NOT IN('DROP','TIMEOT') ";
            $avg_talk_result = $conn->query($avg_talk_query);

            $avg_talk_row = $avg_talk_result ? $avg_talk_result->fetch_assoc() : ['total_talk_time' => 0, 'total_talk_calls' => 0];

            $total_talk_sum = $avg_talk_row['total_talk_time'] ? $avg_talk_row['total_talk_time'] : 0;
            $total_talk_count = $avg_talk_row['total_talk_calls'] ? $avg_talk_row['total_talk_calls'] : 0;

            $avg_talk_time = $total_talk_count > 0 ? $total_talk_sum / $total_talk_count : 0;

            ######### avg hold time #######################
            $avg_hold_query = "SELECT SUM(pl.parked_sec) AS total_hold_time,COUNT(*) AS total_hold_calls FROM park_log pl JOIN vicidial_closer_log vcl ON vcl.uniqueid=pl.uniqueid WHERE pl.parked_time BETWEEN '$hour_start' AND '$hour_end' AND vcl.campaign_id LIKE '%JETOUR_%' ";
            // $avg_hold_query="SELECT SUM(parked_sec) AS total_hold_time,COUNT(*) AS total_hold_calls FROM park_log WHERE parked_time BETWEEN '$hour_start' AND '$hour_end' AND channel_group LIKE '%JETOUR%' ";

            $avg_hold_result = $conn->query($avg_hold_query);
            $avg_hold_row = $avg_hold_result ? $avg_hold_result->fetch_assoc() : ['total_hold_time' => 0, 'total_hold_calls' => 0];

            $total_hold_sum = $avg_hold_row['total_hold_time'] ? $avg_hold_row['total_hold_time'] : 0;
            $total_hold_count = $avg_hold_row['total_hold_calls'] ? $avg_hold_row['total_hold_calls'] : 0;

            $avg_hold_time = $total_hold_count > 0 ? $total_hold_sum / $total_hold_count : 0;

            ####### avg acw time(talk+hold+dispo+dead) ######
            $agent_time_query = "SELECT SUM(dispo_sec) AS total_dispo_time,SUM(dead_sec) AS total_dead_time FROM vicidial_agent_log WHERE event_time BETWEEN '$hour_start' AND '$hour_end' AND campaign_id LIKE '%JETOUR%' AND comments='INBOUND' ";

            $agent_time_result = $conn->query($agent_time_query);
            $agent_time_row = $agent_time_result ? $agent_time_result->fetch_assoc() : ['total_dispo_time' => 0, 'total_dead_time' => 0];

            $total_dispo_sum = $agent_time_row['total_dispo_time'] ? $agent_time_row['total_dispo_time'] : 0;
            $total_dead_sum = $agent_time_row['total_dead_time'] ? $agent_time_row['total_dead_time'] : 0;

            if ($answered_calls > 0) {
                $avg_handle_time = ($total_talk_sum + $total_hold_sum + $total_dispo_sum + $total_dead_sum) / $answered_calls;
            } else {
                $avg_handle_time = 0;
            }

            ######### SLA % ###########################
            if ($total_calls > 0) {
                $sla_percent = ($answered_sla_calls * 100) / $total_calls;
            } else {
                $sla_percent = 0;
            }

            $report_data[] = [
                'date' => $current_date,
                'hour_range' => $hour_label,
                'total_calls' => $total_calls,
                'answered_calls' => $answered_calls,
                'answered_sla_calls' => $answered_sla_calls,
                'abandon_calls' => $abandon_calls,
                'missed_calls' => $missed_calls,
                'short_abandon_calls' => $short_abandon_calls,
                'avg_wait_time' => timeFormat($avg_wait_time),
                'max_wait_time' => timeFormat($max_wait_time),
                'avg_talk_time' => timeFormat($avg_talk_time),
                'avg_hold_time' => timeFormat($avg_hold_time),
                'avg_handle_time' => timeFormat($avg_handle_time),
                'sla_percent' => number_format($sla_percent, 2)
            ];
        }
    }
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
                    <div class="d-flex align-items-center justify-content-end gap-2 mb-2">
                        <label for="from_date" class="mb-0">From Date:</label>
                        <input type="date" name="from_date" id="from_date" value="<?php echo $from_date; ?>" class="form-control form-control-sm" style="width: 140px;">
                    </div>
                    <div class="d-flex align-items-center justify-content-end gap-2 ">
                        <label for="to_date" class="mb-0">To Date:</label>
                        <input type="date" name="to_date" value="<?php echo $to_date; ?>" class="form-control form-control-sm" style="width: 140px;">
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="d-flex align-items-center justify-content-end gap-2 mb-2">
                        <label for="answered_seconds" class="mb-0">Answered Seconds:</label>
                        <input type="text" name="answered_sec" value="<?php echo $answered_sec; ?>" class="form-control form-control-sm" style="width: 40px;">
                    </div>
                    <div class="d-flex align-items-center justify-content-end gap-2">
                        <label for="short_abandon_seconds" class="mb-0">Short Abandon Seconds:</label>
                        <input type="text" name="short_abandon_sec" value="<?php echo $short_abandon_sec; ?>" class="form-control form-control-sm" style="width: 40px;">
                    </div>
                </div>

                <div class="col-lg-4 d-flex justify-content-end">
                    <div class="button-group d-flex gap-2">
                        <a href="/ocdial/admin.php?ADD=999999" class="btn btn-secondary btn-sm">Back</a>
                        <input type="submit" name="SUBMIT" value="SUBMIT" class="btn btn-primary btn-sm">
                        <input type="button" value="download CSV" onclick="downloadCSV()" class="btn btn-success btn-sm">
                    </div>
                </div>

            </div>
        </form>
        <?php
        if (isset($_GET['SUBMIT'])) { ?>
            <?php if (empty($report_data)) { ?>
                <div class="text-center mt-3">No data available for the selected date Range.</div>
            <?php } else { ?>
                <table>
                    <tr>
                        <th>Breakdown by Start Date</th>
                        <th>Hour of the day</th>
                        <th>Total Call Offered</th>
                        <th>Total Call Answered</th>
                        <th>Total Call Answered (85% within <?php echo $answered_sec; ?> sec)</th>
                        <th>Total Abandon</th>
                        <th>Total Missed Call</th>
                        <th>Total Short Abandon</th>
                        <th>AVG Wait Time</th>
                        <th>Max Wait Time</th>
                        <th>AVG Talk Time</th>
                        <th>AVG Hold Time</th>
                        <th>AVG Handling Time</th>
                        <th>Service Level within SLA <?php echo $answered_sec; ?> Sec(%
</th>
                    </tr>

                    <?php foreach ($report_data as $data) { ?>
                        <tr>
                            <td><?php echo $data['date'] ?></td>
                            <td><?php echo $data['hour_range']; ?></td>
                            <td><?php echo $data['total_calls']; ?></td>
                            <td><?php echo $data['answered_calls']; ?></td>
                            <td><?php echo $data['answered_sla_calls']; ?></td>
                            <td><?php echo $data['abandon_calls']; ?></td>
                            <td><?php echo $data['missed_calls']; ?></td>
                            <td><?php echo $data['short_abandon_calls']; ?></td>
                            <td><?php echo $data['avg_wait_time']; ?></td>
                            <td><?php echo $data['max_wait_time']; ?></td>
                            <td><?php echo $data['avg_talk_time']; ?></td>
                            <td><?php echo $data['avg_hold_time']; ?></td>
                            <td><?php echo $data['avg_handle_time']; ?></td>
                            <td><?php echo $data['sla_percent']; ?>%</td>
                        </tr>
                    <?php } ?>
                </table>

        <?php }
        }
        ?>
    </div>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 15px;
            margin-top: 20px;
        }

        td,
        th {
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

            var blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            var a = document.createElement('a');
            a.style.display = 'none';
            a.href = URL.createObjectURL(blob);
            a.download = 'JeTour_Hourly_Interval_Report.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    </script>
</body>

</html>