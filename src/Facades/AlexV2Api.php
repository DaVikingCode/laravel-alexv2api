<?php

namespace DaVikingCode\LaravelAlexV2Api\Facades;

use Illuminate\Support\Facades\Facade;

class LaravelAlexV2Api extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravelalexv2api';
    }
}
