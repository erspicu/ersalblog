<?php
require_once 'auth.php';
requireLogin();

// 檢查是否為 POST 請求且具備 CSRF Token
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    echo json_encode(array('error' => 'Invalid CSRF Token.'));
    exit;
}

// 確保 PHP 執行時間足夠長
@set_time_limit(240);

header('Content-Type: application/json');

if (!isset($aiConfig) || $aiConfig['enabled'] !== true) {
    echo json_encode(array('error' => __('ai_disabled')));
    exit;
}

$content = isset($_POST['content']) ? $_POST['content'] : '';
$tasks = isset($_POST['tasks']) ? explode(',', $_POST['tasks']) : array();

if (empty($content)) {
    echo json_encode(array('error' => 'Content is empty.'));
    exit;
}

/**
 * 呼叫 Gemini API
 */
function callGemini($apiKey, $modelId, $tasks, $content) {
    // 統一使用 v1beta 以確保 response_schema 與 system_instruction 運作
    $version = 'v1beta';
    
    // 確保模型 ID 格式正確 (例如 models/gemini-1.5-flash)
    $modelClean = str_replace('models/', '', $modelId);
    $modelFull = 'models/' . $modelClean;
    
    $url = "https://generativelanguage.googleapis.com/" . $version . "/" . $modelFull . ":generateContent?key=" . $apiKey;

    // --- 紀錄 API 呼叫網址 (Debug 用，遮罩 API Key) ---
    $logFile = __DIR__ . '/../debug.txt';
    
    // 確保時區為 UTC+8
    $dt = new DateTime('now', new DateTimeZone('Asia/Taipei'));
    $timestamp = $dt->format("Y-m-d H:i:s");
    
    $maskedUrl = "https://generativelanguage.googleapis.com/" . $version . "/" . $modelFull . ":generateContent?key=********";
    $logLine = "[$timestamp] AI API Call: $maskedUrl\n";
    
    @file_put_contents($logFile, $logLine, FILE_APPEND);

    // 建立 JSON 結構清單
    $required = array();
    if (in_array('title', $tasks)) $required[] = "title";
    if (in_array('filename', $tasks)) $required[] = "filename";
    if (in_array('desc', $tasks)) $required[] = "description";
    if (in_array('refine', $tasks)) $required[] = "refined_content";
    if (in_array('tags', $tasks)) $required[] = "tags";

    $roleText = "你是一位資深 SEO 專家與部落格總編輯。請根據用戶勾選的任務輸出結構化 JSON 數據。嚴格遵守繁體中文輸出，且不包含任何解釋文字。";
    $userText = "分析以下文章內容並完成任務 (" . implode(', ', $required) . ")：\n\n" . mb_substr($content, 0, 6000, 'UTF-8');

    // 準備進階 Payload (v1beta 規格)
    $payload = array(
        "contents" => array(
            array(
                "role" => "user",
                "parts" => array(array("text" => $userText))
            )
        ),
        "system_instruction" => array(
            "parts" => array(array("text" => $roleText))
        ),
        "generationConfig" => array(
            "temperature" => 0.7,
            "response_mime_type" => "application/json"
        )
    );

    // 動態建立 Schema
    $properties = array();
    if (in_array('title', $tasks)) $properties['title'] = array("type" => "string");
    if (in_array('filename', $tasks)) $properties['filename'] = array("type" => "string");
    if (in_array('desc', $tasks)) $properties['description'] = array("type" => "string");
    if (in_array('refine', $tasks)) $properties['refined_content'] = array("type" => "string");
    if (in_array('tags', $tasks)) $properties['tags'] = array("type" => "array", "items" => array("type" => "string"));
    
    $payload["generationConfig"]["response_schema"] = array(
        "type" => "object",
        "properties" => $properties,
        "required" => $required
    );

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_TIMEOUT, 180);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) return array('code' => 0, 'error' => $error);
    return array('code' => $httpCode, 'body' => $response);
}

$apiKey = $aiConfig['api_key'];
$primaryModel = $aiConfig['model'];

$res = callGemini($apiKey, $primaryModel, $tasks, $content);

// Fallback logic
if ($res['code'] !== 200 && $primaryModel !== 'gemini-3-flash-preview') {
    $res = callGemini($apiKey, 'gemini-3-flash-preview', $tasks, $content);
}

if ($res['code'] !== 200) {
    $errRes = json_decode($res['body'], true);
    $errMsg = isset($errRes['error']['message']) ? $errRes['error']['message'] : 'HTTP ' . $res['code'];
    echo json_encode(array('error' => 'API Error: ' . $errMsg, 'url_debug' => 'v1beta used'));
    exit;
}

$result = json_decode($res['body'], true);
if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    echo $result['candidates'][0]['content']['parts'][0]['text'];
} else {
    echo json_encode(array('error' => 'Unexpected Response Format', 'raw' => $res['body']));
}
