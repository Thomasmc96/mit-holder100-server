<?php

/**
 * The purpose of this file is to get historical tasks from Click Up and filter by assigned persons and companies
 */

// team: 1380008
// space_marketingsaftaler: 8860795
// folder_demoKunde: 27770443
// list_marketingsaftaler: 57095312

include_once '../getToken.php';
include_once '../cors.php';
include_once '../config.php';

$token = getToken();

$page = 0;
$filteredTasks = [];
set_time_limit(0);
$spaceId = "";

$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => HOSTNAME . "/wordpress/wp-json/wp/v2/spaces",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ),
));

$response = curl_exec($curl);

curl_close($curl);

$spaceResponse = json_decode($response);

foreach ($spaceResponse as $space) {
    $spaceId .= "&space_ids%5B%5D=" . $space->acf->space_fields_id;
}

function fetchTasksFromClickUp()
{
    global $page, $filteredTasks, $spaceId;

    $clickUpClientId = "";
    $clickUpCompanies = "";

    if (isset($_GET['clickUpClientId']) && !empty($_GET['clickUpClientId'])) {
        $clickUpClientId = $_GET['clickUpClientId'];
    }
    if (isset($_GET['clickUpCompanies']) && !empty($_GET['clickUpCompanies'])) {
        $clickUpCompanies = $_GET['clickUpCompanies'];
    }

    $companyArray = explode(" ", $clickUpCompanies);

    // URL
    $ch = curl_init("https://api.clickup.com/api/v2/team/1380008/task?page=$page$spaceId");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Headers
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type:application/json',
        'Authorization:' . CLICKUPTOKEN
    ));

    // Execution
    $response = curl_exec($ch);

    // Closing connection
    curl_close($ch);

    foreach (json_decode($response)->tasks as $task) {
        $companyMatch = false;
        $idMatch = false;
        $assignedAnother = false;
        $status = $task->status->status;

        if ($status == "design modtaget - klar") {
            foreach ($task->custom_fields as $custom_field) {
                // Kunde kontakt
                if ($custom_field->name == "Kunde kontakt" && !empty($custom_field->value)) {
                    foreach ($custom_field->value as $clientId) {
                        if ($clickUpClientId == $clientId->id && !in_array($task, $filteredTasks)) {
                            $idMatch = true;
                        } else {
                            $assignedAnother = true;
                        }
                    }
                }
                // Kunde
                else if ($custom_field->name == "Kunde" && !empty($custom_field->value)) {
                    foreach ($custom_field->value as $companyId) {
                        if (in_array($companyId->id, $companyArray) && !in_array($task, $filteredTasks)) {
                            $companyMatch = true;
                        }
                    }
                }
            }
        }

        if ($idMatch === true || ($companyMatch === true && $assignedAnother === false)) {
            array_push($filteredTasks, $task);
        }
    }
    $page++;
    if (!empty(json_decode($response)->tasks)) {
        fetchTasksFromClickUp();
    }
}
fetchTasksFromClickUp();

echo json_encode($filteredTasks);
