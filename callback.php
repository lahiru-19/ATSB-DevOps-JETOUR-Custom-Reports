<?php

######### initialize DB ##########################
include 'db_connect.php';

######### Titlte ################################
$title="CallBack Report - JeTour";

######### set date and time ####################
$now=date("Y-m-d");
$from_date="";
$to_date="";
$start_time="18:00:00";
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
    <h5 class="text-center mt-3 mb-3"><?php echo htmlspecialchars($title);?></h5>
<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>" method="get" name="vicidial_report">

<div class="container-fluid mt-3 mb-3 px-4">
    <div class="row align-items-center">

        <!-- left side(times) -->
        <div class="col-md-6">
            <div class="row align-items-center mb-1 g-0">
                <label for="from date" class="col-md-2 col-form-label">From Date:</label>
                <div class="col-md-3 pe-1">
                    <input type="date" name="from_date" id="from_date" value="<?php echo $from_date;?>" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <input type="text" name="start_time" id="start_time" value="<?php echo $start_time;?>" class="form-control form-control-sm">
                </div>
            </div>
            <div class="row align-items-center mb-1 g-0">
                <label for="to_date" class="col-md-2 col-form-label">To Date:</label>
                <div class="col-md-3 pe-1">
                    <input type="date" name="to_date" id="to_date" value="<?php echo $to_date;?>" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <input type="text" name="end_time" id="end_time" value="<?php echo $end_time;?>" class="form-control form-control-sm">
                </div>

            </div>
        </div>

        <!-- right side(buttons) -->
         <div class="col-md-6 text-end">
            <a href="/ocdial/admin.php?ADD=999999" class="btn btn-secondary btn-sm">Back</a>
            <button type="submit" name="submit" class="btn btn-primary btn-sm">Submit</button>
            <button type="button" onclick="DownloadCSV()" class="btn btn-success btn-sm">DownloadCSV</button>
         </div>

    </div>
</div>
</form>
</body>
</html>

<?php
if(isset($_GET['submit'])){
    $from_date=$_GET['from_date'] ?? $now;
    $to_date=$_GET['to_date'] ?? $now;

    /*----- During hours ----*/
    $start_time_dh="08:00:00";
    $end_time_dh="18:00:00";

    $from_date_time_dh="$from_date $start_time_dh";
    $to_date_time_dh="$to_date $end_time_dh";

    /*----- After hours -----*/
    //$start_time_ah=$_GET['start_time'] ?? "18:00:01";
    //$end_time_ah=$_GET['end_time'] ?? "23:59:59";
    $ah_morning_start="$from_date 00:00:00";
    $ah_morning_end="$to_date 07:59:59";

    $ah_evening_start="$from_date 18:00:01";
    $ah_evening_end="$to_date 23:59:59";

    //$from_date_time_ah="$from_date $start_time_ah";
    //$to_date_time_ah="$to_date $end_time_ah";


    
    ############################ for during workhours callback ###################################

    echo '<div class="container-fluid px-2 mt-4">';
    echo '<h4 class="text-center mb-3">For During Hours</h4>' ;
    echo '<div class="row justify-content-center">';
    echo '<div class="col-md-12">';

    echo "<table class='table table-bordered text-center'>
            <tr>
                <th>Unique ID</th>
                <th>Break down by Date</th>
                <th>Break down by Date & Time</th>
                <th>Phone Number</th>
            </tr>";
    
    $Workhrscallback_query="SELECT MIN(CASE 
                                        WHEN comment_b='JETOUR_WELCOME' 
                                        THEN start_time END) AS callback_time,
    uniqueid,
    MIN(caller_id) AS callback_number,
    MIN(phone_ext) AS call_number,

    GROUP_CONCAT(comment_b ORDER BY start_time SEPARATOR '||') AS call_flow,
    GROUP_CONCAT(comment_d ORDER BY start_time SEPARATOR '||') AS dtmf_flow
    FROM live_inbound_log 
    WHERE start_time between '$from_date_time_dh' AND '$to_date_time_dh'
    
    AND comment_a ='CALLMENU'

    AND uniqueid IN(
        SELECT uniqueid FROM live_inbound_log WHERE comment_b LIKE 'JETOUR%'
    )

    AND uniqueid IN(
        SELECT uniqueid FROM live_inbound_log WHERE comment_b='t' AND comment_d LIKE '%CALLBACK_5>t'
    )
    GROUP BY uniqueid
    ORDER BY callback_time ASC;
     ";

    $resultB=$conn->query($Workhrscallback_query);

    if($resultB->num_rows>0){
        while($rowB=$resultB->fetch_assoc()){
                $uniqueid=$rowB['uniqueid'];
                $date=date("Y-m-d",strtotime($rowB['callback_time']));
                $time=$rowB['callback_time'];
                $number=$rowB['call_number'];

                echo "<tr>
                        <td>{$uniqueid}</td>
                        <td>{$date}</td>
                        <td>{$time}</td>
                        <td>{$number}</td>
                     </tr>";
        }
    }else{
        echo "<tr><td colspan='6'>No results found during Working Hours.</td></tr>" ;
    }


    
    echo "</table>";
    echo "</div></div></div>";

    ####################### end of  for workhours callback ################################

    ############################ for after hours callback #################################
    //echo '<h4>For After Hours</h4><br>' ;
    echo '<div class="container-fluid px-2 mt-4">';
    echo '<h4 class="text-center mb-3">For After Hours</h4>';
    echo '<div class="row justify-content-center">';
    echo '<div class="col-md-12">';

    echo "<table class='table table-bordered text-center'>
            <tr>
                <th>Unique ID</th>
                <th>Break down by Date</th>
                <th>Break down by Date & Time</th>
                <th>Phone Number</th>
            </tr>
    ";
   
