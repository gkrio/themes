<?php

namespace Gkr\Themes\Command;

use Illuminate\Console\Command;

class CacheCommand extends Command
{
    /**
     * Command name.
     *
     * @var string
     */
    protected $name = 'gkr:theme:cache';

    /**
     * Command description.
     *
     * @var string
     */
    protected $description = 'Create a theme cache file for faster theme loading';

    /**
     * Execute command.
     */
    public function fire()
    {
        $this->clear();
        $this->cache();
    }

    /**
     * Clear cached themes.
     */
    protected function clear()
    {
        $this->laravel['gkr.themes']->forgetCache();

        $this->info('Theme cache cleared!');
    }

    /**
     * Cache the themes.
     */
    protected function cache()
    {
        $this->laravel['gkr.themes']->cache();

        $this->info('Themes cached successfully!');
    }
}
