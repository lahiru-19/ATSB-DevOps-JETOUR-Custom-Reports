<?php
$servername='localhost';
$username='cron';
$password='1234';
$dbname='asterisk';

$conn=new mysqli($servername,$username,$password,$dbname);

//check connection
if($conn->connect_error){
    die("connection error: ".$conn->connect_error);
}
?>