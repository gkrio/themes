<?php

namespace Gkr\Themes;

use Illuminate\Support\Facades\Facade;

class ThemeFacade extends Facade
{
    /**
     * Get Facade Accessor.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'gkr.themes';
    }
}
