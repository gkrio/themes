<?php

namespace Gkr\Themes\Command;

use Gkr\Support\Console\ColorTrait;
use Gkr\Support\Stub\Stub;
use Gkr\Themes\Command\Traits\TraitCommand;
use Illuminate\Console\Command;

class MakeCommand extends Command
{
    use TraitCommand;
    use ColorTrait;
    /**
     * Command name.
     *
     * @var string
     */
    protected $signature = 'gkr:theme:make
    {name? : theme name}
    {app? : theme\'s app name,should be web type app}
    {--F|force : Force creation if theme already exists.}
    {--A|active : active a theme to be it\'s app\'s default theme.}
    {--C|continue : if has some callback after theme maked.}';

    /**
     * Command description.
     *
     * @var string
     */
    protected $description = 'Create a new theme';

    /**
     * Execute command.
     */
    public function fire()
    {
        $appsService = $this->laravel['gkr.apps'];
        $themesService = $this->laravel['gkr.themes'];
        $name = $this->argument('name') ?
            strtolower($this->argument('name')) :
            strtolower($this->ask($this->askColor('Theme Name',"like PjaxView,Admintle")));
        if ($themesService->has($name) && !$this->option('force')) {
            $this->error('Theme already exists.');

            return;
        }
        if ($this->argument('app')){
            $app = $this->argument('app');
            $appsService->check($app);
            if ($appsService->getConfig($app)->get('type') != 'web'){
                $this->error("app {$app}'s type is not web");
                exit();
            }
        }else{
            $apps = $this->createWebApp($appsService)->toArray();
            $app = $this->choice($this->askColor("Select App","chose which app of theme to create"), $apps);
        }
        $this->generate($name, $app);
        $this->active($themesService,$name,$app);
    }

    protected function active($container, $name, $app)
    {
        $actived = false;
        $container->rescan();
        $message = 'Theme created successfully';
        if ($this->option('active')) {
            $container->setCurrent($name);
            $actived = true;
        }else{
            if($this->confirm($this->confirmColor("Active Theme","set theme named {$name} to be default theme of {$app}"))) {
                $container->setCurrent($name);
                $actived = true;
            }
        }
        $message = $actived ? $message." and actived!" : $message.'.';
        $this->info($message);
        if (!$this->option('continue')){
            exit();
        }
    }

    /**
     * Generate a new theme by given theme name.
     *
     * @param string $name
     */
    protected function generate($name, $app)
    {
        $themePath = config('gkr.themes.path') . '/' . $name;

        $this->laravel['files']->copyDirectory(__DIR__ . '/stubs/theme', $themePath);

        Stub::createFromPath(__DIR__ . '/stubs/json.stub', compact('name', 'app'))
            ->saveTo($themePath, 'theme.json');

        Stub::createFromPath(__DIR__ . '/stubs/theme.stub')
            ->saveTo($themePath, 'theme.php');
    }
}
