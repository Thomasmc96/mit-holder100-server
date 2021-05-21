<?php

/**
 * The purpose of this file is to get information about the logged in user
 */
include_once '../cors.php';
include_once '../config.php';

$id = "";
$wordPressToken = "";
if (isset($_POST['id']) && !empty($_POST['id'])) {
    $id = $_POST['id'];
    $wordPressToken = $_POST['token'];
}

// URL
$ch = curl_init(HOSTNAME . "/wordpress/wp-json/wp/v2/users/$id");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Headers
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization: Bearer ' . $wordPressToken));

// Execution
$userResponse = curl_exec($ch);

// Closing connection
curl_close($ch);

$userResponse = json_decode($userResponse, true);


if (!empty($userResponse['acf']['user_fields_companies'])) {
    $companies = [];

    $companiesString = trim($userResponse['acf']['user_fields_companies']);
    $companyArray = explode(' ', $companiesString);
    foreach ($companyArray as $companyId) {

        $curl = curl_init();

        // URL
        $ch = curl_init("https://api.clickup.com/api/v2/task/$companyId");

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Headers
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:' . CLICKUPTOKEN));

        // Execution
        $companyResponse = curl_exec($ch);

        // Closing connection
        curl_close($ch);

        $companyResponse = json_decode($companyResponse, true);

        if (!empty($companyResponse['name'])) {
            array_push($companies, ['id' => $companyId, 'name' => $companyResponse['name']]);
        }
    }

    echo json_encode(array(
        'status' => 200,
        'user' => $userResponse,
        'companies' => $companies
    ));
}
