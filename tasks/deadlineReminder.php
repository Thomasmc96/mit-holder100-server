<?php

/**
 * The purpose of this file is to send mails to "kunde kontakt" on a task if the deadline is overdue
 */
include_once '../cors.php';
include_once '../config.php';

// The first hardcoded index is required for a php search function to work
$tasksAndAssociatedUsersWithDeadlineToday = [['taskName' => ['test'], 'username' => 'test', 'userEmail' => 'test']];
$tasksAndAssociatedUsersWithDeadlineTomorrow = [['taskName' => ['test'], 'username' => 'test', 'userEmail' => 'test']];
$page = 0;
set_time_limit(0);

function fetchTasksFromClickUp()
{
    global $page, $tasksAndAssociatedUsersWithDeadlineToday, $tasksAndAssociatedUsersWithDeadlineTomorrow;

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
            $deadlineToday = $task->due_date / 1000;
            $deadlineTomorrow = $deadlineToday - 86400;
            $currentTime = time();

            if ($deadlineTomorrow < $currentTime && $task->status->status == "afventer data fra kunden") {

                $taskName = $task->name;
                $custom_fields = $task->custom_fields;

                foreach ($custom_fields as $custom_field) {
                    if ($custom_field->name == "Kunde kontakt" && isset($custom_field->value)) {
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

                            // Checks if the checkbox on "Modtag notifikation" is checked
                            $recieveNotification = false;
                            foreach ($user_custom_fields as $user_custom_field) {
                                if ($user_custom_field->name == "Modtag notifikation" && isset($user_custom_field->value)) {
                                    $recieveNotification = true;
                                }
                            }
                            foreach ($user_custom_fields as $user_custom_field) {
                                if ($recieveNotification) {
                                    if ($user_custom_field->name == "Email" && !empty($user_custom_field->value)) {
                                        $userEmail = $user_custom_field->value;
                                        $username = $user->name;
                                        $nameExplode = explode("-", $username, 2);
                                        $username = $nameExplode[0];
                                        if ($deadlineToday < $currentTime) {
                                            if (!empty($tasksAndAssociatedUsersWithDeadlineToday)) {
                                                if ($key = array_search($userEmail, array_column($tasksAndAssociatedUsersWithDeadlineToday, 'userEmail'))) {
                                                    array_push($tasksAndAssociatedUsersWithDeadlineToday[$key]['taskName'], $taskName);
                                                } else {
                                                    array_push($tasksAndAssociatedUsersWithDeadlineToday, ['taskName' => [$taskName], 'username' => $username, 'userEmail' => $userEmail]);
                                                }
                                            } else {
                                                array_push($tasksAndAssociatedUsersWithDeadlineToday, ['taskName' => [$taskName], 'username' => $username, 'userEmail' => $userEmail]);
                                            }
                                        } else if ($deadlineTomorrow < $currentTime && $deadlineToday > $currentTime) {
                                            if (!empty($tasksAndAssociatedUsersWithDeadlineTomorrow)) {
                                                if ($key = array_search($userEmail, array_column($tasksAndAssociatedUsersWithDeadlineTomorrow, 'userEmail'))) {
                                                    array_push($tasksAndAssociatedUsersWithDeadlineTomorrow[$key]['taskName'], $taskName);
                                                } else {
                                                    array_push($tasksAndAssociatedUsersWithDeadlineTomorrow, ['taskName' => [$taskName], 'username' => $username, 'userEmail' => $userEmail]);
                                                }
                                            } else {
                                                array_push($tasksAndAssociatedUsersWithDeadlineTomorrow, ['taskName' => [$taskName], 'username' => $username, 'userEmail' => $userEmail]);
                                            }
                                        }
                                    }
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
for ($i = 0; $i < count($tasksAndAssociatedUsersWithDeadlineToday); $i++) {
    if ($i !== 0) {
        $taskName = $tasksAndAssociatedUsersWithDeadlineToday[$i]['taskName'];
        $userEmail = $tasksAndAssociatedUsersWithDeadlineToday[$i]['userEmail'];
        $username = $tasksAndAssociatedUsersWithDeadlineToday[$i]['username'];
        $taskString = "";
        foreach ($taskName as $task) {
            $taskString .= "<li>$task</li>";
        }

        $to = $userEmail;
        $subject = "Overskredet deadline i Mit Holder 100";

        $message = "Hej $username <br><br>
                    Du har følgende opgaver i Mit Holder 100 med en overskredet deadline:
                    <ul>$taskString</ul>
                    Besøg eventuelt appen <a href='https://mit.holder100.dk'>her</a>.<br><br>
                    <b>Mvh. Holder 100 ApS</b> <br><br>
                    <i>Din digitale partner</i> <br><br>
                    <i>+45 33 60 76 08</i> <br><br>
                    <i><a href='https://holder100.dk'>www.holder100.dk</a></i> <br><br>
                    <i><a href='https://outlook.office365.com/owa/calendar/Holder100ApS@holder100.dk/bookings/'>Book en tid</a></i> <br><br>";
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Reply-To: service@holder100.dk" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "Organization: Holder 100 ApS" . "\r\n";
        $headers .= "X-Priority: 3" . "\r\n";
        $headers .= 'From: DIN HOLDER 100 ROBoT <mit@holder100.dk>' . "\r\n";

        mail($to, $subject, $message, $headers);
    }
}
for ($i = 0; $i < count($tasksAndAssociatedUsersWithDeadlineTomorrow); $i++) {
    if ($i !== 0) {
        $taskName = $tasksAndAssociatedUsersWithDeadlineTomorrow[$i]['taskName'];
        $userEmail = $tasksAndAssociatedUsersWithDeadlineTomorrow[$i]['userEmail'];
        $username = $tasksAndAssociatedUsersWithDeadlineTomorrow[$i]['username'];
        $taskString = "";
        foreach ($taskName as $task) {
            $taskString .= "<li>$task</li>";
        }

        $to = $userEmail;
        $subject = "Kommende deadline i Mit Holder 100";

        $message = "Hej $username <br><br>
                    Du har følgende opgaver i Mit Holder 100 som overskrider deadline i morgen:
                    <ul>$taskString</ul>
                    Besøg eventuelt appen <a href='https://mit.holder100.dk'>her</a>.<br><br><br>
                    <b>Mvh. Holder 100 ApS</b> <br><br>
                    <i>Din digitale partner</i> <br><br>
                    <i>+45 33 60 76 08</i> <br><br>
                    <i><a href='https://holder100.dk'>www.holder100.dk</a></i> <br><br>
                    <i><a href='https://outlook.office365.com/owa/calendar/Holder100ApS@holder100.dk/bookings/'>Book en tid</a></i> <br><br>";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Reply-To: service@holder100.dk" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "Organization: Holder 100 ApS" . "\r\n";
        $headers .= "X-Priority: 3" . "\r\n";
        $headers .= 'From: DIN HOLDER 100 ROBoT <mit@holder100.dk>' . "\r\n";

        mail($to, $subject, $message, $headers);
    }
}
