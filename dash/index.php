<?php
// Simple form to accept a topic and optional extra text
session_start();
require_once '../auth/check_auth.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';

// Get course name from sessionStorage (will be handled by JavaScript)
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Roadmap Generator</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <!-- Tailwind CSS (CDN) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .fade-in { opacity: 0; transform: translateY(8px); transition: opacity .4s ease, transform .4s ease; }
    .fade-in.show { opacity: 1; transform: translateY(0); }
  </style>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const el = document.querySelector('.fade-in');
      requestAnimationFrame(() => el && el.classList.add('show'));
      
      // Pre-fill topic with course name from sessionStorage
      const courseName = sessionStorage.getItem('newCourseName');
      if (courseName) {
        document.getElementById('topic').value = courseName;
        // Clear the stored course name
        sessionStorage.removeItem('newCourseName');
      }
    });
  </script>
  </head>
  <body class="bg-light" style="background: linear-gradient(180deg,#f5e6c9 0%, #efdfbf 45%, #e0cfa6 100%); color:#3b2f1c;">
    <nav class="navbar navbar-expand-lg bg-white/70 shadow-sm" style="backdrop-filter: blur(6px);">
      <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo $isLoggedIn ? 'course_manager.php' : 'home.php'; ?>" style="font-family: 'Cinzel', serif; color:#b58900;">Dronacharya</a>
        <?php if ($isLoggedIn): ?>
          <div class="ms-auto">
            <span class="text-muted me-3">Welcome, <?php echo htmlspecialchars($userName); ?>!</span>
            <a href="course_manager.php" class="btn btn-outline-primary btn-sm me-2">Course Manager</a>
            <a href="home.php" class="btn btn-outline-secondary btn-sm">Home</a>
          </div>
        <?php endif; ?>
      </div>
    </nav>
    <div class="container py-4">
      <div class="card shadow-sm fade-in" style="border:1px solid rgba(79,50,21,.25); background:rgba(255,253,246,.8);">
        <div class="card-body">
          <h1 class="h4" style="font-family: 'Cinzel', serif; color:#8a6a28;">Create Course Roadmap</h1>
          <form method="POST" action="process.php" class="mt-3">
            <label for="topic" class="form-label">Course Topic</label>
            <input id="topic" name="topic" type="text" class="form-control" placeholder="e.g., React Basics" required />

            <label for="extra" class="form-label mt-3">Extra instruction (optional)</label>
            <textarea id="extra" name="extra" rows="4" class="form-control" placeholder="Any additional guidance for the course roadmap"></textarea>

            <button type="submit" class="btn btn-primary mt-3">Generate Course Roadmap</button>
          </form>
          <div class="mt-3">
            <?php if ($isLoggedIn): ?>
              <a class="link-primary" href="course_manager.php">Back to Course Manager</a>
            <?php else: ?>
              <a class="link-primary" href="home.php">Go to Home</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  </body>
</html>


