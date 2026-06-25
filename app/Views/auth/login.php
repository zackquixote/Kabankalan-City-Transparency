<?php

/**
 * @var string|null $title
 * @var array|null $validation
 * @var string|null $error
 */

?>

<div class="hero-kb py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow">
                    <div class="card-header bg-white border-0 text-center py-4">
                        <h2 class="mb-1 text-kb-primary">Admin Login</h2>
                        <p class="text-muted small mb-0">Access the administrative dashboard</p>
                    </div>
                    <div class="card-body px-4 py-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= esc($error) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($validation): ?>
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0">
                                    <?php foreach ($validation as $field => $message): ?>
                                        <li><?= esc($message) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?= form_open('auth/authenticate', ['class' => 'needs-validation', 'novalidate' => true]) ?>
                            <?= csrf_field() ?>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" 
                                       class="form-control <?= isset($validation['email']) ? 'is-invalid' : '' ?>" 
                                       id="email" 
                                       name="email" 
                                       value="<?= esc(old('email')) ?>"
                                       required 
                                       autocomplete="email">
                                <?php if (isset($validation['email'])): ?>
                                    <div class="invalid-feedback">
                                        <?= esc($validation['email']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" 
                                       class="form-control <?= isset($validation['password']) ? 'is-invalid' : '' ?>" 
                                       id="password" 
                                       name="password" 
                                       required 
                                       autocomplete="current-password">
                                <?php if (isset($validation['password'])): ?>
                                    <div class="invalid-feedback">
                                        <?= esc($validation['password']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Sign In
                                </button>
                            </div>
                        <?= form_close() ?>
                    </div>
                    <div class="card-footer bg-light text-center py-3">
                        <small class="text-muted">
                            Authorized personnel only. 
                            <a href="<?= base_url() ?>" class="text-decoration-none">Back to Public Site</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .text-kb-primary {
        color: var(--kb-primary) !important;
    }
    .hero-kb {
        min-height: 100vh;
        display: flex;
        align-items: center;
    }
    .card {
        border: none;
        border-radius: 0.75rem;
    }
    .card-header {
        border-top-left-radius: 0.75rem;
        border-top-right-radius: 0.75rem;
    }
    .btn-primary {
        background-color: var(--kb-primary);
        border-color: var(--kb-primary);
        border-radius: 0.5rem;
        font-weight: 600;
        padding: 0.75rem 1rem;
    }
    .btn-primary:hover {
        background-color: #145da0;
        border-color: #145da0;
    }
</style>