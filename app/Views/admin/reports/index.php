<?php
/**
 * @var array $totals
 * @var array $totalsByOffice
 * @var array $countsByStatus
 * @var array $totalsByYear
 * @var array $filters
 * @var array $offices
 * @var array $fiscalYears
 * @var array $statuses
 * @var array $currentUser
 */
?>

<style>
    .kpi-card {
        border: none;
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.08) !important;
    }
    .kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }
    .chart-card {
        border: none;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }
    @media print {
        #admin-sidebar, #admin-topbar, .filter-card, .btn-csv, .btn-print, .alert {
            display: none !important;
        }
        #admin-main {
            margin-left: 0 !important;
            padding: 0 !important;
        }
        body {
            background: #fff !important;
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

<div class="container-fluid">
    <!-- Filter Card -->
    <div class="card filter-card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="get" action="<?= site_url('admin/reports') ?>" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="fiscal_year" class="form-label fw-medium text-secondary">Fiscal Year</label>
                    <select class="form-select" id="fiscal_year" name="fiscal_year" onchange="this.form.submit()">
                        <option value="">All Years</option>
                        <?php foreach ($fiscalYears as $year): ?>
                            <option value="<?= esc((string) $year) ?>"<?= (isset($filters['fiscal_year']) && $filters['fiscal_year'] === $year) ? ' selected' : '' ?>>FY <?= esc((string) $year) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label fw-medium text-secondary">Project Status</label>
                    <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= esc($status) ?>"<?= (isset($filters['status']) && $filters['status'] === $status) ? ' selected' : '' ?>><?= esc(ucfirst(str_replace('_', ' ', $status))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="office_id" class="form-label fw-medium text-secondary">Office Scope</label>
                    <?php if ($currentUser['role'] === 'office_staff'): ?>
                        <select class="form-select bg-light text-muted" id="office_id" disabled>
                            <option selected><?= esc($offices[0]['name'] ?? 'Your Office') ?></option>
                        </select>
                    <?php else: ?>
                        <select class="form-select" id="office_id" name="office_id" onchange="this.form.submit()">
                            <option value="">All Offices</option>
                            <?php foreach ($offices as $office): ?>
                                <option value="<?= esc((string) $office['id']) ?>"<?= (isset($filters['office_id']) && $filters['office_id'] === (int) $office['id']) ? ' selected' : '' ?>><?= esc($office['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <a href="<?= site_url('admin/reports/export?' . http_build_query($filters)) ?>" class="btn btn-success btn-csv w-50">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> CSV Export
                    </a>
                    <button type="button" onclick="window.print()" class="btn btn-outline-primary btn-print w-50">
                        <i class="bi bi-printer me-1"></i> Print / PDF
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card kpi-card p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-primary text-white">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div>
                        <span class="text-muted small text-uppercase d-block">Total Allocated</span>
                        <h4 class="fw-bold mb-0 text-primary">₱<?= esc(number_format((float) $totals['total_allocated'], 2)) ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card kpi-card p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-info text-white">
                        <i class="bi bi-bookmark-fill"></i>
                    </div>
                    <div>
                        <span class="text-muted small text-uppercase d-block">Total Obligated</span>
                        <h4 class="fw-bold mb-0 text-info">₱<?= esc(number_format((float) $totals['total_obligated'], 2)) ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card kpi-card p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-success text-white">
                        <i class="bi bi-check-all"></i>
                    </div>
                    <div>
                        <span class="text-muted small text-uppercase d-block">Total Disbursed</span>
                        <h4 class="fw-bold mb-0 text-success">₱<?= esc(number_format((float) $totals['total_disbursed'], 2)) ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <?php
                $allocated = (float) $totals['total_allocated'];
                $disbursed = (float) $totals['total_disbursed'];
                $rate = $allocated > 0 ? ($disbursed / $allocated) * 100 : 0;
            ?>
            <div class="card kpi-card p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-warning text-dark">
                        <i class="bi bi-percent"></i>
                    </div>
                    <div>
                        <span class="text-muted small text-uppercase d-block">Disbursement Rate</span>
                        <h4 class="fw-bold mb-0 text-dark"><?= esc(number_format($rate, 1)) ?>%</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active filters display for print -->
    <div class="d-none d-print-block mb-4 text-muted small">
        <strong>Filters Applied:</strong>
        Year: <?= isset($filters['fiscal_year']) ? 'FY ' . esc((string) $filters['fiscal_year']) : 'All Years' ?> |
        Status: <?= isset($filters['status']) ? esc(ucfirst(str_replace('_', ' ', $filters['status']))) : 'All Statuses' ?> |
        Scope: <?= $currentUser['role'] === 'office_staff' ? esc($offices[0]['name'] ?? 'Assigned Office') : (isset($filters['office_id']) ? 'Selected Office' : 'All Offices') ?> |
        Total Projects: <?= esc((string) $totals['project_count']) ?>
    </div>

    <!-- Visual Charts Grid -->
    <div class="row g-4 mb-4">
        <!-- Budget Comparison -->
        <div class="col-lg-8">
            <div class="card chart-card p-3 h-100">
                <h5 class="fw-bold text-secondary mb-3">Office Budget Comparison</h5>
                <div class="chart-container">
                    <canvas id="adminDeptBudgetChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Project Status Breakdown -->
        <div class="col-lg-4">
            <div class="card chart-card p-3 h-100">
                <h5 class="fw-bold text-secondary mb-3">Project Status Summary</h5>
                <div class="chart-container d-flex align-items-center justify-content-center">
                    <canvas id="adminStatusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Trend Chart -->
        <div class="col-12">
            <div class="card chart-card p-3">
                <h5 class="fw-bold text-secondary mb-3">Fiscal Year Budget Trends</h5>
                <div class="chart-container" style="height: 280px;">
                    <canvas id="adminTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const colors = {
            allocated: 'rgba(11, 79, 108, 0.85)',
            obligated: 'rgba(1, 186, 239, 0.85)',
            disbursed: 'rgba(20, 164, 77, 0.85)',
            draft: '#94a3b8',
            submitted: '#3b82f6',
            under_review: '#f59e0b',
            approved: '#10b981',
            published: '#059669',
            completed: '#8b5cf6',
            cancelled: '#ef4444'
        };

        const officeData = <?= json_encode($totalsByOffice) ?>;
        const statusData = <?= json_encode($countsByStatus) ?>;
        const yearData = <?= json_encode($totalsByYear) ?>;

        // 1. Office Budgets
        const deptLabels = officeData.map(d => d.office_name);
        const deptAllocated = officeData.map(d => parseFloat(d.total_allocated));
        const deptObligated = officeData.map(d => parseFloat(d.total_obligated));
        const deptDisbursed = officeData.map(d => parseFloat(d.total_disbursed));

        const ctxDept = document.getElementById('adminDeptBudgetChart').getContext('2d');
        new Chart(ctxDept, {
            type: 'bar',
            data: {
                labels: deptLabels,
                datasets: [
                    {
                        label: 'Allocated',
                        data: deptAllocated,
                        backgroundColor: colors.allocated,
                        borderRadius: 4
                    },
                    {
                        label: 'Obligated',
                        data: deptObligated,
                        backgroundColor: colors.obligated,
                        borderRadius: 4
                    },
                    {
                        label: 'Disbursed',
                        data: deptDisbursed,
                        backgroundColor: colors.disbursed,
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
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            callback: function (value) {
                                return '₱' + (value >= 1e6 ? (value / 1e6).toFixed(1) + 'M' : value.toLocaleString());
                            }
                        }
                    }
                }
            }
        });

        // 2. Statuses
        const statusLabels = statusData.map(s => s.status.replace('_', ' ').toUpperCase());
        const statusCounts = statusData.map(s => s.project_count);
        const statusColors = statusData.map(s => colors[s.status] || '#cbd5e1');

        const ctxStatus = document.getElementById('adminStatusChart').getContext('2d');
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusCounts,
                    backgroundColor: statusColors,
                    borderWidth: 1
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

        // 3. Trends
        const yearLabels = yearData.map(y => 'FY ' + y.fiscal_year);
        const yearAlloc = yearData.map(y => parseFloat(y.total_allocated));
        const yearObl = yearData.map(y => parseFloat(y.total_obligated));
        const yearDisb = yearData.map(y => parseFloat(y.total_disbursed));

        const ctxYear = document.getElementById('adminTrendChart').getContext('2d');
        new Chart(ctxYear, {
            type: 'line',
            data: {
                labels: yearLabels,
                datasets: [
                    {
                        label: 'Allocated',
                        data: yearAlloc,
                        borderColor: '#0b4f6c',
                        backgroundColor: 'rgba(11, 79, 108, 0.05)',
                        fill: true,
                        tension: 0.2
                    },
                    {
                        label: 'Obligated',
                        data: yearObl,
                        borderColor: '#01baef',
                        backgroundColor: 'transparent',
                        tension: 0.2
                    },
                    {
                        label: 'Disbursed',
                        data: yearDisb,
                        borderColor: '#10b981',
                        backgroundColor: 'transparent',
                        tension: 0.2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        grid: { color: '#f1f5f9' },
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
