<?php

namespace Gkr\Themes;

use Gkr\Apps\AppsServiceProvider;
use Gkr\Themes\Command\ActiveCommand;
use Gkr\Themes\Command\CacheCommand;
use Gkr\Themes\Command\DeleteCommand;
use Gkr\Themes\Command\ListCommand;
use Gkr\Themes\Command\MakeCommand;
use Gkr\Themes\Command\PublishCommand;
use Gkr\Themes\Factory\Finder;
use Gkr\Themes\Factory\Repository;
use Illuminate\Support\ServiceProvider;

class ThemesServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        $this->registerConfig();

        $this->registerNamespaces();

        $this->registerHelpers();
    }

    /**
     * Register the helpers file.
     */
    public function registerHelpers()
    {
        require __DIR__ . '/helpers.php';
    }

    /**
     * Register configuration file.
     */
    protected function registerConfig()
    {
        $configPath = __DIR__ . '/../config/config.php';

        $this->publishes([$configPath => config_path('gkr/themes.php')]);

        $this->mergeConfigFrom($configPath, 'gkr.themes');
    }

    /**
     * Register the themes namespaces.
     */
    protected function registerNamespaces()
    {
        $this->app['gkr.themes']->registerNamespaces();
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        if (!$this->app->bound('gkr.apps')) {
            $this->app->register(AppsServiceProvider::class);
        }
        $this->app->singleton('gkr.themes', function ($app) {
            return new Repository(new Finder($app),$app);
        });

        $this->app->alias('gkr.themes', Theme::class);

        $this->registerCommands();

    }

    /**
     * Register commands.
     */
    protected function registerCommands()
    {
        $this->commands(MakeCommand::class);
        $this->commands(ActiveCommand::class);
        $this->commands(DeleteCommand::class);
        $this->commands(CacheCommand::class);
        $this->commands(ListCommand::class);
        $this->commands(PublishCommand::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('gkr.themes');
    }
}
