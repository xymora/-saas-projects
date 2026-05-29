<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#c8956c">
<title><?= htmlspecialchars($pageTitle ?? 'Mi Boutique') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
<?php if (strpos($_SERVER['REQUEST_URI'] ?? '/', '/boutique-laulaa') !== false): ?>
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='90'%3E👗%3C/text%3E%3C/svg%3E">
<?php else: ?>
<link rel="icon" href="data:,">
<?php endif; ?>
<!-- BASE_URL inyectada para JS -->
<script>const BASE = '<?= BASE_URL ?>';</script>
</head>
<body>
