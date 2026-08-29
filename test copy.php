<?php
// ── CREDENTIALS ──────────────────────────────────────────
$consumerKey = 'n4CPpQZo1qZy036yY852oYIIxdSMxKDN';
$clientSecret = 'CLGc8Fo0vkJumlcT';
$customerCode = '33104660082';

// ── STEP 1: GET ACCESS TOKEN ─────────────────────────────
$tokenCurl = curl_init();

curl_setopt_array($tokenCurl, [
  CURLOPT_URL => 'https://api.maersk.com/customer-identity/oauth/v2/access_token',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => http_build_query([
    'grant_type' => 'client_credentials',
    'client_id' => $consumerKey,
    'client_secret' => $clientSecret,
    // No scope = Maersk returns ALL scopes your key is entitled to
  ]),
  CURLOPT_HTTPHEADER => [
    "Consumer-Key: $consumerKey",
    "Content-Type: application/x-www-form-urlencoded",
    "Cache-Control: no-cache",
  ],
]);

$tokenResponse = curl_exec($tokenCurl);
$tokenHttpCode = curl_getinfo($tokenCurl, CURLINFO_HTTP_CODE);

if (curl_errno($tokenCurl)) {
  die('Token Error: ' . curl_error($tokenCurl));
}
curl_close($tokenCurl);

echo "=== TOKEN RESPONSE ===\n";
echo "Status Code: $tokenHttpCode\n";

if ($tokenHttpCode !== 200) {
  die("Token request failed: $tokenResponse\n");
}

$tokenData = json_decode($tokenResponse, true);
$accessToken = $tokenData['access_token'] ?? null;
$grantedScope = $tokenData['scope'] ?? 'none';

if (!$accessToken) {
  die("No access token in response: $tokenResponse\n");
}

echo "Granted Scopes: $grantedScope\n";
echo "Expires In: " . ($tokenData['expires_in'] ?? '?') . "s\n";
echo "\n=== FULL TOKEN RESPONSE (debug) ===\n";
echo json_encode($tokenData, JSON_PRETTY_PRINT) . "\n\n";


// ── STEP 2: CALL BILL OF LADING API ─────────────────────
// Replace with your actual B/L number e.g. MAEU1234567890
$transportDocumentReference = '265305368'; // ← UPDATE THIS
// $transportDocumentReference = '262393478'; // ← UPDATE THIS

$apiUrl = "https://api.maersk.com/ocean/document/v1/transport-documents/{$transportDocumentReference}";

$apiCurl = curl_init();

curl_setopt_array($apiCurl, [
  CURLOPT_URL => $apiUrl,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => [
    "Authorization: Bearer $accessToken",
    "Consumer-Key: $consumerKey",
    "API-Version: 1",
    "Accept: application/json",
  ],
]);

$apiResponse = curl_exec($apiCurl);
$apiHttpCode = curl_getinfo($apiCurl, CURLINFO_HTTP_CODE);

if (curl_errno($apiCurl)) {
  echo 'API Error: ' . curl_error($apiCurl);
}
curl_close($apiCurl);

echo "=== BILL OF LADING RESPONSE ===\n";
echo "URL: $apiUrl\n";
echo "Status Code: $apiHttpCode\n";
echo "Response: " . json_encode(json_decode($apiResponse), JSON_PRETTY_PRINT) . "\n";
