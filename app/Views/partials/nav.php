<?php

$segment = service('uri')->getSegment(1) ?? '';
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-kb">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= site_url('/') ?>">Kabankalan Budget Portal</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link<?= $segment === '' ? ' active' : '' ?>" href="<?= site_url('/') ?>">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= $segment === 'visions' ? ' active' : '' ?>" href="<?= site_url('visions') ?>">Visions</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= $segment === 'aip' ? ' active' : '' ?>" href="<?= site_url('aip') ?>">AIP Registry</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= $segment === 'map' ? ' active' : '' ?>" href="<?= site_url('map') ?>">Project Map</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= $segment === 'transparency' ? ' active' : '' ?>" href="<?= site_url('transparency') ?>">Transparency Dashboard</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
