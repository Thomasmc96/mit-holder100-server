<?php
include_once '../cors.php';
include_once '../config.php';

$taskId = "";
$comment = [];
$status = "";
if (isset($_POST['taskId']) && !empty($_POST['taskId'])) {
    $taskId = $_POST['taskId'];
    // $comment['comment_text'] = "En tekst på selve opgaven er blevet tilføjet af ".$_POST['name']. " og lyder som følger:\n\n\"" . $_POST['comment_text'] . "\"";
    $comment['comment_text'] = $_POST['name']. " har tilføjet en tekst:\n\n\"" . $_POST['comment_text'] . "\"";
    $comment['assignee'] = $_POST['assignee'];
    $status = $_POST['status'];
    $comment['notify_all'] = true;
}

// URL
$ch = curl_init("https://api.clickup.com/api/v2/task/$taskId/comment");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Data
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($comment));

// Headers
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type:application/json',
    'Authorization:' . CLICKUPTOKEN
));

// Execution
$response = curl_exec($ch);

// echo $response;

// Closing connection
curl_close($ch);

$response = json_decode($response, true);

if (!empty($response['id']) && $status == 'afventer data fra kunden') {
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.clickup.com/api/v2/task/$taskId",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => 'status=design%20modtaget%20-%20klar',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: ' . CLICKUPTOKEN . ''
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
}
