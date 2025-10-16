<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';

// Require authentication
requireAuth();

$user = getCurrentUser();

// Function to generate roadmap using AI
function generateRoadmap($topic, $extra = '') {
    $apiUrl = 'http://127.0.0.1:5000/greet'; // Flask endpoint from api.py
    
    // Build the instruction for the API
    $instruction = 'Generate a roadmap for ' . $topic . 'in the form of "title","content" line by line just the text nothing else within 10 lines just text dont add other message in the form of reply';
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
        // Fallback to default roadmap if API fails
        return [
            '1. Introduction to ' . $topic,
            '2. Basic Concepts',
            '3. Fundamentals',
            '4. Practical Application',
            '5. Advanced Topics',
            '6. Hands-on Practice',
            '7. Real-world Examples',
            '8. Best Practices',
            '9. Common Challenges',
            '10. Final Project'
        ];
    }

    // Parse the response
    $data = json_decode($response, true);
    $content = $data['message'] ?? $response;
    
    // Extract titles from the response
    $lines = explode("\n", $content);
    $titles = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line) && (preg_match('/^\d+\./', $line) || preg_match('/^[•-]/', $line))) {
            $titles[] = preg_replace('/^[\d\.•\-\s]+/', '', $line);
        }
    }
    
    // If no titles found, create default ones
    if (empty($titles)) {
        $titles = [
            'Introduction to ' . $topic,
            'Basic Concepts',
            'Fundamentals',
            'Practical Application',
            'Advanced Topics',
            'Hands-on Practice',
            'Real-world Examples',
            'Best Practices',
            'Common Challenges',
            'Final Project'
        ];
    }
    
    return array_slice($titles, 0, 10); // Limit to 10 items
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_course') {
        $courseName = trim($_POST['course_name'] ?? '');
        $extraInstructions = trim($_POST['extra_instructions'] ?? '');
        
        if (empty($courseName)) {
            echo json_encode(['success' => false, 'message' => 'Course name is required']);
            exit;
        }
        
        // Generate unique course ID
        $courseId = 'course_' . time() . '_' . uniqid();
        
        // Create courses directory if it doesn't exist
        $coursesDir = __DIR__ . DIRECTORY_SEPARATOR . 'courses';
        if (!is_dir($coursesDir)) {
            mkdir($coursesDir, 0755, true);
        }
        
        // Create course directory
        $courseDir = $coursesDir . DIRECTORY_SEPARATOR . $courseId;
        if (!is_dir($courseDir)) {
            mkdir($courseDir, 0755, true);
        }
        
        // Generate roadmap
        $roadmapTitles = generateRoadmap($courseName, $extraInstructions);
        
        // Create course info file
        $courseInfo = [
            'id' => $courseId,
            'name' => $courseName,
            'description' => 'Learn ' . $courseName . ' with expert guidance.',
            'created_at' => date('Y-m-d H:i:s'),
            'user_id' => $user['id'],
            'status' => 'new',
            'progress' => 0
        ];
        
        file_put_contents($courseDir . DIRECTORY_SEPARATOR . 'course_info.json', json_encode($courseInfo, JSON_PRETTY_PRINT));
        
        // Create roadmap CSV
        $csvFile = $courseDir . DIRECTORY_SEPARATOR . 'roadmap.csv';
        $csvContent = "title,content,status" . PHP_EOL;
        foreach ($roadmapTitles as $title) {
            $csvContent .= '"' . $title . '","Learn about ' . $title . '","pending"' . PHP_EOL;
        }
        file_put_contents($csvFile, $csvContent);
        
        echo json_encode(['success' => true, 'message' => 'Course created successfully', 'course_id' => $courseId]);
        exit;
        
    } elseif ($action === 'get_courses') {
        $courses = [];
        $coursesDir = __DIR__ . DIRECTORY_SEPARATOR . 'courses';
        
        if (is_dir($coursesDir)) {
            $dirs = scandir($coursesDir);
            foreach ($dirs as $dir) {
                if ($dir !== '.' && $dir !== '..' && is_dir($coursesDir . DIRECTORY_SEPARATOR . $dir)) {
                    $courseInfoFile = $coursesDir . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . 'course_info.json';
                    if (file_exists($courseInfoFile)) {
                        $courseInfo = json_decode(file_get_contents($courseInfoFile), true);
                        if ($courseInfo && $courseInfo['user_id'] == $user['id']) {
                            $courses[] = $courseInfo;
                        }
                    }
                }
            }
        }
        
        echo json_encode(['success' => true, 'courses' => $courses]);
        exit;
        
    } elseif ($action === 'delete_course') {
        $courseId = $_POST['course_id'] ?? '';
        
        if (empty($courseId)) {
            echo json_encode(['success' => false, 'message' => 'Course ID is required']);
            exit;
        }
        
        $courseDir = __DIR__ . DIRECTORY_SEPARATOR . 'courses' . DIRECTORY_SEPARATOR . $courseId;
        
        if (is_dir($courseDir)) {
            // Verify ownership
            $courseInfoFile = $courseDir . DIRECTORY_SEPARATOR . 'course_info.json';
            if (file_exists($courseInfoFile)) {
                $courseInfo = json_decode(file_get_contents($courseInfoFile), true);
                if ($courseInfo && $courseInfo['user_id'] == $user['id']) {
                    // Delete course directory
                    $files = glob($courseDir . DIRECTORY_SEPARATOR . '*');
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            unlink($file);
                        }
                    }
                    rmdir($courseDir);
                    
                    echo json_encode(['success' => true, 'message' => 'Course deleted successfully']);
                    exit;
                }
            }
        }
        
        echo json_encode(['success' => false, 'message' => 'Course not found or access denied']);
        exit;
    }
}

