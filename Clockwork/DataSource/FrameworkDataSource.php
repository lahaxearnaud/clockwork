<?php namespace Clockwork\DataSource;

use Clockwork\Request\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class FrameworkDataSource implements ExtraDataSourceInterface
{

    /**
     * @return string
     */
    public function getKey()
    {
        return 'framework';
    }

    /**
     * Adds data to the request and returns it
     */
    public function resolve(Request $request)
    {
        return [
            //'version'      => App::version(),
            'environment'  => App::environment(),
            //'locale'       => App::getLocale(),
        ];
    }
}