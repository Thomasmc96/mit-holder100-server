<?php
include_once '../cors.php';
include_once '../config.php';
include_once './changeStatus.php';

$taskId = "";
$files = [];
if (isset($_POST['taskId']) && !empty($_POST['taskId'])) {
    $taskId = $_POST['taskId'];
    for ($i = 0; $i < count($_FILES['file']['name']); $i++) {
        $attachment = [];
        $attachment['filename'] = $_FILES['file']['name'][$i];
        $attachment['attachment'] = new CURLFILE($_FILES['file']['tmp_name'][$i]);
        $fileComment = $_POST['comment'][$i];
        $fileTags = $_POST['tags'][$i];
        $username = $_POST['name'][$i];
        $status = $_POST['status'][$i];

        if (!empty($attachment)) {

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
                    'Authorization: ' . CLICKUPTOKEN . '',
                    'Content-Type: multipart/form-data'
                ),
            ));

            $response = curl_exec($curl);

            // Closing connection
            curl_close($curl);
            // echo $response;
            $response = json_decode($response, true);

            if (!empty($response['id']) && $status == 'afventer data fra kunden') {
                changeStatus($taskId);
            }

            if (!empty($response['title'])) {
                $title = $response['title'];

                $comment = [];
                if (empty($fileComment) || $fileComment == "undefined") {
                    if(empty($fileTags) || $fileTags == "undefined"){
                        $comment['comment_text'] = "$username har tiløjet en ny fil med titlen \"$title\" uden kommentar eller tags tilknyttet.";
                    }else {
                        $comment['comment_text'] = "$username har tiløjet en ny fil med titlen \"$title\" uden kommentar tilknyttet, men med følgende tags:\n".trim($fileTags). "";
                        
                    }
                } else {
                    if(empty($fileTags) || $fileTags == "undefined"){
                        $comment['comment_text'] = "$username har tiløjet en ny fil med titlen \"$title\". En kommentar er tilknyttet og lyder:\n\n\"$fileComment\"";

                    }else{
                        $comment['comment_text'] = "$username har tiløjet en ny fil med titlen \"$title\". En kommentar er tilknyttet og lyder:\n\n\"$fileComment\"\n\nFølgende tags er desuden tilknyttet:\n".trim($fileTags)."";
                    }
                }
                $comment['assignee'] = $_POST['assignee'];
                $comment['notify_all'] = true;

                // URL
                $ch = curl_init("https://api.clickup.com/api/v2/task/$taskId/comment");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                // Data
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($comment));

                // Headers
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:' . CLICKUPTOKEN));

                // Execution
                $response = curl_exec($ch);

                echo $response;

                // Closing connection
                curl_close($ch);

                
               
            }
        }
    }
}
