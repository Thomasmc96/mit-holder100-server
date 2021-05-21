<?php

/**
 * The purpose of this file is to update all user in WordPress
 * CURRENTY NOT IN USE
 * */

include_once '../getToken.php';
include_once 'getUsersFromClickUp.php';
include_once '../config.php';

$token = getToken();

// Get phonenumbers in db
$ch = curl_init(HOSTNAME . '/wordpress/wp-json/wp/v2/users?per_page=1000');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Headers
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

// Execution
$response = curl_exec($ch);

// Closing connection
curl_close($ch);

$userIdAndEmail = [];
// Pushing every user from db with a phonenumber to $phonenumbers
foreach (json_decode($response) as $user) {
    array_push($userIdAndEmail, ['ID' => $user->id, 'email' => $user->user_email]);
}

foreach ($clientsWithEmail as $client) {
    try {
        $acf = [];
        $user = [];

        $acf['fields']['user_fields_click_up_id'] = $client->id;
        foreach ($client->custom_fields as $custom_field) {
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
        // echo json_encode($user);

        if ($key = array_search($user['email'], array_column($userIdAndEmail, 'email'))) {

            $existingUserId = $userIdAndEmail[$key]['ID'];
            // unset($user['password']);
            // unset($user['name']);
            // unset($user['username']);

            // // User
            // $ch = curl_init(HOSTNAME . "/wordpress/wp-json/wp/v2/users/$existingUserId");
            // // Headers
            // curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:application/json", "Authorization: Bearer $token"));

            // // Data
            // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($user));

            // // Execution
            // $response = curl_exec($ch);

            // ACF
            $ch = curl_init(HOSTNAME . "/wordpress/wp-json/acf/v3/users/$existingUserId");
            // Headers
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:application/json", "Authorization: Bearer $token"));

            // Data
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($acf));

            // Execution
            $response = curl_exec($ch);

            // Closing connection
            curl_close($ch);
        }
    } catch (Exception $e) {
        return $e->getMessage();
    }
}
