<?php

namespace App\Controllers\Siswa;

use App\Controllers\BaseController;
use App\Models\SettingsModel;
use CodeIgniter\HTTP\ResponseInterface;

class Panduan extends BaseController
{
    protected $helpers = ['url', 'html'];

    public function index(): ResponseInterface
    {
        $this->requireSiswa();

        return $this->renderSiswaView('siswa/panduan');
    }
}