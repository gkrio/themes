<?php
namespace Gkr\Themes\Command;

use Gkr\Support\Console\ColorTrait;
use Gkr\Themes\Command\Traits\TraitCommand;
use Illuminate\Console\Command;

class ActiveCommand extends Command
{
    use TraitCommand;
    use ColorTrait;
    protected $name = 'gkr:theme:active';
    protected $description = 'set a theme be default of a app';
    public function handle()
    {
        $themesService = $this->laravel['gkr.themes'];
        $themes = $this->createTheme($themesService,true)->toArray();
        $theme = $this->choice($this->askColor("Select Theme","which you want to be active of its app"), $themes);
        $appName = $themesService->find($theme)->getApp();
        if ($themesService->find($theme)->isActive()){
            $this->error("the theme {$theme} is already be default theme of {$appName}");
            exit();
        }else{
            $themesService->setCurrent($theme);
        }
        $this->info("change app <fg=yellow;options=bold,underscore>{$appName}</>'s default theme to <fg=blue;options=bold,underscore>{$theme}</> success!");
    }
}
