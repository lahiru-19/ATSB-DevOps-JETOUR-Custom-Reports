<?php

$title="Agent Status Report-JeTour";
############ initialize DB connection ############
include 'db_connect.php';

######### define time variables ##################
$now=date("Y-m-d");
$start_date="";
$end_date="";
$start_time="00:00:00";
$end_time="23:59:59";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <title><?php echo htmlspecialchars($title);?></title>
    
</head>
<body>
    <!--<h2 style="text-align: center"><?php echo htmlspecialchars($title);?></h2>-->
    <h5 class="text-center mt-3 mb-3"><?php echo htmlspecialchars($title);?></h5>
    <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name="vicidial_report" id="vicidial_report">
        
        <div class="container-fluid mt-3 mb-3 px-4">
            <div class="row align-items-center">

            <!-- left: times -->
             <div class="col-md-6" >
                <div class="row align-items-center mb-1 g-0">
                    <label class="col-md-2 col-form-label">From Date:</label>
                    <div class="col-md-3 pe-1">
                        <input type="date" name="start_date" id="start_date" value="<?php echo $start_date;?>" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="start_time" id="start_time" value="<?php echo $start_time;?>" class="form-control form-control-sm">
                    </div>          
                </div>

                <div class="row align-items-center mb-1 g-0">
                    <label class="col-md-2 col-form-label">To Date:</label>
                    <div class="col-md-3 pe-1">
                        <input type="date" name="end_date" id="end_date" value="<?php echo $end_date;?>" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <input type="text" id="end_time" name="end_time" value="<?php echo $end_time;?>" class="form-control form-control-sm"> 
                    </div>
                      
                </div>
             </div>

            

            <!-- right: buttons -->
             <div class="col-md-6 text-end">
                <a href="/ocdial/admin.php?ADD=999999" class="btn btn-secondary btn-sm">Back</a>
                <button type="submit" name="submit" class="btn btn-primary btn-sm">Submit</button>
                <button type="button" onclick="DownloadCSV()" class="btn btn-success btn-sm" >Download CSV</button>
             </div>

            </div>
        </div>
        
        <!--
        <div class="row" style="text-align: center;">
        <div class="col-lg-12">
            Start Date:&nbsp;<input type="date" name="start_date" id="start_date">&nbsp;
            <input type="text" name="start_time" id="start_time" value="<?php echo $start_time;?>" style="width: 100px;">&nbsp;&nbsp;<input type="date" name="end_date" id="end_date" value="<?php echo $end_date?>">&nbsp;<input type="text" id="end_time" name="end_time" value="<?php echo $end_time;?>" style="width: 100px;">
        </div>
        <br>
        <div class="submit_box" style="text-align: center;">
            <a href="/ocdial/admin.php?ADD=999999">Back</a>&nbsp;
            <input type="submit" value="submit" name="submit" style="cursor: pointer;">&nbsp;
            <input type="button" value="Download CSV" onclick="DownloadCSV()" style="cursor: pointer;">
        </div>
      </div>
      -->
    </form>
</body>
</html>
<?php
if(isset($_POST['submit'])){
    //$from_date=$_REQUEST['start_date'];
    //$from_time=$_REQUEST['start_time'];
    //$to_date=$_REQUEST['end_date'];
    //$to_time=$_REQUEST['end_time'];

    $from_date=$_POST['start_date'] ?? '';
    $from_time=$_POST['start_time'] ?? '';
    $to_date=$_POST['end_date'] ?? '';
    $to_time=$_POST['end_time'] ?? '';

    $from_date_time=$from_date." ".$from_time;
    $to_date_time=$to_date." ".$to_time;

    echo "<table>
            <tr>
                <th>Date</th>
                <th>Agent name</th>
                <th>Agent ID</th>
                <th>Team Name</th>
                <th>Status Name</th>
                <th>Breakdown by Start Date Time</th>
                <th>Status Duration (seconds)</th>
                <th>Total Time</th>
            </tr>
            ";
    $user_query="SELECT user,full_name,user_group FROM vicidial_users WHERE user_group LIKE '%JETOUR_ADMINS%' ";
    $user_result=$conn->query($user_query);

    if($user_result->num_rows>0){
        while($row=$user_result->fetch_assoc()){
            $user_id=$row['user'];
            $fullname=$row['full_name'];
            $userGroup=$row['user_group'];

            ######### select status for each user ##########
            $status_query="SELECT event_time,sub_status,pause_sec FROM vicidial_agent_log WHERE user='$user_id' AND event_time BETWEEN '$from_date_time' AND '$to_date_time' ORDER BY event_time ";
            $status_result=$conn->query($status_query);
            if($status_result->num_rows>0){
                while($row=$status_result->fetch_assoc()){
                 $status=$row['sub_status'] ? $row['sub_status'] : '';
                 $event_time=$row['event_time'] ? $row['event_time'] : '';
                 $pause_time=$row['sub_status'] ? $row['pause_sec'] : 0 ;

                 $pause_hrs=floor($pause_time/3600);
                 $pause_mins=floor(($pause_time%3600)/60);
                 $pause_secs=floor($pause_time%60);

                 $formatted_pause_time=$row['sub_status']? sprintf("%02d:%02d:%02d",$pause_hrs,$pause_mins,$pause_secs): '';

                 if($pause_time<=0){
                    $formatted_pause_time="00:00:00";
                 }

                echo "<tr>
                    <td>{$from_date}</td>
                    <td>{$fullname}</td>
                    <td>{$user_id}</td>
                    <td>{$userGroup}</td>
                    <td>{$status}</td>
                    <td>{$event_time}</td>
                    <td>{$pause_time}</td>
                    <td>{$formatted_pause_time}</td>
                </tr>";
                }
            }
        }

    }else{
        echo "<tr><td colspan='6'>No Users Found</td></tr>";
    }
    echo "</table>";

}
?>
<html>
    <style>
        table{
            border-collapse: collapse; width: 100%; font-size: 15px;
        }
        td,th{
            border: 1px solid #dddddd; text-align: center; padding: 8px;
        }
        tr:nth-child(even){
            background-color: #f2f2f2;
        }
        .form-control-sm{
        padding:2px 6px;
        border: 1px solid #DEDED1 ;
        height: 28px;
        /*width: 120px;*/
        }
        .col-form-label{
        padding-top:2px;
        padding-bottom:2px;
        font-size:14px;
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
    a.download = 'Agent_status_report_JeTour.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    }
</script>
</html>