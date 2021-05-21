<?php

/**
 * The purpose of this file is to get tasks from Click Up and filter by assigned persons and companies
 */

// team: 1380008
// space_marketingsaftaler: 8860795
// folder_demoKunde: 27770443
// list_marketingsaftaler: 57095312

include_once '../cors.php';
include_once '../config.php';


$page = 0;
$filteredTasks = [];
set_time_limit(0);

function fetchTasksFromClickUp()
{
    global $page, $filteredTasks;

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
    $ch = curl_init("https://api.clickup.com/api/v2/list/57095312/task?archived=false&page=$page");
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

        if ($status == "afventer data fra kunden" || $status == "modtager data fra kunden") {
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
