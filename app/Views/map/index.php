<?php
/**
 * Public project map view.
 *
 * @var string      $title
 * @var string      $metaDescription
 * @var array       $offices          [{id, name}]
 * @var array       $fiscal_years     [int, ...]
 */
?>
<?php ob_start(); ?>

<style>
:root { --kb-primary:#0b4f6c; --kb-accent:#01baef; --kb-muted:#6c757d; }

/* ── Hero ────────────────────────────────────────────────────── */
.map-hero {
    background: linear-gradient(135deg, #0b4f6c 0%, #145da0 100%);
    color: #fff;
    padding: 2rem 0 1.25rem;
}
.map-hero h1 { font-size: 1.85rem; font-weight: 700; margin-bottom: .2rem; }
.map-hero p  { opacity: .85; margin: 0; font-size: .95rem; }

/* ── Sidebar ────────────────────────────────────────────────── */
.map-sidebar {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 12px;
    padding: 1.25rem;
    position: sticky;
    top: 1rem;
    max-height: calc(100vh - 2rem);
    overflow-y: auto;
}
.sidebar-title {
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--kb-muted);
    margin-bottom: .75rem;
}
.map-sidebar label   { font-size: .85rem; font-weight: 500; color: #444; }
.map-sidebar .form-select { font-size: .85rem; border-radius: 8px; }

.btn-apply-map {
    background: var(--kb-primary);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: .875rem;
    font-weight: 600;
    width: 100%;
    padding: .55rem;
    transition: background .2s;
}
.btn-apply-map:hover { background: #145da0; color: #fff; }

.btn-reset-map {
    font-size: .8rem;
    color: var(--kb-muted);
    text-decoration: none;
    display: block;
    text-align: center;
    margin-top: .5rem;
}
.btn-reset-map:hover { color: var(--kb-primary); }

/* ── Map ─────────────────────────────────────────────────────── */
#project-map {
    width: 100%;
    height: 540px;
    border-radius: 12px;
    border: 1px solid #dee2e6;
    z-index: 0;
}

/* ── Status badge counter ───────────────────────────────────── */
#map-count {
    font-size: .82rem;
    color: var(--kb-muted);
}
#map-count strong { color: #1a1a1a; }

/* ── Popup ───────────────────────────────────────────────────── */
.map-popup-title  { font-weight: 600; font-size: .92rem; margin-bottom: .25rem; }
.map-popup-code   { font-family: monospace; font-size: .75rem; color: var(--kb-muted); }
.map-popup-meta   { font-size: .78rem; margin: .4rem 0; color: #555; }
.map-popup-amount { font-size: .82rem; font-weight: 600; }
.map-popup-link   { font-size: .8rem; }

/* ── Loading overlay ─────────────────────────────────────────── */
#map-loading {
    position: absolute;
    inset: 0;
    background: rgba(255,255,255,.75);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    z-index: 1000;
}
</style>

<!-- ── Leaflet CSS ─────────────────────────────────────────────── -->
<link rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
      crossorigin="">

<!-- ── Hero ──────────────────────────────────────────────────── -->
<section class="map-hero" aria-labelledby="map-page-title">
    <div class="container">
        <h1 id="map-page-title">
            <i class="bi bi-geo-alt-fill me-2" aria-hidden="true"></i>Project Map
        </h1>
        <p>Visualise AIP projects across Kabankalan City. Click a pin to see details.</p>
    </div>
</section>

<!-- ── Main ──────────────────────────────────────────────────── -->
<div class="container py-4">
    <div class="row g-4">

        <!-- Sidebar -->
        <div class="col-lg-3">
            <aside class="map-sidebar" aria-label="Map filters">
                <p class="sidebar-title"><i class="bi bi-funnel me-1"></i>Filter Markers</p>

                <div class="mb-3">
                    <label for="filter-office" class="form-label">Office</label>
                    <select id="filter-office" class="form-select">
                        <option value="">All offices</option>
                        <?php foreach ($offices as $o): ?>
                        <option value="<?= (int)$o['id'] ?>">
                            <?= esc($o['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="filter-year" class="form-label">Fiscal Year</label>
                    <select id="filter-year" class="form-select">
                        <option value="">All years</option>
                        <?php foreach ($fiscal_years as $fy): ?>
                        <option value="<?= (int)$fy ?>"><?= (int)$fy ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="filter-status" class="form-label">Status</label>
                    <select id="filter-status" class="form-select">
                        <option value="">All statuses</option>
                        <option value="published">Published</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>

                <button id="btn-apply" type="button" class="btn-apply-map mt-1">
                    <i class="bi bi-search me-1"></i>Apply Filters
                </button>
                <a href="#" id="btn-reset" class="btn-reset-map">Reset</a>

                <hr class="my-3">

                <div id="map-count" class="text-center">
                    <strong>—</strong> projects shown
                </div>

                <!-- Legend -->
                <hr class="my-3">
                <p class="sidebar-title">Legend</p>
                <div class="d-flex flex-column gap-1" style="font-size:.8rem;">
                    <span><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#01baef;margin-right:6px;"></span>Published</span>
                    <span><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#198754;margin-right:6px;"></span>Completed</span>
                </div>
            </aside>
        </div>

        <!-- Map panel -->
        <div class="col-lg-9">
            <div class="position-relative">
                <div id="map-loading" aria-live="polite" aria-label="Loading map markers">
                    <div class="text-center">
                        <div class="spinner-border text-primary" style="width:2rem;height:2rem;" role="status">
                            <span class="visually-hidden">Loading…</span>
                        </div>
                        <p class="mt-2 mb-0 small text-muted">Loading project markers…</p>
                    </div>
                </div>
                <div id="project-map" role="application" aria-label="Interactive project map"></div>
            </div>

            <p class="text-muted small mt-2 mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Only geo-tagged projects with a published or completed status appear on the map.
                <a href="<?= site_url('aip') ?>">Browse all projects →</a>
            </p>
        </div>

    </div>
</div>

<!-- ── Leaflet JS ──────────────────────────────────────────────── -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV/XN/WLcE="
        crossorigin=""></script>

<script>
(function () {
    'use strict';

    /* ── Base map ──────────────────────────────────────────────── */
    const map = L.map('project-map', {
        center: [10.1167, 122.8167], // Kabankalan City, Negros Occidental
        zoom:   12,
        scrollWheelZoom: true,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    /* ── Custom circle marker factories ───────────────────────── */
    function makeIcon(status) {
        const color = status === 'completed' ? '#198754' : '#01baef';
        return L.divIcon({
            className: '',
            html: `<span style="
                display:block;
                width:14px;height:14px;
                border-radius:50%;
                background:${color};
                border:2.5px solid #fff;
                box-shadow:0 1px 4px rgba(0,0,0,.35);
            "></span>`,
            iconSize: [14, 14],
            iconAnchor: [7, 7],
            popupAnchor: [0, -10],
        });
    }

    /* ── Marker layer ─────────────────────────────────────────── */
    let markerLayer = L.layerGroup().addTo(map);

    function phpNum(v) {
        return '₱' + parseFloat(v).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function buildPopup(p) {
        const barangay = p.barangay ? `<br><i class="bi bi-geo-alt"></i> ${p.barangay}` : '';
        return `
            <div style="min-width:210px;max-width:270px;">
                <p class="map-popup-code mb-0">${p.project_code}</p>
                <p class="map-popup-title">${p.title}</p>
                <p class="map-popup-meta">
                    <i class="bi bi-building"></i> ${p.office_name || '—'}${barangay}<br>
                    <i class="bi bi-calendar3"></i> FY ${p.fiscal_year}
                    &nbsp;|&nbsp;
                    <span class="badge ${p.status === 'completed' ? 'text-bg-success' : 'text-bg-primary'} badge-status">
                        ${p.status}
                    </span>
                </p>
                <p class="map-popup-amount">${phpNum(p.allocated_amount)} <small class="fw-normal text-muted">allocated</small></p>
                <a href="${p.url}" class="map-popup-link btn btn-sm btn-outline-primary w-100" target="_blank" rel="noopener">
                    View details <i class="bi bi-box-arrow-up-right ms-1"></i>
                </a>
            </div>`;
    }

    function loadMarkers() {
        document.getElementById('map-loading').style.display = 'flex';
        markerLayer.clearLayers();

        const officeId   = document.getElementById('filter-office').value;
        const fiscalYear = document.getElementById('filter-year').value;
        const status     = document.getElementById('filter-status').value;

        const params = new URLSearchParams();
        if (officeId)   params.set('office_id',   officeId);
        if (fiscalYear) params.set('fiscal_year',  fiscalYear);
        if (status)     params.set('status',       status);

        fetch(`<?= site_url('map/markers') ?>?` + params.toString())
            .then(r => {
                if (! r.ok) throw new Error('Network error ' + r.status);
                return r.json();
            })
            .then(geojson => {
                const features = geojson.features || [];

                features.forEach(f => {
                    const [lng, lat] = f.geometry.coordinates;
                    const p = f.properties;
                    L.marker([lat, lng], { icon: makeIcon(p.status) })
                        .bindPopup(buildPopup(p), { maxWidth: 280 })
                        .addTo(markerLayer);
                });

                // Update count
                const countEl = document.getElementById('map-count');
                countEl.innerHTML = `<strong>${features.length}</strong> project${features.length !== 1 ? 's' : ''} shown`;

                // Fit bounds if we have markers
                if (features.length > 0) {
                    const bounds = features.map(f => [
                        f.geometry.coordinates[1],
                        f.geometry.coordinates[0],
                    ]);
                    map.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
                }
            })
            .catch(err => {
                console.error('Map markers error:', err);
                document.getElementById('map-count').innerHTML =
                    '<span class="text-danger">Failed to load markers.</span>';
            })
            .finally(() => {
                document.getElementById('map-loading').style.display = 'none';
            });
    }

    /* ── Controls ─────────────────────────────────────────────── */
    document.getElementById('btn-apply').addEventListener('click', loadMarkers);

    document.getElementById('btn-reset').addEventListener('click', function (e) {
        e.preventDefault();
        document.getElementById('filter-office').value = '';
        document.getElementById('filter-year').value   = '';
        document.getElementById('filter-status').value = '';
        loadMarkers();
    });

    /* ── Initial load ─────────────────────────────────────────── */
    loadMarkers();

})();
</script>

<?php
$content = ob_get_clean();
echo view('layouts/public', [
    'title'           => $title,
    'metaDescription' => $metaDescription,
    'content'         => $content,
]);
