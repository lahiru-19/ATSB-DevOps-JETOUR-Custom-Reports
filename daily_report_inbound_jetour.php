<?php
######### initialize DB ##########
include 'db_connect.php';

####### title ######
$title = 'Daily Report (Inbound) - JeTour';

###### set date ans time #####
$now = date("Y-m-d");

##### Get the form data from the form ####
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : $now;
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : $now;
$from_time = isset($_GET['from_time']) ? $_GET['from_time'] : '00:00:00';
$to_time = isset($_GET['to_time']) ? $_GET['to_time'] : '23:59:59';

$short_abandon_sec = isset($_GET['short_abandon_sec']) ? (int)$_GET['short_abandon_sec'] : 10;
$answered_sec = isset($_GET['answered_sec']) ? (int)$_GET['answered_sec'] : 20;

#### declaration of array ##########
$report_data = [];

### initialize totals ##########
$Total_calls = 0;
$Total_answered_calls = 0;
$Total_answered_interval_calls = 0;
$Total_abandon_calls = 0;
$Total_missed_calls = 0;
$Total_short_abandon_calls = 0;

$Total_wait_sum = 0;
$Total_wait_count = 0;
$Total_max_wait_time = 0;

$Total_talk_sum = 0;
$Total_talk_count = 0;

$Total_hold_sum = 0;
$Total_hold_count = 0;

$Total_dispo_time = 0;
$Total_dead_time = 0;

$Tot_sla_value = 0;
$Tot_abandon_percent = 0;
$Tot_missed_call_percent = 0;
$Tot_short_abandon_percent = 0;

$formatted_Tot_avg_wait_time = '00:00:00';
$formatted_Total_max_wait_time = '00:00:00';
$formatted_Total_avg_talk_time = '00:00:00';
$formatted_Total_avg_hold_time = '00:00:00';
$formatted_Tot_aht_time = '00:00:00';
$formatted_Total_avg_acw_time = '00:00:00';

###### define funtion for time format #######
function timeFormat($time)
{
    $time=(int)$time;
    $hrs = floor($time / 3600);
    $mins = floor(($time % 3600) / 60);
    $secs = $time % 60;
    return sprintf("%02d:%02d:%02d", $hrs, $mins, $secs);
}


##### generate a date range ########
$start = new DateTime($from_date);
$end = new DateTime($to_date);
$end = $end->modify('+1 day');

$date_range = new DatePeriod($start, new DateInterval('P1D'), $end);

