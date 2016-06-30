<?php

$edan_server = 'http://edan.si.edu/';
$edan_tier_type = 1;
$edan_app_id = "YOUR_APP_ID";
$edan_auth_key = 'YOUR_SECRET_KEY';

$endpoint = "metadata/v1.0/metadata/search.htm";
$param_data = 'applicationId=QUOTIENTPROD&q=' . urlencode('*:*') . '&fqs=' . urlencode('["unit_code:AAADCD"]');

$POST = FALSE;

date_default_timezone_set("America/New_York");

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $edan_server . $endpoint. '?' . $param_data);

curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_HTTPHEADER, encodeHeader($edan_app_id, $edan_auth_key, $edan_tier_type, $param_data ));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLINFO_HEADER_OUT, 1);

$response = curl_exec($ch);
$info = curl_getinfo($ch);

print("<pre>");
print_r(json_decode($response));
print("</pre>");

print("<hr>");

print("<pre>");
print_r($info);
print("</pre>");


/**
       * Creates the header for the request to EDAN. Takes $uri, prepends a nonce, and appends
       * the date and appID key. Hashes as sha1() and base64_encode() the result.
       * @param uri The URI (string) to be hashed and encoded.
       * @returns Array containing all the elements and signed header value
       */
  function encodeHeader($app_id, $edan_key, $auth_type, $uri) {
    $ipnonce = getNonce(); // Alternatively you could do: get_nonce(8, '-'.get_nonce(8));
    $date = date('Y-m-d H:i:s');

    $return = array(
      'X-AppId: ' . $app_id,
      'X-RequestDate: ' . $date,
      'X-AppVersion: ' . 'EDANInterface-0.10.1'
    );

    // For signed/T2 requests
    if ($auth_type === 1) {
      $auth = "{$ipnonce}\n{$uri}\n{$date}\n{$edan_key}";
      $content = base64_encode(sha1($auth));
      $return[] = 'X-Nonce: ' . $ipnonce;
      $return[] = 'X-AuthContent: ' . $content;
    }

    return $return;
  }

  function getNonce($length = 15, $prefix = '') {
  $password = "";
  $possible = "0123456789abcdefghijklmnopqrstuvwxyz";

  $i = 0;

  while ($i < $length) {
    $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);

    if (!strstr($password, $char)) {
      $password .= $char;
      $i++;
    }
  }

  return $prefix.$password;
}

?>