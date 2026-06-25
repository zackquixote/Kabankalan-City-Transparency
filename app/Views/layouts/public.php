<?php

/**
 * @var string         $title
 * @var string|null    $metaDescription
 * @var string         $content
 * @var string|null    $ogTitle
 * @var string|null    $ogDescription
 * @var string|null    $ogImage
 * @var string|null    $ogUrl
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Kabankalan Budget Portal') ?></title>
    <?php if (! empty($metaDescription)): ?>
        <meta name="description" content="<?= esc($metaDescription, 'attr') ?>">
    <?php endif; ?>

    <!-- Open Graph / Social Sharing -->
    <meta property="og:type"        content="website">
    <meta property="og:site_name"   content="Kabankalan Budget Transparency Portal">
    <meta property="og:title"       content="<?= esc($ogTitle ?? $title ?? 'Kabankalan Budget Portal', 'attr') ?>">
    <meta property="og:description" content="<?= esc($ogDescription ?? $metaDescription ?? 'Open budget data for accountable governance in Kabankalan City.', 'attr') ?>">
    <meta property="og:url"         content="<?= esc($ogUrl ?? current_url(), 'attr') ?>">
    <?php if (! empty($ogImage)): ?>
    <meta property="og:image"       content="<?= esc($ogImage, 'attr') ?>">
    <?php endif; ?>
    <!-- Twitter Card -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= esc($ogTitle ?? $title ?? 'Kabankalan Budget Portal', 'attr') ?>">
    <meta name="twitter:description" content="<?= esc($ogDescription ?? $metaDescription ?? 'Open budget data for accountable governance in Kabankalan City.', 'attr') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --kb-primary: #0b4f6c; --kb-accent: #01baef; }
        body { font-family: "Segoe UI", system-ui, sans-serif; color: #1a1a1a; }
        .navbar-kb { background: var(--kb-primary); }
        .navbar-kb .navbar-brand, .navbar-kb .nav-link { color: #fff !important; }
        .navbar-kb .nav-link.active { font-weight: 600; text-decoration: underline; }
        .hero-kb { background: linear-gradient(135deg, #0b4f6c 0%, #145da0 100%); color: #fff; }
        .stat-card { border-left: 4px solid var(--kb-accent); }
        .footer-kb { background: #f4f6f8; border-top: 1px solid #dee2e6; }
        .badge-status { text-transform: capitalize; }
        .honeypot-wrap { display: none !important; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <?= view('partials/nav') ?>

    <main class="flex-grow-1">
        <?php if (session()->getFlashdata('success')): ?>
            <div class="container mt-3">
                <div class="alert alert-success mb-0" role="alert"><?= esc(session()->getFlashdata('success')) ?></div>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="container mt-3">
                <div class="alert alert-danger mb-0" role="alert"><?= esc(session()->getFlashdata('error')) ?></div>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <footer class="footer-kb py-4 mt-5">
        <div class="container text-center text-muted small">
            <p class="mb-1">City Government of Kabankalan — Budget Transparency Portal</p>
            <p class="mb-0">Open data for accountable governance. No login required to browse public records.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
