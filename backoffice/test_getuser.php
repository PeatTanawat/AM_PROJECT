<?php
$ch = curl_init('http://localhost/am/core.php?request_state=listUser&request_function=GetUser');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['user_id' => 1]); // Assume user 1 exists
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP Code: $httpcode\n";
echo "Response: $response\n";
