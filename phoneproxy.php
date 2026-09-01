<?php
// ============================================
// PHONE LOOKUP PROXY - STRONG VERSION
// API: https://mirajxheat.xyz/api/caller/?num=
// ============================================

// ===== STRONG CORS HEADERS =====
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=UTF-8');

// ===== HANDLE PREFLIGHT OPTIONS =====
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ===== GET NUMBER PARAMETER =====
$number = isset($_GET['number']) ? trim($_GET['number']) : '';

if (empty($number)) {
    echo json_encode(['error' => 'Please provide a phone number.']);
    exit;
}

// ===== CLEAN NUMBER =====
$number = preg_replace('/[^0-9+]/', '', $number);

// ============================================
// API URL - NEW API
// ============================================
$api_url = "https://mirajxheat.xyz/api/caller/?num=" . urlencode($number);

// ============================================
// STRONG cURL REQUEST
// ============================================
$ch = curl_init();

// Basic cURL options
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

// User Agent - real browser
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

// Headers
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json, text/plain, */*',
    'Accept-Language: en-US,en;q=0.9',
    'Accept-Encoding: gzip, deflate, br',
    'Connection: keep-alive',
    'Sec-Fetch-Dest: empty',
    'Sec-Fetch-Mode: cors',
    'Sec-Fetch-Site: cross-site'
]);

// Enable compression
curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate, br');

// ============================================
// EXECUTE cURL
// ============================================
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
$curl_info = curl_getinfo($ch);
curl_close($ch);

// ============================================
// DEBUG LOG (for troubleshooting)
// ============================================
$debug = [
    'api_url' => $api_url,
    'http_code' => $http_code,
    'curl_error' => $curl_error,
];

// ============================================
// CHECK cURL ERROR
// ============================================
if ($curl_error) {
    echo json_encode([
        'error' => 'Connection Error: ' . $curl_error,
        'debug' => $debug
    ]);
    exit;
}

// ============================================
// CHECK HTTP STATUS
// ============================================
if ($http_code !== 200) {
    echo json_encode([
        'error' => 'API Error: HTTP ' . $http_code,
        'debug' => $debug,
        'response_preview' => substr($response, 0, 300)
    ]);
    exit;
}

// ============================================
// PARSE JSON RESPONSE
// ============================================
$json_data = json_decode($response, true);

if ($json_data !== null) {
    // Check if API returned success false
    if (isset($json_data['success']) && $json_data['success'] === false) {
        echo json_encode([
            'error' => $json_data['message'] ?? 'API returned error',
            'debug' => $debug
        ]);
        exit;
    }
    
    // Success: Send JSON data
    echo json_encode($json_data);
} else {
    // If not JSON, send raw response
    echo json_encode([
        'error' => 'Invalid response from API (not JSON)',
        'debug' => $debug,
        'raw_response' => substr($response, 0, 500)
    ]);
}
?>