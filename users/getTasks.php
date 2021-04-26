<?php
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

    $clickUpClientId = "hh3tjc";
    $clickUpCompanies = "hn9v97";

    $companyArray = explode(" ", $clickUpCompanies);

    // URL
    $ch = curl_init("https://api.clickup.com/api/v2/list/57095312/task?archived=false&page=$page");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Headers
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:6736916_f6214088e72af5c764e8c970b5aa7063c7dcf32f'));

    // Execution
    $response = curl_exec($ch);

    // echo $response;

    // Closing connection
    curl_close($ch);

     // Filtering on result
//      foreach (json_decode($response)->tasks as $task) {
//          if($task->status->status ="afventer data fra kunden"){
//              foreach ($task->custom_fields as $custom_field) {
//                  // Kunde kontakt
//                  if ($custom_field->name == "Kunde kontakt") {
//                      if(empty($custom_field->value)){
//                         // Kunde (firma)
//                         if($custom_field->name == "Kunde" && !empty($custom_field->value)){

//                             foreach($custom_field->value as $companyId){
//                                 if(in_array($companyId, $companyArray)){
                                    
//                                     array_push($filteredTasks, $task);
//                                 }
//                             }
//                         }
//                      }
//                     else if(!empty($custom_field->value)){
//                         foreach ($custom_field->value as $clientId){
//                             if($clickUpClientId == $clientId->id){
//                                 array_push($filteredTasks, $task);
//                             }
//                         }
//                     }
//                  }
                 
//          }
//     }
// }
     foreach (json_decode($response)->tasks as $task) {

        $companyMatch = false;
        $idMatch = false;
        $assignedAnother = false;

         if($task->status->status == "afventer data fra kunden"){
             foreach ($task->custom_fields as $custom_field) {
                 // Kunde kontakt
                if ($custom_field->name == "Kunde kontakt" && !empty($custom_field->value)) {
                        foreach ($custom_field->value as $clientId){
                            if($clickUpClientId == $clientId->id && !in_array($task, $filteredTasks)){
                                // array_push($filteredTasks, $task);
                                $idMatch = true;
                            } else {
                                $assignedAnother = true;
                            }
                        }
                 } 
                 // Kunde (firma)
                else if($custom_field->name == "Kunde" && !empty($custom_field->value)){
                    foreach($custom_field->value as $companyId){
                        if(in_array($companyId->id, $companyArray) && !in_array($task, $filteredTasks)){
                            // array_push($filteredTasks, $task);
                            $companyMatch = true;
                        }
                    }       
                }
            }
        }

        if($idMatch === true || ($companyMatch === true && $assignedAnother === false)) {
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