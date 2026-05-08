<?php
include 'db_connect.php';

$title="Agent Report-Inbound Jetour";

####### set time ##########################
$now=date("Y-m-d");
$start_time="00:00:00";
$end_time="23:59:59";
$start_date="";
$end_date="";

############# Get list of user Groups #####################
$user_group_query="SELECT user_group FROM vicidial_user_groups WHERE user_group LIKE '%JETOUR_AGENTS%' OR user_group LIKE '%JETOUR_ADMINS%' ORDER BY user_group;";
$user_group_result=$conn->query($user_group_query);

############ Get list of Inbound Groups ######################
$inbound_group_query="SELECT group_id FROM vicidial_inbound_groups WHERE group_id LIKE '%JETOUR%' ORDER BY answer_sec_pct_rt_stat_one desc; ";
$inbound_group_result=$conn->query($inbound_group_query);

################ set time with HH:MM:SS format ##############
function time_Format($time){
    $hours=floor($time/3600);
    $minutes=floor(($time%3600)/60);
    $seconds=$time%60;
    //$formatted=sprintf("%02d:%02d:%02d",$hours,$minutes,$seconds);
    return sprintf("%02d:%02d:%02d",$hours,$minutes,$seconds);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <title><?php echo $title?></title>
    
</head>
<body>
    <!--<h2 style="text-align:center;"><?php echo $title?></h2>-->
    <h5 class="text-center mt-3 mb-3"><?php echo $title?></h5>
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>" method="get" name="vicidial_report" id="vicidial_report">
    
<div class="container-fluid mt-3 mb-3 px-4">
    <div class="row align-items-start">

        <!-- Left side : Times -->
        <div class="col-md-4">
            <div class="d-flex flex-column gap-1">

                <div class="row align-items-center mb-1">
                    <label class="col-md-4 col-form-label">From Date</label>
                    <div class="col-md-6">
                        <input type="date" id="query_date" name="query_date"
                               value="<?php echo $query_date; ?>" class="form-control-sm">
                    </div>
                </div>

                <div class="row align-items-center mb-1">
                    <label class="col-md-4 col-form-label">From Time</label>
                    <div class="col-md-6">
                        <input type="text" id="query_time" name="query_time"
                               value="<?php echo $start_time; ?>" class="form-control-sm">
                    </div>
                </div>

                <div class="row align-items-center mb-1">
                    <label class="col-md-4 col-form-label">To Date</label>
                    <div class="col-md-6">
                        <input type="date" id="end_date" name="end_date"
                               value="<?php echo $end_date; ?>" class="form-control-sm">
                    </div>
                </div>

                <div class="row align-items-center mb-1">
                    <label class="col-md-4 col-form-label">To Time</label>
                    <div class="col-md-6">
                        <input type="text" id="end_time" name="end_time"
                               value="<?php echo $end_time; ?>" class="form-control-sm">
                    </div>
                </div>

            </div>
        </div>

        <!-- middle : group boxes -->
        <div class="col-md-4">
            <div class="d-flex flex-column gap-3">

                <div class="row align-items-center">
                    <label class="col-4 col-form-label">Campaign</label>
                    <div class="col-8">
                        <select size="3" name="group[]" class="form-select" multiple>
                            <option selected value="Jetour Contact Centre">Jetour Contact Centre</option>
                        </select>
                    </div>
                </div>

                <div class="row align-items-center">
                    <label class="col-4 col-form-label">User Group</label>
                    <div class="col-8">
                        <select size="3" id="user_group" name="user_group[]" class="form-select" multiple>
                            <?php
                            if($user_group_result->num_rows > 0){
                                while($row = $user_group_result->fetch_assoc()){
                                    $user_group_name = $row['user_group'];
                                    echo "<option selected value='$user_group_name'>$user_group_name</option>";
                                }
                            }else{
                                echo "<option value=''>No Groups found</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="row align-items-center">
                    <label class="col-4 col-form-label">Inbound Group</label>
                    <div class="col-8">
                        <select size="3" id="inbound_group" name="inbound_group[]" class="form-select" multiple>
                            <?php
                            if($inbound_group_result->num_rows > 0){
                                while($row = $inbound_group_result->fetch_assoc()){
                                    $inbound_group_name = $row['group_id'];
                                    echo "<option selected value='$inbound_group_name'>$inbound_group_name</option>";
                                }
                            }else{
                                echo "<option value=''>No Groups found</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

            </div>
        </div>
        <!-- right side boxes -->
        <div class="col-md-4 d-flex justify-content-end">
            <div class="d-flex flex-column gap-3">
                <a href="/ocdial/admin.php?ADD=999999" class="btn btn-secondary me-2 "> Back </a>
                <button type="submit" name="SUBMIT" class="btn btn-primary me-2"> Submit</button>
                <button type="button" onclick="DownloadCSV()" class="btn btn-success">Download CSV</button>
            </div>
        </div>

    </div>
</div>
</form>
</body>
</html>

<?php
if(isset($_GET['user_group'])){
    $userGroups=$_GET['user_group'] ?? [];
    $from_date=$_GET['query_date'];
    $end_date=$_GET['end_date'];
    $start_time=$_GET['query_time'];
    $end_time=$_GET['end_time'];

    $from_date_time=$from_date. ' ' .$start_time;
    $to_date_time=$end_date. ' ' .$end_time;

    if(preg_match('/--ALL--/i',implode(',',$userGroups))){
        $sql="SELECT * FROM vicidial_user_groups;";
    }else{
        $quotedItems=array_map(function($group) use($conn){
            return '"'.$conn->real_escape_string($group).'"';
        },$userGroups);
        
        $overall_user='(' .implode(',',$quotedItems). ')';

        $sql="SELECT * FROM vicidial_users u JOIN vicidial_user_groups g ON u.user_group=g.user_group WHERE u.user_group IN('JETOUR_AGENTS','JETOUR_ADMINS') ";
        $result=$conn->query($sql);

        if($result->num_rows>0){
            echo "
            <table border='1'>
             <tr>
                <th>Date</th>
                <th>Agent name</th>
                <th>Agent ID</th>
                <th>Total Calls answered</th>
                <th>Call Answered within SL (20Sec)</th>
                <th>% Call Answered within SL (20Sec)</th>
                <th>Missed Call</th>
                <th>% Missed Call</th>
                <th>AVG Talk Time</th>
                <th>AVG Hold Time</th>
                <th>AVG Handling Time</th>
                <th>AVG ACW Time</th>
                <th>AVG Waiting Time</th>
                <th>AVG Occupancy %</th>
             </tr> ";
            
            foreach($result as $row_user){
                $full_name=$row_user['full_name'];
                $user_name=$row_user['user'];

                ##################### total answered calls ########
                $agent_total_attend_calls_query="SELECT COUNT(*) AS attended_calls FROM vicidial_closer_log WHERE user='".$user_name."' AND call_date BETWEEN '".$from_date_time."' AND '".$to_date_time."' ";
                
                $agent_total_attend_calls_result=$conn->query($agent_total_attend_calls_query);

                if($agent_total_attend_calls_result){
                    $row_attendedcalls=$agent_total_attend_calls_result->fetch_assoc();
                    $attended_calls_value=$row_attendedcalls  ['attended_calls'];
                }

                ######### total call answered with in 20S #########
                $call_answered_within_SL_query="SELECT COUNT(*) AS answered_interval_calls FROM vicidial_closer_log WHERE user='".$user_name."' AND call_date BETWEEN '".$from_date_time."' AND '".$to_date_time."' AND status NOT IN('DROP','TIMEOT') AND queue_seconds<=20 ";
                $call_answered_within_SL_result=$conn->query($call_answered_within_SL_query);

                $call_answered_within_SL_value=0;

                if($call_answered_within_SL_result){
                    $row_SL_value=$call_answered_within_SL_result->fetch_assoc();
                    $call_answered_within_SL_value=(int)$row_SL_value['answered_interval_calls'];
                }

                if($attended_calls_value>0){
                    $SL_percentage=($call_answered_within_SL_value/$attended_calls_value)*100;
                    $formatted_SL_percentage=number_format($SL_percentage,2).'%';
                }else{
                    $formatted_SL_percentage="0.00%";
                }

                ######### Missed calls ######################
                $missed_calls_query="SELECT COUNT(*) AS missed_calls FROM vicidial_closer_log WHERE user='".$user_name."' AND call_date BETWEEN '".$from_date_time."' AND '".$to_date_time."' AND campaign_id LIKE '%JETOUR_%' AND status IN('DROP','XDROP','TIMEOT')";

                $missed_call_result=$conn->query($missed_calls_query);
                if($missed_call_result){
                    $row_missed_call_value=$missed_call_result->fetch_assoc();
                    $missed_calls_value=(int)$row_missed_call_value['missed_calls'];

                }else{
                    $missed_calls_value='0';
                }

                ######### Missed calls % ######################
                if($attended_calls_value+$missed_calls_value>0){
                $missed_call_percentage=($missed_calls_value/($attended_calls_value+$missed_calls_value))*100;
                $formatted_missed_call_percentage=number_format($missed_call_percentage,2).'%';
                }else{
                $formatted_missed_call_percentage='0.00%';   
                }

                ######### AVG talk time ######################
                $agent_avg_talk_time_query="SELECT AVG(length_in_sec) AS avg_talk_time FROM vicidial_closer_log WHERE user='".$user_name."' AND call_date BETWEEN '".$from_date_time."' AND '".$to_date_time."' ";
                $agent_avg_talk_time_result=$conn->query($agent_avg_talk_time_query);

                if($agent_avg_talk_time_result){
                $row_avg_talk_time=$agent_avg_talk_time_result->fetch_assoc();
                $avg_talk_time_value=$row_avg_talk_time['avg_talk_time'];
                $formatted_avg_talk_time=time_Format($avg_talk_time_value);
                }

                ######### AVG Hold time ####################
                $agent_avg_hold_time_query="SELECT AVG(pl.parked_sec) AS avg_hold_time FROM park_log pl JOIN vicidial_closer_log vcl ON vcl.uniqueid=pl.uniqueid WHERE pl.user='".$user_name."' AND pl.parked_time BETWEEN '".$from_date_time."' AND '".$to_date_time."' ";
                $avg_hold_time_result=$conn->query($agent_avg_hold_time_query);

                if($avg_hold_time_result){
                $avg_hold_time_row=$avg_hold_time_result->fetch_assoc();
                $avg_hold_time_value=$avg_hold_time_row['avg_hold_time'];
                $formatted_avg_hold_time=time_Format($avg_hold_time_value);
                }

                ######### AVG Handle time(Talk+Hold+ACW) ############
                $talk_time_query="SELECT COUNT(*) AS total_calls,SUM(length_in_sec) AS total_talk_time FROM vicidial_closer_log WHERE call_date BETWEEN '".$from_date_time."' AND '".$to_date_time."' AND user='".$user_name."' AND status NOT IN('TIMEOT','DROP') ";
                $talk_time_result=$conn->query($talk_time_query);
            
                $total_calls=$talk_time_result?$talk_time_result->fetch_assoc()['total_calls']:0;
                $total_talk_time=$talk_time_result?$talk_time_result->fetch_assoc()['total_talk_time']:0;

                $hold_time_query="SELECT SUM(pl.parked_sec) AS total_hold_time FROM park_log pl JOIN vicidial_closer_log vcl ON vcl.uniqueid=pl.uniqueid WHERE pl.parked_time BETWEEN '".$from_date_time."' AND '".$to_date_time."' AND pl.user='".$user_name."' ";
                $hold_time_result=$conn->query($hold_time_query);
                $total_hold_time=$hold_time_result?$hold_time_result->fetch_assoc()['total_hold_time']:0;

                $acw_time_query="SELECT SUM(val.dispo_sec) AS total_acw_time FROM vicidial_agent_log val JOIN vicidial_closer_log vcl ON val.uniqueid=vcl.uniqueid WHERE val.event_time BETWEEN '".$from_date_time."' AND '".$to_date_time."' AND val.user='".$user_name."' ";
                $acw_result=$conn->query($acw_time_query);
                $total_acw_time=$acw_result?$acw_result->fetch_assoc()['total_acw_time']:0;

                if($total_calls>0){
                $aht_total=($total_talk_time+$total_hold_time+$total_acw_time)/$total_calls;
                $formatted_avg_handle_time=time_Format($aht_total);
                }else{
                $formatted_avg_handle_time="00:00:00";
                }

                ############# AVG ACW time #################
                $acw_query="SELECT AVG(val.dispo_sec) AS avg_acw_time FROM vicidial_agent_log val JOIN vicidial_closer_log vcl ON val.uniqueid=vcl.uniqueid WHERE val.event_time BETWEEN '".$from_date_time."' AND '".$to_date_time."' AND val.user='".$user_name."' ";
                $acw_result=$conn->query($acw_query);
                $avg_acw_time=$acw_result?$acw_result->fetch_assoc()['avg_acw_time']:0;
                if($attended_calls_value==0){
                $avg_acw_time=0;
                }
                $formatted_acw_time=time_Format($avg_acw_time);

                ############# AVG Waiting time #################
                $avg_wait_time_query="SELECT AVG(val.wait_sec) AS avg_wait_sec FROM vicidial_agent_log val JOIN vicidial_closer_log vcl ON val.uniqueid=vcl.uniqueid WHERE val.event_time BETWEEN '".$from_date_time."' AND '".$to_date_time."' AND val.user='".$user_name."' ";
                $avg_wait_time_result=$conn->query($avg_wait_time_query);
                $avg_wait_time=$avg_wait_time_result?$avg_wait_time_result->fetch_assoc()['avg_wait_sec']:0;

                if($attended_calls_value==0){
                $avg_wait_time=0;
                }
                $formatted_avg_wait_time=time_Format($avg_wait_time);

                ############# AVG Occupancy% #################
                $talk_sec_query="SELECT SUM(val.talk_sec) AS talk_sec FROM vicidial_agent_log val JOIN vicidial_closer_log vcl ON val.uniqueid=vcl.uniqueid WHERE val.event_time BETWEEN '".$from_date_time."' AND '".$to_date_time."' AND val.user='".$user_name."' ";
                $talk_time_result=$conn->query($talk_sec_query);
                $talk_time=$talk_time_result?$talk_time_result->fetch_assoc()['talk_sec']:0;

                $wait_sec_query="SELECT SUM(val.wait_sec) AS wait_sec FROM vicidial_agent_log val JOIN vicidial_closer_log vcl ON val.uniqueid=vcl.uniqueid WHERE val.event_time BETWEEN '".$from_date_time."' AND '".$to_date_time."' AND val.user='".$user_name."' ";
                $wait_sec_result=$conn->query($wait_sec_query);
                $wait_time=$wait_sec_result?$wait_sec_result->fetch_assoc()['wait_sec']:0;

                $acw_query="SELECT SUM(val.dispo_sec) AS acw_time FROM vicidial_agent_log val JOIN vicidial_closer_log vcl ON val.uniqueid=vcl.uniqueid WHERE val.event_time BETWEEN '".$from_date_time."' AND '".$to_date_time."' AND val.user='".$user_name."' ";
                $acw_result=$conn->query($acw_query);
                $acw_time=$acw_result?$acw_result->fetch_assoc()['acw_time']:0;

                $pause_sec_query="SELECT SUM(val.pause_sec) AS pause_sec FROM vicidial_agent_log val JOIN vicidial_closer_log vcl ON val.uniqueid=vcl.uniqueid WHERE val.event_time BETWEEN '".$from_date_time."' AND '".$to_date_time."' AND val.user='".$user_name."' ";
                $pause_sec_result=$conn->query($pause_sec_query);
                $pause_sec=$pause_sec_result?$pause_sec_result->fetch_assoc()['pause_sec']:0;

                $numerator=$talk_time+$acw_time+$wait_time;
                $denominator=$talk_time+$acw_time+$wait_time+$pause_sec;

                if($denominator>0){
                $avg_occupancy=($numerator/$denominator)*100;
                $formatted_avg_occupancy=number_format($avg_occupancy,2)."%";
                }else{
                $formatted_avg_occupancy="0:00%";  
                }

                echo "<tr>
                        <td>{$from_date} to {$end_date}</td>
                        <td>{$full_name}</td>
                        <td>{$user_name}</td>
                        <td>{$attended_calls_value}</td>
                        <td>{$call_answered_within_SL_value}</td>
                        <td>{$formatted_SL_percentage}</td>
                        <td>{$missed_calls_value}</td>
                        <td>{$formatted_missed_call_percentage}</td>
                        <td>{$formatted_avg_talk_time}</td>
                        <td>{$formatted_avg_hold_time}</td>
                        <td>{$formatted_avg_handle_time}</td>
                        <td>{$formatted_acw_time}</td>
                        <td>{$formatted_avg_wait_time}</td>
                        <td>{$formatted_avg_occupancy}</td>
                      </tr>";

            }
          echo "</table>";
        }

    }
}
/*
else{
    echo "No data found for the selected date";
}
*/
?>

<html>
<style>
    table{
        border-collapse:collapse;
        width: 100%;
        font: size 10px;
    }

    td,th{
        border:1px solid #dddddd;
        text-align:left;
        padding:8px;
    }

    tr:nth-child(even){
        background-color:#dddddd;
    }

    .form-control-sm{
        padding:2px 6px;
        border: 1px solid #DEDED1 ;
        width: 120px;
    }
</style>
<script>
    function DownloadCSV(){
        var csv = '';
    var tables = document.querySelectorAll('table');
    tables.forEach(function(table, index) {
        if (index > 0) {
            csv += '\n'; // Add a new line separator between tables
        }
        table.querySelectorAll('tr').forEach(function(row, rowIndex) {
            row.querySelectorAll('th, td').forEach(function(cell, cellIndex) {
                if (rowIndex === 0) {
                    if (cellIndex > 0) {
                        csv += ','; // Add a comma separator between header cells
                    }
                    csv += cell.textContent;
                } else {
                    if (cellIndex > 0) {
                        csv += ',';
                    }
                    csv += '"' + cell.textContent + '"';
                }
            });
            csv += '\n';
        });
    });
    var blob = new Blob([csv], { type: 'text/csv' });
    var a = document.createElement('a');
    a.style.display = 'none';
    a.href = window.URL.createObjectURL(blob);
    a.download = 'Agent_report_Inbound_JeTour.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    }
</script>

</html>