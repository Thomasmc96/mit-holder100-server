<?php
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

$phonenumbers = [];
$userIdAndEmail = [];
// Pushing every user from db with a phonenumber to $phonenumbers
foreach (json_decode($response) as $user) {
    if (!empty($user->acf->user_fields_phone)) {
        array_push($phonenumbers, $user->acf->user_fields_phone);
    }
    array_push($userIdAndEmail, ['ID' => $user->id, 'email' => $user->user_email]);
}
// print_r($userIdAndEmail);
foreach ($clientsWithEmail as $client) {
    try {
        $userId = $client->id;
        $user = [];
        $acf = [];

        $randomString = substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(10 / strlen($x)))), 1, 10);
        $user['password'] = $randomString;

        $name = $client->name;
        $nameExplode = explode("-", $name, 2);
        $name = $nameExplode[0];
        $user['name'] = $name;

        $username = $client->name;

        // Removes everything but letters
        $username = preg_replace('/[^A-Za-z0-9]/', '', $username);

        $user['username'] = $username;
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

        // New user
        if (empty($acf['fields']['user_fields_phone']) || (!empty($acf['fields']['user_fields_phone']) && !in_array($acf['fields']['user_fields_phone'], $phonenumbers))) {

            $ch = curl_init(HOSTNAME . '/wordpress/wp-json/wp/v2/users');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Data
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($user));

            // Headers
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:application/json", "Authorization: Bearer $token"));

            // Execution
            $response = curl_exec($ch);

            // Closing connection
            curl_close($ch);

            // Updating the newly added uses's acf fields with the id from the response
            if (!empty(json_decode($response)->id)) {
                $newUserId = json_decode($response)->id;

                // ACF
                $ch = curl_init(HOSTNAME . "/wordpress/wp-json/acf/v3/users/$newUserId");
                // Headers
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:application/json", "Authorization: Bearer $token"));

                // Data
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($acf));

                // Execution
                $response = curl_exec($ch);

                // Closing connection
                curl_close($ch);
            }
        }
        // else if ($key = array_search($user['email'], array_column($userIdAndEmail, 'email'))) {

        //     $existingUserId = $userIdAndEmail[$key]['ID'];
        //     // unset($user['password']);
        //     // unset($user['name']);
        //     // unset($user['username']);

        //     // // User
        //     // $ch = curl_init(HOSTNAME . "/wordpress/wp-json/wp/v2/users/$existingUserId");
        //     // // Headers
        //     // curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:application/json", "Authorization: Bearer $token"));

        //     // // Data
        //     // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($user));

        //     // // Execution
        //     // $response = curl_exec($ch);

        //     // ACF
        //     $ch = curl_init(HOSTNAME . "/wordpress/wp-json/acf/v3/users/$existingUserId");
        //     // Headers
        //     curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:application/json", "Authorization: Bearer $token"));

        //     // Data
        //     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($acf));

        //     // Execution
        //     $response = curl_exec($ch);

        //     // Closing connection
        //     curl_close($ch);
        // }
    } catch (Exception $e) {
        return $e->getMessage();
    }
}
