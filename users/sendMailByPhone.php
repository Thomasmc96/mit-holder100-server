<?php

/**
 * The purpose of this file is to send a mail to a user with a pincode by their phonenumber
 */
include_once '../cors.php';
include_once '../getToken.php';
include_once '../config.php';

$phone = "";

if (isset($_POST['phone']) && !empty($_POST['phone'])) {
    $phone = $_POST['phone'];
}

$ch = curl_init(HOSTNAME . '/wordpress/wp-json/wp/v2/users?per_page=1000');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Headers
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

// Execution
$response = curl_exec($ch);

// Closing connection
curl_close($ch);

foreach (json_decode($response) as $user) {
    if (!empty($user->acf->user_fields_phone)) {

        $userPhone = $user->acf->user_fields_phone;

        if ($userPhone == $phone) {
            $userEmail = $user->user_email;
            $userName = $user->name;
            $userId = $user->id;
        }
    }
}
if (!empty($userEmail)) {
    $token = getToken();

    $pincode = rand(pow(10, 4 - 1), pow(10, 4) - 1);
    $pincodeData = array(
        'password' => (string)$pincode
    );

    $ch = curl_init(HOSTNAME . "/wordpress/wp-json/wp/v2/users/$userId");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Headers
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:application/json", "Authorization: Bearer $token"));

    // Data
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($pincodeData));

    // // Execution
    $response = curl_exec($ch);

    // Closing connection
    curl_close($ch);


    $to = $userEmail;
    $subject = "Pinkode til Mit Holder 100";
    $message = "Hej $userName <br><br>
                    Din pinkode til Mit Holder 100 er: <b>$pincode</b> <br><br>
                    Du kan skifte din kode inde i Mit Holder 100 <br><br>
                    Hilsen Holder 100";
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Reply-To: service@holder100.dk" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "Organization: Holder 100 ApS" . "\r\n";
    $headers .= "X-Priority: 3" . "\r\n";
    $headers .= 'From: DIN HOLDER 100 ROBoT <mit@holder100.dk>' . "\r\n";

    // mail($to, $subject, $message, $headers);

    echo json_encode($result = [
        'status' => 200,
        'message' => 'success',
    ]);
} else {
    echo json_encode($result = [
        'status' => 400,
        'message' => 'No user with given phonenumber',
    ]);
}
