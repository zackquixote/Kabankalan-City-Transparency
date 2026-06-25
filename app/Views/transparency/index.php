<?php
/**
 * @var array $totals
 * @var array $totalsByOffice
 * @var array $countsByStatus
 * @var array $totalsByYear
 * @var array $filters
 * @var array $filter_options
 */
?>
<?php ob_start(); ?>
<style>
    .transparency-header {
        background: linear-gradient(135deg, #0b4f6c 0%, #1e5f74 100%);
        color: #fff;
        border-radius: 0 0 1rem 1rem;
        padding: 3rem 1.5rem;
        margin-bottom: 2rem;
    }
    .kpi-card {
        border: none;
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        background: #fff;
    }
    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
    }
    .kpi-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .chart-card {
        border: none;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    }
    .chart-container {
        position: relative;
        height: 320px;
        width: 100%;
    }
    @media print {
        body {
            background: #fff !important;
            color: #000 !important;
        }
        .navbar-kb, .footer-kb, .filter-card, .btn-print, .btn-csv {
            display: none !important;
        }
        .transparency-header {
            background: none !important;
            color: #000 !important;
            padding: 0 !important;
            margin-bottom: 1.5rem !important;
            border-bottom: 2px solid #dee2e6;
        }
        .transparency-header h1 {
            color: #000 !important;
        }
        .kpi-card {
            border: 1px solid #dee2e6 !important;
            box-shadow: none !important;
            margin-bottom: 1rem;
        }
        .chart-card {
            border: 1px solid #dee2e6 !important;
            box-shadow: none !important;
            page-break-inside: avoid;
        }
    }
</style>

<div class="transparency-header text-center">
    <div class="container">
        <h1 class="display-6 fw-bold mb-2">City Budget Transparency Dashboard</h1>
        <p class="lead text-white-50 mb-0">Real-time financial summaries and visualization of Kabankalan's Annual Investment Programs.</p>
    </div>
</div>

