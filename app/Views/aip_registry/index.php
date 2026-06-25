<?php

use CodeIgniter\Pager\Pager;

/**
 * @var list<array<string, mixed>>     $projects
 * @var Pager                          $pager
 * @var array<string, mixed>           $filters
 * @var array{
 *     fiscal_years: list<int>,
 *     offices: list<array<string, mixed>>,
 *     visions: list<array<string, mixed>>,
 *     statuses: list<string>
 * } $filter_options
 */

// Build active filter chip labels
$activeChips = [];
if (! empty($filters['search']))      $activeChips['search']     = 'Keyword: "' . esc($filters['search']) . '"';
if (! empty($filters['office_id']))  {
    $oName = '';
    foreach ($filter_options['offices'] as $o) { if ((int)$o['id'] === $filters['office_id']) { $oName = $o['name']; break; } }
    $activeChips['office_id'] = 'Office: ' . esc($oName);
}
if (! empty($filters['vision_id']))  {
    $vName = '';
    foreach ($filter_options['visions'] as $v) { if ((int)$v['id'] === $filters['vision_id']) { $vName = $v['title']; break; } }
    $activeChips['vision_id'] = 'Vision: ' . esc($vName);
}
if (! empty($filters['fiscal_year'])) $activeChips['fiscal_year'] = 'FY ' . esc((string)$filters['fiscal_year']);
if (! empty($filters['status']))      $activeChips['status']      = 'Status: ' . esc(ucfirst($filters['status']));
if (isset($filters['budget_min']))    $activeChips['budget_min']  = 'Min: P' . esc(number_format((float)$filters['budget_min']));
if (isset($filters['budget_max']))    $activeChips['budget_max']  = 'Max: P' . esc(number_format((float)$filters['budget_max']));

$pageInfo    = $pager->getDetails('aip');
$totalCount  = (int) ($pageInfo['total'] ?? 0);
$currentPage = (int) ($pageInfo['currentPage'] ?? 1);
$pageCount   = (int) $pager->getPageCount('aip');
$hasFilters  = ! empty($activeChips);
?>
<?php ob_start(); ?>