/*
$Afterhrscallback_query = "
SELECT 
    MIN(CASE WHEN comment_b = 'JETOUR_WELCOME' THEN start_time END) AS callback_time,
    MIN(uniqueid) AS uniqueid,
    MIN(caller_id) AS callback_number,
    MIN(phone_ext) AS Call_number,
    GROUP_CONCAT(comment_b ORDER BY start_time SEPARATOR ' || ') AS call_flow,
    GROUP_CONCAT(comment_d ORDER BY start_time SEPARATOR ' || ') AS dtmf_flow
FROM live_inbound_log 
WHERE start_time BETWEEN '$from_date_time' AND '$to_date_time'
AND comment_a = 'CALLMENU'
AND comment_b = 't'
AND comment_d LIKE '%AFTER_HOURS_5>t%'
GROUP BY uniqueid 
ORDER BY callback_time ASC
";*/
$Afterhrscallback_query = "SELECT 
    MIN(CASE 
        WHEN comment_b = 'JETOUR_WELCOME' 
        THEN start_time 
    END) AS callback_time,

    uniqueid,

    MIN(caller_id) AS callback_number,
    MIN(phone_ext) AS Call_number,

    GROUP_CONCAT(comment_b ORDER BY start_time SEPARATOR ' || ') AS call_flow,
    GROUP_CONCAT(comment_d ORDER BY start_time SEPARATOR ' || ') AS dtmf_flow

FROM live_inbound_log 

WHERE (start_time BETWEEN '$ah_morning_start' AND '$ah_morning_end'
        OR start_time BETWEEN '$ah_evening_start' AND '$ah_evening_end')

AND comment_a = 'CALLMENU'

-- to check it's JETOUR flow
AND uniqueid IN (
    SELECT uniqueid
    FROM live_inbound_log
    WHERE comment_b LIKE 'JETOUR%'
)

-- to check callback happened
AND uniqueid IN (
    SELECT uniqueid
    FROM live_inbound_log
    WHERE comment_b = 't'
    AND comment_d LIKE '%AFTER_HOURS_5>t%'
)

GROUP BY uniqueid
ORDER BY callback_time ASC;
";
$resultA=$conn->query($Afterhrscallback_query);

if($resultA->num_rows>0){
    while($rowA=$resultA->fetch_assoc()){
        $uniqueid=$rowA['uniqueid'];
        //$date=$rowA['callback_time'];//in y-m-d
        $date=date("Y-m-d",strtotime($rowA['callback_time']));
        $time=$rowA['callback_time'];//in h:m:s
        $number=$rowA['Call_number'];

        echo "<tr>
                <td>{$uniqueid}</td>
                <td>{$date}</td>
                <td>{$time}</td>
                <td>{$number}</td>
              </tr>";
    }
}else{
   echo "<tr><td colspan='6'>No results found for After Hours.</td></tr>" ;
}
echo "</table>";
echo "</div></div></div>";

}
?>
<html>
    <style>
        table{
            border-collapse: collapse;
            width: 100%;
            font-size: 15px;
        }

        td,th{
            border: 1px solid #dddddd;
            text-align: center;
            padding: 8px;
        }

        tr:nth-child(even){
            background-color: #f2f2f2;
        }
        .form-control-sm{
            padding: 2px 6px;
            border: 1px solid #DEDED1;
            height: 28px;
        }
        .col-form-label{
            padding-top: 2px;
            padding-bottom: 2px;
            font-size: 14px;
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
    a.download = 'CallBack_report_JeTour.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    }   
    </script>
</html>