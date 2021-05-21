<?php

/**
 * The purpose of this file is to change the status of a task in Click Up
 */
include_once '../cors.php';
include_once '../config.php';

function changeStatus($taskId)
{

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
