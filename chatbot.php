<?php
session_start();
include 'db_connect.php';
include 'ai_config.php';

header('Content-Type: application/json');

$userMessage = isset($_POST['message']) ? trim($_POST['message']) : "";

if ($userMessage == "") {
  echo json_encode(["reply" => "Please type a question."]);
  exit;
}

$currentUser = isset($_SESSION['profileName']) ? $_SESSION['profileName'] : "a visitor";

$systemContext = "You are the helpful AI assistant for Skill Connect, a college platform for MIT CSN (Maharashtra Institute of Technology Chhatrapati Sambhajinagar) students to list skills they can teach, skills they want to learn, and connect with each other. Key features: students create a profile with their branch, year of study, and skills (comma-separated, multiple allowed). They browse other students, use 'Find your perfect match' to find complementary skill swaps, send connection requests, and once accepted, can see each other's contact info, message each other, and rate each other. The current user is named '$currentUser'. Answer questions about how to use the site, or give friendly, brief advice about skills/learning if asked. Keep answers short (2-4 sentences), friendly, and relevant to a college student. If asked something totally unrelated to the platform or learning, politely redirect to topics you can help with.";

$fullPrompt = $systemContext . "\n\nUser question: " . $userMessage;

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent?key=" . GEMINI_API_KEY;

$data = [
  "contents" => [
    [
      "parts" => [
        ["text" => $fullPrompt]
      ]
    ]
  ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $httpCode != 200) {
  echo json_encode(["reply" => "Sorry, I'm having trouble connecting right now. Please try again in a moment."]);
  exit;
}

$result = json_decode($response, true);
$reply = $result['candidates'][0]['content']['parts'][0]['text'] ?? "Sorry, I couldn't generate a response.";

echo json_encode(["reply" => trim($reply)]);
?>