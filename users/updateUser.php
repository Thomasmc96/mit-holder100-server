<?php
include_once '../getToken.php';
include_once '../config.php';

$token = getToken();

$acf = [];
$userId = "";
$user = [];
$userIdAndEmail = [];

if (
    isset($_GET['id']) && !empty($_GET['id'])
) {
    $userId = $_GET['id'];

    // Getting the client from Click Up
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.clickup.com/api/v2/task/$userId/",
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

    // Looping through client data
    foreach (json_decode($response)->custom_fields as $custom_field) {
        if ($custom_field->name == "Email") {
            $user['email'] = $custom_field->value;
        }
        if ($custom_field->name == 'Telefon' && !empty($custom_field->value)) {
            $phone = $custom_field->value;
            $phone = str_replace(' ', '', $phone);
            $phone = str_replace('+45', '', $phone);
            $acf['fields']['user_fields_phone'] = $phone;
        }
        if ($custom_field->name == 'Virksomhed' && !empty($custom_field->value)) {
            $companies = $custom_field->value;
            $companiesString = "";
            foreach ($companies as $company) {
                $companiesString .= $company->id . " ";
            }
            $acf['fields']['user_fields_companies'] = $companiesString;
        }
    };
}
// Getting users from Wordpress
$ch = curl_init(HOSTNAME . '/wordpress/wp-json/wp/v2/users?per_page=1000');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Headers
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

// Execution
$response = curl_exec($ch);

// Closing connection
curl_close($ch);

// Pushing every users email and ID to array from db
foreach (json_decode($response) as $userFromDb) {
    array_push($userIdAndEmail, ['ID' => $userFromDb->id, 'email' => $userFromDb->user_email]);
}

// If the client email exists in our db with users
if ($key = array_search($user['email'], array_column($userIdAndEmail, 'email'))) {

    $userId = $userIdAndEmail[$key]['ID'];

    // Update ACF in WordPress
    $ch = curl_init(HOSTNAME . "/wordpress/wp-json/acf/v3/users/$userId");
    // Headers
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:application/json", "Authorization: Bearer $token"));

    // Data
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($acf));

    // Execution
    $response = curl_exec($ch);

    // Closing connection
    curl_close($ch);
}
