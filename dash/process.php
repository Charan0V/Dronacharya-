<?php
// Receives form input, calls Flask API with constructed prompt, parses result, saves to CSV, then redirects to dashboard
session_start();
require_once '../auth/check_auth.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$user = $isLoggedIn ? getCurrentUser() : null;

// Basic config
$apiUrl = 'http://127.0.0.1:5000/greet'; // Flask endpoint from api.py
$dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
$csvFile = $dataDir . DIRECTORY_SEPARATOR . 'roadmap.csv';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: index.php');
  exit;
}

$topic = trim($_POST['topic'] ?? '');
$extra = trim($_POST['extra'] ?? '');

if ($topic === '') {
  header('Location: index.php');
  exit;
}

// Ensure data directory exists
if (!is_dir($dataDir)) {
  @mkdir($dataDir, 0777, true);
}

// Build the instruction for the API
$instruction = 'Generate a roadmap for ' . $topic . ' just the text nothing elese within 10 just text dont add other message in the form of reply u create';
if ($extra !== '') {
  $instruction .= ' Extra: ' . $extra;
}

// Call the Flask API
$payload = json_encode([ 'name' => $instruction ]);

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'Content-Type: application/json',
  'Content-Length: ' . strlen($payload)
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($response === false || $httpCode !== 200) {
  $msg = 'API error: ' . ($curlErr ?: 'HTTP ' . $httpCode);
  die($msg);
}

$json = json_decode($response, true);
$raw = $json['reply'] ?? '';

// Extract quoted titles: "Title 1","Title 2",...
preg_match_all('/"([^"]+)"/', $raw, $matches);
$titles = $matches[1] ?? [];

// Fallback: if nothing matched, try splitting by commas and trimming quotes
if (empty($titles)) {
  $parts = array_map('trim', explode(',', $raw));
  $titles = array_map(function ($p) {
    return trim($p, " \"'\n\r\t");
  }, array_filter($parts));
}

// If still empty, seed with a default basic React roadmap
if (empty($titles)) {
  $titles = [
    'Introduction to Web Development',
    'HTML Basics',
    'CSS Fundamentals',
    'JavaScript Essentials',
    'Responsive Design',
    'Version Control with Git',
    'Basic DOM Manipulation',
    'Forms and Validation',
    'Introduction to Web Hosting',
    'Basics of Debugging'
  ];
}

// If user is logged in, create a course; otherwise save to general roadmap
if ($isLoggedIn && $user) {
  // Create course directory and files
  $courseId = time() . '_' . rand(1000, 9999);
  $courseDir = __DIR__ . DIRECTORY_SEPARATOR . 'courses' . DIRECTORY_SEPARATOR . $courseId;
  
  if (!is_dir($courseDir)) {
    mkdir($courseDir, 0777, true);
  }
  
  // Create course info file
  $courseInfo = [
    'id' => $courseId,
    'name' => $topic,
    'created_at' => date('Y-m-d H:i:s'),
    'user_id' => $user['id'],
    'status' => 'new',
    'progress' => 0
  ];
  
  file_put_contents($courseDir . DIRECTORY_SEPARATOR . 'course_info.json', json_encode($courseInfo, JSON_PRETTY_PRINT));
  
  // Save roadmap to course-specific CSV
  $courseCsvFile = $courseDir . DIRECTORY_SEPARATOR . 'roadmap.csv';
  $fp = fopen($courseCsvFile, 'w');
  fputcsv($fp, ['title', 'content']); // Header
  
  foreach ($titles as $title) {
    $content = $title; // per spec: just the text as content for now
    fputcsv($fp, [$title, $content]);
  }
  fclose($fp);
  
  // Redirect to course dashboard
  header('Location: course_dashboard.php?course_id=' . $courseId);
  exit;
} else {
  // Save to general roadmap CSV (for non-logged in users)
  $fp = fopen($csvFile, file_exists($csvFile) ? 'a' : 'w');
  if (filesize($csvFile) === 0) {
    fputcsv($fp, ['title', 'content']);
  }

  foreach ($titles as $title) {
    $content = $title; // per spec: just the text as content for now
    fputcsv($fp, [$title, $content]);
  }
  fclose($fp);

  header('Location: dashboard.php');
  exit;
}


