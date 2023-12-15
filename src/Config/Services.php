<?php

namespace Aminos\CodeigniterBlade\Config;

use Aminos\CodeigniterBlade\Blade;
use CodeIgniter\Config\BaseService;
use Aminos\CodeigniterBlade\Config\Blade as BladeConfig;
use Config\Paths;

class Services extends BaseService
{
    public static function blade(?BladeConfig $config = null, bool $getShared = true): Blade
    {
        if ($getShared) {
            return static::getSharedInstance('blade', $config);
        }

        $viewPath = config(Paths::class)->viewDirectory;
        $cachePath = config(Paths::class)->writableDirectory . '/blade';

        return new Blade($viewPath, $cachePath);
    }
}