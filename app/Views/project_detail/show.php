<?php

use CodeIgniter\Pager\Pager;

/**
 * @var array<string, mixed>         $project
 * @var list<array<string, mixed>>   $versions
 * @var int                          $feedback_count
 * @var list<array<string, mixed>>   $public_feedback
 * @var list<array<string, mixed>>   $related_projects
 * @var list<array<string, mixed>>   $attachments
 */

// Split attachments
$attachImages = array_filter($attachments ?? [], fn($a) => str_starts_with($a['mime_type'], 'image/'));
$attachDocs   = array_filter($attachments ?? [], fn($a) => ! str_starts_with($a['mime_type'], 'image/'));

// ── Status stepper config ──────────────────────────────────────────────
$STEPPER = [
    ['key' => 'submitted',    'label' => 'Submitted',    'icon' => 'bi-send'],
    ['key' => 'under_review', 'label' => 'Under Review', 'icon' => 'bi-eye'],
    ['key' => 'approved',     'label' => 'Approved',     'icon' => 'bi-patch-check'],
    ['key' => 'published',    'label' => 'Published',    'icon' => 'bi-globe2'],
    ['key' => 'completed',    'label' => 'Completed',    'icon' => 'bi-trophy'],
];
$STATUS_ORDER = ['draft'=>0,'submitted'=>1,'under_review'=>2,'approved'=>3,'published'=>4,'completed'=>5,'cancelled'=>5];
$currentStatusRank = $STATUS_ORDER[$project['status']] ?? 0;

// ── Budget numbers ────────────────────────────────────────────────────
$allocated  = (float)($project['allocated_amount']  ?? 0);
$obligated  = (float)($project['obligated_amount']  ?? 0);
$disbursed  = (float)($project['disbursed_amount']  ?? 0);
$oblPct     = $allocated > 0 ? min(100, round($obligated / $allocated * 100)) : 0;
$disPct     = $allocated > 0 ? min(100, round($disbursed / $allocated * 100)) : 0;
$remaining  = max(0, $allocated - $disbursed);

// ── Share URL ──────────────────────────────────────────────────────────
$shareUrl    = current_url();
$shareTitle  = urlencode($project['title'] . ' — Kabankalan Budget Portal');
$fbShareUrl  = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($shareUrl);
$twShareUrl  = 'https://twitter.com/intent/tweet?url=' . urlencode($shareUrl) . '&text=' . $shareTitle;

// ── Meta description for OG ───────────────────────────────────────────
$metaDesc = 'Project ' . $project['project_code'] . ': ' . $project['title']
    . ' — allocated P' . number_format($allocated, 2) . ' | ' . ucfirst($project['status'])
    . ' | ' . ($project['office_name'] ?? '');
?>
<?php ob_start(); ?>

