<?php
namespace Gkr\Themes\Factory\Resolves;


use Gkr\Themes\Factory\Repository;
use Illuminate\Support\Str;
use Illuminate\Translation\Translator;
use Illuminate\View\Factory;

class ThemeResolve
{
    protected $repo;
    protected $views;
    protected $lang;
    public function __construct(Repository $repo,Factory $views,Translator $lang)
    {
        $this->repo = $repo;
        $this->views = $views;
        $this->lang = $lang;
    }

    /**
     * 获取一个主题的动态配置.
     * 使用"主题名称::配置"的方法获取配置,例如:"admintle::name"
     *
     * @param $key
     * @param null $default
     *
     * @return null|string
     */
    public function config($key, $default = null)
    {
        $name = null;
        $config = 'name';
        if (Str::contains($key, '::')) {
            list($name, $config) = explode('::', $key);
        }
        $theme = $this->repo->find($name);
        return $theme ? $theme->config($config) : $default;
    }

    /**
     * 获取一个App默认主题的某个文件的命名空间
     *
     * @param $theme
     * @param $key
     *
     * @return string
     */
    protected function getThemeNamespace($theme,$key)
    {
        return "{$theme}::{$key}";
    }

    /**
     * getThemeNamespace方法的简写
     *
     * @param $theme
     * @param $key
     *
     * @return string
     */
    public function getNamespace($theme,$key)
    {
        return $this->getThemeNamespace($theme,$key);
    }

    /**
     * 翻译一个App默认主题的语言文件
     *
     * @param $theme
     * @param $key
     * @param array $replace 需要翻译的字符
     * @param null $locale 指定语言
     *
     * @return array|null|string
     */
    public function lang($theme,$key, $replace = array(), $locale = null)
    {
        return $this->lang->get($this->getThemeNamespace($theme,$key), $replace, $locale);
    }

    /**
     * 解析一个App的默认主题的视图文件
     *
     * @param $theme
     * @param $view
     * @param array $data
     * @param array $mergeData
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function view($theme,$view, $data = array(), $mergeData = array())
    {
        return $this->views->make($this->getThemeNamespace($theme,$view), $data, $mergeData);
    }

    /**
     * 解析一个App默认主题的Blade模板引擎的Composer
     *
     * @param $theme
     * @param $views
     * @param $callback
     */
    public function composer($theme,$views, $callback)
    {
        $theViews = [];

        foreach ((array)$views as $view) {
            $theViews[] = $this->getThemeNamespace($theme,$view);
        }

        $this->views->composer($theViews, $callback);
    }

    /**
     * 加载一个App默认主题的资源文件
     *
     * @param $theme
     * @param $asset
     * @param null $secure
     *
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public function asset($theme,$asset, $secure = null)
    {
        return url("{$theme}/{$asset}", null, $secure);
    }

    /**
     * @param $theme
     * @param $asset
     *
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public function secure_asset($theme,$asset)
    {
        return $this->asset($theme,$asset, true);
    }
}