<?php

namespace App\Controllers\Admin;

/**
 * Admin dashboard home page.
 */
class Dashboard extends AdminBaseController
{
    /**
     * Admin dashboard home page
     */
    public function index(): string
    {
        return $this->adminView('admin/dashboard/index', [
            'title' => 'Admin Dashboard',
            'user'  => $this->getCurrentUser(),
        ]);
    }
}