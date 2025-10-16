<?php
$dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
$csvFile = $dataDir . DIRECTORY_SEPARATOR . 'roadmap.csv';
$index = isset($_GET['i']) ? intval($_GET['i']) : -1;

$rows = [];
if (is_file($csvFile)) {
  if (($h = fopen($csvFile, 'r')) !== false) {
    $header = fgetcsv($h);
    while (($r = fgetcsv($h)) !== false) {
      $rows[] = $r;
    }
    fclose($h);
  }
}

$item = null;
if ($index >= 0 && $index < count($rows)) {
  $item = $rows[$index];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Roadmap Detail</title>
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
      <div class="ms-auto">
        <a href="dashboard.php" class="btn btn-outline-primary">Back to Dashboard</a>
      </div>
    </div>
  </nav>

  <main class="container py-4">
    <?php if (!$item): ?>
      <div class="alert alert-warning" role="alert">Item not found.</div>
    <?php else: ?>
      <div class="card shadow-sm fade-in">
        <div class="card-body">
          <h1 class="h4 mb-3"><?php echo htmlspecialchars($item[0] ?? ''); ?></h1>
          <div class="text-muted"><?php echo nl2br(htmlspecialchars($item[1] ?? '')); ?></div>
        </div>
      </div>
    <?php endif; ?>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>


