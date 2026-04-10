<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\AutoloadConfig;

class Autoload extends AutoloadConfig
{
    public $psr4 = [
        'App'    => APPPATH,
        'Config' => APPPATH . 'Config',
    ];

    public $classmap = [];
    public $files = [];
    public $helpers = ['url', 'form', 'html', 'security'];
}
