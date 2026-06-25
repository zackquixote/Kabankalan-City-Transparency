<?php
/**
 * Shared admin layout.
 *
 * Expected variables:
 *   @var string     $title
 *   @var array      $currentUser
 *   @var string     $content      (rendered inner view)
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Admin') ?> — Kabankalan Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --kb-primary:   #0b4f6c;
            --kb-accent:    #01baef;
            --kb-sidebar:   #0a3d54;
            --kb-sidebar-w: 250px;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            min-height: 100vh;
        }

        /* ── Sidebar ─────────────────────────────────────────────── */
        #admin-sidebar {
            position: fixed; top: 0; left: 0;
            width: var(--kb-sidebar-w);
            height: 100vh;
            background: var(--kb-sidebar);
            color: #e2e8f0;
            display: flex; flex-direction: column;
            z-index: 1040;
            overflow-y: auto;
        }
        .sidebar-brand {
            padding: 1.25rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,.1);
            font-weight: 700; font-size: 1rem;
            color: #fff; text-decoration: none;
            display: flex; align-items: center; gap: .5rem;
        }
        .sidebar-brand:hover { color: var(--kb-accent); }
        .sidebar-section {
            padding: .5rem 1rem .25rem;
            font-size: .6875rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: .08em;
            color: #94a3b8;
        }
        .sidebar-link {
            display: flex; align-items: center; gap: .625rem;
            padding: .5rem 1rem;
            color: #cbd5e1; text-decoration: none;
            font-size: .875rem;
            border-left: 3px solid transparent;
            transition: all .15s;
        }
        .sidebar-link:hover, .sidebar-link.active {
            color: #fff; background: rgba(255,255,255,.07);
            border-left-color: var(--kb-accent);
        }
        .sidebar-link i { width: 1.1rem; text-align: center; }
        .sidebar-footer {
            margin-top: auto; padding: 1rem;
            border-top: 1px solid rgba(255,255,255,.1);
            font-size: .75rem; color: #94a3b8;
        }
        .sidebar-user { font-weight: 600; color: #e2e8f0; margin-bottom: .25rem; }
        .sidebar-role {
            display: inline-block; font-size: .65rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: .06em;
            background: var(--kb-accent); color: #000;
            padding: .1rem .45rem; border-radius: 4px;
        }

        /* ── Main wrapper ────────────────────────────────────────── */
        #admin-main {
            margin-left: var(--kb-sidebar-w);
            min-height: 100vh;
        }
        #admin-topbar {
            position: sticky; top: 0; z-index: 1030;
            background: #fff; border-bottom: 1px solid #e2e8f0;
            padding: .75rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
        }
        .topbar-title { font-weight: 700; font-size: 1.125rem; color: var(--kb-primary); }
        #admin-content { padding: 1.5rem; }

        /* ── Cards / tables ──────────────────────────────────────── */
        .admin-card {
            background: #fff; border-radius: .75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.08);
            border: 1px solid #e8edf2;
        }
        .admin-card .card-header {
            background: transparent; border-bottom: 1px solid #e8edf2;
            font-weight: 600; padding: 1rem 1.25rem;
        }
        .table th { background: #f8fafc; font-size: .78rem; text-transform: uppercase;
                    letter-spacing: .05em; color: #64748b; border-color: #e8edf2; }
        .table td { vertical-align: middle; border-color: #f1f5f9; }
        .badge-draft      { background: #e2e8f0; color: #475569; }
        .badge-submitted  { background: #dbeafe; color: #1d4ed8; }
        .badge-under_review { background: #fef9c3; color: #92400e; }
        .badge-approved   { background: #d1fae5; color: #065f46; }
        .badge-published  { background: #bbf7d0; color: #14532d; }
        .badge-completed  { background: #ede9fe; color: #5b21b6; }
        .badge-cancelled  { background: #fee2e2; color: #991b1b; }

        /* ── Buttons ─────────────────────────────────────────────── */
        .btn-kb-primary { background: var(--kb-primary); border-color: var(--kb-primary); color: #fff; }
        .btn-kb-primary:hover { background: #093e56; border-color: #093e56; color: #fff; }

        /* ── Forms ───────────────────────────────────────────────── */
        .form-label { font-weight: 500; font-size: .875rem; }
        .form-control:focus, .form-select:focus {
            border-color: var(--kb-accent);
            box-shadow: 0 0 0 .2rem rgba(1,186,239,.2);
        }

        @media (max-width: 767px) {
            #admin-sidebar { transform: translateX(-100%); }
            #admin-main    { margin-left: 0; }
        }
    </style>
</head>
<body>

<!-- ── Sidebar ──────────────────────────────────────────────────── -->
<aside id="admin-sidebar">
    <a href="<?= site_url('admin/dashboard') ?>" class="sidebar-brand">
        <i class="bi bi-shield-lock-fill text-info"></i>
        Kabankalan Admin
    </a>

    <nav class="py-2">
        <div class="sidebar-section">Main</div>
        <a href="<?= site_url('admin/dashboard') ?>"
           class="sidebar-link <?= str_contains(current_url(), 'admin/dashboard') ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <?php if (in_array($currentUser['role'], ['super_admin', 'admin', 'office_staff'])): ?>
        <div class="sidebar-section mt-2">Projects</div>
        <a href="<?= site_url('admin/projects') ?>"
           class="sidebar-link <?= str_contains(current_url(), 'admin/projects') ? 'active' : '' ?>">
            <i class="bi bi-folder2-open"></i> Projects
        </a>
        <a href="<?= site_url('admin/feedback') ?>"
           class="sidebar-link <?= str_contains(current_url(), 'admin/feedback') ? 'active' : '' ?>">
            <i class="bi bi-chat-dots"></i> Feedback
        </a>
        <a href="<?= site_url('admin/reports') ?>"
           class="sidebar-link <?= str_contains(current_url(), 'admin/reports') ? 'active' : '' ?>">
            <i class="bi bi-bar-chart-line"></i> Reports
        </a>
        <?php endif; ?>

        <?php if (in_array($currentUser['role'], ['super_admin', 'admin'])): ?>
        <div class="sidebar-section mt-2">Reference Data</div>
        <a href="<?= site_url('admin/visions') ?>"
           class="sidebar-link <?= str_contains(current_url(), 'admin/visions') ? 'active' : '' ?>">
            <i class="bi bi-eye"></i> Visions
        </a>
        <a href="<?= site_url('admin/cycle-stages') ?>"
           class="sidebar-link <?= str_contains(current_url(), 'admin/cycle-stages') ? 'active' : '' ?>">
            <i class="bi bi-diagram-3"></i> Cycle Stages
        </a>
        <?php endif; ?>

        <?php if ($currentUser['role'] === 'super_admin'): ?>
        <div class="sidebar-section mt-2">System</div>
        <a href="<?= site_url('admin/offices') ?>"
           class="sidebar-link <?= str_contains(current_url(), 'admin/offices') ? 'active' : '' ?>">
            <i class="bi bi-building"></i> Offices
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user"><?= esc($currentUser['full_name']) ?></div>
        <span class="sidebar-role"><?= esc(str_replace('_', ' ', $currentUser['role'])) ?></span>
    </div>
</aside>

<!-- ── Main area ─────────────────────────────────────────────────── -->
<div id="admin-main">
    <div id="admin-topbar">
        <span class="topbar-title"><?= esc($title ?? 'Admin') ?></span>
        <div class="d-flex gap-2 align-items-center">
            <a href="<?= site_url() ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-house me-1"></i>Public Site
            </a>
            <a href="<?= site_url('auth/logout') ?>" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </a>
        </div>
    </div>

    <main id="admin-content">
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= esc(session()->getFlashdata('success')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= esc(session()->getFlashdata('error')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
