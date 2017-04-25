<?php
namespace Gkr\Themes\Command;

use Gkr\Support\Console\ColorTrait;
use Gkr\Themes\Command\Traits\TraitCommand;
use Illuminate\Console\Command;

class DeleteCommand extends Command
{
    use TraitCommand;
    use ColorTrait;
    protected $signature = 'gkr:theme:delete {name? : which theme you want to delete}';
    protected $description = 'delete a theme';
    public function handle()
    {
        $themesService = $this->laravel['gkr.themes'];
        $theme = $this->argument('name') ? strtolower($this->argument('name')) : false;
        if ($theme && !$themesService->has($theme)) {
            $this->error('Theme not exists');
            return;
        }
        if (!$theme){
            $themes = $this->createTheme($themesService,false,true);
            $theme = $this->choice($this->askColor('Select Theme',"which theme you want to delete"),$themes->toArray());
        }
        $message = "confirmed will delete its all files and directories,do you sure delete theme <fg=white>{$theme}</>";
        if ($this->confirm($this->confirmColor('confirm delete?',$message))){
            $themesService->delete($theme);
        }
    }
}