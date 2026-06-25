<?php

namespace App\Controllers;

class FeedbackSubmit extends BaseController
{
    public function form(int $id): string
    {
        $data = service('projectDetail')->getForFeedbackForm($id);

        if ($data === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('feedback_submit/form', $data);
    }

    public function store(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $result = service('feedback')->submit([
            'project_id'   => $id,
            'author_name'  => $this->request->getPost('author_name'),
            'author_email' => $this->request->getPost('author_email'),
            'body'         => $this->request->getPost('body'),
        ]);

        if ($result === false) {
            return redirect()->back()->withInput()->with('errors', service('feedback')->getErrors());
        }

        return redirect()->to(site_url('feedback/success/' . $id))
            ->with('success', 'Thank you. Your feedback has been submitted for review.');
    }

    public function success(int $id): string
    {
        $data = service('projectDetail')->getForFeedbackForm($id);

        if ($data === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('feedback_submit/success', $data);
    }
}
