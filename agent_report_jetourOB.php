<?php
include 'db_connect.php';

$title="Agent Report-Outbound Jetour";

####### set time variables ############
$now=date("Y-m-d");
$start_time="00:00:00";
$end_time="23:59:59";
$start_date="";
$end_date="";

############ get list of user groups #################
$user_group_query="SELECT user_group FROM vicidial_user_groups WHERE user_group LIKE 'JETOUR_AGENTS%' OR user_group LIKE 'JETOUR_ADMINS%' ORDER BY user_group; ";
$user_group_result=$conn->query($user_group_query);

############ get list of inbound groups ##[for OB no need]#######
$outbound_query="SELECT group_id FROM vicidial_inbound_groups WHERE group_id LIKE 'JETOUR_%' order by answer_sec_pct_rt_stat_one desc; ";
$ingroup_result=$conn->query($outbound_query);

function timeFormat($time_convert){
    $hours=floor($time_convert/3600);
    $minutes=floor(($time_convert%3600)/60);
    $seconds=$time_convert%60;
    return sprintf("%02d:%02d:%02d",$hours,$minutes,$seconds);    
}
?> 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Agent Report-Outbound Jetour</title>
    
</head>
<body>
  <!--<h2 style="text-align:center;"><?php echo $title;?></h2>-->
  <h5 class="text-center mt-3 mb-3"><?php echo $title;?></h5>
  <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="get" name="vicidial_report" id="vicidial_report">
    <div class="container-fluid mt-3 mb-3 px-4">
        <div class="row align-items-start">

        <!--  left side:  dates-->
          <div class="col-md-4">
            <div class="d-flex flex-column gap-1">

                <div class="row align-items-center mb-1">
                    <label class="col-md-4 col-form-label">From Date:</label>
                    <div class="col-md-6">
                        <input type="date" name="query_date" id="query_date" value="<?php echo $query_date;?>" class="form-control-sm">
                    </div>
                </div>

                <div class="row align-items-center mb-1">
                    <label class="col-md-4 col-form-label">From Time:</label>
                    <div class="col-md-6">
                        <input type="text" name="query_time" id="query_time" value="<?php echo $start_time;?>" class="form-control-sm">
                    </div>
                </div>

                <div class="row align-items-center mb-1">
                    <label class="col-md-4 col-form-label">To Date:</label>
                    <div class="col-md-6">
                        <input type="date" name="end_date" id="end_date" value="<?php echo $end_date;?>" class="form-control-sm">
                    </div>
                </div>

                <div class="row align-items-center mb-1">
                   <label class="col-md-4 col-form-label">To Time:</label>
                   <div class="col-md-6">
                       <input type="text" name="end_time" id="end_time" value="<?php echo $end_time;?>" class="form-control-sm">
                   </div>
                </div>

            </div>
          </div>

        <!-- middle : boxes -->
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
                        <select size="3" name="user_group[]" id="user_group" class="form-select" multiple>
                            <?php
                            if($user_group_result->num_rows>0){
                            while($row=$user_group_result->fetch_assoc()){
                                    $user_group_name=$row['user_group'];
                                    echo "<option selected value='$user_group_name'>$user_group_name</option>";
                                }
                            }else{
                                echo "<option value=''>No Groups Found</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="row align-items-center">
                    <label class="col-4 col-form-label">Inbound Group</label>
                    <div class="col-8">
                      <select size="3" name="inbound_group[]" id="inbound_group" class="form-select" multiple>
                        <?php
                        if($ingroup_result->num_rows>0){
                        while($row=$ingroup_result->fetch_assoc()){
                            $ingroup_name=$row['group_id'];
                            echo "<option selected value='$ingroup_name'>$ingroup_name</option>";
                            }
                        }else{
                            echo "<option value=''>No Groups Found</option>";
                        }
                        ?>
                      </select>  
                    </div>
                </div>
            
            </div>
         </div>

        <!-- right : buttons -->
         <div class="col-md-4 d-flex justify-content-end">
            <div class="d-flex flex-column gap-3">
                <a href="/ocdial/admin.php?ADD=999999" class="btn btn-secondary me-2">Back</a>
                <button type="submit" name="SUBMIT" class="btn btn-primary me-2">Submit</button>
                <button type="button" onclick="DownloadCSV()" class="btn btn-success">DownloadCSV</button>
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

    $from_date_time=$from_date.' '.$start_time;
    $to_date_time=$end_date.' '.$end_time; 

    if(preg_match('/--ALL--/i',implode(',',$userGroups))){
        $sql="SELECT * FROM vicidial_user_groups;";
    }else{
        $quotedItems=array_map(function($group) use($conn){
            return "'".$conn->real_escape_string($group)."'";
        },$userGroups);
    }
    
    $Overall_user='('.implode(',',$quotedItems).')';
    $sql="SELECT * FROM vicidial_user_groups WHERE user_group IN $Overall_user";
    $result=$conn->query($sql);

    if($result->num_rows>0){

        echo "
            <table border='1'>
                <tr>
                 <th>Date</th>
                 <th>Agent name</th>
                 <th>Agent ID</th>
                 <th>Total Outbound Call</th>
                 <th>AVG Talk Time</th>
                 <th>AVG Hold Time</th>
                 <th>AVG Handling Time</th>
                </tr>
            ";

        foreach($result as $row_usergroup){
            $group_name=$row_usergroup['user_group'];


            $totalHandledCalls=0;

            ##### nested query to fetch users based on group name ##
            $user_query="SELECT * FROM vicidial_users WHERE user_group='".$conn->real_escape_string($group_name)."' ";
            $user_result=$conn->query($user_query);

            while($row_user=$user_result->fetch_assoc()){
                $fullname=$row_user['full_name'];
                $username=$row_user['user'];

            ############### total outbound calls ##############
                $attended_calls_val=0;

                $attended_calls_query="SELECT COUNT(*) AS attended_calls FROM vicidial_log WHERE user='".$username."' AND call_date BETWEEN '".$from_date_time."' AND '".$to_date_time."'  ";
                
                $attended_calls_result=$conn->query($attended_calls_query);

                $attended_calls_val=$attended_calls_result?$attended_calls_result->fetch_assoc()['attended_calls'] : 0;

            ############### AVG talk time #################
            $avg_talk_time_query="SELECT AVG(length_in_sec) AS avg_talk_time FROM vicidial_log WHERE user='".$username."' AND call_date BETWEEN '".$from_date_time."' AND '".$to_date_time."' ";

            $avg_talk_time_result=$conn->query($avg_talk_time_query);
            $avg_talk_time_val=$avg_talk_time_result?$avg_talk_time_result->fetch_assoc()['avg_talk_time'] : 0 ;

            $formatted_avg_talk_time=timeFormat($avg_talk_time_val);

            if($attended_calls_val<=0){
                $formatted_avg_talk_time='00:00:00';
            }

            ############ AVG Hold time ####################
            $avg_hold_time_query="SELECT AVG(parked_sec) AS avg_hold_time FROM park_log WHERE user='".$username."' AND extension LIKE 'M%' AND parked_time BETWEEN '".$from_date_time."' AND '".$to_date_time."' ";

            $avg_hold_time_result=$conn->query($avg_hold_time_query);

            $avg_hold_time_val=$avg_hold_time_result?$avg_hold_time_result->fetch_assoc()['avg_hold_time'] : 0 ;

            $formatted_avg_hold_time=timeFormat($avg_hold_time_val);

            if($attended_calls_val<=0){
                $formatted_avg_hold_time='00:00:00';
            }

            ########### AVG Handle time ###################
            $talk_time_query="SELECT COUNT(*) AS total_calls, SUM(length_in_sec) AS total_talk_time FROM vicidial_log WHERE call_date BETWEEN '".$from_date_time."' AND '".$to_date_time."' AND user='".$username."' AND status NOT IN('TIMEOT','DROP') ";

            $talk_time_result=$conn->query($talk_time_query);
            
            $total_calls=0;
            $talk_time=0;
            if($talk_time_result && $row=$talk_time_result->fetch_assoc()){
                $total_calls=$row['total_calls'];
                $talk_time=$row['total_talk_time'];
            }
            
            $hold_time_query="SELECT SUM(parked_sec) AS total_hold_time FROM park_log WHERE parked_time BETWEEN '".$from_date_time."' AND '".$to_date_time."' AND user='".$username."' AND extension LIKE 'M%' ";

            $hold_time_result=$conn->query($hold_time_query);

            $hold_time=$hold_time_result ? $hold_time_result->fetch_assoc()['total_hold_time'] : 0;

            $acw_time_query="SELECT SUM(pause_sec) AS total_acw_time FROM vicidial_agent_log WHERE sub_status='ACW' AND event_time BETWEEN '".$from_date_time."' AND '".$to_date_time."' AND user='".$username."' AND comments='MANUAL' ";

            $acw_result=$conn->query($acw_time_query);

            $acw_time=$acw_result?$acw_result->fetch_assoc()['total_acw_time'] : 0;

            if($total_calls>0){
            $aht_val=($talk_time+$hold_time+$acw_time)/ $total_calls;
             $formatted_aht=timeFormat($aht_val);
            }else{
             $formatted_aht="00:00:00";   
            }

            echo "<tr>
                   <td>{$from_date} to {$end_date}</td>
                   <td>{$fullname}</td>
                   <td>{$username}</td>
                   <td>{$attended_calls_val}</td>
                   <td>{$formatted_avg_talk_time}</td>
                   <td>{$formatted_avg_hold_time}</td>
                   <td>{$formatted_aht}</td>
                 </tr>";

            }
        
        }
        echo "</table>";
    }
    

}
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
    a.download = 'Agent_report_Outbound_JeTour.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    }
</script>

</html>