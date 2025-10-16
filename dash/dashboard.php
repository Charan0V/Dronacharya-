<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';

// Require authentication
requireAuth();

$user = getCurrentUser();

// Get course ID from URL
$courseId = $_GET['course_id'] ?? '';
$lessonIndex = $_GET['lesson'] ?? '';

$rows = [];
$courseInfo = null;
$isCourseSpecific = false;

if (!empty($courseId)) {
    // Load course-specific data
    $courseDir = __DIR__ . DIRECTORY_SEPARATOR . 'courses' . DIRECTORY_SEPARATOR . $courseId;
    $courseInfoFile = $courseDir . DIRECTORY_SEPARATOR . 'course_info.json';
    $csvFile = $courseDir . DIRECTORY_SEPARATOR . 'roadmap.csv';
    
    if (file_exists($courseInfoFile) && file_exists($csvFile)) {
        $courseInfo = json_decode(file_get_contents($courseInfoFile), true);
        
        // Verify ownership
        if ($courseInfo && $courseInfo['user_id'] == $user['id']) {
            $isCourseSpecific = true;
            
            // Load course roadmap data
            if (($h = fopen($csvFile, 'r')) !== false) {
                $header = fgetcsv($h);
                while (($r = fgetcsv($h)) !== false) {
                    if (isset($r[0]) && isset($r[1]) && !empty(trim($r[0]))) {
                        $rows[] = $r;
                    }
                }
                fclose($h);
            }
        }
    }
}

