<?php
namespace Gkr\Themes\Factory;

use Gkr\Themes\Factory\Resolves\AppResolve;
use Gkr\Themes\Factory\Resolves\ThemeResolve;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * 主题集合逻辑处理类
 * Class Repository
 * @package Gkr\Themes\Factory
 */
class Repository implements Arrayable
{
    /**
     * Gkr Themes目录操作类对象.
     *
     * @var Finder
     */
    protected $finder;

    /**
     * 容器对象
     * @var Application
     */
    protected $app;

    /**
     * Laravel配置服务对象.
     *
     * @var \Illuminate\Config\Repository
     */
    protected $config;

    protected $views;

    protected $lang;

    /**
     * Laravel缓存服务对象
     * @var \Illuminate\Cache\Repository
     */
    protected $cache;

    /**
     * Laravel文件系统服务对象
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * Gkr Apps服务对象
     * @var \Gkr\Apps\Factory\Repository
     */
    protected $apps;

    /**
     * 主题对象集合数组
     * @var array
     */
    protected $themes = [];

    /**
     * 每个App的默认主题数组
     * @var array
     */
    protected $current = [];

    /**
     * 主题根目录路径.
     *
     * @var
     */
    protected $path;

    /**
     * 初始化逻辑处理类,并注入所需服务.
     *
     * Repository constructor.
     * @param Finder $finder
     * @param Application $app
     */
    public function __construct(Finder $finder,Application $app)
    {
        $this->finder = $finder;
        $this->config = $app['config'];
        $this->views = $app['view'];
        $this->lang = $app['translator'];
        $this->cache = $app['cache.store'];
        $this->filesystem = $app['files'];
        $this->apps = $app['gkr.apps'];
        $this->app = $app;
        foreach ($this->apps->type('web')->pluck('name') as $name){
            $this->themes[$this->apps->keyName($name)] = [];
        }
    }

    /**
     * 临时设置所有主题的根路径.
     *
     * @param $path
     *
     * @return self
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }
    /**
     * 获取主题根路径.
     *
     * @return mixed
     */
    public function getPath()
    {
        return $this->path ?: $this->config->get('gkr.themes.path');
    }

    /**
     * 获取主题包路径.
     *
     * @param $theme
     * @return null|string
     */
    public function getThemePath($theme)
    {
        return $this->getPath() . "/{$theme}";
    }

    /**
     * 获取一个主题对象.
     *
     * @param string $search
     *
     * @return \Gkr\Themes\Theme|null
     */
    public function find($search)
    {
        foreach ($this->all() as $theme) {
            if ($theme->getLowerName() == strtolower($search)) {
                return $theme;
            }
        }
        return null;
    }

    /**
     * 通过App获取该App挂载的某个主题对象
     *
     * @param null $app 值为null,则获取所有主题对象集合
     * @param null $theme 值为null,则获取该App挂载的所有主题对象集合
     * @return array|Collection|mixed|null
     */
    public function findFromApp($app = null,$theme = null)
    {
        if (!$app){
            return new Collection($this->themes);
        }
        $this->apps->check($app);
        if (!key_exists($this->apps->keyName($app),$this->themes)){
            return [];
        }
        $appThemes = $this->themes[$this->apps->keyName($app)];
        if (!$theme){
            return $appThemes;
        }
        return $this->find($theme) ? $appThemes[strtolower($theme)] : null;
    }

    /**
     * 扫描所有主题,返回主题对象集合
     *
     * @param bool $rescan  是否重新扫描主题
     * @return array
     */
    public function scan($rescan = false)
    {
        $themes = $this->finder->find($this->getPath(),$rescan);
        foreach ($themes as $theme){
            $this->themes[$theme->getApp()][$theme->getLowerName()] = $theme;
        }
        return $themes;
    }

    /**
     * 重新扫描所有主题,返回主题对象集合
     *
     * @return array
     */
    public function rescan()
    {
        return $this->scan(true);
    }

    /**
     * 获取所有主题的对象集合.
     * 根据配置决定是否缓存主题集合
     *
     * @return array
     */
    public function all()
    {
        if ($this->useCache()) {
            return $this->cache->remember($this->getCacheKey(), $this->getCacheLifetime(), function () {
                return $this->scan();
            });
        }
        return $this->scan();
    }

    /**
     * 删除主题以及主题文件包
     * @param $theme
     * @return mixed
     * @throws \Exception
     */
    public function delete($theme)
    {
        if(!$this->exists($theme)){
            throw new \Exception("theme {$theme} not exists");
        }
        $this->filesystem->deleteDirectory($this->find($theme)->getPath(),true);
        $this->filesystem->deleteDirectory($this->find($theme)->getPath());
        return $theme;
    }

