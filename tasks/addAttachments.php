<?php
include_once '../cors.php';
include_once '../config.php';

$taskId = "";
$files = [];
if(isset($_POST['taskId']) && !empty($_POST['taskId'])){
    $taskId = $_POST['taskId'];
    for ($i=0; $i < count($_FILES['file']['name']); $i++) { 
        $attachment = [];
        $attachment['filename'] = $_FILES['file']['name'][$i];
        $attachment['attachment'] = new CURLFILE($_FILES['file']['tmp_name'][$i]);

        // echo $_FILES['file']['name'][$i];
        if(!empty($attachment)){

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.clickup.com/api/v2/task/$taskId/attachment",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $attachment,
                CURLOPT_HTTPHEADER => array(
                'Authorization: '.CLICKUPTOKEN.'',
                'Content-Type: multipart/form-data'
                ),
            ));
            
            $response = curl_exec($curl);
            
            // Closing connection
            curl_close($curl);

            echo $response;
        }
    }
}
   