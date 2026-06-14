<?php
// logic/ai_processor.php
require_once __DIR__ . '/config.php';

function analyzeImageWithGemini($imagePath) {
    if (!defined('GEMINI_API_KEY') || empty(trim(GEMINI_API_KEY))) {
        error_log("GEMINI ERROR: API Key is missing or empty.");
        return null; 
    }
    
    $cleanKey = trim(GEMINI_API_KEY);
    $url = "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key=" . $cleanKey;

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
    $today = date('d/m/Y'); // השרת מעדכן את זה אוטומטית בכל יום!
    $prompt = "Analyze this food image for a sharing app. 
    CURRENT DATE: $today.
    
    INSTRUCTIONS:
    1. Extract the expiration date if visible (e.g., 06/06).
    2. Do NOT decide if the food is safe based on the date. Only check if it looks visually spoiled (mold, rot).
    3. If the food looks visually okay, set 'is_safe' to true.
    4. You MUST return ONLY a valid JSON object.
    
    Fields:
    {
      \"is_food\": (boolean),
      \"is_safe\": (boolean),
      \"label\": \"(Hebrew name)\",
      \"description\": \"(Hebrew tasty description)\",
      \"expiry_date\": \"(The date you see on the product)\",
      \"ai_feedback\": \"(Hebrew feedback)\"
    }";

    $data = [
        "contents" => [
            ["parts" => [
                ["text" => $prompt],
                ["inline_data" => ["mime_type" => $mimeType, "data" => $imageData]]
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
        }
    }
    return null;
}