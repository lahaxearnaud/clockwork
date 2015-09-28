<?php
namespace Clockwork\Facade;

use Illuminate\Support\Facades\Facade as IlluminateFacade;

class Clockwork extends IlluminateFacade
{

    protected static function getFacadeAccessor()
    {
        return 'clockwork';
    }
}
