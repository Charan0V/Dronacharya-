<?php
$dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
$csvFile = $dataDir . DIRECTORY_SEPARATOR . 'roadmap.csv';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Roadmap Dashboard</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <!-- Tailwind CSS (CDN) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .roadmap-card.fade { opacity: 0; transform: translateY(12px); transition: opacity .5s ease, transform .5s ease; }
    .roadmap-card.show { opacity: 1; transform: translateY(0); }
  </style>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const cards = document.querySelectorAll('.roadmap-card');
      const io = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('show');
            io.unobserve(entry.target);
          }
        });
      }, { threshold: 0.15 });
      cards.forEach(c => io.observe(c));
    });
  </script>
  <style>
    a { text-decoration: none; }
  </style>
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg bg-white shadow-sm">
    <div class="container">
      <a class="navbar-brand fw-bold" href="#">Roadmap</a>
      <div class="ms-auto">
        <a href="index.php" class="btn btn-primary">Create new</a>
      </div>
    </div>
  </nav>

  <main class="container py-4">
    <div class="d-flex align-items-center gap-3">
      <h1 class="h3 m-0">Roadmap Dashboard</h1>
      <span class="badge text-bg-secondary"><?php echo count($rows); ?> items</span>
    </div>

    <?php if (empty($rows)): ?>
      <div class="alert alert-info mt-4" role="alert">
        No items yet. Generate from <a class="alert-link" href="index.php">Create Roadmap</a>.
      </div>
    <?php else: ?>
      <div class="mt-3">
        <div id="roadmapWrap" class="position-relative bg-white rounded-3 shadow-sm overflow-hidden" style="height: 520px;">
          <svg id="roadmapSvg" width="100%" height="100%" viewBox="0 0 1200 520" preserveAspectRatio="none" class="w-100 h-100">
            <defs>
              <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%">
                <feDropShadow dx="0" dy="2" stdDeviation="3" flood-color="#000" flood-opacity="0.25"/>
              </filter>
            </defs>
            <path id="roadPath" d="M40,480 C200,360 320,120 520,160 S860,460 1040,300 1120,140 1160,200" fill="none" stroke="#2f3640" stroke-linecap="round" stroke-width="18" filter="url(#shadow)" />
            <path d="M40,480 C200,360 320,120 520,160 S860,460 1040,300 1120,140 1160,200" fill="none" stroke="#f1c40f" stroke-width="3" stroke-dasharray="14 12" opacity="0.9" />
          </svg>
          <div id="pinsLayer" class="position-absolute top-0 start-0 w-100 h-100"></div>
        </div>
        <div class="d-flex align-items-center gap-2 mt-2 text-muted small">
          <span class="d-inline-block" style="width:18px;height:18px;background:#2f3640;border-radius:3px"></span>
          <span>Road</span>
          <span class="ms-3 d-inline-block" style="width:18px;height:2px;background:#f1c40f"></span>
          <span>Center line</span>
        </div>
      </div>
    <?php endif; ?>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Build fun map-like pins along the SVG road
      var data = [
        <?php foreach ($rows as $i => $row): ?>
          {
            index: <?php echo (int)$i; ?>,
            title: <?php echo json_encode($row[0] ?? ''); ?>,
            content: <?php echo json_encode($row[1] ?? ''); ?>
          }<?php echo $i < count($rows) - 1 ? ',' : ''; ?>
        <?php endforeach; ?>
      ];
      var path = document.getElementById('roadPath');
      var wrap = document.getElementById('roadmapWrap');
      var layer = document.getElementById('pinsLayer');
      if (!path || !wrap || !layer || !data.length) return;

      var total = data.length;
      var totalLen = path.getTotalLength();

      data.forEach(function (item, idx) {
        var t = (idx + 1) / (total + 1); // space nodes across the road
        var pt = path.getPointAtLength(totalLen * t);

        var pin = document.createElement('a');
        pin.href = 'detail.php?i=' + item.index;
        pin.className = 'pin d-inline-block position-absolute text-decoration-none';
        pin.setAttribute('data-bs-toggle', 'popover');
        pin.setAttribute('data-bs-trigger', 'hover focus');
        pin.setAttribute('data-bs-placement', 'top');
        pin.setAttribute('data-bs-title', item.title);
        pin.setAttribute('data-bs-content', item.content || item.title);

        // marker element
        var marker = document.createElement('div');
        marker.className = 'marker';
        pin.appendChild(marker);

        // label below
        var label = document.createElement('div');
        label.className = 'small text-center mt-1 text-dark fw-semibold';
        label.textContent = item.title;
        pin.appendChild(label);

        // position (account for marker size ~ 24x24)
        pin.style.left = (pt.x - 12) + 'px';
        pin.style.top = (pt.y - 24) + 'px';

        layer.appendChild(pin);

        // small staggered reveal
        setTimeout(function(){ pin.classList.add('show'); }, 60 * idx);
      });

      // Enable Bootstrap popovers
      var popovers = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
        .map(function (el) { return new bootstrap.Popover(el, { container: 'body' }); });
    });
  </script>
  <style>
    /* pin style: map drop-pin */
    .pin { opacity: 0; transform: translateY(8px); transition: opacity .4s ease, transform .4s ease; }
    .pin.show { opacity: 1; transform: translateY(0); }
    .pin .marker {
      width: 22px; height: 22px; background: #e74c3c; border-radius: 50% 50% 50% 0;
      transform: rotate(-45deg);
      position: relative;
      box-shadow: 0 2px 6px rgba(0,0,0,.25);
    }
    .pin .marker::after {
      content: '';
      position: absolute; inset: 4px; background: #fff; border-radius: 50%;
      transform: rotate(45deg);
    }
  </style>
</body>
</html>


