<?php
// logic/ai_processor.php
require_once __DIR__ . '/config.php';

function analyzeImageWithGemini($imagePath) {
    if (!defined('GEMINI_API_KEY') || empty(trim(GEMINI_API_KEY))) {
        error_log("GEMINI ERROR: API Key is missing or empty.");
        return null; 
    }
    
    $cleanKey = trim(GEMINI_API_KEY);
    $url = "https://generativelanguage.googleapis.com/v1/models/gemini-3.1-flash-lite:generateContent?key=" . $cleanKey;

    if (!file_exists($imagePath)) {
        error_log("GEMINI ERROR: File not found at path: " . $imagePath);
        return null;
    }

    $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
    $mimeType = in_array($extension, ['png', 'webp']) ? "image/$extension" : 'image/jpeg';
    
    $fileContent = @file_get_contents($imagePath);
    if ($fileContent === false) return null;
    $imageData = base64_encode($fileContent);

    // 4. PROMPT דינמי וחכם - לא צריך לעדכן אותו לעולם
    $today = date('d/m/Y'); 
    $todayShort = date('d/m/y'); 
    $prompt = "Analyze this food image for a sharing app. 
    CURRENT DATE: $today (also represented as $todayShort).
    
    INSTRUCTIONS:
    1. Search carefully for any printed or stamped expiration date on the packaging (e.g., stamped on the top flap, neck, bottom, or back of the product, such as '13/06/26').
    2. Compare the extracted expiration date with the CURRENT DATE. Note that 2-digit years like '26' represent the year '2026'. If the expiration date is in the past, the food is expired and unsafe! In this case, you MUST set 'is_safe' to false and write a clear message in 'safety_warning' (in Hebrew) explaining that the food has expired.
    3. Check if the food looks visually spoiled (mold, rot, decay). If so, set 'is_safe' to false and write in 'safety_warning' (in Hebrew) that the food is visually spoiled.
    4. If the food is a valid food product, is NOT expired, and does NOT look visually spoiled, set 'is_safe' to true.
    5. You MUST return ONLY a valid JSON object.
    
    Fields:
    {
      \"is_food\": (boolean),
      \"is_safe\": (boolean),
      \"label\": \"(Hebrew name)\",
      \"description\": \"(Hebrew tasty description)\",
      \"expiry_date\": \"(The date you see on the product, format: YYYY-MM-DD or DD/MM/YYYY or as read)\",
      \"ai_feedback\": \"(Hebrew feedback)\",
      \"safety_warning\": \"(Hebrew warning if unsafe/expired, otherwise empty)\"
    }";

    $data = [
        "contents" => [
            ["parts" => [
                ["text" => $prompt],
                ["inlineData" => ["mimeType" => $mimeType, "data" => $imageData]]
            ]]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        $logFile = __DIR__ . '/../uploads/ai_debug.log';
        $logMsg = date('[Y-m-d H:i:s] ') . "GEMINI API FAILURE! HTTP Code: " . $httpCode . " Response: " . $response . "\n";
        @file_put_contents($logFile, $logMsg, FILE_APPEND);
        error_log("GEMINI API FAILURE! HTTP Code: " . $httpCode . " Response: " . $response);
        return null;
    }

    $decodedResponse = json_decode($response, true);
    if (isset($decodedResponse['candidates'][0]['content']['parts'][0]['text'])) {
        $aiResultRaw = $decodedResponse['candidates'][0]['content']['parts'][0]['text'];
        
        // חילוץ JSON חסין
        if (preg_match('/\{.*\}/s', $aiResultRaw, $matches)) {
            $jsonToDecode = $matches[0];
        } else {
            $jsonToDecode = $aiResultRaw;
        }

        $finalData = json_decode($jsonToDecode, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $finalData;
        } else {
            $logFile = __DIR__ . '/../uploads/ai_debug.log';
            $logMsg = date('[Y-m-d H:i:s] ') . "JSON DECODE ERROR: " . json_last_error_msg() . " Raw Text: " . $aiResultRaw . "\n";
            @file_put_contents($logFile, $logMsg, FILE_APPEND);
        }
    } else {
        $logFile = __DIR__ . '/../uploads/ai_debug.log';
        $logMsg = date('[Y-m-d H:i:s] ') . "NO TEXT IN CANDIDATES: " . $response . "\n";
        @file_put_contents($logFile, $logMsg, FILE_APPEND);
    }
    return null;
}