    /**
     * 判断主题是否存在.
     *
     * @param $theme
     * @return bool
     */
    public function has($theme)
    {
        return !is_null($this->find($theme));
    }

    /**
     * has方法的别名.
     *
     * @param $theme
     *
     * @return bool
     */
    public function exists($theme)
    {
        return $this->has($theme);
    }

    /**
     * 获取某个App的当前主题
     *
     * @param $app
     * @return mixed|string
     * @throws \Exception
     */
    public function getCurrent($app)
    {
        $appName = $this->apps->check($app);
        if (key_exists($appName,$this->current)){
            return $this->current[$appName];
        }
        $theme = $this->apps->getConfig($app)->get('theme');
        return ($theme) ?:$appName;
    }

    /**
     * 设置某个主题为其挂载的App的默认主题
     *
     * @param $current
     * @param bool $preview 临时激活主题
     * @return $this
     * @throws \Exception
     */
    public function setCurrent($current,$preview = false)
    {
        $appName = $this->find($current)->getApp();
        if (!$preview){
            $this->apps->set($appName,'theme',$current);
        }
        $this->current[$appName] = $current;
        return $this;
    }

    /**
     * 激活一个主题
     * 设置为该主题挂载App的默认主题
     *
     * @param $theme
     * @return $this
     */
    public function active($theme)
    {
        $this->setCurrent($theme);
        return $this;
    }

    /**
     * 反激活主题
     * 设置该主题挂载App的默认主题为空
     *
     * @param $theme
     * @param bool $preview
     * @return $this
     */
    public function unActive($theme,$preview = false)
    {
        $appName = $this->find($theme)->getApp();
        if($this->getCurrent($appName) == $theme){
            $this->current[$appName] = null;
            if (!$preview){
                $this->apps->unset($appName,'theme');
            }
        }
        return $this;
    }

    /**
     * 转换所有主题对象为数组格式.
     *
     * @return array
     */
    public function toArray()
    {
        return array_map(function ($theme) {
            return $theme->toArray();
        }, $this->scan());
    }

    /**
     * 转换所有主题对象为Json格式.
     *
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * 缓存所有主题对象集合.
     */
    public function cache()
    {
        $this->cache->remember($this->getCacheKey(), $this->getCacheLifetime(), function () {
            return $this->scan();
        });
    }

    /**
     * 获取已经缓存的主题对象集合.
     *
     * @return array
     */
    public function getCached()
    {
        return $this->cache->get($this->getCacheKey(), []);
    }

    /**
     * 根据配置判断是否开启缓存.
     *
     * @return bool
     */
    public function useCache()
    {
        return $this->getCacheStatus() == true;
    }

    /**
     * 获取主题配置的缓存名称.
     *
     * @return string
     */
    public function getCacheKey()
    {
        return $this->config->get('gkr.themes.cache.key');
    }

    /**
     * 获取主题信息的缓存时间.
     *
     * @return int
     */
    public function getCacheLifetime()
    {
        return $this->config->get('gkr.themes.cache.lifetime');
    }

    /**
     * 获取是否开启缓存的值.
     *
     * @return bool
     */
    public function getCacheStatus()
    {
        return $this->config->get('gkr.themes.cache.enabled');
    }

    /**
     * 清除主题集合的缓存.
     */
    public function forgetCache()
    {
        $this->cache->forget($this->getCacheKey());
    }

    public function resolveApp()
    {
        return with(new AppResolve($this,$this->views,$this->lang));
    }

    public function resolveTheme()
    {
        return with(new ThemeResolve($this,$this->views,$this->lang));
    }
    public function resolve($name = 'App')
    {
        $name = 'resolve'.Str::studly($name);
        return $this->$name();
    }
    /**
     * 注册所有主题的命名空间.
     */
    public function registerNamespaces()
    {
        foreach ($this->all() as $theme) {
            foreach (array('views', 'lang') as $hint) {
                $this->$hint->addNamespace($theme->getLowerName(), $theme->getPath($hint));
            }
            $theme->boot();
        }
    }
//    public function registerViewLocation($app,$theme = null)
//    {
//        if (is_null($theme)) {
//            $theme = $this->getCurrent($app);
//        }
//        if (!is_null($theme)){
//            $this->views->addLocation($this->getPath() . '/' . $theme . '/views');
//        }
//    }
}
