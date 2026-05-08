<?php
############ initialize DB connection #######
include 'db_connect.php';

$title="Agent Login Logout Details - Jetour";

#### define variables ##################
$now=date("Y-m-d");
$start_date="";
$start_time="00:00:00";
$end_date="";
$end_time="23:59:59";

$campaign_id='JETOUR';

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
    <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="get" name="vicidial_report" id="vicidial_report">
        <!--
        <div class="container-fluid mt-3 mb-3 px-4">
            <div class="row align-items-start">
                
                 <div class="col-md-4">
                    <div class="d-flex flex-column gap-1">

                        <div class="row align-items-center mb-1">
                        <label class="col-md-4 col-form-label">From Date:</label>
                            <div class="col-md-6">
                                <input type="date" name="start_date" id="start_date" value="<?php echo $start_date;?>" class="form-control-sm" >  
                            </div>
                        </div>
                        <div class="row align-items-center mb-1">
                        <label class="col-md-4 col-form-label">To Date:</label>
                            <div class="col-md-6">
                                <input type="date" name="end_date" id="end_date" value="<?php echo $end_date;?>" class="form-control-sm">
                            </div>
                        </div>

                    </div>
                 </div>
                
                 <div class="col-md-8 d-flex justify-content-end px-4">
                    <div class="d-flex flex-row gap-2">
                        <a href="/ocdial/admin.php?ADD=999999" class="btn btn-secondary">Back</a>
                        <button type="submit" name="SUBMIT" class="btn btn-primary">Submit</button>
                        <button type="button" onclick="DownloadCSV()" class="btn btn-success">DownloadCSV</button>
                    </div>
                 </div>

            </div>
        </div>
        -->
        <!--
        <div class="row">
            <div class="col-lg-12 form-box" >
                <label> From Date: </label>
                <input type="date" name="start_date" id="start_date" value="<?php echo $start_date;?>" >
                <label>To Date: </label>
                <input type="date" name="end_date" id="end_date" value="<?php echo $end_date;?>">&nbsp;
                <a href="/ocdial/admin.php?ADD=999999" class="button">Back</a>&nbsp;
                <input type="SUBMIT" value="SUBMIT" name="SUBMIT" style="cursor: pointer;" >
                <input type="button" value="downloadCSV" onclick="downloadCSV()" style="cursor: pointer;">
            </div>
        </div>
        -->
    <div class="container-fluid mt-3 mb-3 px-4">
    <div class="row align-items-center">

    <!-- Left : Date Filters -->
    <div class="col-md-4">
        <div class="row align-items-center mb-1">
        <label class="col-md-4 col-form-label">From Date:</label>
            <div class="col-md-6">
                <input type="date" name="start_date" id="start_date" value="<?php echo $start_date;?>" class="form-control form-control-sm">
            </div>
        </div>

        <div class="row align-items-center">
        <label class="col-md-4 col-form-label">To Date:</label>
            <div class="col-md-6">
                <input type="date" name="end_date" id="end_date" value="<?php echo $end_date;?>" class="form-control form-control-sm">
            </div>
        </div>
    </div>

    <!-- Middle : Empty  -->
    <div class="col-md-4"></div>

    <!-- Right : Buttons -->
    <div class="col-md-4 text-end">
        <a href="/ocdial/admin.php?ADD=999999" class="btn btn-secondary btn-sm">Back</a>
        <button type="submit" name="SUBMIT" class="btn btn-primary btn-sm">Submit</button>
        <button type="button" onclick="DownloadCSV()" class="btn btn-success btn-sm">Download CSV</button>
    </div>

   </div>
   </div>   
</form>
</body>
</html>

<?php
if(isset($_GET['SUBMIT'])){
    $from_date=$_GET['start_date'];
    $end_date=$_GET['end_date'];

    $from_date_time=$from_date.' '.$start_time;
    $to_date_time=$end_date.' '.$end_time;

    $login_logout_query="SELECT 
            vu.user,
            vu.full_name AS agent_name,
            l1.event_date AS login_time,
            (
                SELECT MIN(l2.event_date)
                FROM vicidial_user_log l2
                WHERE l2.user = l1.user
                  AND l2.event = 'LOGOUT'
                  AND l2.event_date > l1.event_date
                  AND l2.event_date <= '$to_date_time'
            ) AS logout_time
        FROM vicidial_user_log l1
        JOIN vicidial_users vu ON vu.user = l1.user
        WHERE l1.event = 'LOGIN'
          AND l1.event_date BETWEEN '$from_date_time' AND '$to_date_time'
          AND vu.user_group LIKE '%JETOUR%'
        ORDER BY vu.user, l1.event_date";

    $result=$conn->query($login_logout_query);

    echo "<table>
               <tr>
                 <th>Date</th>
                 <th>Agent name</th>
                 <th>Agent ID</th>
                 <th>Login Time</th>
                 <th>Logout Time</th>
                 <th>Total Time</th>
               </tr>
        ";

    if($result && $result->num_rows>0){
     while($row=$result->fetch_assoc()){
        
        $agent_name=$row['agent_name'];
        $agent_id=$row['user'];
        $login_time=$row['login_time'];
        $logout_time=$row['logout_time'];
        $login_date=!empty($login_time) ? date("Y-m-d",strtotime($login_time)) : '';

         $formatted_time='-';
         if(!empty($logout_time) && !empty($login_time)){
            $time=strtotime($logout_time)-strtotime($login_time);
            if($time>=0){
                $hrs=floor($time/3600);
                $minutes=floor(($time%3600)/60);
                $secs=floor($time%60);
                $formatted_time=sprintf("%02d:%02d:%02d",$hrs,$minutes,$secs);

            }
         }

    echo "<tr>
        <td>{$login_date}</td>
        <td>{$agent_name}</td>
        <td>{$agent_id}</td>
        <td>{$login_time}</td>
        <td>{$logout_time}</td>
        <td>{$formatted_time}</td>

    </tr>";

     }   
    } else {
        echo "<tr><td colspan='6'>No Records Found for the selected date range.</td></tr>";
    }

    echo "</table>";
}
?>
<html>
  <style>
    table{
        border-collapse: collapse;
        width: 100%;
        font-size: 12px;
    }

    td,th{
        border: 1px solid #dddddd;
        text-align: center;
        padding: 8px;
    }
    tr:nth-child(even){
        background-color: #f2f2f2;
    }
    .form-box {
        text-align: center;
        margin-top: 20px;
    }
    .form-control-sm{
        padding:2px 6px;
        border: 1px solid #DEDED1 ;
        width: 120px;
    }

  </style>
  <script>
    function downloadCSV(){
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
    a.download = 'Agent_login_logout_report_JeTour.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    }
</script>
</html>