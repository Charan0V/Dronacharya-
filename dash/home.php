<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dronacharya - Multimodal Tutor</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Tailwind -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Marcellus+SC&family=Poppins:wght@400;600&display=swap" rel="stylesheet">

  <style>
    body { font-family: "Poppins", sans-serif; }
    .epic-bg {
      background-image: url('assets/img.jpg');
      background-size: cover;
      background-position: center 20%;
      position: relative;
    }
    .epic-bg::before {
      content: "";
      position: absolute;
      inset: 0;
      background: rgba(0,0,0,0.7);
      z-index: 1;
    }
    .epic-bg > .container {
      position: relative;
      z-index: 2;
    }
    .marcellus { font-family: "Marcellus SC", serif; }
    .glow-text {
      text-shadow: 0 0 10px rgba(250, 204, 21, 0.8), 0 0 20px rgba(250, 204, 21, 0.6);
    }
    .feature-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 8px 15px rgba(0,0,0,0.5);
    }
    .alert {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
      min-width: 300px;
    }
  </style>
</head>
<body class="bg-slate-900 text-slate-100">

  <!-- Alert Container -->
  <div id="alertContainer"></div>

  <!-- Navbar -->
  <nav class="navbar navbar-dark bg-transparent position-absolute w-100 z-10 px-4 py-3">
    <a class="navbar-brand d-flex align-items-center gap-2 text-yellow-300 marcellus text-xl" href="home.php">
      <i class="fa-solid fa-dharmachakra"></i> Dronacharya
    </a>
    <div>
      <?php if ($isLoggedIn): ?>
        <span class="text-yellow-300 me-3">Welcome, <?php echo htmlspecialchars($userName); ?>!</span>
        <a href="course_manager.php" class="btn btn-warning text-dark fw-bold me-2">Course Manager</a>
        <button class="btn btn-outline-warning" onclick="logout()">Logout</button>
      <?php else: ?>
        <button class="btn btn-outline-warning me-2" data-bs-toggle="modal" data-bs-target="#authModal">Login</button>
        <button class="btn btn-warning text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#authModal">Sign Up</button>
      <?php endif; ?>
    </div>
  </nav>

  <!-- Hero Section -->
  <header class="epic-bg min-h-screen flex items-center">
    <div class="container text-center py-20">
      <h1 class="text-6xl md:text-7xl font-bold text-yellow-300 marcellus glow-text">Learn Like a Disciple</h1>
      <p class="mt-6 text-lg md:text-xl text-slate-200 max-w-2xl mx-auto">
        Step into a new era of learning guided by your AI Guru — personalized, interactive, and multimodal.
      </p>
      <div class="mt-8 flex justify-center gap-4">
        <?php if ($isLoggedIn): ?>
          <a href="course_manager.php" class="btn btn-lg btn-warning text-dark px-6 fw-bold shadow-lg">Continue Learning</a>
          <a href="index.php" class="btn btn-lg btn-outline-warning px-6">Create New Course</a>
        <?php else: ?>
          <a href="course_manager.php" class="btn btn-lg btn-warning text-dark px-6 fw-bold shadow-lg">Start Your Journey</a>
        <?php endif; ?>
        <a href="#features" class="btn btn-lg btn-outline-light px-6">Explore Features</a>
      </div>
    </div>
  </header>

  <!-- Features Section -->
  <section id="features" class="container px-6 py-16">
    <h2 class="text-4xl font-bold text-center mb-12 text-yellow-300 marcellus">Why Dronacharya?</h2>
    <div class="grid md:grid-cols-3 gap-8">
      <div class="feature-card bg-slate-800 p-6 rounded-xl transition duration-300">
        <i class="fa-solid fa-user-tie text-4xl text-yellow-300"></i>
        <h3 class="text-xl font-semibold mt-4">Guru Avatar</h3>
        <p class="mt-2 text-slate-300">An AI-powered speaking avatar guides you like an ancient master.</p>
      </div>
      <div class="feature-card bg-slate-800 p-6 rounded-xl transition duration-300">
        <i class="fa-solid fa-sitemap text-4xl text-yellow-300"></i>
        <h3 class="text-xl font-semibold mt-4">Roadmaps</h3>
        <p class="mt-2 text-slate-300">Follow structured learning paths inspired by Mahabharata formations.</p>
      </div>
      <div class="feature-card bg-slate-800 p-6 rounded-xl transition duration-300">
        <i class="fa-solid fa-scroll text-4xl text-yellow-300"></i>
        <h3 class="text-xl font-semibold mt-4">Live Notes</h3>
        <p class="mt-2 text-slate-300">AI auto-summarizes lessons into live notes for easy recall.</p>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="py-6 text-center text-slate-400 border-t border-slate-700">
    © 2025 Dronacharya. All Rights Reserved.
  </footer>

  <!-- Auth Modal -->
  <div class="modal fade" id="authModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-gradient-to-r from-yellow-100 to-amber-200 border border-amber-600 shadow-xl rounded-xl">
        
        <!-- Header with Tabs -->
        <div class="modal-header border-0 justify-content-center">
          <ul class="nav nav-tabs border-0" id="authTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active fw-bold text-amber-800" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">
                Login
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link fw-bold text-amber-800" id="signup-tab" data-bs-toggle="tab" data-bs-target="#signup" type="button" role="tab">
                Signup
              </button>
            </li>
          </ul>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <!-- Body with Forms -->
        <div class="modal-body">
          <div class="tab-content" id="authTabsContent">
            
            <!-- Login Form -->
            <div class="tab-pane fade show active" id="login" role="tabpanel">
              <form id="loginForm">
                <div class="mb-3">
                  <label class="fw-bold text-amber-900">Email</label>
                  <input type="email" name="email" class="form-control border border-amber-600" placeholder="you@example.com" required>
                </div>
                <div class="mb-3">
                  <label class="fw-bold text-amber-900">Password</label>
                  <input type="password" name="password" class="form-control border border-amber-600" placeholder="••••••••" required>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <a href="#" class="text-decoration-none text-amber-800 small">Forgot password?</a>
                </div>
                <button type="submit" class="btn btn-warning w-100 fw-bold shadow">Login</button>
              </form>
            </div>

            <!-- Signup Form -->
            <div class="tab-pane fade" id="signup" role="tabpanel">
              <form id="signupForm">
                <div class="mb-3">
                  <label class="fw-bold text-amber-900">Full Name</label>
                  <input type="text" name="full_name" class="form-control border border-amber-600" placeholder="Arjuna Pandava" required>
                </div>
                <div class="mb-3">
                  <label class="fw-bold text-amber-900">Email</label>
                  <input type="email" name="email" class="form-control border border-amber-600" placeholder="you@example.com" required>
                </div>
                <div class="mb-3">
                  <label class="fw-bold text-amber-900">Password</label>
                  <input type="password" name="password" class="form-control border border-amber-600" placeholder="Create a strong password" required>
                </div>
                <button type="submit" class="btn btn-warning w-100 fw-bold shadow">Create Account</button>
              </form>
            </div>
            
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
  
  <script>
    // Show alert function
    function showAlert(message, type = 'success') {
      const alertContainer = document.getElementById('alertContainer');
      const alertId = 'alert-' + Date.now();
      
      const alertHTML = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
          ${message}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      `;
      
      alertContainer.insertAdjacentHTML('beforeend', alertHTML);
      
      // Auto remove after 5 seconds
      setTimeout(() => {
        const alert = document.getElementById(alertId);
        if (alert) {
          alert.remove();
        }
      }, 5000);
    }

    // Login form handler
    document.getElementById('loginForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      formData.append('action', 'login');
      
      try {
        const response = await fetch('../auth/auth.php', {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          showAlert(result.message, 'success');
          setTimeout(() => {
            location.reload();
          }, 1500);
        } else {
          showAlert(result.message, 'danger');
        }
      } catch (error) {
        showAlert('An error occurred. Please try again.', 'danger');
      }
    });

    // Signup form handler
    document.getElementById('signupForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      formData.append('action', 'register');
      
      try {
        const response = await fetch('../auth/auth.php', {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          showAlert(result.message, 'success');
          // Switch to login tab
          document.getElementById('signup-tab').classList.remove('active');
          document.getElementById('signup').classList.remove('show', 'active');
          document.getElementById('login-tab').classList.add('active');
          document.getElementById('login').classList.add('show', 'active');
        } else {
          showAlert(result.message, 'danger');
        }
      } catch (error) {
        showAlert('An error occurred. Please try again.', 'danger');
      }
    });

    // Logout function
    async function logout() {
      try {
        const formData = new FormData();
        formData.append('action', 'logout');
        
        const response = await fetch('../auth/auth.php', {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          showAlert(result.message, 'success');
          setTimeout(() => {
            location.reload();
          }, 1500);
        } else {
          showAlert(result.message, 'danger');
        }
      } catch (error) {
        showAlert('An error occurred. Please try again.', 'danger');
      }
    }

    // Clear form when modal is hidden
    document.getElementById('authModal').addEventListener('hidden.bs.modal', function() {
      document.getElementById('loginForm').reset();
      document.getElementById('signupForm').reset();
    });
  </script>
</body>
</html>