<style>
:root { --kb-primary:#0b4f6c; --kb-accent:#01baef; --kb-success:#198754; --kb-warn:#ffc107; --kb-muted:#6c757d; }

/* ── Hero ───────────────────────────────────────────────────────────── */
.detail-hero {
    background: linear-gradient(135deg, #0b4f6c 0%, #145da0 100%);
    color: #fff;
    padding: 2.5rem 0 3.5rem;
    position: relative;
}
.detail-hero .breadcrumb-item a { color: rgba(255,255,255,.75); text-decoration:none; }
.detail-hero .breadcrumb-item a:hover { color:#fff; }
.detail-hero .breadcrumb-item.active { color:rgba(255,255,255,.9); }
.detail-hero .breadcrumb-separator { color:rgba(255,255,255,.5); }
.hero-code  { font-family:monospace; font-size:.8rem; opacity:.7; margin-bottom:.5rem; }
.hero-title { font-size:1.9rem; font-weight:700; margin:0 0 .75rem; line-height:1.25; }
.hero-meta  { display:flex; flex-wrap:wrap; gap:.5rem 1.25rem; font-size:.85rem; opacity:.85; }
.hero-meta span { display:flex; align-items:center; gap:.3rem; }
.hero-status-badge { font-size:.85rem; text-transform:capitalize; padding:.4rem .9rem; border-radius:20px; }

/* ── Status stepper ─────────────────────────────────────────────────── */
.stepper-wrap {
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:12px;
    padding:1.5rem 2rem;
    margin-top:-2rem;
    box-shadow:0 4px 20px rgba(0,0,0,.07);
}
.stepper { display:flex; align-items:center; justify-content:space-between; position:relative; }
.stepper-line {
    position:absolute; top:18px; left:0; right:0; height:3px;
    background:#e2e8f0; z-index:0;
}
.stepper-line-fill {
    height:3px; background:var(--kb-accent); transition:width .4s;
}
.step { display:flex; flex-direction:column; align-items:center; position:relative; z-index:1; flex:1; }
.step-circle {
    width:36px; height:36px; border-radius:50%; display:flex; align-items:center;
    justify-content:center; font-size:.9rem; border:3px solid #e2e8f0;
    background:#fff; transition:all .3s;
}
.step.done   .step-circle { background:var(--kb-success); border-color:var(--kb-success); color:#fff; }
.step.active .step-circle { background:var(--kb-accent);  border-color:var(--kb-accent);  color:#fff; box-shadow:0 0 0 4px rgba(1,186,239,.2); }
.step-label { font-size:.7rem; margin-top:.5rem; color:var(--kb-muted); font-weight:500; text-align:center; }
.step.done   .step-label { color:var(--kb-success); }
.step.active .step-label { color:var(--kb-accent); font-weight:700; }

/* ── Main content ───────────────────────────────────────────────────── */
.section-card {
    background:#fff; border:1px solid #e8ecef; border-radius:12px;
    overflow:hidden; margin-bottom:1.25rem;
}
.section-card .card-head {
    padding:.9rem 1.25rem; background:#f8fafc;
    border-bottom:1px solid #e8ecef; display:flex; align-items:center; gap:.5rem;
}
.section-card .card-head h2 { font-size:.9rem; font-weight:700; margin:0; color:#374151; }
.section-card .card-body-p { padding:1.25rem; }

/* ── Tab nav ──────────────────────────────────────────────────────────*/
.detail-tabs .nav-link { color:var(--kb-muted); font-size:.875rem; font-weight:500; padding:.6rem 1.1rem; border-radius:8px 8px 0 0; }
.detail-tabs .nav-link.active { color:var(--kb-primary); background:#fff; border-color:#e8ecef #e8ecef #fff; font-weight:700; }

/* ── Budget sidebar card ─────────────────────────────────────────────── */
.budget-card { background:#fff; border:1px solid #e8ecef; border-radius:12px; overflow:hidden; }
.budget-card .bcard-head { background:var(--kb-primary); color:#fff; padding:.9rem 1.25rem; }
.budget-card .bcard-head h2 { font-size:.875rem; font-weight:700; margin:0; }
.budget-row { display:flex; justify-content:space-between; align-items:center; padding:.65rem 1.25rem; border-bottom:1px solid #f0f4f8; }
.budget-row:last-child { border:none; }
.budget-row .label { font-size:.82rem; color:var(--kb-muted); }
.budget-row .amount { font-size:.9rem; font-weight:700; color:#1a1a1a; }
.budget-row.highlight .amount { color:var(--kb-primary); }

/* Budget bars */
.bbar-wrap { padding:.25rem 1.25rem 1rem; }
.bbar-label { font-size:.72rem; color:var(--kb-muted); display:flex; justify-content:space-between; margin-bottom:.2rem; }
.bbar-track { height:8px; background:#e9ecef; border-radius:4px; overflow:hidden; margin-bottom:.6rem; }
.bbar-fill-obl { background:var(--kb-warn);    height:100%; border-radius:4px; }
.bbar-fill-dis { background:var(--kb-success); height:100%; border-radius:4px; }

/* ── Info list ────────────────────────────────────────────────────────── */
.info-list { list-style:none; margin:0; padding:0; }
.info-list li { display:flex; justify-content:space-between; padding:.55rem 1.25rem; border-bottom:1px solid #f0f4f8; font-size:.83rem; }
.info-list li:last-child { border:none; }
.info-list li .il-label { color:var(--kb-muted); }
.info-list li .il-val { font-weight:600; color:#1a1a1a; text-align:right; max-width:60%; }

/* ── Share buttons ────────────────────────────────────────────────────── */
.btn-share { display:inline-flex; align-items:center; gap:.4rem; font-size:.8rem; border-radius:8px; padding:.35rem .9rem; text-decoration:none; font-weight:600; transition:opacity .2s; }
.btn-share:hover { opacity:.85; }
.btn-fb { background:#1877F2; color:#fff; }
.btn-tw { background:#000; color:#fff; }
.btn-copy { background:#f1f5f9; color:#374151; border:1px solid #e2e8f0; cursor:pointer; }

/* ── Version timeline ─────────────────────────────────────────────────── */
.version-item { display:flex; gap:1rem; padding:.75rem 0; border-bottom:1px solid #f0f4f8; }
.version-item:last-child { border:none; }
.ver-badge { min-width:36px; height:36px; border-radius:50%; background:#f1f5f9; display:flex; align-items:center; justify-content:center; font-size:.7rem; font-weight:700; color:var(--kb-primary); flex-shrink:0; }
.ver-body { flex:1; }
.ver-body .ver-summary { font-size:.85rem; font-weight:500; color:#1a1a1a; margin:0 0 .2rem; }
.ver-body .ver-date    { font-size:.75rem; color:var(--kb-muted); }

/* ── Feedback ─────────────────────────────────────────────────────────── */
.feedback-item { padding:1rem 0; border-bottom:1px solid #f0f4f8; }
.feedback-item:last-child { border:none; }
.fb-author { font-size:.78rem; color:var(--kb-muted); font-weight:600; margin-bottom:.3rem; }
.fb-body   { font-size:.9rem; color:#374151; margin:0 0 .5rem; }
.fb-response { background:#f0f9f4; border-left:3px solid var(--kb-success); border-radius:0 6px 6px 0; padding:.6rem .9rem; font-size:.83rem; color:#1a5c32; margin-top:.5rem; }
.fb-response strong { display:block; font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; margin-bottom:.2rem; }

/* ── Related projects ─────────────────────────────────────────────────── */
.related-card { background:#fff; border:1px solid #e8ecef; border-radius:10px; padding:1rem; margin-bottom:.75rem; display:block; text-decoration:none; transition:box-shadow .2s, transform .15s; }
.related-card:hover { box-shadow:0 4px 16px rgba(11,79,108,.1); transform:translateY(-2px); }
.rel-code  { font-family:monospace; font-size:.72rem; color:var(--kb-muted); }
.rel-title { font-size:.85rem; font-weight:600; color:#1a1a1a; margin:.2rem 0 .4rem; line-height:1.3; }
.rel-amount{ font-size:.78rem; color:var(--kb-primary); font-weight:600; }

/* ── Feedback CTA ─────────────────────────────────────────────────────── */
.cta-feedback { background:linear-gradient(135deg,#0b4f6c,#145da0); border-radius:12px; padding:1.5rem; color:#fff; text-align:center; margin-bottom:1.25rem; }
.cta-feedback p { font-size:.85rem; opacity:.85; margin-bottom:.75rem; }
.btn-cta-feedback { background:var(--kb-accent); color:#fff; border:none; border-radius:8px; padding:.5rem 1.25rem; font-weight:700; font-size:.875rem; text-decoration:none; display:inline-block; transition:background .2s; }
.btn-cta-feedback:hover { background:#00a3d5; color:#fff; }

/* Print */
@media print {
    .detail-hero,.stepper-wrap,.btn-share,.cta-feedback,.related-card,nav,.footer-kb { display:none!important; }
    body { background:#fff!important; color:#000!important; }
}
</style>

<!-- ── Hero ───────────────────────────────────────────────────────────── -->
<div class="detail-hero">
  <div class="container">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?= site_url('aip') ?>">AIP Registry</a></li>
        <li class="breadcrumb-item active"><?= esc($project['project_code']) ?></li>
      </ol>
    </nav>

    <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
      <span class="badge hero-status-badge
        <?= $project['status'] === 'completed' ? 'bg-secondary' : 'bg-success' ?>">
        <?= esc(ucfirst(str_replace('_', ' ', $project['status']))) ?>
      </span>
      <?php if (! empty($project['fiscal_year'])): ?>
        <span class="badge bg-white bg-opacity-25 text-white">FY <?= esc((string)$project['fiscal_year']) ?></span>
      <?php endif; ?>
    </div>

    <div class="hero-code"><?= esc($project['project_code']) ?></div>
    <h1 class="hero-title"><?= esc($project['title']) ?></h1>

    <div class="hero-meta">
      <?php if (! empty($project['office_name'])): ?>
        <span><i class="bi bi-building"></i><?= esc($project['office_name']) ?></span>
      <?php endif; ?>
      <?php if (! empty($project['barangay'])): ?>
        <span><i class="bi bi-geo-alt"></i><?= esc($project['barangay']) ?></span>
      <?php endif; ?>
      <?php if (! empty($project['vision_title'])): ?>
        <span><i class="bi bi-bullseye"></i><?= esc($project['vision_title']) ?></span>
      <?php endif; ?>
      <?php if (! empty($project['target_completion_date'])): ?>
        <span><i class="bi bi-calendar-check"></i>Target: <?= esc($project['target_completion_date']) ?></span>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="container">
  <!-- ── Status Stepper ──────────────────────────────────────────────── -->
  <?php if ($project['status'] !== 'draft' && $project['status'] !== 'cancelled'): ?>
  <div class="stepper-wrap mb-4">
    <?php
      // Calculate how far along the stepper fill should be
      $stepCount   = count($STEPPER);
      $activeIdx   = -1;
      foreach ($STEPPER as $i => $step) {
          if ($step['key'] === $project['status']) { $activeIdx = $i; break; }
          if (($STATUS_ORDER[$step['key']] ?? 0) <= $currentStatusRank) { $activeIdx = $i; }
      }
      $fillPct = $stepCount > 1 ? round($activeIdx / ($stepCount - 1) * 100) : 100;
    ?>
    <div class="stepper">
      <div class="stepper-line">
        <div class="stepper-line-fill" style="width:<?= $fillPct ?>%"></div>
      </div>
      <?php foreach ($STEPPER as $i => $step):
          $rank = $STATUS_ORDER[$step['key']] ?? 0;
          $cls  = $rank < $currentStatusRank ? 'done' : ($rank === $currentStatusRank ? 'active' : '');
      ?>
      <div class="step <?= $cls ?>">
        <div class="step-circle">
          <i class="<?= $step['icon'] ?>"></i>
        </div>
        <span class="step-label"><?= $step['label'] ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="row g-4 py-2">

    <!-- ── Left column: tabs ─────────────────────────────────────────── -->
    <div class="col-lg-8">

      <!-- Tab navigation -->
      <ul class="nav nav-tabs detail-tabs mb-0 border-bottom-0" id="detailTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="tab-desc" data-bs-toggle="tab"
                  data-bs-target="#pane-desc" type="button" role="tab">
            <i class="bi bi-file-text me-1"></i> Description
          </button>
        </li>
        <?php if (! empty($attachImages)): ?>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-photos" data-bs-toggle="tab"
                  data-bs-target="#pane-photos" type="button" role="tab">
            <i class="bi bi-images me-1"></i> Photos
            <span class="badge bg-secondary ms-1" style="font-size:.65rem"><?= count($attachImages) ?></span>
          </button>
        </li>
        <?php endif; ?>
        <?php if (! empty($attachDocs)): ?>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-docs" data-bs-toggle="tab"
                  data-bs-target="#pane-docs" type="button" role="tab">
            <i class="bi bi-paperclip me-1"></i> Documents
            <span class="badge bg-secondary ms-1" style="font-size:.65rem"><?= count($attachDocs) ?></span>
          </button>
        </li>
        <?php endif; ?>
        <?php if ($versions !== []): ?>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-ver" data-bs-toggle="tab"
                  data-bs-target="#pane-ver" type="button" role="tab">
            <i class="bi bi-clock-history me-1"></i>
            Version History
            <span class="badge bg-secondary ms-1" style="font-size:.65rem"><?= count($versions) ?></span>
          </button>
        </li>
        <?php endif; ?>
        <?php if ($public_feedback !== []): ?>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-fb" data-bs-toggle="tab"
                  data-bs-target="#pane-fb" type="button" role="tab">
            <i class="bi bi-chat-dots me-1"></i>
            Feedback
            <span class="badge bg-secondary ms-1" style="font-size:.65rem"><?= $feedback_count ?></span>
          </button>
        </li>
        <?php endif; ?>
      </ul>

      <div class="tab-content section-card" style="border-top-left-radius:0">
        <!-- Description pane -->
        <div class="tab-pane fade show active" id="pane-desc" role="tabpanel">
          <div class="card-body-p">
            <?php if (! empty($project['description'])): ?>
              <p class="mb-0" style="line-height:1.75;white-space:pre-wrap"><?= esc($project['description']) ?></p>
            <?php else: ?>
              <p class="text-muted mb-0"><em>No description provided for this project.</em></p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Version history pane -->
        <?php if ($versions !== []): ?>
        <div class="tab-pane fade" id="pane-ver" role="tabpanel">
          <div class="card-body-p">
            <?php foreach ($versions as $v): ?>
            <div class="version-item">
              <div class="ver-badge">v<?= esc((string)$v['version_number']) ?></div>
              <div class="ver-body">
                <p class="ver-summary"><?= esc($v['change_summary'] ?? 'Record updated') ?></p>
                <div class="d-flex flex-wrap gap-2 small mb-1" style="font-size:.78rem;color:var(--kb-muted)">
                  <?php if (! empty($v['status'])): ?>
                    <span><i class="bi bi-tag"></i><?= esc(ucfirst($v['status'])) ?></span>
                  <?php endif; ?>
                  <?php if (! empty($v['allocated_amount'])): ?>
                    <span><i class="bi bi-cash"></i>P<?= number_format((float)$v['allocated_amount'], 2) ?></span>
                  <?php endif; ?>
                </div>
                <p class="ver-date"><i class="bi bi-clock me-1"></i><?= esc($v['created_at'] ?? '') ?></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Feedback pane -->
        <?php if ($public_feedback !== []): ?>
        <div class="tab-pane fade" id="pane-fb" role="tabpanel">
          <div class="card-body-p">
            <?php foreach ($public_feedback as $item): ?>
            <div class="feedback-item">
              <p class="fb-author"><i class="bi bi-person-circle me-1"></i><?= esc($item['author_name']) ?></p>
              <p class="fb-body"><?= esc($item['body']) ?></p>
              <?php if (! empty($item['admin_response'])): ?>
                <div class="fb-response">
                  <strong><i class="bi bi-shield-check me-1"></i>Official Response</strong>
                  <?= esc($item['admin_response']) ?>
                </div>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Photos Pane -->
        <?php if (! empty($attachImages)): ?>
        <div class="tab-pane fade" id="pane-photos" role="tabpanel">
          <div class="card-body-p">
            <div class="row g-3">
              <?php foreach ($attachImages as $img): ?>
              <div class="col-sm-6 col-md-4">
                <div class="card h-100 border shadow-sm" style="border-radius:12px; overflow:hidden">
                  <a href="<?= site_url('attachments/' . $img['id']) ?>" target="_blank" rel="noopener">
                    <img src="<?= site_url('attachments/' . $img['id']) ?>"
                         class="card-img-top"
                         alt="<?= esc($img['label'] ?? $img['original_filename']) ?>"
                         style="height:160px; object-fit:cover">
                  </a>
                  <?php if (! empty($img['label'])): ?>
                  <div class="card-body p-2 bg-light">
                    <p class="card-text small text-dark mb-0"><?= esc($img['label']) ?></p>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Documents Pane -->
        <?php if (! empty($attachDocs)): ?>
        <div class="tab-pane fade" id="pane-docs" role="tabpanel">
          <div class="card-body-p">
            <div class="list-group list-group-flush">
              <?php foreach ($attachDocs as $doc): ?>
              <div class="list-group-item d-flex align-items-center justify-content-between p-3 border-bottom" style="background:transparent">
                <div class="d-flex align-items-center gap-3">
                  <i class="bi bi-file-earmark-pdf-fill text-danger fs-3"></i>
                  <div>
                    <h4 class="h6 mb-1">
                      <a href="<?= site_url('attachments/' . $doc['id']) ?>" target="_blank" rel="noopener" class="text-decoration-none text-dark fw-bold">
                        <?= esc($doc['label'] ?? $doc['original_filename']) ?>
                      </a>
                    </h4>
                    <p class="mb-0 text-muted small">
                      <?= esc($doc['original_filename']) ?> &bull; <?= round($doc['file_size'] / 1024, 1) ?> KB
                    </p>
                  </div>
                </div>
                <a href="<?= site_url('attachments/' . $doc['id']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-download"></i> Download
                </a>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Share row -->
      <div class="d-flex flex-wrap align-items-center gap-2 mt-3 mb-4">
        <span class="text-muted small">Share this project:</span>
        <a href="<?= $fbShareUrl ?>" target="_blank" rel="noopener" class="btn-share btn-fb">
          <i class="bi bi-facebook"></i> Facebook
        </a>
        <a href="<?= $twShareUrl ?>" target="_blank" rel="noopener" class="btn-share btn-tw">
          <i class="bi bi-twitter-x"></i> X / Twitter
        </a>
        <button type="button" class="btn-share btn-copy" id="btn-copy-link"
                onclick="copyProjectLink()" title="Copy link">
          <i class="bi bi-link-45deg"></i> <span id="copy-label">Copy Link</span>
        </button>
      </div>

    </div><!-- /col-lg-8 -->

    <!-- ── Right sidebar ─────────────────────────────────────────────── -->
    <div class="col-lg-4">

      <!-- Feedback CTA -->
      <div class="cta-feedback">
        <i class="bi bi-chat-heart-fill" style="font-size:1.5rem;display:block;margin-bottom:.5rem"></i>
        <p>Have a question or concern about this project? Citizens can submit feedback directly.</p>
        <a href="<?= site_url('feedback/' . $project['id']) ?>" class="btn-cta-feedback">
          <i class="bi bi-pencil-square me-1"></i> Submit Feedback
        </a>
      </div>

      <!-- Budget summary -->
      <div class="budget-card mb-3">
        <div class="bcard-head">
          <h2><i class="bi bi-cash-stack me-2"></i>Budget Summary</h2>
        </div>

        <div class="budget-row highlight">
          <span class="label">Allocated</span>
          <span class="amount">P<?= number_format($allocated, 2) ?></span>
        </div>
        <div class="budget-row">
          <span class="label">Obligated</span>
          <span class="amount">P<?= number_format($obligated, 2) ?></span>
        </div>
        <div class="budget-row">
          <span class="label">Disbursed</span>
          <span class="amount" style="color:var(--kb-success)">P<?= number_format($disbursed, 2) ?></span>
        </div>
        <div class="budget-row">
          <span class="label">Remaining</span>
          <span class="amount" style="color:var(--kb-muted)">P<?= number_format($remaining, 2) ?></span>
        </div>

        <?php if ($allocated > 0): ?>
        <div class="bbar-wrap">
          <div class="bbar-label">
            <span style="color:var(--kb-warn)">Obligated <?= $oblPct ?>%</span>
            <span></span>
          </div>
          <div class="bbar-track">
            <div class="bbar-fill-obl" style="width:<?= $oblPct ?>%"></div>
          </div>
          <div class="bbar-label">
            <span style="color:var(--kb-success)">Disbursed <?= $disPct ?>%</span>
            <span></span>
          </div>
          <div class="bbar-track">
            <div class="bbar-fill-dis" style="width:<?= $disPct ?>%"></div>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Project info -->
      <div class="section-card mb-3">
        <div class="card-head"><i class="bi bi-info-circle"></i><h2>Project Info</h2></div>
        <ul class="info-list">
          <li>
            <span class="il-label">Project Code</span>
            <span class="il-val"><code><?= esc($project['project_code']) ?></code></span>
          </li>
          <?php if (! empty($project['office_name'])): ?>
          <li>
            <span class="il-label">Office</span>
            <span class="il-val"><?= esc($project['office_name']) ?></span>
          </li>
          <?php endif; ?>
          <?php if (! empty($project['vision_title'])): ?>
          <li>
            <span class="il-label">Vision</span>
            <span class="il-val"><?= esc($project['vision_title']) ?></span>
          </li>
          <?php endif; ?>
          <li>
            <span class="il-label">Fiscal Year</span>
            <span class="il-val"><?= esc((string)$project['fiscal_year']) ?></span>
          </li>
          <?php if (! empty($project['barangay'])): ?>
          <li>
            <span class="il-label">Barangay</span>
            <span class="il-val"><?= esc($project['barangay']) ?></span>
          </li>
          <?php endif; ?>
          <?php if (! empty($project['target_completion_date'])): ?>
          <li>
            <span class="il-label">Target Completion</span>
            <span class="il-val"><?= esc($project['target_completion_date']) ?></span>
          </li>
          <?php endif; ?>
          <?php if (! empty($project['published_at'])): ?>
          <li>
            <span class="il-label">Published</span>
            <span class="il-val"><?= esc(date('M d, Y', strtotime($project['published_at']))) ?></span>
          </li>
          <?php endif; ?>
          <li>
            <span class="il-label">Feedback</span>
            <span class="il-val"><?= esc((string)$feedback_count) ?> submitted</span>
          </li>
        </ul>
      </div>

      <!-- Related projects -->
      <?php if (! empty($related_projects)): ?>
      <div class="section-card">
        <div class="card-head"><i class="bi bi-collection"></i><h2>Other Projects (<?= esc($project['office_name'] ?? 'Same Office') ?>)</h2></div>
        <div class="card-body-p">
          <?php foreach ($related_projects as $rp): ?>
            <a href="<?= site_url('projects/' . $rp['id']) ?>" class="related-card">
              <div class="rel-code"><?= esc($rp['project_code']) ?> &bull; FY <?= esc((string)$rp['fiscal_year']) ?></div>
              <div class="rel-title"><?= esc($rp['title']) ?></div>
              <div class="rel-amount">P<?= number_format((float)$rp['allocated_amount'], 2) ?></div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </div><!-- /col-lg-4 -->
  </div><!-- /row -->
</div><!-- /container -->

<script>
function copyProjectLink() {
  navigator.clipboard.writeText(window.location.href).then(function() {
    const lbl = document.getElementById('copy-label');
    lbl.textContent = 'Copied!';
    setTimeout(() => { lbl.textContent = 'Copy Link'; }, 2000);
  });
}
</script>

<?php
$content = ob_get_clean();
echo view('layouts/public', [
    'title'          => esc($project['title']) . ' — Kabankalan Budget Portal',
    'metaDescription'=> $metaDesc,
    'ogTitle'        => $project['title'] . ' | Kabankalan Budget Portal',
    'ogDescription'  => $metaDesc,
    'ogUrl'          => current_url(),
    'content'        => $content,
]);
