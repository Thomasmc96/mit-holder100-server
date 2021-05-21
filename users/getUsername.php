<?php

/**
 * The purpose of this file is to get the username from WordPress db by a phonenumber or email
 */
include_once '../cors.php';
include_once '../config.php';

$phone = "";
$email = "";

if (isset($_POST['phone']) && !empty($_POST['phone'])) {
    $phone = $_POST['phone'];
}
if (isset($_POST['email']) && !empty($_POST['email'])) {
    $email = $_POST['email'];
}

$ch = curl_init(HOSTNAME . '/wordpress/wp-json/wp/v2/users?per_page=1000');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Headers
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

// Execution
$response = curl_exec($ch);

// echo $response;

// Closing connection
curl_close($ch);
foreach (json_decode($response) as $user) {
    if (!empty($user->acf->user_fields_phone)) {
        $userPhone = $user->acf->user_fields_phone;
    }
    if (!empty($user->user_email)) {
        $userEmail = $user->user_email;
    }

    if (!empty($phone) && $userPhone == $phone || !empty($email) && $userEmail == $email) {
        $userName = $user->user_login;
        $companies = $user->acf->user_fields_companies;
        echo json_encode(array(
            'status' => 200,
            'username' => $userName,
            'companies' => $companies
        ));
    }
}
