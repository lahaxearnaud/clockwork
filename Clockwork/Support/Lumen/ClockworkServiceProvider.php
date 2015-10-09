<?php namespace Clockwork\Support\Lumen;

use Clockwork\Clockwork;
use Clockwork\DataSource\EloquentDataSource;
use Clockwork\DataSource\LumenDataSource;
use Clockwork\DataSource\MonologDataSource;
use Clockwork\DataSource\PhpDataSource;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

class ClockworkServiceProvider extends ServiceProvider
{

    public function boot()
    {
        if ($this->isRunningWithFacades() && !class_exists('Clockwork')) {
            class_alias('Clockwork\Facade\Clockwork', 'Clockwork');
        }

        if (!$this->app['clockwork.support']->isCollectingData()) {

            return; // Don't bother registering event listeners as we are not collecting data
        }

        if ($this->app['clockwork.support']->isCollectingDatabaseQueries()) {
            $this->app['clockwork.eloquent']->listenToEvents();
        }

        if (!$this->app['clockwork.support']->isEnabled()) {
            return; // Clockwork is disabled, don't register the route
        }

        /*
        |--------------------------------------------------------------------------
        | Debug routes
        |--------------------------------------------------------------------------
        */

        if (env('APP_DEBUG', false)) {
                $this->app->group(['middleware' => 'cors'], function () {
                $this->app->get('/__clockwork/{id}', ['as' => 'profiler.native', 'uses' => \Clockwork\Http\Profiler::class . '@getData']);
                $this->app->get('api/__profiler/profiles/', ['as' => 'profiler.list', 'uses' => \Clockwork\Http\Profiler::class . '@index']);
                $this->app->get('api/__profiler/profiles/stats', ['as' => 'profiler.stats', 'uses' => \Clockwork\Http\Profiler::class . '@stats']);
                $this->app->get('api/__profiler/profiles/last', ['as' => 'profiler.last', 'uses' => \Clockwork\Http\Profiler::class . '@last']);
                $this->app->get('api/__profiler/profiles/{id}', ['as' => 'profiler.show', 'uses' => \Clockwork\Http\Profiler::class . '@show']);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Override env configuration
        |--------------------------------------------------------------------------
        |
        | Disable profiler for profiler request
        |
        */
        $pathInfo = \Illuminate\Support\Facades\Request::getPathInfo();
        if(strpos($pathInfo, 'api/__profiler') > 0) {
            putenv("CLOCKWORK_COLLECT_DATA_ALWAYS=false");
            putenv("CLOCKWORK_ENABLE=false");
        }
    }

    public function register()
    {
        $this->app->singleton('clockwork.support', function ($app) {
            return new ClockworkSupport($app);
        });

        $this->app->singleton('clockwork.lumen', function ($app) {
            return new LumenDataSource($app);
        });

        $this->app->singleton('clockwork.eloquent', function ($app) {
            return new EloquentDataSource($app['db'], $app['events']);
        });

        foreach ($this->app['clockwork.support']->getAdditionalDataSources() as $name => $callable) {
            $this->app->singleton($name, $callable);
        }

        $this->app->singleton('clockwork', function ($app) {
            $clockwork = new Clockwork();

            $clockwork
                ->addDataSource(new PhpDataSource())
                ->addDataSource(new MonologDataSource($app['log']))
                ->addDataSource($app['clockwork.lumen']);

            $extraDataProviders = $app['config']->get('profiler.extraDataProviders', []);
            foreach ($extraDataProviders as $extraDataProvider) {
                $clockwork->addDataSource(new $extraDataProvider);
            }

            if ($app['clockwork.support']->isCollectingDatabaseQueries()) {
                $clockwork->addDataSource($app['clockwork.eloquent']);
            }

            foreach ($app['clockwork.support']->getAdditionalDataSources() as $name => $callable) {
                $clockwork->addDataSource($app[$name]);
            }

            $clockwork->setStorage($app['clockwork.support']->getStorage());

            return $clockwork;
        });

        $this->app['clockwork.lumen']->listenToEvents();

        // set up aliases for all Clockwork parts so they can be resolved by the IoC container
        $this->app->alias('clockwork.support', 'Clockwork\Support\Lumen\ClockworkSupport');
        $this->app->alias('clockwork.lumen', 'Clockwork\DataSource\LumenDataSource');
        $this->app->alias('clockwork.eloquent', 'Clockwork\DataSource\EloquentDataSource');
        $this->app->alias('clockwork', 'Clockwork\Clockwork');

        $this->registerCommands();
    }

    /**
     * Register the artisan commands.
     */
    public function registerCommands()
    {
        // Clean command
        $this->app['command.clockwork.clean'] = $this->app->share(function ($app) {
            return $app->make(\Clockwork\Support\Lumen\ClockworkCleanCommand::class);
        });

        $this->commands(
            'command.clockwork.clean'
        );
    }

    public function provides()
    {
        return array('clockwork');
    }

    protected function isRunningWithFacades()
    {
        return Facade::getFacadeApplication() !== null;
    }
}
