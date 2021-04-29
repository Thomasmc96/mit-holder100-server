<?php
// team: 1380008
// space_holder100: 6610314
// folder_kundeoverblik: 17358509
// list_kundekontakter: 38186322
include_once '../config.php';

$page = 0;
$clientsWithEmail = [];
set_time_limit(0);

function fetchUserFromClickUp()
{
    global $page, $clientsWithEmail;

    // URL
    $ch = curl_init("https://api.clickup.com/api/v2/list/38186322/task?archived=false&page=$page");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Headers
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:' . CLICKUPTOKEN));

    // Execution
    $response = curl_exec($ch);

    // Closing connection
    curl_close($ch);

    // Filtering on result
    foreach (json_decode($response)->tasks as $client) {
        foreach ($client->custom_fields as $custom_field) {
            if ($custom_field->name == "Email" && !empty($custom_field->value)) {
                array_push($clientsWithEmail, $client);
            }
        };
    }
    $page++;
    if (!empty(json_decode($response)->tasks)) {
        fetchUserFromClickUp();
    }
}
fetchUserFromClickUp();
