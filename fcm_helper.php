<?php

function sendPushNotification($user_fcm_token, $title, $body) {
    // IMPORTANT: Get this key from your Firebase Console
    // Project Settings > Cloud Messaging > Server key (under Cloud Messaging API (Legacy))
    $serverKey = 'BIPVT74lZxAl9QVN09so4z1JuoBvj5Y-5OKjCp4kAt9FNJJSyeBra0XVfb6dJrym6N51j5fpHDeR0T2S70QHqrw';

    if (empty($user_fcm_token)) {
        return 'FCM token is empty, cannot send notification.';
    }

    $notification = [
        'title' => $title,
        'body' => $body,
        'sound' => 'default'
    ];

    $payload = [
        'to' => $user_fcm_token,
        'notification' => $notification
    ];

    $headers = [
        'Authorization: key=' . $serverKey,
        'Content-Type: application/json'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $result = curl_exec($ch);
    if ($result === FALSE) {
        $error = 'Curl failed: ' . curl_error($ch);
        curl_close($ch);
        return $error;
    }

    curl_close($ch);
    return $result;
}

?>