########### only build report when submit ####
if (isset($_GET['SUBMIT'])) {
####### loop through each date in the range ######
foreach ($date_range as $date) {
    $current_date = $date->format("Y-m-d");

    $day_start = $current_date . ' 00:00:00';
    $day_end = $current_date . ' 23:59:59';

    if ($current_date == $from_date) {
        $day_start = $current_date . ' ' . $from_time;
    }

    if ($current_date == $to_date) {
        $day_end = $current_date . ' ' . $to_time;
    }

    ########## daily total calls ##################
    $daily_calls_query = "SELECT COUNT(*) AS total_calls FROM vicidial_closer_log WHERE call_date BETWEEN '$day_start' AND '$day_end' AND campaign_id LIKE '%JETOUR_%'";
    $daily_calls_result = $conn->query($daily_calls_query);
    $daily_calls = $daily_calls_result ? $daily_calls_result->fetch_assoc()['total_calls'] : 0;

    $Total_calls += $daily_calls;

    ############ daily answered calls ##############
    $daily_answered_query = "SELECT COUNT(*) AS answered_calls FROM vicidial_closer_log WHERE call_date BETWEEN '$day_start' AND '$day_end' AND campaign_id LIKE '%JETOUR_%' AND status NOT IN('DROP','TIMEOT')";
    $daily_answered_result = $conn->query($daily_answered_query);
    $daily_answered_calls = $daily_answered_result ? $daily_answered_result->fetch_assoc()['answered_calls'] : 0;

    $Total_answered_calls += $daily_answered_calls;

    ####### daily calls answered within 20sec ####
    $daily_answered_interval_query = "SELECT COUNT(*) AS answered_interval_calls FROM vicidial_closer_log WHERE call_date BETWEEN '$day_start' AND '$day_end' AND campaign_id LIKE '%JETOUR_%' AND queue_seconds <= $answered_sec AND status NOT IN('DROP','TIMEOT')";
    $daily_answered_interval_result = $conn->query($daily_answered_interval_query);
    $daily_answered_interval_calls = $daily_answered_interval_result ? $daily_answered_interval_result->fetch_assoc()['answered_interval_calls'] : 0;

    $Total_answered_interval_calls += $daily_answered_interval_calls;

    ########### service level% ###########
    if ($daily_calls > 0) {
        $sla_value = $daily_answered_interval_calls * 100 / $daily_calls;
    } else {
        $sla_value = 0;
    }

    $Tot_sla_value = $Total_calls > 0 ? $Total_answered_interval_calls * 100 / $Total_calls : 0;


    ##### daily abandon calls ############
    $daily_abandon_query = "SELECT COUNT(*) AS abandon_calls FROM vicidial_closer_log WHERE call_date BETWEEN '$day_start' AND '$day_end' AND campaign_id LIKE '%JETOUR_%' AND status='DROP'";
    $daily_abandon_result = $conn->query($daily_abandon_query);
    $daily_abandon_calls = $daily_abandon_result ? $daily_abandon_result->fetch_assoc()['abandon_calls'] : 0;

    $Total_abandon_calls += $daily_abandon_calls;


    ########## Abandon % #################
    if ($daily_calls > 0) {
        $abandon_percent = $daily_abandon_calls * 100 / $daily_calls;
    } else {
        $abandon_percent = 0;
    }

    $Tot_abandon_percent = $Total_calls > 0 ? $Total_abandon_calls * 100 / $Total_calls : 0;


    ###### daily missed calls ############
    $daily_missed_calls_query = "SELECT COUNT(*) AS missed_calls FROM vicidial_closer_log WHERE call_date BETWEEN '$day_start' AND '$day_end' AND campaign_id LIKE '%JETOUR_%' AND status='TIMEOT'";
    $daily_missed_calls_result = $conn->query($daily_missed_calls_query);
    $daily_missed_calls = $daily_missed_calls_result ? $daily_missed_calls_result->fetch_assoc()['missed_calls'] : 0;

    $Total_missed_calls += $daily_missed_calls;

    ########## missed calls% #############
    if ($daily_calls > 0) {
        $missed_call_percent = $daily_missed_calls * 100 / $daily_calls;
    } else {
        $missed_call_percent = 0;
    }

    $Tot_missed_call_percent = $Total_calls > 0 ? $Total_missed_calls * 100 / $Total_calls : 0;

    ####### daily calls answered within short abandon sec ####
    $daily_short_abandon_query = "SELECT COUNT(*) AS short_abandon_calls FROM vicidial_closer_log WHERE call_date BETWEEN '$day_start' AND '$day_end' AND campaign_id LIKE '%JETOUR_%' AND status IN('DROP','TIMEOT') AND queue_seconds <= $short_abandon_sec";
    $daily_short_abandon_result = $conn->query($daily_short_abandon_query);
    $daily_short_abandon_calls = $daily_short_abandon_result ? $daily_short_abandon_result->fetch_assoc()['short_abandon_calls'] : 0;

    $Total_short_abandon_calls += $daily_short_abandon_calls;

    ######## short abandon % ################
    if ($daily_calls > 0) {
        $short_abandon_percent = $daily_short_abandon_calls * 100 / $daily_calls;
    } else {
        $short_abandon_percent = 0;
    }

    $Tot_short_abandon_percent = $Total_calls > 0 ? $Total_short_abandon_calls * 100 / $Total_calls : 0;

    ######## daily avg wait time ############
    $daily_avg_wait_time_query = "SELECT SUM(queue_seconds) AS total_wait_time, COUNT(*) AS total_wait_calls FROM vicidial_closer_log WHERE call_date BETWEEN '$day_start' AND '$day_end' AND campaign_id LIKE '%JETOUR_%' AND status NOT IN('DROP','TIMEOT')";

    $daily_avg_wait_time_result = $conn->query($daily_avg_wait_time_query);
    $wait_row = $daily_avg_wait_time_result ? $daily_avg_wait_time_result->fetch_assoc() : ['total_wait_time' => 0, 'total_wait_calls' => 0];

    $wait_sum = $wait_row['total_wait_time'] ? $wait_row['total_wait_time'] : 0;
    $wait_count = $wait_row['total_wait_calls'] ? $wait_row['total_wait_calls'] : 0;
    $avg_wait_time = $wait_count > 0 ? $wait_sum / $wait_count : 0;

    $Total_wait_sum += $wait_sum;
    $Total_wait_count += $wait_count;

    $Tot_avg_wait_time = $Total_wait_count > 0 ? $Total_wait_sum / $Total_wait_count : 0;

    $formatted_avg_wait_time = timeFormat($avg_wait_time);
    $formatted_Tot_avg_wait_time = timeFormat($Tot_avg_wait_time);



    ########## Max wait time ####################
    $max_wait_time_query = "SELECT MAX(queue_seconds) AS max_wait_time FROM vicidial_closer_log WHERE call_date BETWEEN '$day_start' AND '$day_end' AND campaign_id LIKE '%JETOUR_%'";

    $max_wait_time_result = $conn->query($max_wait_time_query);
    $max_wait_time = $max_wait_time_result ? $max_wait_time_result->fetch_assoc()['max_wait_time'] : 0;

    if ($max_wait_time > $Total_max_wait_time) {
        $Total_max_wait_time = $max_wait_time;
    }

    $formatted_max_wait_time = timeFormat($max_wait_time);
    $formatted_Total_max_wait_time = timeFormat($Total_max_wait_time);

    ######### Avg talk time ##################
    $avg_talk_time_query = "SELECT SUM(length_in_sec) AS total_talk_time, COUNT(*) AS total_talk_calls FROM vicidial_closer_log WHERE call_date BETWEEN '$day_start' AND '$day_end' AND campaign_id LIKE '%JETOUR_%' AND status NOT IN('DROP','TIMEOT')";

    $avg_talk_time_result = $conn->query($avg_talk_time_query);
    $talk_row = $avg_talk_time_result ? $avg_talk_time_result->fetch_assoc() : ['total_talk_time' => 0, 'total_talk_calls' => 0];

    $talk_sum = $talk_row['total_talk_time'] ? $talk_row['total_talk_time'] : 0;
    $talk_count = $talk_row['total_talk_calls'] ? $talk_row['total_talk_calls'] : 0;
    $avg_talk_time = $talk_count > 0 ? $talk_sum / $talk_count : 0;

    $Total_talk_sum += $talk_sum;
    $Total_talk_count += $talk_count;

    $Tot_avg_talk_time = $Total_talk_count > 0 ? $Total_talk_sum / $Total_talk_count : 0;

    $formatted_avg_talk_time = timeFormat($avg_talk_time);
    $formatted_Total_avg_talk_time = timeFormat($Tot_avg_talk_time);

    ######### Avg hold time ##############
    $avg_hold_time_query = "SELECT SUM(parked_sec) AS total_hold_time, COUNT(*) AS total_hold_calls FROM park_log WHERE parked_time BETWEEN '$day_start' AND '$day_end' AND channel_group LIKE '%JETOUR%'";

    $avg_hold_time_result = $conn->query($avg_hold_time_query);
    $hold_row = $avg_hold_time_result ? $avg_hold_time_result->fetch_assoc() : ['total_hold_time' => 0, 'total_hold_calls' => 0];

    $hold_sum = $hold_row['total_hold_time'] ? $hold_row['total_hold_time'] : 0;
    $hold_count = $hold_row['total_hold_calls'] ? $hold_row['total_hold_calls'] : 0;
    $avg_hold_time = $hold_count > 0 ? $hold_sum / $hold_count : 0;

    $Total_hold_sum += $hold_sum;
    $Total_hold_count += $hold_count;

    $Tot_avg_hold_time = $Total_hold_count > 0 ? $Total_hold_sum / $Total_hold_count : 0;

    $formatted_avg_hold_time = timeFormat($avg_hold_time);
    $formatted_Total_avg_hold_time = timeFormat($Tot_avg_hold_time);

    ######## Avg handle time(talk+hold+dispo+dead) #############
    $agent_time_query = "SELECT SUM(dispo_sec) AS total_dispo_time, SUM(dead_sec) AS total_dead_time FROM vicidial_agent_log WHERE event_time BETWEEN '$day_start' AND '$day_end' AND comments='INBOUND' AND campaign_id LIKE '%JETOUR%'";

    $agent_time_result = $conn->query($agent_time_query);
    $agent_row = $agent_time_result ? $agent_time_result->fetch_assoc() : ['total_dispo_time' => 0, 'total_dead_time' => 0];

    $dispo_time = $agent_row['total_dispo_time'] ? $agent_row['total_dispo_time'] : 0;
    $dead_time = $agent_row['total_dead_time'] ? $agent_row['total_dead_time'] : 0;

    if ($daily_answered_calls > 0) {
        $aht_time = ($talk_sum + $hold_sum + $dispo_time + $dead_time) / $daily_answered_calls;
    } else {
        $aht_time = 0;
    }

    $Total_dispo_time += $dispo_time;
    $Total_dead_time += $dead_time;

    $Total_aht = $Total_answered_calls > 0 ? ($Total_talk_sum + $Total_hold_sum + $Total_dispo_time + $Total_dead_time) / $Total_answered_calls : 0;

    $formatted_aht_time = timeFormat($aht_time);
    $formatted_Tot_aht_time = timeFormat($Total_aht);

    ########### Avg ACW time ###############
    if ($daily_answered_calls > 0) {
        $avg_acw_time = ($dispo_time + $dead_time) / $daily_answered_calls;
    } else {
        $avg_acw_time = 0;
    }

    $Total_avg_acw_time = $Total_answered_calls > 0 ? ($Total_dispo_time + $Total_dead_time) / $Total_answered_calls : 0;

    $formatted_avg_acw_time = timeFormat($avg_acw_time);
    $formatted_Total_avg_acw_time = timeFormat($Total_avg_acw_time);

    ########## add data to array ##################
    $report_data[]=[
            'date'=>$current_date,
            'calls'=>$daily_calls,
            'answered_calls'=>$daily_answered_calls,
            'answered_interval_calls'=>$daily_answered_interval_calls,
            'service_level_percent'=>$sla_value,
            'abandon_calls'=>$daily_abandon_calls,
            'abandon_percent'=>$abandon_percent,
            'missed_calls'=>$daily_missed_calls,
            'missed_calls_percent'=>$missed_call_percent,
            'short_abandon'=>$daily_short_abandon_calls,
            'short_abandon_percent'=>$short_abandon_percent,
            'avg_wait_time'=>$formatted_avg_wait_time,
            'max_wait_time'=>$formatted_max_wait_time,
            'avg_talk_time'=>$formatted_avg_talk_time,
            'avg_hold_time'=>$formatted_avg_hold_time,
            'avg_handle_time'=>$formatted_aht_time,
            'avg_acw_time'=>$formatted_avg_acw_time
        ];
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
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="get" name="vicidial_report" id="vicidial_report">
            <div class="row form-section">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center justify-content-end gap-2 mb-2">
                        <label for="from_date" class="mb-0">From Date:</label>
                        <input type="date" name="from_date" id="from_date" value="<?php echo $from_date; ?>" class="form-control form-control-sm" style="width: 140px;">
                        <input type="text" name="from_time" id="from_time" value="<?php echo $from_time; ?>" class="form-control form-control-sm" style="width: 90px;">
                    </div>
                    <div class="d-flex align-items-center justify-content-end gap-2">
                        <label for="to_date" class="mb-0">To Date:</label>
                        <input type="date" name="to_date" id="to_date" value="<?php echo $to_date; ?>" class="form-control form-control-sm" style="width: 140px;">
                        <input type="text" name="to_time" id="to_time" value="<?php echo $to_time; ?>" class="form-control form-control-sm" style="width: 90px;">
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="d-flex align-items-center justify-content-end gap-2 mb-2">
                        <label for="answered_sec" class="mb-0">Answered Seconds:</label>
                        <input type="text" name="answered_sec" id="answered_sec" value="<?php echo $answered_sec; ?>" class="form-control form-control-sm" style="width: 40px;">
                    </div>
                    <div class="d-flex align-items-center justify-content-end gap-2">
                        <label for="short_abandon_sec" class="mb-0">Short Abandon Seconds:</label>
                        <input type="text" name="short_abandon_sec" id="short_abandon_sec" value="<?php echo $short_abandon_sec; ?>" class="form-control form-control-sm" style="width: 40px;">
                    </div>
                </div>

                <div class="col-lg-4 d-flex justify-content-end">
                    <div class="button-group d-flex gap-2">
                        <a href="/ocdial/admin.php?ADD=999999" class="btn btn-secondary btn-sm">Back</a>
                        <input type="submit" value="SUBMIT" name="SUBMIT" id="SUBMIT" class="btn btn-primary btn-sm">
                        <input type="button" value="download CSV" onclick="downloadCSV()" class="btn btn-success btn-sm">
                    </div>
                </div>

            </div>

        </form>
        <?php
        if (isset($_GET['SUBMIT'])) {
            if (empty($report_data)) { ?>
                <div style="text-align: center; margin-top:20px; color: black;">
                    No data available for the selected date range.
                </div>
            <?php
            } else { ?>
                <table>
                    <tr>
                        <th>Break down by Date</th>
                        <th>Total Call Offered</th>
                        <th>Total Call Answered</th>
                        <th>Total Call Answered (85% within <?php echo $answered_sec; ?> sec)</th>
                        <th>Service Level %</th>
                        <th>Total Abandon</th>
                        <th>Abandon %</th>
                        <th>Total Missed Call</th>
                        <th>Missed Call %</th>
                        <th>Total Short Abandon (<?php echo $short_abandon_sec; ?> Sec)</th>
                        <th>Short Abandon %</th>
                        <th>AVG Wait Time</th>
                        <th>Max Wait Time</th>
                        <th>AVG Talk Time</th>
                        <th>AVG Hold Time</th>
                        <th>AVG Handling Time</th>
                        <th>AVG ACW Time</th>
                    </tr>
                    <?php
                    foreach ($report_data as $data) { ?>

                        <tr>
                            <td><?php echo $data['date']; ?></td>
                            <td><?php echo $data['calls']; ?></td>
                            <td><?php echo $data['answered_calls']; ?></td>
                            <td><?php echo $data['answered_interval_calls']; ?></td>
                            <td><?php echo number_format($data['service_level_percent'], 2); ?>%</td>
                            <td><?php echo $data['abandon_calls']; ?></td>
                            <td><?php echo number_format($data['abandon_percent'], 2); ?>%</td>
                            <td><?php echo $data['missed_calls']; ?></td>
                            <td><?php echo number_format($data['missed_calls_percent'], 2); ?>%</td>
                            <td><?php echo $data['short_abandon']; ?></td>
                            <td><?php echo number_format($data['short_abandon_percent'], 2); ?>%</td>
                            <td><?php echo $data['avg_wait_time']; ?></td>
                            <td><?php echo $data['max_wait_time']; ?></td>
                            <td><?php echo $data['avg_talk_time']; ?></td>
                            <td><?php echo $data['avg_hold_time']; ?></td>
                            <td><?php echo $data['avg_handle_time']; ?></td>
                            <td><?php echo $data['avg_acw_time']; ?></td>
                        </tr>

                    <?php } ?>
                    <tr style="background-color: #fab4af; font-weight: bold;">
                        <td>Total:</td>
                        <td><?php echo $Total_calls; ?></td>
                        <td><?php echo $Total_answered_calls; ?></td>
                        <td><?php echo $Total_answered_interval_calls; ?></td>
                        <td><?php echo number_format($Tot_sla_value, 2); ?>%</td>
                        <td><?php echo $Total_abandon_calls; ?></td>
                        <td><?php echo number_format($Tot_abandon_percent, 2); ?>%</td>
                        <td><?php echo $Total_missed_calls; ?></td>
                        <td><?php echo number_format($Tot_missed_call_percent, 2); ?>%</td>
                        <td><?php echo $Total_short_abandon_calls; ?></td>
                        <td><?php echo number_format($Tot_short_abandon_percent, 2); ?>%</td>
                        <td><?php echo $formatted_Tot_avg_wait_time; ?></td>
                        <td><?php echo $formatted_Total_max_wait_time; ?></td>
                        <td><?php echo $formatted_Total_avg_talk_time; ?></td>
                        <td><?php echo $formatted_Total_avg_hold_time; ?></td>
                        <td><?php echo $formatted_Tot_aht_time; ?></td>
                        <td><?php echo $formatted_Total_avg_acw_time; ?></td>
                    </tr>
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
            a.download = 'JeTour_Daily_Report_Inbound.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    </script>

</body>

</html>