// If no course-specific data, load general data
if (!$isCourseSpecific) {
    $dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
    $csvFile = $dataDir . DIRECTORY_SEPARATOR . 'roadmap.csv';
    
    if (is_file($csvFile)) {
        if (($h = fopen($csvFile, 'r')) !== false) {
            $header = fgetcsv($h);
            while (($r = fgetcsv($h)) !== false) {
                if (isset($r[0]) && isset($r[1]) && !empty(trim($r[0]))) {
                    $rows[] = $r;
                }
            }
            fclose($h);
        }
    } else {
        error_log("CSV file not found: " . $csvFile);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isCourseSpecific ? htmlspecialchars($courseInfo['name']) . ' - ' : ''; ?>Dashboard - Dronacharya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Noto+Serif:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        /* Base styles and animations */
        a { text-decoration: none; }
        .roadmap-card.fade { opacity: 0; transform: translateY(12px); transition: opacity .5s ease, transform .5s ease; }
        .roadmap-card.show { opacity: 1; transform: translateY(0); }

        /* Thematic styles: Enchanted Forest & Golden Path */
        .enchanted-body {
            background: radial-gradient(ellipse at center, #2a3a2b 0%, #1a251a 100%);
            color: #c8d8c9;
            font-family: 'Noto Serif', serif;
        }
        .brand-title {
            font-family: 'Cinzel', serif;
            letter-spacing: .5px;
            color: #FFD700 !important;
            text-shadow: 0 1px 1px #121, 0 0 12px rgba(255, 215, 0, .6);
        }
        .navbar-custom {
            background-color: rgba(13, 29, 15, 0.75);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(255, 215, 0, 0.3);
        }
        /* Ambient vignette */
        .vignette:before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            background: radial-gradient(ellipse at center, rgba(0,0,0,0) 40%, rgba(0,0,0,.45) 100%);
        }
        
        /* Pin and Label Styles */
        @keyframes pulse {
            0% { box-shadow: 0 0 8px rgba(255, 215, 0, .4), 0 0 12px rgba(255, 215, 0, .3), 0 0 16px rgba(255, 215, 0, .2); }
            50% { box-shadow: 0 0 16px rgba(255, 215, 0, .6), 0 0 24px rgba(255, 215, 0, .4), 0 0 32px rgba(255, 215, 0, .3); }
            100% { box-shadow: 0 0 8px rgba(255, 215, 0, .4), 0 0 12px rgba(255, 215, 0, .3), 0 0 16px rgba(255, 215, 0, .2); }
        }
        .pin { opacity: 0; transform: translateY(8px); transition: opacity .4s ease, transform .4s ease; }
        .pin.show { opacity: 1; transform: translateY(0); }
        .pin .marker {
            width: 20px; height: 20px;
            background: radial-gradient(circle at 40% 35%, #FFFDE7, #FFD700 80%);
            border: 2px solid #FFFDE7;
            border-radius: 50%;
            animation: pulse 3s infinite ease-in-out;
            transition: transform 0.3s ease;
        }
        .pin:hover .marker {
             transform: scale(1.3);
        }
        .label-plate {
            color: #FFFDE7;
            background: rgba(13, 29, 15, 0.85);
            border: 1px solid rgba(255, 215, 0, 0.7);
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            max-width: 220px;
            box-shadow: 0 2px 10px rgba(0,0,0,.3);
            text-shadow: 0 0 8px rgba(255, 215, 0, .8);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Popover Styles */
        .pop-gold.popover {
            border: 1px solid #FFD700;
            box-shadow: 0 6px 18px rgba(0,0,0,.3), 0 0 30px rgba(255, 215, 0, .3);
            background: #1a251a;
        }
        .pop-gold .popover-header {
            background: #1a251a;
            color: #FFD700;
            font-family: 'Cinzel', serif;
            border-bottom: 1px solid rgba(255, 215, 0, .5);
        }
        .pop-gold .popover-body {
            background: #1a251a;
            color: #e8f5e9;
        }
        .pop-gold[data-bs-popper] .popover-arrow::after {
             background-color: #1a251a;
             border-color: #FFD700 transparent transparent transparent;
        }
        .pop-gold[data-bs-popper] .popover-arrow::before {
             border-color: #FFD700 transparent transparent transparent;
        }
        /* Roadmap canvas + stage */
        #roadmapWrap { position: relative; isolation: isolate; }
        #stage { position: absolute; inset: 0; transform-origin: 50% 50%; transition: transform .35s ease; }
        #particlesCanvas { position: absolute; inset: 0; z-index: 0; opacity: .28; filter: blur(.2px) saturate(1.2); }
        #roadmapSvg { z-index: 1; }
        #pinsLayer { z-index: 2; }
        /* Zoom control */
        .zoom-controls { position: absolute; right: 12px; bottom: 12px; z-index: 3; background: rgba(13, 29, 15, 0.78); border: 1px solid rgba(255,215,0,.35); box-shadow: 0 6px 18px rgba(0,0,0,.35); }
        .zoom-controls input[type="range"] { accent-color: #FFD700; width: 160px; }
        /* Legend */
        .legend { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
        .legend .dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 6px; }
    </style>
</head>
<body class="enchanted-body vignette">
    <nav class="navbar navbar-expand-lg shadow-sm navbar-custom">
        <div class="container">
            <a class="navbar-brand fw-bold brand-title" href="course_manager.php">
                <?php echo $isCourseSpecific ? '← ' . htmlspecialchars($courseInfo['name']) : 'Course Manager'; ?>
            </a>
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="text-yellow-300">Welcome, <?php echo htmlspecialchars($user['name']); ?>!</span>
                <a href="home.php" class="btn btn-outline-warning">Home</a>
                <button class="btn btn-outline-danger" onclick="logout()">Logout</button>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <div class="d-flex align-items-center gap-3">
            <?php if ($isCourseSpecific): ?>
                <a href="course_dashboard.php?course_id=<?php echo $courseId; ?>" class="btn btn-outline-warning me-2">
                    <i class="fas fa-arrow-left"></i> Back to Course
                </a>
            <?php endif; ?>
            <h1 class="h3 m-0 brand-title">
                <?php echo $isCourseSpecific ? 'Course Roadmap' : 'Journey of Learning'; ?>
            </h1>
            <span class="badge text-bg-secondary"><?php echo count($rows); ?> items</span>
            <?php if ($isCourseSpecific): ?>
                <span class="badge text-bg-info"><?php echo htmlspecialchars($courseInfo['name']); ?></span>
            <?php endif; ?>
            <?php if (count($rows) === 0): ?>
                <span class="badge text-bg-warning">Debug: No data loaded</span>
            <?php endif; ?>
        </div>

        <?php if (empty($rows)): ?>
            <div class="alert alert-info mt-4" role="alert">
                No items yet. Generate from <a class="alert-link" href="course_manager.php">Course Manager</a>.
            </div>
        <?php else: ?>
            <div class="mt-3">
                <div id="roadmapWrap" class="position-relative rounded-3 shadow-lg overflow-hidden" style="height: 560px; background: linear-gradient(180deg, rgba(20,33,21,.35) 0%, rgba(20,33,21,0) 30%), transparent; border: 2px solid rgba(255,215,0,0.2);">
                    <div id="stage">
                        <canvas id="particlesCanvas"></canvas>
                        <svg id="roadmapSvg" width="100%" height="100%" viewBox="0 0 1200 520" preserveAspectRatio="none" class="w-100 h-100">
                            <defs>
                                <linearGradient id="goldPathGradient" x1="0" x2="0" y1="0" y2="1">
                                    <stop offset="0%" stop-color="#FFFDE7" />
                                    <stop offset="50%" stop-color="#FFD700" />
                                    <stop offset="100%" stop-color="#FFB300" />
                                </linearGradient>
                                <filter id="goldGlow" x="-50%" y="-50%" width="200%" height="200%">
                                    <feGaussianBlur stdDeviation="6" result="blur" />
                                    <feMerge>
                                        <feMergeNode in="blur" />
                                        <feMergeNode in="SourceGraphic" />
                                    </feMerge>
                                </filter>
                            </defs>
                            
                            <path id="roadPath" d="M40,480 C200,360 320,120 520,160 S860,460 1040,300 1120,140 1160,200" fill="none" stroke="url(#goldPathGradient)" stroke-linecap="round" stroke-width="15" opacity="0.4" filter="url(#goldGlow)" />
                            <path id="roadStroke" d="M40,480 C200,360 320,120 520,160 S860,460 1040,300 1120,140 1160,200" fill="none" stroke="url(#goldPathGradient)" stroke-linecap="round" stroke-width="5" />
                        </svg>
                        <div id="pinsLayer" class="position-absolute top-0 start-0 w-100 h-100"></div>
                    </div>
                    <div class="zoom-controls rounded-3 p-2 d-flex align-items-center gap-2">
                        <span class="text-warning small fw-semibold">Zoom</span>
                        <input id="zoomRange" type="range" min="60" max="140" step="5" value="100">
                        <button id="zoomReset" class="btn btn-sm btn-outline-warning">Reset</button>
                    </div>
                </div>
            </div>
            <div class="mt-3 legend small text-secondary">
                <span class="fw-semibold me-2 brand-title" style="color:#FFD700">Legend</span>
                <span><span class="dot" style="background:#FFD700;"></span>Milestone</span>
                <span><span class="dot" style="background:#AEEA00;"></span>Highlight</span>
                <span><span class="dot" style="background:#FFB300;"></span>Upcoming</span>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var data = [
            <?php foreach ($rows as $i => $row): ?>
            {
                index: <?php echo (int)$i; ?>,
                title: <?php echo json_encode($row[0] ?? ''); ?>,
                content: <?php echo json_encode($row[1] ?? ''); ?>
            }<?php echo $i < count($rows) - 1 ? ',' : ''; ?>
            <?php endforeach; ?>
        ];
        
        // Debug: Log data
        console.log('Roadmap data loaded:', data);
        console.log('Number of items:', data.length);
        
        var path = document.getElementById('roadPath');
        var stroke = document.getElementById('roadStroke');
        var wrap = document.getElementById('roadmapWrap');
        var stage = document.getElementById('stage');
        var layer = document.getElementById('pinsLayer');
        var particles = document.getElementById('particlesCanvas');
        var zoomRange = document.getElementById('zoomRange');
        var zoomReset = document.getElementById('zoomReset');
        
        // Debug: Check elements
        console.log('Elements found:', {
            path: !!path,
            stroke: !!stroke,
            wrap: !!wrap,
            stage: !!stage,
            layer: !!layer,
            particles: !!particles,
            dataLength: data.length
        });
        
        if (!path || !stroke || !wrap || !layer || !stage || !particles || !data.length) {
            console.log('Roadmap initialization failed - missing elements or data');
            return;
        }

        var total = data.length;
        var totalLen = path.getTotalLength();

        // Animate the road stroke draw
        try {
            stroke.style.strokeDasharray = totalLen;
            stroke.style.strokeDashoffset = totalLen;
            stroke.style.transition = 'stroke-dashoffset 1.8s ease-out';
            requestAnimationFrame(function(){ stroke.style.strokeDashoffset = '0'; });
        } catch (e) {}

        data.forEach(function (item, idx) {
            var t = (idx + 1) / (total + 1); // space nodes across the road
            var pt = path.getPointAtLength(totalLen * t);

            var pin = document.createElement('a');
            pin.href = '#';
            pin.className = 'pin d-inline-block position-absolute text-decoration-none';
            pin.setAttribute('data-bs-toggle', 'popover');
            pin.setAttribute('data-bs-trigger', 'hover focus');
            pin.setAttribute('data-bs-placement', 'top');
            pin.setAttribute('data-bs-title', item.title);
            pin.setAttribute('data-bs-content', item.content || 'No details provided.');
            pin.setAttribute('data-bs-custom-class', 'pop-gold');
            
            // Add click handler to navigate to learning.php with session data
            pin.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Store lesson data in session storage
                var lessonData = {
                    index: item.index,
                    title: item.title,
                    content: item.content || '',
                    courseId: '<?php echo $courseId; ?>',
                    courseName: '<?php echo $isCourseSpecific ? htmlspecialchars($courseInfo['name']) : 'General Course'; ?>'
                };
                
                sessionStorage.setItem('currentLesson', JSON.stringify(lessonData));
                
                // Redirect to learning page
                window.location.href = 'learning.php';
            });

            var marker = document.createElement('div');
            marker.className = 'marker';
            pin.appendChild(marker);

            var label = document.createElement('div');
            label.className = 'label-plate small text-center mt-2 fw-semibold';
            label.textContent = item.title;
            pin.appendChild(label);

            // position (account for marker size ~ 20x20)
            pin.style.left = (pt.x - 10) + 'px';
            pin.style.top = (pt.y - 10) + 'px';

            layer.appendChild(pin);
            setTimeout(function(){ pin.classList.add('show'); }, 80 * idx);
        });

        // Enable Bootstrap popovers
        var popovers = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
            .map(function (el) { return new bootstrap.Popover(el, { container: 'body', customClass: 'pop-gold', html: true }); });

        // Particles (fireflies) background
        (function initParticles(){
            var ctx = particles.getContext('2d');
            var w, h, dpr = Math.max(1, window.devicePixelRatio || 1);
            var fireflies = [];
            function resize(){
                w = wrap.clientWidth; h = wrap.clientHeight;
                particles.width = Math.floor(w * dpr);
                particles.height = Math.floor(h * dpr);
                particles.style.width = w + 'px';
                particles.style.height = h + 'px';
                ctx.setTransform(dpr,0,0,dpr,0,0);
            }
            function spawn(count){
                fireflies = [];
                for (var i=0;i<count;i++){
                    fireflies.push({
                        x: Math.random()*w,
                        y: Math.random()*h,
                        r: 1 + Math.random()*1.6,
                        vx: (-.2 + Math.random()*.4),
                        vy: (-.15 + Math.random()*.3),
                        tw: Math.random()*Math.PI*2
                    });
                }
            }
            function draw(){
                ctx.clearRect(0,0,w,h);
                for (var i=0;i<fireflies.length;i++){
                    var f = fireflies[i];
                    f.x += f.vx; f.y += f.vy;
                    f.tw += 0.02;
                    var glow = (Math.sin(f.tw)*0.5+0.5)*0.7 + 0.3;
                    if (f.x < -10) f.x = w+10; if (f.x > w+10) f.x = -10;
                    if (f.y < -10) f.y = h+10; if (f.y > h+10) f.y = -10;
                    var grad = ctx.createRadialGradient(f.x, f.y, 0, f.x, f.y, 18);
                    grad.addColorStop(0, 'rgba(255, 215, 0,' + (0.45*glow) + ')');
                    grad.addColorStop(0.6, 'rgba(255, 215, 0,' + (0.15*glow) + ')');
                    grad.addColorStop(1, 'rgba(255, 215, 0,0)');
                    ctx.fillStyle = grad;
                    ctx.beginPath();
                    ctx.arc(f.x, f.y, 18, 0, Math.PI*2);
                    ctx.fill();
                    ctx.fillStyle = 'rgba(255, 253, 231,'+ (.8*glow) +')';
                    ctx.beginPath(); ctx.arc(f.x, f.y, f.r, 0, Math.PI*2); ctx.fill();
                }
                requestAnimationFrame(draw);
            }
            resize();
            spawn(Math.max(24, Math.floor((wrap.clientWidth*wrap.clientHeight)/38000)));
            draw();
            window.addEventListener('resize', function(){
                var prevCount = fireflies.length;
                resize();
                spawn(prevCount);
            });
        })();

        // Zoom controls
        function applyZoom(val){
            var scale = (parseInt(val, 10) || 100) / 100;
            stage.style.transform = 'scale(' + scale + ')';
        }
        zoomRange.addEventListener('input', function(e){ applyZoom(e.target.value); });
        zoomReset.addEventListener('click', function(){ zoomRange.value = 100; applyZoom(100); });
        applyZoom(zoomRange.value);
    });

    // Logout function
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