// Load existing courses
$courses = [];
$coursesDir = __DIR__ . DIRECTORY_SEPARATOR . 'courses';

if (is_dir($coursesDir)) {
    $dirs = scandir($coursesDir);
    foreach ($dirs as $dir) {
        if ($dir !== '.' && $dir !== '..' && is_dir($coursesDir . DIRECTORY_SEPARATOR . $dir)) {
            $courseInfoFile = $coursesDir . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . 'course_info.json';
            if (file_exists($courseInfoFile)) {
                $courseInfo = json_decode(file_get_contents($courseInfoFile), true);
                if ($courseInfo && $courseInfo['user_id'] == $user['id']) {
                    $courses[] = $courseInfo;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dronacharya - Course Management</title>
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
        
        /* Header Section */
        .header-section {
            background: linear-gradient(135deg, #5e0b15 0%, #8b4513 50%, #a0522d 100%);
            padding: 2.5rem 0;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255, 215, 0, 0.2);
            border-bottom: 6px solid #deb887;
            position: relative;
            overflow: hidden;
        }
        
        .header-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 215, 0, 0.1) 50%, transparent 70%), 
                        radial-gradient(circle at 50% 50%, rgba(255, 215, 0, 0.05) 0%, transparent 70%);
            animation: shimmer 3s ease-in-out infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.7; }
        }
        
        .header-content {
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            min-height: 120px;
        }
        
        .user-section {
            position: absolute;
            top: 1rem;
            left: 1rem;
            z-index: 10;
        }
        
        .welcome-message {
            background: linear-gradient(135deg, rgba(255, 250, 240, 0.9) 0%, rgba(255, 255, 255, 0.9) 100%);
            border: 2px solid #ffd700;
            border-radius: 10px;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            font-family: 'Crimson Text', serif;
            font-weight: 500;
            color: #5e0b15;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15), inset 0 1px 0 rgba(255, 215, 0, 0.2);
            letter-spacing: 0.3px;
            text-align: center;
            backdrop-filter: blur(5px);
        }
        
        .title-section {
            text-align: center;
        }
        
        .main-title {
            font-family: 'Cinzel', serif;
            font-size: 4.5rem;
            font-weight: 800;
            color: #ffd700;
            text-shadow: 4px 4px 8px rgba(0,0,0,0.7), 0 0 30px rgba(255, 215, 0, 0.6), 0 0 60px rgba(255, 215, 0, 0.4), 0 0 90px rgba(255, 215, 0, 0.2);
            letter-spacing: 4px;
            margin: 0;
            animation: enhancedGlow 5s ease-in-out infinite alternate;
            position: relative;
            z-index: 2;
            text-align: center;
            line-height: 1.1;
        }
        
        .main-title::before {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            background: linear-gradient(45deg, transparent, rgba(255, 215, 0, 0.1), transparent);
            border-radius: 20px;
            z-index: -1;
            animation: shimmer 3s ease-in-out infinite;
        }
        
        @keyframes enhancedGlow {
            from {
                text-shadow: 4px 4px 8px rgba(0,0,0,0.7), 0 0 30px rgba(255, 215, 0, 0.6), 0 0 60px rgba(255, 215, 0, 0.4), 0 0 90px rgba(255, 215, 0, 0.2);
                transform: scale(1);
            }
            to {
                text-shadow: 4px 4px 8px rgba(0,0,0,0.7), 0 0 40px rgba(255, 215, 0, 0.8), 0 0 80px rgba(255, 215, 0, 0.6), 0 0 120px rgba(255, 215, 0, 0.4);
                transform: scale(1.02);
            }
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            padding: 3rem 2rem;
            background: linear-gradient(to bottom, #fff8dc, #fdf6e3);
        }
        
        .add-course-section {
            background: linear-gradient(135deg, #fffaf0, #ffe4b5);
            border: 3px solid #deb887;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .add-course-section:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        .add-course-input {
            background: linear-gradient(135deg, #ffffff 0%, #fffaf0 100%);
            border: 3px solid #a0522d;
            border-radius: 15px;
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
            font-family: 'Crimson Text', serif;
            font-weight: 500;
            color: #5e0b15;
            width: 100%;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1), inset 0 1px 0 rgba(255, 255, 255, 0.8);
            letter-spacing: 0.3px;
        }
        
        .add-course-input:focus {
            outline: none;
            border-color: #ffd700;
            box-shadow: 0 0 25px rgba(255, 215, 0, 0.4), 0 8px 25px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255, 255, 255, 0.9);
            transform: translateY(-2px);
        }
        
        .add-course-btn {
            background: linear-gradient(135deg, #a0522d 0%, #5e0b15 50%, #8b4513 100%);
            border: 3px solid #ffd700;
            color: #ffd700;
            padding: 1rem 1.5rem;
            border-radius: 15px;
            font-size: 1.1rem;
            font-family: 'Cinzel', serif;
            font-weight: 600;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3), inset 0 1px 0 rgba(255, 215, 0, 0.3);
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        
        .add-course-btn:hover {
            background: linear-gradient(135deg, #5e0b15 0%, #8b4513 50%, #a0522d 100%);
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 10px 30px rgba(0,0,0,0.4), 0 0 20px rgba(255, 215, 0, 0.3), inset 0 1px 0 rgba(255, 215, 0, 0.4);
            border-color: #ffd700;
        }
        
        .course-tiles-label {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            font-weight: 700;
            color: #5e0b15;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            letter-spacing: 1px;
        }
        
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }
        
        .course-tile {
            background: linear-gradient(135deg, #fffaf0 0%, #ffe4b5 50%, #f5deb3 100%);
            border: 4px solid #deb887;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15), inset 0 1px 0 rgba(255, 255, 255, 0.8);
            min-height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .course-tile::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 215, 0, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .course-tile:hover::before {
            left: 100%;
        }
        
        .course-tile:hover {
            transform: translateY(-12px) scale(1.05);
            border-color: #ffd700;
            box-shadow: 0 15px 40px rgba(0,0,0,0.25), 0 0 20px rgba(255, 215, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.9);
        }
        
        .course-icon {
            font-size: 3rem;
            color: #5e0b15;
            margin-bottom: 0.8rem;
            filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.2));
            transition: all 0.3s ease;
        }
        
        .course-tile:hover .course-icon {
            transform: scale(1.1) rotate(5deg);
            color: #ffd700;
        }
        
        .course-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: #5e0b15;
            margin-bottom: 0.8rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
            letter-spacing: 0.5px;
        }
        
        .course-description {
            font-family: 'Crimson Text', serif;
            font-size: 0.9rem;
            color: #8b4513;
            margin-bottom: 1rem;
            line-height: 1.4;
        }
        
        .course-status {
            font-family: 'Crimson Text', serif;
            font-size: 0.9rem;
            color: #a0522d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .course-status.in-progress {
            color: #ff6b35;
        }
        
        .course-status.completed {
            color: #28a745;
        }
        
        .course-actions {
            margin-top: 1rem;
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .btn-action {
            padding: 0.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .btn-start {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .btn-start:hover {
            background: linear-gradient(135deg, #20c997, #28a745);
            transform: scale(1.1);
        }
        
        .btn-edit {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
        }
        
        .btn-edit:hover {
            background: linear-gradient(135deg, #fd7e14, #ffc107);
            transform: scale(1.1);
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #dc3545, #e83e8c);
            color: white;
        }
        
        .btn-delete:hover {
            background: linear-gradient(135deg, #e83e8c, #dc3545);
            transform: scale(1.1);
        }
        
        /* No courses message */
        .no-courses {
            text-align: center;
            padding: 4rem 2rem;
            background: linear-gradient(135deg, #fffaf0, #ffe4b5);
            border: 2px solid #deb887;
            border-radius: 20px;
            margin: 2rem 0;
        }
        
        .no-courses-icon {
            font-size: 4rem;
            color: #a0522d;
            margin-bottom: 1rem;
        }
        
        .no-courses h3 {
            color: #5e0b15;
            margin-bottom: 1rem;
        }
        
        .no-courses p {
            color: #8b4513;
            margin-bottom: 2rem;
        }
        
        /* Floating Action Button */
        .fab {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            border: 3px solid #deb887;
            color: #5e0b15;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .fab:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0,0,0,0.4);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
                min-height: 100px;
            }
            
            .main-title {
                font-size: 3rem;
            }
            
            .user-section {
                top: 0.5rem;
                left: 0.5rem;
            }
            
            .welcome-message {
                font-size: 0.8rem;
                padding: 0.4rem 0.8rem;
            }
            
            .course-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .main-content {
                padding: 2rem 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .main-title {
                font-size: 2.2rem;
                letter-spacing: 2px;
            }
            
            .user-section {
                top: 0.3rem;
                left: 0.3rem;
            }
            
            .welcome-message {
                font-size: 0.7rem;
                padding: 0.3rem 0.6rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="header-section">
        <div class="container">
            <div class="header-content">
                <!-- User Section -->
                <div class="user-section">
                    <div class="welcome-message">
                        Welcome, <?php echo htmlspecialchars($user['name']); ?>!
                    </div>
                </div>
                
                <!-- Title Section -->
                <div class="title-section">
                    <h1 class="main-title">DRONACHARYA</h1>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Add Course Section -->
            <div class="add-course-section">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <input type="text" class="add-course-input" id="courseInput" placeholder="Enter course name (e.g., Python Programming, Web Development, Data Science...)" maxlength="100">
                    </div>
                    <div class="col-md-4">
                        <button class="btn add-course-btn w-100" id="addCourseBtn">
                            <i class="fas fa-magic"></i> Generate Course
                        </button>
                    </div>
                </div>
            </div>

            <!-- Course Tiles Section -->
            <div class="course-tiles-label">
                <i class="fas fa-graduation-cap"></i>
                Your Course Collection
            </div>

            <div class="course-grid" id="courseGrid">
                <!-- Course tiles will be dynamically generated here -->
            </div>
            
            <!-- No courses message -->
            <div id="noCoursesMessage" class="no-courses" style="display: none;">
                <i class="fas fa-graduation-cap no-courses-icon"></i>
                <h3>No courses yet!</h3>
                <p>Create your first course to start your learning journey with AI-powered roadmaps.</p>
                <button class="btn add-course-btn" onclick="document.getElementById('courseInput').focus()">
                    <i class="fas fa-plus"></i> Create Your First Course
                </button>
            </div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <button class="fab" onclick="document.getElementById('courseInput').focus()" title="Create New Course">
        <i class="fas fa-plus"></i>
    </button>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let courses = [];

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            loadCourses();
            setupEventListeners();
        });

        function setupEventListeners() {
            // Add course button
            document.getElementById('addCourseBtn').addEventListener('click', addCourse);
            
            // Enter key in course input
            document.getElementById('courseInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    addCourse();
                }
            });
        }

        function loadCourses() {
            fetch('course_manager.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_courses'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    courses = data.courses;
                    renderCourseGrid();
                }
            })
            .catch(error => {
                console.error('Error loading courses:', error);
            });
        }

        function addCourse() {
            const courseInput = document.getElementById('courseInput');
            const courseName = courseInput.value.trim();
            
            if (!courseName) {
                alert('Please enter a course name');
                courseInput.focus();
                return;
            }
            
            const addBtn = document.getElementById('addCourseBtn');
            const originalText = addBtn.innerHTML;
            
            // Show loading state
            addBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            addBtn.disabled = true;
            
            // Send request
            const formData = new FormData();
            formData.append('action', 'create_course');
            formData.append('course_name', courseName);
            formData.append('extra_instructions', '');
            
            fetch('course_manager.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    courseInput.value = '';
                    loadCourses(); // Reload courses
                    showAlert('Course created successfully!', 'success');
                } else {
                    showAlert(data.message || 'Error creating course', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
            })
            .finally(() => {
                // Reset button
                addBtn.innerHTML = originalText;
                addBtn.disabled = false;
            });
        }

        function startCourse(courseId) {
            window.location.href = `dashboard.php?course_id=${courseId}`;
        }

        function editCourse(courseId) {
            window.location.href = `edit_course.php?course_id=${courseId}`;
        }

        function deleteCourse(courseId) {
            if (confirm('Are you sure you want to delete this course? This action cannot be undone.')) {
                const formData = new FormData();
                formData.append('action', 'delete_course');
                formData.append('course_id', courseId);
                
                fetch('course_manager.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadCourses(); // Reload courses
                        showAlert('Course deleted successfully', 'success');
                    } else {
                        showAlert(data.message || 'Error deleting course', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred. Please try again.', 'danger');
                });
            }
        }

        function renderCourseGrid() {
            const grid = document.getElementById('courseGrid');
            const noCoursesMessage = document.getElementById('noCoursesMessage');
            
            grid.innerHTML = '';
            
            // Show no courses message if no courses exist
            if (courses.length === 0) {
                noCoursesMessage.style.display = 'block';
                return;
            } else {
                noCoursesMessage.style.display = 'none';
            }
            
            // Render course tiles
            courses.forEach(course => {
                const tile = document.createElement('div');
                tile.className = 'course-tile';
                tile.innerHTML = `
                    <i class="fas fa-graduation-cap course-icon"></i>
                    <div class="course-title">${course.name}</div>
                    <div class="course-description">${course.description}</div>
                    <div class="course-status ${course.status}">${getStatusText(course.status)}</div>
                    <div class="course-actions">
                        <button class="btn-action btn-start" onclick="startCourse('${course.id}')" title="Start Course">
                            <i class="fas fa-play"></i>
                        </button>
                        <button class="btn-action btn-edit" onclick="editCourse('${course.id}')" title="Edit Course">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-action btn-delete" onclick="deleteCourse('${course.id}')" title="Delete Course">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
                grid.appendChild(tile);
            });
        }

        function getStatusText(status) {
            switch(status) {
                case 'new': return 'New Course';
                case 'in-progress': return 'In Progress';
                case 'completed': return 'Completed';
                default: return 'New Course';
            }
        }

        function showAlert(message, type) {
            // Create alert element
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alert);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 5000);
        }
    </script>
</body>
</html>