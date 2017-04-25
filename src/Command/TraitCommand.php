<?php

namespace Gkr\Themes\Command\Traits;
use Illuminate\Support\Collection;

Trait TraitCommand
{
    protected function getWebApps($container)
    {
        return $container->type('web')->pluck('name');
    }

    protected function createWebApp($container)
    {
        $apps = $this->getWebApps($container);
        if (!$apps || $apps->isEmpty()) {
            $message = "you must have at least one<fg=black;bg=white;options=bold,underscore> web type </>app!";
            $this->line($this->noteColor($message));
            if ($this->confirm($this->confirmColor("create one","create a web type app"), true)) {
                $this->call('gkr:app:make',['type' => 'web'] );
            } else {
                exit();
            }
        }
        return $this->getWebApps($container);
    }
    protected function fetchThemes($container)
    {
        $themes = [];
        foreach ($container->all() as $theme){
            $themes[] = $theme->getName();
        }
        return $themes;
    }
    protected function fetchAppThemes($container,$app)
    {
        $themes = [];
        foreach ($container->findFromApp($app) as $theme){
            $themes[] = $theme->getName();
        }
        return $themes;
    }
    protected function createTheme($container,$active = false,$continue = false)
    {

        if (count($this->fetchThemes($container)) < 1){
            $this->line($this->noteColor("you have not any themes,please make one first!"));
            $this->call('gkr:theme:make',['--active' => $active,'--continue' => $continue]);
        }
        return new Collection($this->fetchThemes($container));
    }

    protected function createAppTheme($container,$app)
    {
        if (count($this->fetchAppThemes($container,$app)) < 1){
            $this->line($this->noteColor("you have not any themes in app {$app},please make one first!"));
            $this->call('gkr:theme:make',['app' => $app,'--active' => true]);
        }
        return $this->fetchAppThemes($container,$app);
    }
}