<style>
:root { --kb-primary:#0b4f6c; --kb-accent:#01baef; --kb-success:#198754; --kb-muted:#6c757d; }

/* Hero */
.aip-hero{background:linear-gradient(135deg,#0b4f6c 0%,#145da0 100%);color:#fff;padding:2.5rem 0 1.5rem;}
.aip-hero h1{font-size:2rem;font-weight:700;margin-bottom:.25rem;}
.aip-hero p{opacity:.85;margin:0;}

/* Sidebar */
.filter-sidebar{background:#fff;border:1px solid #dee2e6;border-radius:12px;padding:1.25rem;position:sticky;top:1rem;}
.filter-title{font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--kb-muted);margin-bottom:.75rem;}
.filter-sidebar label{font-size:.85rem;font-weight:500;color:#444;}
.filter-sidebar .form-control,.filter-sidebar .form-select{font-size:.85rem;border-radius:8px;}
.btn-apply-filters{background:var(--kb-primary);color:#fff;border:none;border-radius:8px;font-size:.875rem;font-weight:600;width:100%;padding:.55rem;transition:background .2s;}
.btn-apply-filters:hover{background:#145da0;color:#fff;}
.btn-clear-filters{font-size:.8rem;color:var(--kb-muted);text-decoration:none;display:block;text-align:center;margin-top:.5rem;}
.btn-clear-filters:hover{color:var(--kb-primary);}

/* Chips */
.filter-chip{display:inline-flex;align-items:center;gap:.35rem;background:#e8f4fd;color:#0b4f6c;border:1px solid #b3d9f0;border-radius:20px;padding:.25rem .75rem;font-size:.78rem;font-weight:500;text-decoration:none;transition:background .15s;}
.filter-chip:hover{background:#cce8f8;color:#07395a;}
.chip-x{font-size:.65rem;opacity:.6;}

/* Result bar */
.result-bar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;margin-bottom:1.25rem;}
.result-count{font-size:.9rem;color:var(--kb-muted);}
.result-count strong{color:#1a1a1a;}

/* Cards */
.project-card{background:#fff;border:1px solid #e8ecef;border-radius:12px;padding:1.25rem 1.5rem;height:100%;transition:box-shadow .2s,transform .15s;display:flex;flex-direction:column;}
.project-card:hover{box-shadow:0 6px 24px rgba(11,79,108,.12);transform:translateY(-2px);}
.card-code{font-family:monospace;font-size:.75rem;color:var(--kb-muted);margin-bottom:.35rem;}
.card-title{font-size:.95rem;font-weight:600;color:#1a1a1a;margin-bottom:.5rem;line-height:1.35;flex-grow:1;}
.card-meta{font-size:.78rem;color:var(--kb-muted);display:flex;flex-wrap:wrap;gap:.4rem .75rem;margin-bottom:.85rem;}
.card-meta span{display:flex;align-items:center;gap:.25rem;}
.card-budget{font-size:.82rem;font-weight:600;color:#1a1a1a;margin-bottom:.75rem;}
.card-budget small{font-weight:400;color:var(--kb-muted);font-size:.75rem;}
.util-bar-wrap{margin-bottom:.9rem;}
.util-bar-track{height:6px;background:#e9ecef;border-radius:4px;overflow:hidden;display:flex;}
.util-bar-obligated{background:#ffc107;height:100%;transition:width .4s;}
.util-bar-disbursed{background:var(--kb-success);height:100%;transition:width .4s;}
.badge-published{background-color:#198754;}
.badge-completed{background-color:#0b4f6c;}
.badge-status{text-transform:capitalize;font-size:.7rem;}
.btn-card-detail{border:1.5px solid var(--kb-accent);color:var(--kb-primary);border-radius:8px;font-size:.8rem;font-weight:600;padding:.35rem .9rem;text-decoration:none;transition:background .2s,color .2s;align-self:flex-start;}
.btn-card-detail:hover{background:var(--kb-accent);color:#fff;}

/* Empty */
.empty-state{text-align:center;padding:4rem 2rem;color:var(--kb-muted);}
.empty-state i{font-size:3rem;margin-bottom:1rem;opacity:.4;}

/* Print */
@media print{
  .aip-hero,.filter-sidebar,.filter-chip,.result-bar .d-flex,
  .btn-card-detail,nav,.footer-kb,.pagination{display:none!important;}
  body{background:#fff!important;color:#000!important;}
  .project-card{border:1px solid #ccc;page-break-inside:avoid;}
}
</style>

<!-- Hero -->
<div class="aip-hero">
  <div class="container">
    <h1><i class="bi bi-journal-bookmark-fill me-2"></i>AIP Project Registry</h1>
    <p>Browse and filter all published Annual Investment Program projects of Kabankalan City.</p>
  </div>
</div>

<div class="container py-4">
  <div class="row g-4">

    <!-- Sidebar -->
    <div class="col-lg-3">
      <form method="get" action="<?= site_url('aip') ?>" id="filter-form" class="filter-sidebar">
        <div class="filter-title"><i class="bi bi-funnel-fill me-1"></i> Filter Projects</div>

        <div class="mb-3">
          <label for="search" class="form-label">Keyword</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="search" class="form-control" id="search" name="search"
                   value="<?= esc((string)($filters['search'] ?? '')) ?>"
                   placeholder="Title, code, barangay...">
          </div>
        </div>

        <div class="mb-3">
          <label for="office_id" class="form-label">Office</label>
          <select class="form-select form-select-sm" id="office_id" name="office_id">
            <option value="">All offices</option>
            <?php foreach ($filter_options['offices'] as $office): ?>
              <option value="<?= esc((string)$office['id'],'attr') ?>"
                <?= (($filters['office_id'] ?? null) === (int)$office['id']) ? 'selected' : '' ?>>
                <?= esc($office['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php if (! empty($filter_options['visions'])): ?>
        <div class="mb-3">
          <label for="vision_id" class="form-label">Vision / Plan Period</label>
          <select class="form-select form-select-sm" id="vision_id" name="vision_id">
            <option value="">All visions</option>
            <?php foreach ($filter_options['visions'] as $vision): ?>
              <option value="<?= esc((string)$vision['id'],'attr') ?>"
                <?= (($filters['vision_id'] ?? null) === (int)$vision['id']) ? 'selected' : '' ?>>
                <?= esc($vision['title']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

        <div class="mb-3">
          <label for="fiscal_year" class="form-label">Fiscal Year</label>
          <select class="form-select form-select-sm" id="fiscal_year" name="fiscal_year">
            <option value="">All years</option>
            <?php foreach ($filter_options['fiscal_years'] as $year): ?>
              <option value="<?= esc((string)$year,'attr') ?>"
                <?= (($filters['fiscal_year'] ?? null) === $year) ? 'selected' : '' ?>>
                <?= esc((string)$year) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label for="status" class="form-label">Status</label>
          <select class="form-select form-select-sm" id="status" name="status">
            <option value="">All public</option>
            <?php foreach ($filter_options['statuses'] as $status): ?>
              <option value="<?= esc($status,'attr') ?>"
                <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>>
                <?= esc(ucfirst($status)) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Budget Range (P)</label>
          <div class="row g-1">
            <div class="col">
              <input type="number" class="form-control form-control-sm" id="budget_min" name="budget_min"
                     placeholder="Min" min="0" step="1000"
                     value="<?= isset($filters['budget_min']) ? esc((string)$filters['budget_min']) : '' ?>">
            </div>
            <div class="col-auto d-flex align-items-center"><span class="text-muted small">-</span></div>
            <div class="col">
              <input type="number" class="form-control form-control-sm" id="budget_max" name="budget_max"
                     placeholder="Max" min="0" step="1000"
                     value="<?= isset($filters['budget_max']) ? esc((string)$filters['budget_max']) : '' ?>">
            </div>
          </div>
        </div>

        <button type="submit" class="btn-apply-filters">
          <i class="bi bi-funnel me-1"></i> Apply Filters
        </button>
        <?php if ($hasFilters): ?>
          <a href="<?= site_url('aip') ?>" class="btn-clear-filters">
            <i class="bi bi-x-circle me-1"></i> Clear all filters
          </a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Results -->
    <div class="col-lg-9">

      <!-- Active filter chips -->
      <?php if ($hasFilters): ?>
      <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
        <span class="text-muted small me-1">Active filters:</span>
        <?php foreach ($activeChips as $key => $label):
          $rem = $filters; unset($rem[$key]);
          $clearUrl = site_url('aip') . ($rem ? '?' . http_build_query($rem) : '');
        ?>
        <a href="<?= $clearUrl ?>" class="filter-chip">
          <?= $label ?>
          <span class="chip-x bi bi-x-lg"></span>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Result count + export -->
      <div class="result-bar">
        <p class="result-count mb-0">
          <strong><?= number_format($totalCount) ?></strong>
          <?= $totalCount === 1 ? 'project' : 'projects' ?>
          <?= $hasFilters ? 'match your filters' : 'published' ?>
          <?php if ($pageCount > 1): ?>
            &mdash; page <strong><?= $currentPage ?></strong> of <strong><?= $pageCount ?></strong>
          <?php endif; ?>
        </p>
        <div class="d-flex gap-2">
          <a href="<?= site_url('aip/export?' . http_build_query($filters)) ?>"
             class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
          </a>
          <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-printer me-1"></i> Print
          </button>
        </div>
      </div>

      <!-- Cards / empty -->
      <?php if ($projects === []): ?>
        <div class="empty-state">
          <i class="bi bi-inbox d-block"></i>
          <h5>No projects found</h5>
          <p class="mb-3">Try adjusting your filters or clearing the search term.</p>
          <?php if ($hasFilters): ?>
            <a href="<?= site_url('aip') ?>" class="btn btn-outline-primary btn-sm">
              <i class="bi bi-arrow-counterclockwise me-1"></i> Reset filters
            </a>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="row g-3">
          <?php foreach ($projects as $p):
            $allocated = (float)($p['allocated_amount'] ?? 0);
            $obligated = (float)($p['obligated_amount'] ?? 0);
            $disbursed = (float)($p['disbursed_amount'] ?? 0);
            $oblPct    = $allocated > 0 ? min(100, round($obligated / $allocated * 100)) : 0;
            $disPct    = $allocated > 0 ? min(100, round($disbursed / $allocated * 100)) : 0;
            $badgeCls  = $p['status'] === 'completed' ? 'badge-completed' : 'badge-published';
          ?>
          <div class="col-md-6 col-xl-4 d-flex">
            <div class="project-card">
              <div class="card-code"><?= esc($p['project_code']) ?></div>
              <div class="card-title"><?= esc($p['title']) ?></div>
              <div class="card-meta">
                <?php if (! empty($p['office_name'])): ?>
                  <span><i class="bi bi-building"></i><?= esc($p['office_name']) ?></span>
                <?php endif; ?>
                <?php if (! empty($p['barangay'])): ?>
                  <span><i class="bi bi-geo-alt"></i><?= esc($p['barangay']) ?></span>
                <?php endif; ?>
                <span><i class="bi bi-calendar3"></i>FY <?= esc((string)$p['fiscal_year']) ?></span>
              </div>
              <div class="card-budget">
                P<?= number_format($allocated, 2) ?>
                <small> allocated</small>
              </div>
              <?php if ($allocated > 0): ?>
              <div class="util-bar-wrap">
                <div class="util-bar-track"
                     title="Obligated: <?= $oblPct ?>%  |  Disbursed: <?= $disPct ?>%">
                  <div class="util-bar-obligated" style="width:<?= $oblPct ?>%"></div>
                  <div class="util-bar-disbursed" style="width:<?= $disPct ?>%"></div>
                </div>
                <div class="d-flex mt-1">
                  <span style="font-size:.68rem;color:var(--kb-muted)">
                    <span style="color:#ffc107">&#9679;</span> <?= $oblPct ?>% obligated
                    &nbsp;<span style="color:#198754">&#9679;</span> <?= $disPct ?>% disbursed
                  </span>
                </div>
              </div>
              <?php endif; ?>
              <div class="d-flex align-items-center justify-content-between mt-auto">
                <span class="badge <?= $badgeCls ?> badge-status"><?= esc($p['status']) ?></span>
                <a href="<?= site_url('projects/' . $p['id']) ?>" class="btn-card-detail">
                  Details <i class="bi bi-arrow-right"></i>
                </a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="mt-4"><?= $pager->links('aip', 'bootstrap_full') ?></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
echo view('layouts/public', [
    'title'           => 'AIP Project Registry - Kabankalan Budget Portal',
    'metaDescription' => 'Browse, search, and filter all published Annual Investment Program projects of the City Government of Kabankalan.',
    'content'         => $content,
]);
