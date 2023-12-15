<?php

use Aminos\CodeigniterBlade\Blade;

if (!function_exists('blade')) {
    function blade($view = null, $data = [], $mergeData = []): Blade|string
    {
        /** @var Blade $blade */
        $blade = service('blade');

        if (func_num_args() === 0) {
            return $blade;
        }

        return $blade->make($view, $data, $mergeData)->render();
    }
}