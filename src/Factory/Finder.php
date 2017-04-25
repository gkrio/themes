<?php

namespace Gkr\Themes\Factory;

use Gkr\Themes\Theme;
use Illuminate\Contracts\Console\Application;
use Symfony\Component\Finder\Finder as SymfonyFinder;

/**
 * 主题目录操作类
 * Class Finder
 * @package Gkr\Themes\Factory
 */
class Finder
{
    protected $apps;
    /**
     * Symfony finder对象.
     *
     * @var SymfonyFinder
     */
    protected $finder;

    /**
     * 主题对象集合数组.
     *
     * @var array
     */
    protected $themes = [];

    /**
     * 判断主题是否已经被扫描.
     *
     * @var bool
     */
    protected $scanned = false;

    /**
     * 用于扫描的主题根路径.
     *
     * @var string
     */
    protected $path;
    protected $json;

    /**
     * 主题属性配置文件路径.
     *
     * @var string
     */
    const FILENAME = 'theme.json';

    /**
     * 集合类构造函数.
     * @param Application $app
     * @param SymfonyFinder|null $finder
     */
    public function __construct(Application $app,SymfonyFinder $finder = null)
    {
        $this->finder = $finder ?: new SymfonyFinder();
        $this->apps = $app->make('gkr.apps');
        $this->configure = $app->make('gkr.support.config');
    }

    /**
     * 设置主题根路径.
     *
     * @param string $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * 获取主题根路径.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * 改变主题集合状态,用于重新扫描
     * @param bool $scanned
     * @return $this
     */
    public function status($scanned = true)
    {
        $this->scanned = $scanned;
        $this->themes = [];
        $this->finder = new SymfonyFinder();
        return $this;
    }

    /**
     * 扫描主题根路径,扫描所有主题
     *
     * @return $this
     */
    public function scan()
    {
        if ($this->scanned == true) {
            return $this;
        }

        if (is_dir($path = $this->getPath())) {
            $found = $this->finder
                ->in($path)
                ->files()
                ->name(self::FILENAME)
                ->depth('== 1')
                ->followLinks();
            foreach ($found as $file) {
                $this->themes[] = new Theme($this->getInfo($file),$this->apps);
            }
        }

        $this->scanned = true;

        return $this;
    }

    /**
     * 获取主题对象集合
     *
     * @return array
     */
    public function getThemes()
    {
        return $this->themes;
    }

    /**
     * 通过根路径获取主题对象集合
     *
     * @param $path
     * @param bool $rescan
     * @return array
     */
    public function find($path,$rescan = false)
    {
        return !$rescan ?
            $this->setPath($path)->scan()->getThemes() :
            $this->setPath($path)->status(false)->scan()->getThemes() ;
    }

    /**
     * 通过主题的.json文件获取主题设置属性.
     *
     * @param \SplFileInfo $file
     *
     * @return array
     */
    protected function getInfo($file)
    {
//        $attributes = Json::make($path = $file->getRealPath())->toArray();
        $path = $file->getRealPath();
        $attributes = $this->configure->driver('json',$path)->all();
        $attributes['path'] = dirname($path);

        return $attributes;
    }
}
