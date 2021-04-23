<?php
// team: 1380008
// space_holder100: 6610314
// folder_kundeoverblik: 17358509
// list_kundekontakter: 38186322

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
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:6736916_f6214088e72af5c764e8c970b5aa7063c7dcf32f'));

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
