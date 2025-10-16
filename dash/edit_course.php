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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_roadmap') {
        $roadmapData = $_POST['roadmap'] ?? [];
        
        // Update CSV file
        $csvContent = "title,content,status" . PHP_EOL;
        foreach ($roadmapData as $item) {
            $csvContent .= '"' . addslashes($item['title']) . '","' . addslashes($item['content']) . '","' . addslashes($item['status']) . '"' . PHP_EOL;
        }
        
        if (file_put_contents($roadmapFile, $csvContent)) {
            $success = 'Roadmap updated successfully!';
        } else {
            $error = 'Error updating roadmap. Please try again.';
        }
    }
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
    <title>Edit <?php echo htmlspecialchars($courseInfo['name']); ?> - Dronacharya</title>
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
            font-size: 2.5rem;
            font-weight: 800;
            color: #ffd700;
            text-shadow: 4px 4px 8px rgba(0,0,0,0.7), 0 0 30px rgba(255, 215, 0, 0.6);
            letter-spacing: 2px;
            margin: 0;
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
            padding: 2rem 0;
        }
        
        .edit-container {
            background: linear-gradient(135deg, #fffaf0, #ffe4b5);
            border: 3px solid #deb887;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .edit-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            color: #5e0b15;
            margin-bottom: 2rem;
            text-align: center;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .roadmap-item {
            background: white;
            border: 2px solid #deb887;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .roadmap-item:hover {
            border-color: #ffd700;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .roadmap-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .roadmap-item-number {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #5e0b15;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .roadmap-item-controls {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-control {
            padding: 0.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .btn-move-up {
            background: #17a2b8;
            color: white;
        }
        
        .btn-move-down {
            background: #6c757d;
            color: white;
        }
        
        .btn-remove {
            background: #dc3545;
            color: white;
        }
        
        .btn-control:hover {
            transform: scale(1.1);
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #5e0b15;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border: 2px solid #deb887;
            border-radius: 10px;
            padding: 0.8rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #ffd700;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
        }
        
        .status-select {
            border: 2px solid #deb887;
            border-radius: 10px;
            padding: 0.5rem;
            background: white;
        }
        
        .btn-save {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-save:hover {
            background: linear-gradient(135deg, #20c997, #28a745);
            transform: translateY(-2px);
        }
        
        .btn-add-item {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #5e0b15;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }
        
        .btn-add-item:hover {
            background: linear-gradient(135deg, #ffed4e, #ffd700);
            transform: translateY(-2px);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-title {
                font-size: 2rem;
            }
            
            .edit-container {
                padding: 1rem;
            }
            
            .roadmap-item-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
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
            <h1 class="main-title">Edit Course</h1>
            <p class="text-light"><?php echo htmlspecialchars($courseInfo['name']); ?></p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container main-content">
        <div class="edit-container">
            <h2 class="edit-title">
                <i class="fas fa-edit"></i> Course Roadmap Editor
            </h2>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="roadmapForm">
                <input type="hidden" name="action" value="update_roadmap">
                
                <button type="button" class="btn-add-item" onclick="addRoadmapItem()">
                    <i class="fas fa-plus"></i> Add New Lesson
                </button>
                
                <div id="roadmapItems">
                    <?php foreach ($roadmapData as $index => $item): ?>
                        <div class="roadmap-item" data-index="<?php echo $index; ?>">
                            <div class="roadmap-item-header">
                                <div class="roadmap-item-number"><?php echo $index + 1; ?></div>
                                <div class="roadmap-item-controls">
                                    <button type="button" class="btn-control btn-move-up" onclick="moveItem(<?php echo $index; ?>, 'up')" title="Move Up">
                                        <i class="fas fa-arrow-up"></i>
                                    </button>
                                    <button type="button" class="btn-control btn-move-down" onclick="moveItem(<?php echo $index; ?>, 'down')" title="Move Down">
                                        <i class="fas fa-arrow-down"></i>
                                    </button>
                                    <button type="button" class="btn-control btn-remove" onclick="removeItem(<?php echo $index; ?>)" title="Remove">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Lesson Title</label>
                                <input type="text" class="form-control" name="roadmap[<?php echo $index; ?>][title]" value="<?php echo htmlspecialchars($item['title']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Lesson Content</label>
                                <textarea class="form-control" name="roadmap[<?php echo $index; ?>][content]" rows="3" required><?php echo htmlspecialchars($item['content']); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select class="status-select" name="roadmap[<?php echo $index; ?>][status]">
                                    <option value="pending" <?php echo $item['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="in-progress" <?php echo $item['status'] === 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="completed" <?php echo $item['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let itemCount = <?php echo count($roadmapData); ?>;
        
        function addRoadmapItem() {
            const container = document.getElementById('roadmapItems');
            const newIndex = itemCount;
            
            const newItem = document.createElement('div');
            newItem.className = 'roadmap-item';
            newItem.setAttribute('data-index', newIndex);
            newItem.innerHTML = `
                <div class="roadmap-item-header">
                    <div class="roadmap-item-number">${newIndex + 1}</div>
                    <div class="roadmap-item-controls">
                        <button type="button" class="btn-control btn-move-up" onclick="moveItem(${newIndex}, 'up')" title="Move Up">
                            <i class="fas fa-arrow-up"></i>
                        </button>
                        <button type="button" class="btn-control btn-move-down" onclick="moveItem(${newIndex}, 'down')" title="Move Down">
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <button type="button" class="btn-control btn-remove" onclick="removeItem(${newIndex})" title="Remove">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Lesson Title</label>
                    <input type="text" class="form-control" name="roadmap[${newIndex}][title]" value="" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Lesson Content</label>
                    <textarea class="form-control" name="roadmap[${newIndex}][content]" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select class="status-select" name="roadmap[${newIndex}][status]">
                        <option value="pending">Pending</option>
                        <option value="in-progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
            `;
            
            container.appendChild(newItem);
            itemCount++;
            updateItemNumbers();
        }
        
        function removeItem(index) {
            if (confirm('Are you sure you want to remove this lesson?')) {
                const item = document.querySelector(`[data-index="${index}"]`);
                if (item) {
                    item.remove();
                    updateItemNumbers();
                }
            }
        }
        
        function moveItem(index, direction) {
            const items = Array.from(document.querySelectorAll('.roadmap-item'));
            const currentIndex = items.findIndex(item => item.getAttribute('data-index') == index);
            
            if (direction === 'up' && currentIndex > 0) {
                items[currentIndex].parentNode.insertBefore(items[currentIndex], items[currentIndex - 1]);
                updateItemNumbers();
            } else if (direction === 'down' && currentIndex < items.length - 1) {
                items[currentIndex].parentNode.insertBefore(items[currentIndex], items[currentIndex + 1].nextSibling);
                updateItemNumbers();
            }
        }
        
        function updateItemNumbers() {
            const items = document.querySelectorAll('.roadmap-item');
            items.forEach((item, index) => {
                const numberElement = item.querySelector('.roadmap-item-number');
                numberElement.textContent = index + 1;
                
                // Update form field names
                const inputs = item.querySelectorAll('input, textarea, select');
                inputs.forEach(input => {
                    const name = input.getAttribute('name');
                    if (name) {
                        input.setAttribute('name', name.replace(/\[\d+\]/, `[${index}]`));
                    }
                });
                
                // Update onclick attributes
                const buttons = item.querySelectorAll('button[onclick]');
                buttons.forEach(button => {
                    const onclick = button.getAttribute('onclick');
                    if (onclick) {
                        button.setAttribute('onclick', onclick.replace(/\d+/, index));
                    }
                });
            });
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