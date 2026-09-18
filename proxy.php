<?php
// CORS হেডার সেট করুন
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS রিকোয়েস্ট হ্যান্ডেল করুন
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// number প্যারামিটার নিন
$number = isset($_GET['number']) ? $_GET['number'] : '';

if (empty($number)) {
    echo json_encode(['error' => 'নম্বর প্রদান করুন']);
    exit;
}

// ============================================
// NEW API: darktoolshub.site
// ============================================
$api_url = "https://darktoolshub.site/bot/trueapi.php?num=" . urlencode($number);

// cURL দিয়ে API কল করুন
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// cURL error চেক করুন
if ($curl_error) {
    echo json_encode(['error' => "cURL Error: " . $curl_error]);
    exit;
}

// HTTP status code চেক করুন
if ($http_code !== 200) {
    echo json_encode(['error' => "API ত্রুটি: HTTP $http_code"]);
    exit;
}

// রেসপন্স ফরম্যাট চেক করুন
$json_data = json_decode($response, true);

if ($json_data !== null) {
    echo json_encode($json_data);
} else {
    echo json_encode(['raw_response' => $response]);
}
?>