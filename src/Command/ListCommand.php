<?php

namespace Gkr\Themes\Command;

use Gkr\Support\Console\ColorTrait;
use Gkr\Themes\Command\Traits\TraitCommand;
use Illuminate\Console\Command;

class ListCommand extends Command
{
    use TraitCommand;
    use ColorTrait;
    /**
     * Command name.
     *
     * @var string
     */
    protected $name = 'gkr:theme:list';

    /**
     * Command description.
     *
     * @var string
     */
    protected $description = 'Show all themes';

    /**
     * Execute command.
     */
    public function fire()
    {
        $this->table(['Name','App', 'Path', 'Enabled', 'Active'], $this->getRows());
    }

    /**
     * Get table rows.
     *
     * @return array
     */
    public function getRows()
    {
        $rows = [];

        $themes = $this->laravel['gkr.themes']->all();
        if (count($themes) < 1){
            $themes = $this->createTheme($this->laravel['gkr.themes'],false,true);
        }
        foreach ($themes as $theme) {
            $app = $theme->getApp();
            $rows[] = [
                $theme->getName(),
                $app,
                $theme->getPath(),
                $theme->enabled() ? 'Yes' : 'No',
                $theme->isActive($app) ? 'Yes' : 'No',
            ];
        }

        return $rows;
    }
}
