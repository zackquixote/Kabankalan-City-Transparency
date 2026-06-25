<?php

namespace App\Controllers;

class Visions extends BaseController
{
    public function index(): string
    {
        return view('visions/index', [
            'visions' => service('visionCatalog')->getActiveVisions(),
        ]);
    }
}
