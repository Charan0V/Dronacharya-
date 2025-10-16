<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';

// Require authentication
requireAuth();

$user = getCurrentUser();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'generate_notes') {
        $lessonTitle = $_POST['lesson_title'] ?? '';
        $lessonContent = $_POST['lesson_content'] ?? '';
        $courseId = $_POST['course_id'] ?? '';
        
        if (empty($lessonTitle)) {
            echo json_encode(['success' => false, 'message' => 'Lesson title is required']);
            exit;
        }
        
        // Call AI API to generate notes
        $apiUrl = 'http://10.0.0.245:5000/greet';
        $prompt = "Create comprehensive study notes for the topic: " . $lessonTitle . ". " . $lessonContent . " Make it educational and well-structured within 200 words.";
        
        $payload = json_encode(['name' => $prompt]);
        
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
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($response === false) {
            echo json_encode(['success' => false, 'message' => 'CURL Error: ' . $curlError]);
            exit;
        }
        
        if ($httpCode !== 200) {
            echo json_encode(['success' => false, 'message' => 'HTTP Error: ' . $httpCode . ' - ' . $response]);
            exit;
        }
        
        $data = json_decode($response, true);
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON response: ' . $response]);
            exit;
        }
        
        $aiNotes = $data['message'] ?? $data['reply'] ?? 'Failed to generate notes';
        
        // Generate image URL from notes
        $imageUrl = null;
        try {
            // Convert notes to single string - remove all formatting and HTML
            $cleanedNotes = $aiNotes;
            $cleanedNotes = preg_replace('/\n/', ' ', $cleanedNotes);           // Replace newlines with spaces
            $cleanedNotes = preg_replace('/\r/', ' ', $cleanedNotes);           // Replace carriage returns with spaces
            $cleanedNotes = preg_replace('/\t/', ' ', $cleanedNotes);           // Replace tabs with spaces
            $cleanedNotes = preg_replace('/<br\s*\/?>/i', ' ', $cleanedNotes);  // Replace HTML line breaks with spaces
            $cleanedNotes = preg_replace('/<[^>]*>/', ' ', $cleanedNotes);      // Remove all HTML tags
            $cleanedNotes = str_replace('&nbsp;', ' ', $cleanedNotes);          // Replace HTML non-breaking spaces
            $cleanedNotes = str_replace('&amp;', '&', $cleanedNotes);           // Replace HTML entities
            $cleanedNotes = str_replace('&lt;', '<', $cleanedNotes);            // Replace HTML entities
            $cleanedNotes = str_replace('&gt;', '>', $cleanedNotes);            // Replace HTML entities
            $cleanedNotes = str_replace('&quot;', '"', $cleanedNotes);          // Replace HTML entities
            $cleanedNotes = str_replace('&#39;', "'", $cleanedNotes);           // Replace HTML entities
            $cleanedNotes = preg_replace('/\s+/', ' ', $cleanedNotes);          // Replace multiple spaces with single space
            $cleanedNotes = trim($cleanedNotes);                                // Remove leading/trailing spaces
            
            $imageResponse = file_get_contents('http://localhost:5000/greet', false, stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/json',
                    'content' => json_encode(['name' => $cleanedNotes])
                ]
            ]));
            
            if ($imageResponse) {
                $imageData = json_decode($imageResponse, true);
                if (isset($imageData['reply']) && !empty($imageData['reply'])) {
                    $imageUrl = $imageData['reply'];
                }
            }
        } catch (Exception $e) {
            error_log("Error generating image: " . $e->getMessage());
        }
        
        // Save notes to database
        try {
            $pdo = getConnection();
            
            // Check if notes already exist
            $stmt = $pdo->prepare("SELECT id FROM notes WHERE user_id = ? AND course_id = ? AND lesson_title = ?");
            $stmt->execute([$user['id'], $courseId, $lessonTitle]);
            $existingNote = $stmt->fetch();
            
            if ($existingNote) {
                // Update existing notes
                $stmt = $pdo->prepare("UPDATE notes SET ai_notes = ?, lesson_content = ?, image_url = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $result = $stmt->execute([$aiNotes, $lessonContent, $imageUrl, $existingNote['id']]);
            } else {
                // Insert new notes
                $stmt = $pdo->prepare("INSERT INTO notes (user_id, course_id, lesson_title, lesson_content, ai_notes, image_url) VALUES (?, ?, ?, ?, ?, ?)");
                $result = $stmt->execute([$user['id'], $courseId, $lessonTitle, $lessonContent, $aiNotes, $imageUrl]);
            }
            
            if ($result) {
                $message = $existingNote ? 'Notes updated successfully!' : 'Notes generated successfully!';
                echo json_encode(['success' => true, 'notes' => $aiNotes, 'image_url' => $imageUrl, 'message' => $message]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save notes to database']);
            }
        } catch(PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
        
    } elseif ($action === 'ask_question') {
        $question = $_POST['question'] ?? '';
        $lessonTitle = $_POST['lesson_title'] ?? '';
        $lessonContent = $_POST['lesson_content'] ?? '';
        
        if (empty($question)) {
            echo json_encode(['success' => false, 'message' => 'Question is required']);
            exit;
        }
        
        // Call AI API to answer question
        $apiUrl = 'http://10.0.0.245:5000/greet';
        $prompt = "Topic: " . $lessonTitle . ". Context: " . $lessonContent . ". Question: " . $question . ". Please provide a helpful answer within 150 words.";
        
        $payload = json_encode(['name' => $prompt]);
        
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
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($response === false) {
            echo json_encode(['success' => false, 'message' => 'CURL Error: ' . $curlError]);
            exit;
        }
        
        if ($httpCode !== 200) {
            echo json_encode(['success' => false, 'message' => 'HTTP Error: ' . $httpCode . ' - ' . $response]);
            exit;
        }
        
        $data = json_decode($response, true);
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON response: ' . $response]);
            exit;
        }
        
        $answer = $data['message'] ?? $data['reply'] ?? 'Failed to get answer';
        
        echo json_encode(['success' => true, 'answer' => $answer]);
        exit;
        
    } elseif ($action === 'load_notes') {
        $lessonTitle = $_POST['lesson_title'] ?? '';
        $courseId = $_POST['course_id'] ?? '';
        
        if (empty($lessonTitle) || empty($courseId)) {
            echo json_encode(['success' => false, 'message' => 'Lesson title and course ID are required']);
            exit;
        }
        
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("SELECT ai_notes, image_url FROM notes WHERE user_id = ? AND course_id = ? AND lesson_title = ?");
            $stmt->execute([$user['id'], $courseId, $lessonTitle]);
            $note = $stmt->fetch();
            
            if ($note && !empty($note['ai_notes'])) {
                echo json_encode([
                    'success' => true, 
                    'notes' => $note['ai_notes'],
                    'image_url' => $note['image_url'] ?? null
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No notes found']);
            }
        } catch(PDOException $e) {
            error_log("Error loading notes: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
        
    } elseif ($action === 'get_course_roadmap') {
        $courseId = $_POST['course_id'] ?? '';
        
        if (empty($courseId)) {
            echo json_encode(['success' => false, 'message' => 'Course ID is required']);
            exit;
        }
        
        try {
            $pdo = getConnection();
            
            // Get course roadmap from CSV file
            $courseDir = __DIR__ . DIRECTORY_SEPARATOR . 'courses' . DIRECTORY_SEPARATOR . $courseId;
            $roadmapFile = $courseDir . DIRECTORY_SEPARATOR . 'roadmap.csv';
            
            if (!file_exists($roadmapFile)) {
                echo json_encode(['success' => false, 'message' => 'Course roadmap not found']);
                exit;
            }
            
            $roadmap = [];
            if (($handle = fopen($roadmapFile, "r")) !== FALSE) {
                $header = fgetcsv($handle);
                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (isset($data[0]) && isset($data[1]) && !empty(trim($data[0]))) {
                        $roadmap[] = [
                            'title' => $data[0],
                            'content' => $data[1],
                            'status' => $data[2] ?? 'pending'
                        ];
                    }
                }
                fclose($handle);
            }
            
            // Find current lesson index
            $currentIndex = 0;
            if (isset($_POST['current_lesson_title'])) {
                $currentTitle = $_POST['current_lesson_title'];
                foreach ($roadmap as $index => $lesson) {
                    if ($lesson['title'] === $currentTitle) {
                        $currentIndex = $index;
                        break;
                    }
                }
            }
            
            echo json_encode(['success' => true, 'roadmap' => $roadmap, 'currentIndex' => $currentIndex]);
        } catch(PDOException $e) {
            error_log("Error loading course roadmap: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Load existing notes for current lesson
$currentNotes = '';
$lessonTitle = '';
$lessonContent = '';
$courseId = '';

// Get lesson data from session storage (passed via JavaScript)
$lessonData = null;
if (isset($_GET['lesson_data'])) {
    $lessonData = json_decode($_GET['lesson_data'], true);
}

// Load notes from database if we have lesson data
if ($lessonData) {
    $lessonTitle = $lessonData['title'] ?? '';
    $lessonContent = $lessonData['content'] ?? '';
    $courseId = $lessonData['courseId'] ?? '';
    
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT ai_notes FROM notes WHERE user_id = ? AND course_id = ? AND lesson_title = ?");
        $stmt->execute([$user['id'], $courseId, $lessonTitle]);
        $note = $stmt->fetch();
        
        if ($note && !empty($note['ai_notes'])) {
            $currentNotes = $note['ai_notes'];
        }
    } catch(PDOException $e) {
        error_log("Error loading notes: " . $e->getMessage());
        // Handle database error silently
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Module - Dronacharya</title>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Cormorant+Garamond:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700;800;900&family=Dancing+Script:wght@400;500;600;700&family=Crimson+Text:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Cormorant Garamond', serif;
            background: linear-gradient(135deg, #fdf6e3 0%, #fff8dc 50%, #f5deb3 100%);
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 80%, rgba(255, 215, 0, 0.1) 0%, transparent 50%), 
                        radial-gradient(circle at 80% 20%, rgba(139, 69, 19, 0.1) 0%, transparent 50%), 
                        radial-gradient(circle at 40% 40%, rgba(94, 11, 21, 0.05) 0%, transparent 50%);
            z-index: -1;
            pointer-events: none;
        }
        
        /* Header */
        .header-section {
            background: linear-gradient(135deg, #5e0b15 0%, #8b4513 50%, #a0522d 100%);
            padding: 2rem 0;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255, 215, 0, 0.2);
            border-bottom: 6px solid #deb887;
        }
        
        .main-title {
            font-family: 'Cinzel', serif;
            font-size: 3rem;
            font-weight: 800;
            color: #ffd700;
            text-shadow: 4px 4px 8px rgba(0,0,0,0.7), 0 0 30px rgba(255, 215, 0, 0.6);
            letter-spacing: 2px;
            margin: 0;
        }
        
        .course-info {
            color: #f5deb3;
            font-size: 1.1rem;
            margin-top: 0.5rem;
        }
        
        /* Navigation */
        .nav-custom {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid #deb887;
        }
        
        .nav-link {
            color: #5e0b15 !important;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            color: #ffd700 !important;
            transform: translateY(-2px);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            min-height: calc(100vh - 200px);
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #fffaf0, #ffe4b5);
            border-right: 3px solid #deb887;
            padding: 2rem 1rem;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: #5e0b15;
            margin-bottom: 1.5rem;
            text-align: center;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        
        .sidebar a {
            display: block;
            padding: 0.8rem 1rem;
            margin-bottom: 0.5rem;
            color: #5e0b15;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .sidebar a:hover {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #5e0b15;
            transform: translateX(5px);
            text-decoration: none;
        }
        
        .sidebar a i {
            margin-right: 0.5rem;
            width: 20px;
        }
        
        /* Content Area */
        .content-area {
            flex: 1;
            padding: 2rem;
            display: flex;
            flex-direction: column;
        }
        
        .lesson-header {
            background: linear-gradient(135deg, #fffaf0, #ffe4b5);
            border: 2px solid #deb887;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .lesson-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: #5e0b15;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .lesson-content {
            font-size: 1.1rem;
            color: #8b4513;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        
        .lesson-meta {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .lesson-number {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #5e0b15;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
        }
        
        .lesson-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #ffeaa7;
            color: #d63031;
        }
        
        .status-in-progress {
            background: #74b9ff;
            color: white;
        }
        
        .status-completed {
            background: #00b894;
            color: white;
        }
        
        /* Notes Section */
        .notes-section {
            background: linear-gradient(135deg, #fffaf0, #ffe4b5);
            border: 2px solid #deb887;
            border-radius: 15px;
            padding: 2rem;
            flex: 1;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .notes-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: #5e0b15;
            margin-bottom: 1.5rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        
        .notes-content {
            background: white;
            border: 1px solid #deb887;
            border-radius: 10px;
            padding: 1.5rem;
            min-height: 200px;
            font-size: 1rem;
            line-height: 1.6;
            color: #5e0b15;
            white-space: pre-wrap;
            overflow-y: auto;
            max-height: 300px;
        }
        
        .notes-actions {
            margin-top: 1rem;
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .btn-generate-notes {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-generate-notes:hover {
            background: linear-gradient(135deg, #20c997, #28a745);
            transform: translateY(-2px);
        }
        
        .btn-generate-notes:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-nav {
            background: linear-gradient(135deg, #a0522d, #8b4513);
            color: white;
            border: 2px solid #ffd700;
            padding: 0.6rem 1rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .btn-nav:hover {
            background: linear-gradient(135deg, #8b4513, #a0522d);
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-nav:disabled {
            background: #6c757d;
            border-color: #6c757d;
            cursor: not-allowed;
            transform: none;
            opacity: 0.6;
        }
        
        .btn-prev {
            background: linear-gradient(135deg, #6c757d, #495057);
        }
        
        .btn-prev:hover {
            background: linear-gradient(135deg, #495057, #6c757d);
        }
        
        .btn-next {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        
        .btn-next:hover {
            background: linear-gradient(135deg, #20c997, #28a745);
        }
        
        /* Audio Player Styles */
        .audio-player-section {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 2px solid #dee2e6;
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .audio-player-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .audio-title {
            font-family: 'Cinzel', serif;
            font-size: 1.2rem;
            font-weight: 600;
            color: #5e0b15;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .audio-controls {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .btn-audio-control {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
            border: 2px solid #ffd700;
            padding: 0.5rem 0.8rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            min-width: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-audio-control:hover {
            background: linear-gradient(135deg, #495057, #6c757d);
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-audio-control:active {
            transform: translateY(0);
        }
        
        .btn-play-pause.playing {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        
        .btn-play-pause.playing:hover {
            background: linear-gradient(135deg, #20c997, #28a745);
        }
        
        .btn-stop {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        
        .btn-stop:hover {
            background: linear-gradient(135deg, #c82333, #dc3545);
        }
        
        .btn-speed {
            background: linear-gradient(135deg, #007bff, #0056b3);
            min-width: 50px;
        }
        
        .btn-speed:hover {
            background: linear-gradient(135deg, #0056b3, #007bff);
        }
        
        .btn-speed.active {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #5e0b15;
            border-color: #5e0b15;
        }
        
        .btn-speed.active:hover {
            background: linear-gradient(135deg, #ffed4e, #ffd700);
        }
        
        .audio-progress-container {
            margin-bottom: 1rem;
        }
        
        .audio-progress {
            background: #dee2e6;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 0.5rem;
            cursor: pointer;
        }
        
        .audio-progress-bar {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            height: 100%;
            width: 0%;
            transition: width 0.1s ease;
            border-radius: 4px;
        }
        
        .audio-time {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        /* Image Section */
        #img {
            margin: 1.5rem 0;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 2px solid #dee2e6;
            border-radius: 15px;
            padding: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .image-container {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }
        
        .camera-stream {
            width: 200px;
            height: 150px;
            background: #000;
            border-radius: 10px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            flex-shrink: 0;
        }
        
        .camera-stream video {
            width: 100%;
            height: 100%;
            border-radius: 8px;
            object-fit: cover;
        }
        
        .camera-controls {
            position: absolute;
            top: 8px;
            right: 8px;
            z-index: 10;
        }
        
        .camera-controls button {
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .camera-controls button:hover {
            background: rgba(0, 0, 0, 0.9);
            transform: scale(1.1);
        }
        
        .camera-controls button:active {
            transform: scale(0.95);
        }
        
        .generated-image {
            flex: 1;
            text-align: center;
        }
        
        .image-controls {
            margin-bottom: 1rem;
            text-align: center;
            display: none;
        }
        
        .btn-generate-image {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
            border: 2px solid #ffd700;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-generate-image:hover {
            background: linear-gradient(135deg, #138496, #17a2b8);
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-generate-image:active {
            transform: translateY(0);
        }
        
        .btn-generate-image:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            opacity: 0.6;
        }
        
        #generatedImage {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        #generatedImage:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        /* Responsive design for mobile */
        @media (max-width: 768px) {
            .image-container {
                flex-direction: column;
                align-items: center;
            }
            
            .camera-stream {
                width: 100%;
                max-width: 300px;
                height: 200px;
            }
        }
        
        
        /* Q&A Section */
        .qa-section {
            background: linear-gradient(135deg, #fffaf0, #ffe4b5);
            border: 2px solid #deb887;
            border-radius: 15px;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .qa-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: #5e0b15;
            margin-bottom: 1.5rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        
        .chat-input-container {
            display: flex;
            align-items: center;
            border: 2px solid #deb887;
            border-radius: 25px;
            padding: 5px 10px;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        
        .chat-input-container input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 16px;
            padding: 10px;
            border-radius: 25px;
            background: transparent;
        }
        
        .chat-input-container button {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 20px;
            margin-left: 10px;
            padding: 5px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .chat-input-container button:hover {
            background: rgba(255, 215, 0, 0.2);
        }
        
        .qa-response {
            background: white;
            border: 1px solid #deb887;
            border-radius: 10px;
            padding: 1rem;
            min-height: 100px;
            font-size: 1rem;
            line-height: 1.6;
            color: #5e0b15;
            white-space: pre-wrap;
            display: none;
        }
        
        .qa-response.show {
            display: block;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 1rem;
            }
            
            .lesson-title {
                font-size: 2rem;
            }
            
            .content-area {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg nav-custom">
        <div class="container">
            <a class="navbar-brand fw-bold" href="course_manager.php" style="font-family: 'Cinzel', serif; color: #5e0b15;">
                <i class="fas fa-arrow-left"></i> Back to Courses
            </a>
            <div class="ms-auto">
                <span class="text-muted me-3">Welcome, <?php echo htmlspecialchars($user['name']); ?>!</span>
                <a href="home.php" class="nav-link">Home</a>
                <button class="btn btn-outline-danger btn-sm ms-2" onclick="logout()">Logout</button>
            </div>
        </div>
    </nav>

    <!-- Header Section -->
    <div class="header-section">
        <div class="container text-center">
            <h1 class="main-title" id="lessonTitle">Learning Module</h1>
            <p class="course-info" id="courseInfo">Course: Loading...</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Sidebar -->
        <div class="sidebar">
            <h2>Menu</h2>
            <a href="home.php"><i class="fas fa-home"></i> Home</a>
            <a href="course_manager.php"><i class="fas fa-graduation-cap"></i> Course Manager</a>
            <a href="javascript:history.back()"><i class="fas fa-map"></i> Back to Course Map</a>
            <a href="profile.html"><i class="fas fa-user"></i> Profile</a>
            <a href="language.html"><i class="fas fa-language"></i> Language Switch</a>
            <a href="#" onclick="logout()" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Lesson Header -->
            <div class="lesson-header">
                <h2 class="lesson-title" id="lessonTitleContent">Lesson Title</h2>
                <div class="lesson-content" id="lessonContent">Lesson content will appear here...</div>
                <div class="lesson-meta">
                    <span class="lesson-number" id="lessonNumber">Lesson 1</span>
                    <span class="lesson-status status-pending" id="lessonStatus">Pending</span>
                </div>
            </div>
        <div id="img">
            <div class="image-container">
                <div class="camera-stream">
                    <video id="cameraVideo" autoplay muted style="width: 100%; height: 100%; border-radius: 8px; object-fit: cover;"></video>
                    <div class="camera-controls">
                        <button id="startCameraBtn" onclick="startCamera()" title="Start Camera">
                            <i class="fas fa-video"></i>
                        </button>
                        <button id="stopCameraBtn" onclick="stopCamera()" title="Stop Camera" style="display: none;">
                            <i class="fas fa-video-slash"></i>
                        </button>
                    </div>
                </div>
                <div class="generated-image">
                    <div class="image-controls">
                        <button id="generateImageBtn" onclick="generateImageFromNotes()" class="btn-generate-image" title="Generate Image from Notes">
                            <i class="fas fa-image"></i> Generate Image
                        </button>
                    </div>
                    <img id="generatedImage" src="" alt="Generated Image" style="max-width: 100%; height: auto; display: none; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                </div>
            </div>
        </div>
            <!-- Notes Section -->
            <div class="notes-section">
                <h3 class="notes-title">
                    <i class="fas fa-sticky-note"></i> AI-Generated Study Notes
                </h3>
                <div class="notes-content" id="notesContent">
                    <?php if ($currentNotes): ?>
                        <?php echo htmlspecialchars($currentNotes); ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-sticky-note"></i><br>
                            No notes generated yet. Click "Generate Notes" to create AI-powered study notes for this lesson.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="notes-actions">
                    <div class="d-flex gap-2 align-items-center">
                        <button class="btn-nav btn-prev" id="prevLessonBtn" onclick="goToPreviousLesson()" title="Previous Lesson">
                            <i class="fas fa-chevron-left"></i> Previous
                        </button>
                        <button class="btn-generate-notes" id="generateNotesBtn" onclick="generateNotes()">
                            <i class="fas fa-magic"></i> Generate Notes
                        </button>
                        <button class="btn-nav btn-next" id="nextLessonBtn" onclick="goToNextLesson()" title="Next Lesson">
                            Next <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <div class="mt-2">
                        <span class="text-muted" id="notesStatus"></span>
                    </div>
                </div>
                
                <!-- Audio Player Section -->
                <div class="audio-player-section" id="audioPlayerSection" style="display: none;">
                    <div class="audio-player-header">
                        <h4 class="audio-title">
                            <i class="fas fa-volume-up"></i> Audio Notes
                        </h4>
                        <div class="audio-controls">
                            <button class="btn-audio-control btn-play-pause" id="playPauseBtn" onclick="toggleAudio()" title="Play/Pause">
                                <i class="fas fa-play" id="playPauseIcon"></i>
                            </button>
                            <button class="btn-audio-control btn-stop" id="stopBtn" onclick="stopAudio()" title="Stop">
                                <i class="fas fa-stop"></i>
                            </button>
                            <button class="btn-audio-control btn-speed" id="speed1xBtn" onclick="setSpeed(1)" title="1x Speed">
                                1x
                            </button>
                            <button class="btn-audio-control btn-speed" id="speed1_5xBtn" onclick="setSpeed(1.5)" title="1.5x Speed">
                                1.5x
                            </button>
                            <button class="btn-audio-control btn-speed" id="speed2xBtn" onclick="setSpeed(2)" title="2x Speed">
                                2x
                            </button>
                        </div>
                    </div>
                    <div class="audio-progress-container">
                        <div class="audio-progress" id="audioProgress">
                            <div class="audio-progress-bar" id="audioProgressBar"></div>
                        </div>
                        <div class="audio-time">
                            <span id="currentTime">0:00</span> / <span id="totalTime">0:00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Q&A Section -->
            <div class="qa-section">
                <h3 class="qa-title">
                    <i class="fas fa-question-circle"></i> Ask Questions
                </h3>
                <div class="chat-input-container">
                    <input type="text" id="questionInput" placeholder="Ask a question about this lesson...">
                    <button id="micButton" onclick="startVoiceInput()" title="Voice Input">
                        <i class="fas fa-microphone"></i>
                    </button>
                    <button id="askButton" onclick="askQuestion()" title="Ask Question">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                <div class="qa-response" id="qaResponse">
                    <!-- AI answers will appear here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let currentLesson = null;
        let speechRecognition = null;
        let courseRoadmap = [];
        let currentLessonIndex = 0;
        let audioPlayer = null;
        let isPlaying = false;
        let currentSpeed = 1;
        let countdownTimer = null;
        let autoPlayTimer = null;
        let cameraStream = null;

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            loadLessonData();
            setupSpeechRecognition();
        });

        // Cleanup when page is unloaded
        window.addEventListener('beforeunload', function() {
            stopCamera();
        });

        function loadLessonData() {
            // Try to get lesson data from session storage
            const lessonData = sessionStorage.getItem('currentLesson');
            
            if (lessonData) {
                currentLesson = JSON.parse(lessonData);
                displayLesson(currentLesson);
                loadExistingNotes(); // Load existing notes from database
                loadCourseRoadmap(); // Load course roadmap for navigation
            } else {
                // Fallback: show placeholder content
                document.getElementById('lessonTitle').textContent = 'Learning Module';
                document.getElementById('courseInfo').textContent = 'Course: No lesson selected';
                document.getElementById('lessonTitleContent').textContent = 'Welcome to Learning Module';
                document.getElementById('lessonContent').textContent = 'Please select a lesson from your course roadmap to begin learning.';
            }
        }

        function loadCourseRoadmap() {
            if (!currentLesson) return;

            const formData = new FormData();
            formData.append('action', 'get_course_roadmap');
            formData.append('course_id', currentLesson.courseId);
            formData.append('current_lesson_title', currentLesson.title);
            
            fetch('learning.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    courseRoadmap = data.roadmap;
                    currentLessonIndex = data.currentIndex;
                    updateNavigationButtons();
                }
            })
            .catch(error => {
                console.error('Error loading course roadmap:', error);
            });
        }

        function loadExistingNotes() {
            if (!currentLesson) return;

            const formData = new FormData();
            formData.append('action', 'load_notes');
            formData.append('lesson_title', currentLesson.title);
            formData.append('course_id', currentLesson.courseId);
            
            fetch('learning.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.notes) {
                    document.getElementById('notesContent').innerHTML = data.notes;
                    document.getElementById('notesStatus').textContent = 'Notes loaded from previous session';
                    document.getElementById('notesStatus').className = 'text-success';
                    
                    // Show audio player and start auto-play countdown
                    showAudioPlayer();
                    startAutoPlayCountdown();
                    
                    // Load saved image if available
                    if (data.image_url) {
                        loadSavedImage(data.image_url);
                    }
                }
            })
            .catch(error => {
                console.error('Error loading existing notes:', error);
            });
        }

        function showAudioPlayer() {
            const audioSection = document.getElementById('audioPlayerSection');
            const imageControls = document.querySelector('.image-controls');
            const notesContent = document.getElementById('notesContent').textContent.trim();
            
            if (notesContent && notesContent !== 'No notes generated yet. Click "Generate Notes" to create AI-powered study notes for this lesson.') {
                audioSection.style.display = 'block';
                imageControls.style.display = 'block';
                // Initialize with 1x speed selected
                setSpeed(1);
                // Generate image from notes
                generateImageFromNotes(notesContent);
            }
        }

        function loadSavedImage(imageUrl) {
            const imageElement = document.getElementById('generatedImage');
            
            if (imageUrl && imageUrl.trim() !== '') {
                imageElement.src = imageUrl;
                imageElement.alt = 'Generated image for lesson notes';
                imageElement.style.display = 'block';
            } else {
                imageElement.style.display = 'none';
            }
        }

        async function generateImageFromNotes(notesContent = null) {
            const imageElement = document.getElementById('generatedImage');
            const generateBtn = document.getElementById('generateImageBtn');
            
            // Get notes content if not provided
            if (!notesContent) {
                notesContent = document.getElementById('notesContent').textContent.trim();
            }
            
            // Check if notes are available
            if (!notesContent || notesContent === 'No notes generated yet. Click "Generate Notes" to create AI-powered study notes for this lesson.') {
                alert('Please generate notes first before creating an image.');
                return;
            }
            
            // Convert notes to single string - replace all formatting with spaces
            const cleanedNotesContent = notesContent
                .replace(/\n/g, ' ')           // Replace newlines with spaces
                .replace(/\r/g, ' ')           // Replace carriage returns with spaces
                .replace(/\t/g, ' ')           // Replace tabs with spaces
                .replace(/<br\s*\/?>/gi, ' ')  // Replace HTML line breaks with spaces
                .replace(/<[^>]*>/g, ' ')      // Remove all HTML tags
                .replace(/&nbsp;/g, ' ')       // Replace HTML non-breaking spaces
                .replace(/&amp;/g, '&')        // Replace HTML entities
                .replace(/&lt;/g, '<')         // Replace HTML entities
                .replace(/&gt;/g, '>')         // Replace HTML entities
                .replace(/&quot;/g, '"')       // Replace HTML entities
                .replace(/&#39;/g, "'")        // Replace HTML entities
                .replace(/\s+/g, ' ')          // Replace multiple spaces with single space
                .trim();                       // Remove leading/trailing spaces
            
            // Log the cleaned string for debugging
            console.log('Cleaned notes content for image generation:', cleanedNotesContent);
            
            // Disable button and show loading state
            generateBtn.disabled = true;
            generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            
            // Show loading state
            imageElement.style.display = 'block';
            imageElement.src = '';
            imageElement.alt = 'Generating image...';
            
            try {
                const response = await fetch('http://localhost:5000/greet', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ name: cleanedNotesContent })
                });

                const data = await response.json();

                if (response.ok && data.reply) {
                    // Assuming the Flask endpoint returns an image URL in data.reply
                    imageElement.src = data.reply;
                    imageElement.alt = 'Generated image for lesson notes';
                    imageElement.style.display = 'block';
                } else {
                    console.error('Error generating image:', data.error || 'Unknown error');
                    imageElement.style.display = 'none';
                    alert('Failed to generate image. Please try again.');
                }
            } catch (error) {
                console.error('Error sending request to Flask:', error);
                imageElement.style.display = 'none';
                alert('Error connecting to image generation service. Please try again.');
            } finally {
                // Re-enable button
                generateBtn.disabled = false;
                generateBtn.innerHTML = '<i class="fas fa-image"></i> Generate Image';
            }
        }

        function startAutoPlayCountdown() {
            let countdown = 5;
            
            // Clear any existing timers
            if (countdownTimer) clearInterval(countdownTimer);
            if (autoPlayTimer) clearTimeout(autoPlayTimer);
            
            countdownTimer = setInterval(() => {
                countdown--;
                
                if (countdown < 0) {
                    clearInterval(countdownTimer);
                    startAudioPlayback();
                }
            }, 1000);
        }

        function startAudioPlayback() {
            const notesContent = document.getElementById('notesContent').textContent.trim();
            
            if (!notesContent || notesContent === 'No notes generated yet. Click "Generate Notes" to create AI-powered study notes for this lesson.') {
                return;
            }
            
            // Initialize speech synthesis
            if ('speechSynthesis' in window) {
                // Stop any existing speech
                speechSynthesis.cancel();
                
                // Create new utterance
                const utterance = new SpeechSynthesisUtterance(notesContent);
                utterance.rate = currentSpeed;
                utterance.pitch = 1;
                utterance.volume = 0.8;
                
                // Set up event listeners
                utterance.onstart = function() {
                    isPlaying = true;
                    updateAudioUI();
                    updateProgress();
                };
                
                utterance.onend = function() {
                    isPlaying = false;
                    updateAudioUI();
                };
                
                utterance.onerror = function(event) {
                    console.error('Speech synthesis error:', event.error);
                    isPlaying = false;
                    updateAudioUI();
                };
                
                // Start speaking
                speechSynthesis.speak(utterance);
                audioPlayer = utterance;
                
            } else {
                alert('Text-to-speech is not supported in this browser.');
            }
        }

        function toggleAudio() {
            if (isPlaying) {
                stopAudio();
            } else {
                startAudioPlayback();
            }
        }

        function stopAudio() {
            if (speechSynthesis) {
                speechSynthesis.cancel();
            }
            isPlaying = false;
            updateAudioUI();
            resetProgress();
        }

        function setSpeed(speed) {
            currentSpeed = speed;
            
            // Update button states
            document.querySelectorAll('.btn-speed').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Activate the selected speed button
            const speedBtn = document.getElementById(`speed${speed === 1 ? '1x' : speed === 1.5 ? '1_5x' : '2x'}Btn`);
            if (speedBtn) {
                speedBtn.classList.add('active');
            }
            
            // Update current utterance if playing
            if (isPlaying && audioPlayer) {
                audioPlayer.rate = currentSpeed;
            }
        }

        function updateAudioUI() {
            const playPauseBtn = document.getElementById('playPauseBtn');
            const playPauseIcon = document.getElementById('playPauseIcon');
            
            if (isPlaying) {
                playPauseIcon.className = 'fas fa-pause';
                playPauseBtn.classList.add('playing');
            } else {
                playPauseIcon.className = 'fas fa-play';
                playPauseBtn.classList.remove('playing');
            }
        }

        function updateProgress() {
            if (!isPlaying) return;
            
            // Since we can't get exact progress from speechSynthesis,
            // we'll simulate progress based on time
            const progressBar = document.getElementById('audioProgressBar');
            const currentTimeElement = document.getElementById('currentTime');
            const totalTimeElement = document.getElementById('totalTime');
            
            // Estimate duration (rough calculation: ~150 words per minute)
            const notesContent = document.getElementById('notesContent').textContent;
            const wordCount = notesContent.split(' ').length;
            const estimatedDuration = (wordCount / 150) * 60; // in seconds
            
            let currentTime = 0;
            const progressInterval = setInterval(() => {
                if (!isPlaying) {
                    clearInterval(progressInterval);
                    return;
                }
                
                currentTime += 0.1;
                const progress = Math.min((currentTime / estimatedDuration) * 100, 100);
                
                progressBar.style.width = progress + '%';
                currentTimeElement.textContent = formatTime(currentTime);
                totalTimeElement.textContent = formatTime(estimatedDuration);
                
                if (progress >= 100) {
                    clearInterval(progressInterval);
                }
            }, 100);
        }

        function resetProgress() {
            document.getElementById('audioProgressBar').style.width = '0%';
            document.getElementById('currentTime').textContent = '0:00';
            document.getElementById('totalTime').textContent = '0:00';
        }

        function formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return mins + ':' + (secs < 10 ? '0' : '') + secs;
        }

        // Camera control functions
        async function startCamera() {
            try {
                const video = document.getElementById('cameraVideo');
                const startBtn = document.getElementById('startCameraBtn');
                const stopBtn = document.getElementById('stopCameraBtn');
                
                // Request camera access
                cameraStream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        width: { ideal: 320 },
                        height: { ideal: 240 }
                    } 
                });
                
                // Set video source
                video.srcObject = cameraStream;
                
                // Update button states
                startBtn.style.display = 'none';
                stopBtn.style.display = 'flex';
                
                // Show video element
                video.style.display = 'block';
                
            } catch (error) {
                console.error('Error accessing camera:', error);
                alert('Unable to access camera. Please check permissions and try again.');
            }
        }

        function stopCamera() {
            try {
                const video = document.getElementById('cameraVideo');
                const startBtn = document.getElementById('startCameraBtn');
                const stopBtn = document.getElementById('stopCameraBtn');
                
                // Stop all tracks
                if (cameraStream) {
                    cameraStream.getTracks().forEach(track => track.stop());
                    cameraStream = null;
                }
                
                // Clear video source
                video.srcObject = null;
                
                // Update button states
                startBtn.style.display = 'flex';
                stopBtn.style.display = 'none';
                
                // Hide video element
                video.style.display = 'none';
                
            } catch (error) {
                console.error('Error stopping camera:', error);
            }
        }

        // Auto-stop camera when navigating away
        function navigateToLesson(lesson, index) {
            // Stop any playing audio
            stopAudio();
            
            // Stop camera
            stopCamera();
            
            // Hide audio player, image controls, and image
            document.getElementById('audioPlayerSection').style.display = 'none';
            document.querySelector('.image-controls').style.display = 'none';
            document.getElementById('generatedImage').style.display = 'none';
            
            // Update current lesson data
            currentLesson = {
                ...currentLesson,
                title: lesson.title,
                content: lesson.content,
                index: index
            };
            
            // Update session storage
            sessionStorage.setItem('currentLesson', JSON.stringify(currentLesson));
            
            // Update current index
            currentLessonIndex = index;
            
            // Update UI
            displayLesson(currentLesson);
            updateNavigationButtons();
            
            // Clear existing notes and Q&A
            document.getElementById('notesContent').innerHTML = '<div class="text-center text-muted py-3"><i class="fas fa-sticky-note"></i><br>No notes generated yet. Click "Generate Notes" to create AI-powered study notes for this lesson.</div>';
            document.getElementById('qaResponse').innerHTML = '';
            document.getElementById('qaResponse').classList.remove('show');
            document.getElementById('notesStatus').textContent = '';
            
            // Load notes for new lesson
            loadExistingNotes();
        }

        function displayLesson(lesson) {
            document.getElementById('lessonTitle').textContent = lesson.title;
            document.getElementById('courseInfo').textContent = `Course: ${lesson.courseName}`;
            document.getElementById('lessonTitleContent').textContent = lesson.title;
            document.getElementById('lessonContent').textContent = lesson.content;
            document.getElementById('lessonNumber').textContent = `Lesson ${lesson.index + 1}`;
            
            // Update page title
            document.title = `${lesson.title} - ${lesson.courseName} - Dronacharya`;
        }

        function generateNotes() {
            if (!currentLesson) {
                alert('No lesson data available');
                return;
            }

            const btn = document.getElementById('generateNotesBtn');
            const status = document.getElementById('notesStatus');
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            status.textContent = 'Generating AI notes...';
            
            const formData = new FormData();
            formData.append('action', 'generate_notes');
            formData.append('lesson_title', currentLesson.title);
            formData.append('lesson_content', currentLesson.content);
            formData.append('course_id', currentLesson.courseId);
            
            fetch('learning.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('notesContent').textContent = data.notes;
                    status.textContent = data.message || 'Notes generated successfully!';
                    status.className = 'text-success';
                    
                    // Show audio player and start auto-play countdown
                    showAudioPlayer();
                    startAutoPlayCountdown();
                    
                    // Load saved image or generate new one
                    if (data.image_url) {
                        loadSavedImage(data.image_url);
                    } else {
                        generateImageFromNotes(data.notes);
                    }
                } else {
                    status.textContent = 'Error: ' + data.message;
                    status.className = 'text-danger';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                status.textContent = 'Error generating notes';
                status.className = 'text-danger';
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-magic"></i> Generate Notes';
            });
        }

        function askQuestion() {
            const questionInput = document.getElementById('questionInput');
            const question = questionInput.value.trim();
            
            if (!question) {
                alert('Please enter a question');
                return;
            }

            if (!currentLesson) {
                alert('No lesson data available');
                return;
            }

            const responseDiv = document.getElementById('qaResponse');
            responseDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Thinking...';
            responseDiv.className = 'qa-response show';
            
            const formData = new FormData();
            formData.append('action', 'ask_question');
            formData.append('question', question);
            formData.append('lesson_title', currentLesson.title);
            formData.append('lesson_content', currentLesson.content);
            
            fetch('learning.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    responseDiv.textContent = data.answer;
                } else {
                    responseDiv.textContent = 'Error: ' + data.message;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                responseDiv.textContent = 'Error getting answer';
            });
            
            questionInput.value = '';
        }

        function setupSpeechRecognition() {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (SpeechRecognition) {
                speechRecognition = new SpeechRecognition();
                speechRecognition.continuous = false;
                speechRecognition.interimResults = false;
                speechRecognition.lang = 'en-US';

                speechRecognition.onresult = (event) => {
                    const transcript = event.results[0][0].transcript;
                    document.getElementById('questionInput').value = transcript;
                };

                speechRecognition.onerror = (event) => {
                    console.error('Speech recognition error:', event.error);
                };
            } else {
                document.getElementById('micButton').disabled = true;
                document.getElementById('micButton').title = 'Speech recognition not supported';
            }
        }

        function startVoiceInput() {
            if (speechRecognition) {
                speechRecognition.start();
            }
        }

        function updateNavigationButtons() {
            const prevBtn = document.getElementById('prevLessonBtn');
            const nextBtn = document.getElementById('nextLessonBtn');
            
            // Disable previous button if at first lesson
            prevBtn.disabled = currentLessonIndex <= 0;
            
            // Disable next button if at last lesson
            nextBtn.disabled = currentLessonIndex >= courseRoadmap.length - 1;
        }

        function goToPreviousLesson() {
            if (currentLessonIndex > 0) {
                const prevLesson = courseRoadmap[currentLessonIndex - 1];
                navigateToLesson(prevLesson, currentLessonIndex - 1);
            }
        }

        function goToNextLesson() {
            if (currentLessonIndex < courseRoadmap.length - 1) {
                const nextLesson = courseRoadmap[currentLessonIndex + 1];
                navigateToLesson(nextLesson, currentLessonIndex + 1);
            }
        }


        // Enter key to ask question
        document.getElementById('questionInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                askQuestion();
            }
        });

        async function logout() {
            if (confirm('Are you sure you want to logout?')) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'logout');
                    
                    const response = await fetch('../auth/auth.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        window.location.href = 'home.php';
                    } else {
                        alert('Error logging out. Please try again.');
                    }
                } catch (error) {
                    alert('An error occurred. Please try again.');
                }
            }
        }
    </script>
</body>
</html>