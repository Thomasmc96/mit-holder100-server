<?php
include_once '../cors.php';
include_once '../config.php';

$taskId = "";
$comment = [];
if(isset($_POST['taskId']) && !empty($_POST['taskId'])){
    $taskId = $_POST['taskId'];
    $comment['comment_text'] = $_POST['comment_text'];
    $comment['assignee'] = $_POST['assignee'];
    // $comment['notify_all'] = true;
}

 // URL
 $ch = curl_init("https://api.clickup.com/api/v2/task/$taskId/comment");
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Data
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($comment));

 // Headers
 curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:' . CLICKUPTOKEN));

 // Execution
 $response = curl_exec($ch);

 echo $response;

 // Closing connection
 curl_close($ch);