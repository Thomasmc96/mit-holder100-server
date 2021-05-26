<?php
include_once '../cors.php';
include_once '../config.php';


$taskId = "";

if(isset($_GET['taskId']) && !empty($_GET['taskId'])){
    $taskId = $_GET['taskId'];
}

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.clickup.com/api/v2/task/$taskId/comment/",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
    'Authorization: ' . CLICKUPTOKEN . ''
),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;
