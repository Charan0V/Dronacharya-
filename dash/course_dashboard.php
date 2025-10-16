<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';

// Require authentication
requireAuth();

$user = getCurrentUser();

// Get course ID from URL
$courseId = $_GET['course_id'] ?? '';

if (empty($courseId)) {
    header('Location: course_manager.php');
    exit;
}

// Load course information
$courseDir = __DIR__ . DIRECTORY_SEPARATOR . 'courses' . DIRECTORY_SEPARATOR . $courseId;
$courseInfoFile = $courseDir . DIRECTORY_SEPARATOR . 'course_info.json';
$roadmapFile = $courseDir . DIRECTORY_SEPARATOR . 'roadmap.csv';

if (!file_exists($courseInfoFile) || !file_exists($roadmapFile)) {
    header('Location: course_manager.php');
    exit;
}

$courseInfo = json_decode(file_get_contents($courseInfoFile), true);

// Verify ownership
if ($courseInfo['user_id'] != $user['id']) {
    header('Location: course_manager.php');
    exit;
}

// Load roadmap data
$roadmapData = [];
if (($handle = fopen($roadmapFile, "r")) !== FALSE) {
    $header = fgetcsv($handle);
    while (($data = fgetcsv($handle)) !== FALSE) {
        if (count($data) >= 2) {
            $roadmapData[] = [
                'title' => $data[0],
                'content' => $data[1],
                'status' => $data[2] ?? 'pending'
            ];
        }
    }
    fclose($handle);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($courseInfo['name']); ?> - Dronacharya</title>
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
        
        .course-description {
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
        
        /* Roadmap Container */
        .roadmap-container {
            background: linear-gradient(135deg, #fffaf0, #ffe4b5);
            border: 3px solid #deb887;
            border-radius: 20px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .roadmap-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            color: #5e0b15;
            text-align: center;
            margin-bottom: 2rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .roadmap-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .roadmap-item {
            background: linear-gradient(135deg, #ffffff, #fff8dc);
            border: 2px solid #deb887;
            border-radius: 15px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .roadmap-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 215, 0, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .roadmap-item:hover::before {
            left: 100%;
        }
        
        .roadmap-item:hover {
            transform: translateY(-5px);
            border-color: #ffd700;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .roadmap-item-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #5e0b15;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        
        .roadmap-item-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            font-weight: 600;
            color: #5e0b15;
            margin-bottom: 0.5rem;
        }
        
        .roadmap-item-content {
            color: #8b4513;
            font-size: 0.9rem;
            line-height: 1.4;
            margin-bottom: 1rem;
        }
        
        .roadmap-item-status {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
        
        /* Back Button */
        .btn-back {
            background: linear-gradient(135deg, #a0522d, #8b4513);
            border: 2px solid #ffd700;
            color: #ffd700;
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-back:hover {
            background: linear-gradient(135deg, #8b4513, #a0522d);
            transform: translateY(-2px);
            color: #ffd700;
            text-decoration: none;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-title {
                font-size: 2rem;
            }
            
            .roadmap-grid {
                grid-template-columns: 1fr;
            }
            
            .roadmap-container {
                padding: 1rem;
                margin: 1rem 0;
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
            <h1 class="main-title"><?php echo htmlspecialchars($courseInfo['name']); ?></h1>
            <p class="course-description"><?php echo htmlspecialchars($courseInfo['description']); ?></p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container py-4">
        <div class="roadmap-container">
            <h2 class="roadmap-title">
                <i class="fas fa-map"></i> Learning Roadmap
            </h2>
            
            <?php if (empty($roadmapData)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                    <h4 class="text-muted mt-3">No roadmap data available</h4>
                    <p class="text-muted">This course doesn't have a roadmap yet.</p>
                </div>
            <?php else: ?>
                <div class="roadmap-grid">
                    <?php foreach ($roadmapData as $index => $item): ?>
                        <div class="roadmap-item" onclick="openLesson(<?php echo $index; ?>, '<?php echo htmlspecialchars($item['title']); ?>', '<?php echo htmlspecialchars($item['content']); ?>')">
                            <div class="roadmap-item-number"><?php echo $index + 1; ?></div>
                            <div class="roadmap-item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                            <div class="roadmap-item-content"><?php echo htmlspecialchars($item['content']); ?></div>
                            <div class="roadmap-item-status status-<?php echo $item['status']; ?>">
                                <?php echo ucfirst($item['status']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function openLesson(index, title, content) {
            // Redirect to dashboard.php with course ID
            window.location.href = `dashboard.php?course_id=<?php echo $courseId; ?>&lesson=${index}`;
        }
        
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