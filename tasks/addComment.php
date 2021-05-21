<?php

/**
 * The purpose of this file is to add a single comment to a Click Up task when the user uploads a task-text
 */
include_once '../cors.php';
include_once '../config.php';
include_once './changeStatus.php';

$taskId = "";
$comment = [];
$status = "";
$writtenComment = "";
if (isset($_POST['taskId']) && !empty($_POST['taskId'])) {
    $taskId = $_POST['taskId'];
    $writtenComment = $_POST['comment_text'];
    // $comment['comment_text'] = "En tekst på selve opgaven er blevet tilføjet af ".$_POST['name']. " og lyder som følger:\n\n\"" . $_POST['comment_text'] . "\"";
    $comment['comment_text'] = $_POST['name'] . " har tilføjet en tekst:\n\n\"" . $writtenComment . "\"";
    $comment['assignee'] = $_POST['assignee'];
    $status = $_POST['status'];
    $comment['notify_all'] = true;
}
if ($writtenComment !== "") {

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
        changeStatus($taskId);
    }
}
