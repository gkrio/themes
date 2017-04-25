<?php
namespace Gkr\Themes\Factory\Resolves;


use Gkr\Themes\Factory\Repository;
use Illuminate\Support\Str;
use Illuminate\Translation\Translator;
use Illuminate\View\Factory;

class AppResolve
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
        $app = null;
        $config = 'name';
        if (Str::contains($key, '::')) {
            list($app, $config) = explode('::', $key);
        }
        $theme = $this->repo->find($this->repo->getCurrent($app));
        return $theme ? $theme->config($config) : $default;
    }

    /**
     * 获取一个App默认主题的某个文件的命名空间
     *
     * @param $app
     * @param $key
     * @return string
     */
    protected function getThemeNamespace($app,$key)
    {
        return $this->repo->getCurrent($app) . "::{$key}";
    }

    /**
     * getThemeNamespace方法的简写
     *
     * @param $app
     * @param $key
     *
     * @return string
     */
    public function getNamespace($app,$key)
    {
        return $this->getThemeNamespace($app,$key);
    }

    /**
     * 翻译一个App默认主题的语言文件
     *
     * @param $app
     * @param $key
     * @param array $replace 需要翻译的字符
     * @param null $locale 指定语言
     *
     * @return array|null|string
     */
    public function lang($app,$key, $replace = array(), $locale = null)
    {
        return $this->lang->get($this->getThemeNamespace($app,$key), $replace, $locale);
    }

    /**
     * 解析一个App的默认主题的视图文件
     *
     * @param $app
     * @param $view
     * @param array $data
     * @param array $mergeData
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function view($app,$view, $data = array(), $mergeData = array())
    {
        return $this->views->make($this->getThemeNamespace($app,$view), $data, $mergeData);
    }

    /**
     * 解析一个App默认主题的Blade模板引擎的Composer
     *
     * @param $app
     * @param $views
     * @param $callback
     */
    public function composer($app,$views, $callback)
    {
        $theViews = [];

        foreach ((array)$views as $view) {
            $theViews[] = $this->getThemeNamespace($app,$view);
        }

        $this->views->composer($theViews, $callback);
    }

    /**
     * 加载一个App默认主题的资源文件
     *
     * @param $app
     * @param $asset
     * @param null $secure
     *
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public function asset($app,$asset, $secure = null)
    {
        return url("{$this->repo->getCurrent($app)}/{$asset}", null, $secure);
    }

    /**
     * @param $app
     * @param $asset
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public function secure_asset($app,$asset)
    {
        return $this->asset($app,$asset, true);
    }
}