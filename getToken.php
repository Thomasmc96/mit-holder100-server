<?php
include_once '../config.php';
include_once '../cors.php';

function getToken()
{
    $admin = [
        'username' => USERNAME,
        'password' => PASSWORD
    ];

    $ch = curl_init(HOSTNAME . '/wordpress/wp-json/jwt-auth/v1/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Data
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($admin));

    // Headers
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

    // Execution
    $response = curl_exec($ch);

    // Closing connection
    curl_close($ch);

    return json_decode($response)->token;
}
