<?php
include_once '../cors.php';
include_once '../config.php';

$page = 0;
$filteredTasks = [];
$companyId = "";

if(isset($_GET['company']) && !empty($_GET['company'])){
  $companyId = $_GET['company'];
}

function getEmbeddedLinks(){

  global $page, $filteredTasks, $companyId;

  $curl = curl_init();
  
  curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.clickup.com/api/v2/list/59168976/task?archived=false&page=$page",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => array(
      'Content-Type: application/json',
      'Authorization: 6736916_f6214088e72af5c764e8c970b5aa7063c7dcf32f'
    ),
  ));
  
  $response = curl_exec($curl);
  
  curl_close($curl);
  
  foreach (json_decode($response)->tasks as $task) {
    $typeMatch = false;
    $companyMatch = false;

    foreach ($task->custom_fields as $custom_field) {
      // if($custom_field->name === "Link type" && $custom_field->value === 0){
      //   if(!in_array($task, $filteredTasks)){
      //     $typeMatch = true;
      //   }
      // }
      if($custom_field->name === "Virksomhed"){
        foreach($custom_field->value as $company){
          if($company->id === $companyId && !in_array($task, $filteredTasks)){
            $companyMatch = true;
          }
        }
      }
    } 
    if($companyMatch === true){
      array_push($filteredTasks, $task);
    } 
  }
  
  $page++;
  if (!empty(json_decode($response)->tasks)) {
    getEmbeddedLinks();    
  }
}
getEmbeddedLinks();
echo json_encode($filteredTasks);