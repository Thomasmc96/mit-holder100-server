<?php
include_once '../cors.php';
include_once '../config.php';

$page = 0;
set_time_limit(0);

function fetchTasksFromClickUp()
{
    global $page;

    // URL
    $ch = curl_init("https://api.clickup.com/api/v2/list/57095312/task?archived=false&page=$page");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Headers
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:' . CLICKUPTOKEN));

    // Execution
    $tasksResponse = curl_exec($ch);

    // Closing connection
    curl_close($ch);


    foreach (json_decode($tasksResponse)->tasks as $task) {
        if (isset($task->due_date) && !empty($task->due_date)) {
            $deadline = $task->due_date / 1000;
            $currentTime = time();
            if ($deadline < $currentTime && $task->status->status == "afventer data fra kunden") {
                $taskName = $task->name;
                $custom_fields = $task->custom_fields;

                foreach ($custom_fields as $custom_field) {
                    /*Kommende cond. statement her med de kommende felt i Click Up */
                    $recieveNotification = true;
                    // if($custom_field->name == "Modtag notifikation" && isset($custom_field->value)){
                    //     $recieveNotification = true;
                    // }
                    if ($custom_field->name == "Kunde kontakt" && isset($custom_field->value) && $recieveNotification) {
                        foreach ($custom_field->value as $value) {
                            $userId = $value->id;

                            // URL
                            $ch = curl_init("https://api.clickup.com/api/v2/task/$userId");
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                            // Headers
                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:' . CLICKUPTOKEN));

                            // Execution
                            $response = curl_exec($ch);

                            // Closing connection
                            curl_close($ch);

                            $user = json_decode($response);
                            $user_custom_fields = $user->custom_fields;
                            foreach ($user_custom_fields as $user_custom_field) {

                                if ($user_custom_field->name == "Email" && !empty($user_custom_field->value)) {
                                    $userEmail = $user_custom_field->value;

                                    $name = $user->name;
                                    $nameExplode = explode("-", $name, 2);
                                    $name = $nameExplode[0];

                                    $to = $userEmail;
                                    $subject = "Overskredet deadline i Mit Holder 100";
                                    $message = "Hej $name <br><br>
                                                Du har en opgave i Mit Holder 100 kaldet \"$taskName\" med en overskredet deadline.<br><br>
                                                Besøg eventuelt appen <a href='https://mit.holder100.dk'>her</a>.<br><br>
                                                Hilsen Holder 100";

                                    $headers = "MIME-Version: 1.0" . "\r\n";
                                    $headers .= "Reply-To: service@holder100.dk" . "\r\n";
                                    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                                    $headers .= "Organization: Holder 100 ApS" . "\r\n";
                                    $headers .= "X-Priority: 3" . "\r\n";
                                    $headers .= 'From: DIN HOLDER 100 ROBoT <mit@holder100.dk>' . "\r\n";

                                    mail($to, $subject, $message, $headers);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    $page++;
    if (!empty(json_decode($tasksResponse)->tasks)) {
        fetchTasksFromClickUp();
    }
}
fetchTasksFromClickUp();
