<?php
// ── CREDENTIALS ──────────────────────────────────────────
$consumerKey = 'n4CPpQZo1qZy036yY852oYIIxdSMxKDN';
$clientSecret = 'CLGc8Fo0vkJumlcT';
$customerCode = '33104660082';

// ── CANDIDATE SCOPE NAMES ─────────────────────────────────
// Trying all plausible names based on Maersk's observed scope pattern:
//   ten.ocean.cc.{custCode}.pr.{ProductName}.{permission}
$candidateScopes = [
    "ten.ocean.cc.{$customerCode}.pr.OceanDocument.readonly",
    "ten.ocean.cc.{$customerCode}.pr.TransportDocument.readonly",
    "ten.ocean.cc.{$customerCode}.pr.CarrierBillOfLading.readonly",
    "ten.ocean.cc.{$customerCode}.pr.BillOfLading.read",
    "ten.ocean.cc.{$customerCode}.pr.OceanBillOfLading.readonly",
    "ten.ocean.cc.{$customerCode}.pr.OceanCarrierBillOfLading.readonly",
    "ten.ocean.cc.{$customerCode}.pr.CarrierBol.readonly",
    "ten.ocean.cc.{$customerCode}.pr.DCsaBillOfLading.readonly",
    "ten.ocean.cc.{$customerCode}.pr.DcsaBillOfLading.readonly",
    "ten.ocean.cc.{$customerCode}.pr.Document.readonly",
];

function tryScope($consumerKey, $clientSecret, $scope)
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.maersk.com/customer-identity/oauth/v2/access_token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'client_credentials',
            'client_id' => $consumerKey,
            'client_secret' => $clientSecret,
            'scope' => $scope,
        ]),
        CURLOPT_HTTPHEADER => [
            "Consumer-Key: $consumerKey",
            "Content-Type: application/x-www-form-urlencoded",
        ],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => json_decode($body, true)];
}

echo "=== SCOPE DISCOVERY ===\n\n";
$found = null;

foreach ($candidateScopes as $scope) {
    $result = tryScope($consumerKey, $clientSecret, $scope);

    if ($result['code'] === 200) {
        echo "✅ FOUND! Scope works: $scope\n";
        echo "   Token starts with: " . substr($result['body']['access_token'] ?? '', 0, 30) . "...\n";
        $found = $scope;
        break;
    } else {
        $err = $result['body']['error'] ?? '?';
        $desc = $result['body']['error_description'] ?? '?';
        echo "❌ [$err] $scope\n    → $desc\n";
    }
    usleep(300000); // 300ms pause between requests
}

echo "\n";
if ($found) {
    echo "=== USE THIS SCOPE IN YOUR TOKEN REQUEST ===\n'scope' => \"$found\"\n";
} else {
    echo "=== NO MATCHING SCOPE FOUND ===\n";
    echo "The Bill of Lading API scope has not been provisioned for this consumer key.\n";
    echo "Please contact: xdmaerskapisteam@maersk.com\n";
    echo "Include your Consumer Key: n4CPpQZo1qZy036yY852oYIIxdSMxKDN\n";
    echo "And Customer Code: 33104660082\n";
}