<div class="container pb-5">
    <!-- Filter Card -->
    <div class="card filter-card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body">
            <form method="get" action="<?= site_url('transparency') ?>" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="fiscal_year" class="form-label fw-semibold text-secondary">Fiscal Year</label>
                    <select class="form-select border-0 shadow-sm" id="fiscal_year" name="fiscal_year" onchange="this.form.submit()">
                        <option value="">All Years</option>
                        <?php foreach ($filter_options['fiscal_years'] as $year): ?>
                            <option value="<?= esc((string) $year) ?>"<?= (isset($filters['fiscal_year']) && $filters['fiscal_year'] === $year) ? ' selected' : '' ?>>FY <?= esc((string) $year) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="office_id" class="form-label fw-semibold text-secondary">City Office / Department</label>
                    <select class="form-select border-0 shadow-sm" id="office_id" name="office_id" onchange="this.form.submit()">
                        <option value="">All Offices</option>
                        <?php foreach ($filter_options['offices'] as $office): ?>
                            <option value="<?= esc((string) $office['id']) ?>"<?= (isset($filters['office_id']) && $filters['office_id'] === (int) $office['id']) ? ' selected' : '' ?>><?= esc($office['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <a href="<?= site_url('transparency') ?>" class="btn btn-outline-secondary w-50 border-0 bg-white shadow-sm">Clear</a>
                    <button type="button" onclick="window.print()" class="btn btn-primary w-50 btn-print shadow-sm">
                        <i class="bi bi-printer me-1"></i> Print/PDF
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <!-- Allocated -->
        <div class="col-md-6 col-lg-3">
            <div class="card kpi-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-primary text-white">
                        <span style="font-weight: 700;">₱</span>
                    </div>
                    <div>
                        <h6 class="text-uppercase text-muted small mb-1">Allocated Budget</h6>
                        <h4 class="fw-bold mb-0 text-primary">₱<?= esc(number_format((float) $totals['total_allocated'], 2)) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Obligated -->
        <div class="col-md-6 col-lg-3">
            <div class="card kpi-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-info text-white">
                        <span style="font-weight: 700;">O</span>
                    </div>
                    <div>
                        <h6 class="text-uppercase text-muted small mb-1">Obligated Budget</h6>
                        <h4 class="fw-bold mb-0 text-info">₱<?= esc(number_format((float) $totals['total_obligated'], 2)) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Disbursed -->
        <div class="col-md-6 col-lg-3">
            <div class="card kpi-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-success text-white">
                        <span style="font-weight: 700;">D</span>
                    </div>
                    <div>
                        <h6 class="text-uppercase text-muted small mb-1">Disbursed Budget</h6>
                        <h4 class="fw-bold mb-0 text-success">₱<?= esc(number_format((float) $totals['total_disbursed'], 2)) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Disbursement Rate -->
        <div class="col-md-6 col-lg-3">
            <?php
                $allocated = (float) $totals['total_allocated'];
                $disbursed = (float) $totals['total_disbursed'];
                $rate = $allocated > 0 ? ($disbursed / $allocated) * 100 : 0;
            ?>
            <div class="card kpi-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-warning text-dark">
                        <span style="font-weight: 700;">%</span>
                    </div>
                    <div>
                        <h6 class="text-uppercase text-muted small mb-1">Disbursement Rate</h6>
                        <h4 class="fw-bold mb-0 text-dark"><?= esc(number_format($rate, 1)) ?>%</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Filter Info (Print view helper) -->
    <div class="d-none d-print-block mb-3 text-secondary small">
        <strong>Filters Applied:</strong>
        Year: <?= isset($filters['fiscal_year']) ? 'FY ' . esc((string) $filters['fiscal_year']) : 'All Years' ?> |
        Office: <?= isset($filters['office_id']) ? 'Selected Office' : 'All Offices' ?> |
        Total Projects: <?= esc((string) $totals['project_count']) ?>
    </div>

    <!-- Visual Charts Grid -->
    <div class="row g-4 mb-4">
        <!-- Department/Office Budget Comparison -->
        <div class="col-lg-8">
            <div class="card chart-card shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="fw-bold mb-0 text-secondary">Office Budget Comparison</h5>
                    <p class="text-muted small mb-0">Total budget figures assigned to active departments</p>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="deptBudgetChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Project Status Breakdown -->
        <div class="col-lg-4">
            <div class="card chart-card shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="fw-bold mb-0 text-secondary">AIP Project Status</h5>
                    <p class="text-muted small mb-0">Proportion of public projects by workflow status</p>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div class="chart-container" style="max-height: 280px;">
                        <canvas id="statusBreakdownChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Budget Trend by Year -->
        <div class="col-12">
            <div class="card chart-card shadow-sm">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="fw-bold mb-0 text-secondary">Fiscal Year Trends</h5>
                    <p class="text-muted small mb-0">Annual trend of city allocations and disbursement rates</p>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="yearlyTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // ── Color Palette Configuration (Curated HSL / Sleek Theme) ─────────────────
        const colors = {
            allocated: 'hsla(199, 81%, 23%, 0.85)',   // Deep Blue
            obligated: 'hsla(193, 99%, 47%, 0.85)',   // Accent Cyan
            disbursed: 'hsla(145, 63%, 42%, 0.85)',   // Teal Green
            hoverAllocated: 'hsla(199, 81%, 23%, 1)',
            hoverObligated: 'hsla(193, 99%, 47%, 1)',
            hoverDisbursed: 'hsla(145, 63%, 42%, 1)',
            completed: 'hsla(262, 52%, 47%, 0.8)',     // Purple
            published: 'hsla(145, 63%, 42%, 0.8)'      // Teal Green
        };

        // ── Data Sources Injected from PHP ──────────────────────────────────────────
        const officeData = <?= json_encode($totalsByOffice) ?>;
        const statusData = <?= json_encode($countsByStatus) ?>;
        const yearData = <?= json_encode($totalsByYear) ?>;

        // 1. Department/Office Budget Comparison Chart
        const deptLabels = officeData.map(d => d.office_name);
        const deptAllocated = officeData.map(d => parseFloat(d.total_allocated));
        const deptObligated = officeData.map(d => parseFloat(d.total_obligated));
        const deptDisbursed = officeData.map(d => parseFloat(d.total_disbursed));

        const ctxDept = document.getElementById('deptBudgetChart').getContext('2d');
        new Chart(ctxDept, {
            type: 'bar',
            data: {
                labels: deptLabels,
                datasets: [
                    {
                        label: 'Allocated',
                        data: deptAllocated,
                        backgroundColor: colors.allocated,
                        hoverBackgroundColor: colors.hoverAllocated,
                        borderRadius: 4
                    },
                    {
                        label: 'Obligated',
                        data: deptObligated,
                        backgroundColor: colors.obligated,
                        hoverBackgroundColor: colors.hoverObligated,
                        borderRadius: 4
                    },
                    {
                        label: 'Disbursed',
                        data: deptDisbursed,
                        backgroundColor: colors.disbursed,
                        hoverBackgroundColor: colors.hoverDisbursed,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.dataset.label + ': ₱' + context.raw.toLocaleString(undefined, { minimumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        grid: { color: '#e8edf2' },
                        ticks: {
                            callback: function (value) {
                                return '₱' + (value >= 1e6 ? (value / 1e6).toFixed(1) + 'M' : value.toLocaleString());
                            }
                        }
                    }
                }
            }
        });

        // 2. Project Status Breakdown Chart
        const statusLabels = statusData.map(s => s.status.charAt(0).toUpperCase() + s.status.slice(1));
        const statusCounts = statusData.map(s => s.project_count);

        const ctxStatus = document.getElementById('statusBreakdownChart').getContext('2d');
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusCounts,
                    backgroundColor: [colors.published, colors.completed, 'rgba(100, 116, 139, 0.7)'],
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        // 3. Fiscal Year Budget Trend Chart
        const yearLabels = yearData.map(y => 'FY ' + y.fiscal_year);
        const yearAllocated = yearData.map(y => parseFloat(y.total_allocated));
        const yearObligated = yearData.map(y => parseFloat(y.total_obligated));
        const yearDisbursed = yearData.map(y => parseFloat(y.total_disbursed));

        const ctxYear = document.getElementById('yearlyTrendChart').getContext('2d');
        new Chart(ctxYear, {
            type: 'line',
            data: {
                labels: yearLabels,
                datasets: [
                    {
                        label: 'Allocated Budget',
                        data: yearAllocated,
                        borderColor: 'rgba(11, 79, 108, 1)',
                        backgroundColor: 'rgba(11, 79, 108, 0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4
                    },
                    {
                        label: 'Obligated Budget',
                        data: yearObligated,
                        borderColor: 'rgba(1, 186, 239, 1)',
                        backgroundColor: 'transparent',
                        tension: 0.3,
                        pointRadius: 4
                    },
                    {
                        label: 'Disbursed Budget',
                        data: yearDisbursed,
                        borderColor: 'rgba(20, 164, 77, 1)',
                        backgroundColor: 'transparent',
                        tension: 0.3,
                        pointRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.dataset.label + ': ₱' + context.raw.toLocaleString(undefined, { minimumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        grid: { color: '#e8edf2' },
                        ticks: {
                            callback: function (value) {
                                return '₱' + (value >= 1e6 ? (value / 1e6).toFixed(1) + 'M' : value.toLocaleString());
                            }
                        }
                    }
                }
            }
        });
    });
</script>
<?php
$content = ob_get_clean();
echo view('layouts/public', [
    'title'           => 'Transparency Dashboard — Kabankalan Budget Portal',
    'metaDescription' => 'Interactive visualizations and summaries of budget allocations, obligation status, and departments in Kabankalan City.',
    'content'         => $content,
]);
