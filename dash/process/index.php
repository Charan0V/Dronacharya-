<?php
// Simple form to accept a topic and optional extra text
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
    });
  </script>
  </head>
  <body class="bg-light">
    <nav class="navbar navbar-expand-lg bg-white shadow-sm">
      <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">Roadmap</a>
      </div>
    </nav>
    <div class="container py-4">
      <div class="card shadow-sm fade-in">
        <div class="card-body">
          <h1 class="h4">Create Roadmap</h1>
          <form method="POST" action="process.php" class="mt-3">
            <label for="topic" class="form-label">Topic</label>
            <input id="topic" name="topic" type="text" class="form-control" placeholder="e.g., React Basics" required />

            <label for="extra" class="form-label mt-3">Extra instruction (optional)</label>
            <textarea id="extra" name="extra" rows="4" class="form-control" placeholder="Any additional guidance"></textarea>

            <button type="submit" class="btn btn-primary mt-3">Generate</button>
          </form>
          <div class="mt-3">
            <a class="link-primary" href="dashboard.php">Go to Dashboard</a>
          </div>
        </div>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  </body>
</html>


