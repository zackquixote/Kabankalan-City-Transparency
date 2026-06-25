<?php

namespace App\Controllers;

class ProjectDetail extends BaseController
{
    public function show(int $id): string
    {
        $detail = service('projectDetail')->getPublicDetail($id);

        if ($detail === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('project_detail/show', $detail);
    }
}
