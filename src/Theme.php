<?php

namespace Gkr\Themes;

use Illuminate\Contracts\Support\Arrayable;
use Gkr\Apps\Factory\Repository as Apps;

/**
 * 主题对象类,包括主题属性,主题动态配置信息
 * @package Gkr\Themes
 */
class Theme implements Arrayable
{
    /**
     * 主题名称.
     *
     * @var string
     */
    protected $name;

    protected $app;

    /**
     * 主题描述.
     *
     * @var string
     */
    protected $description;

    /**
     * 作者信息.
     *
     * @var array
     */
    protected $author = [];

    /**
     * 主题状态. 启用 (true) 或者 禁用 (false).
     *
     * @var bool
     */
    protected $enabled = true;

    /**
     * 主题路径.
     *
     * @var string
     */
    protected $path;

    /**
     * 获取主题初始化PHP文件.
     *
     * @var array
     */
    protected $files = [];

    protected $apps;
    /**
     * 主题构造函数,初始化主题属性设置.
     * @param array $attributes
     * @param Apps $apps
     */
    public function __construct(array $attributes = array(),Apps $apps)
    {
        $this->setAttributes($attributes);
        $this->apps = $apps;
    }

    /**
     * 自动设置属性.
     *
     * @param array $attributes
     */
    public function setAttributes(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * 获取主题路径.
     *
     * @param string $hint
     *
     * @return string
     */
    public function getPath($hint = null)
    {
        if (!is_null($hint)) {
            return $this->path.'/'.$hint;
        }

        return $this->path;
    }

    /**
     * 获取主题名称.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 获取主题所属App别名
     * @return string
     */
    public function getApp()
    {
        $this->apps->check($this->app,"theme {$this->getName()}'s app not config or not exists!");
        return $this->apps->keyName($this->app);
    }

    /**
     * 获取主题所属App配置
     * @return \Illuminate\Support\Collection
     */
    public function getAppConfig()
    {
        return $this->apps->getConfig($this->getApp());
    }

    /**
     * 获取主题所属App名称
     * @return mixed
     */
    public function getAppName()
    {
        return $this->getAppConfig()->get('name');
    }

    /**
     * 获取主题名称的小写.
     *
     * @return string
     */
    public function getLowerName()
    {
        return strtolower($this->name);
    }

    /**
     * 获取主题描述.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * 获取主题作者.
     *
     * @return array
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * 判断主题是否被禁用.
     *
     * @return bool
     */
    public function disabled()
    {
        return !$this->enabled();
    }

    /**
     * 判断主题是否被启用.
     *
     * @return bool
     */
    public function enabled()
    {
        return $this->enabled == true;
    }

    /**
     * 判断主题是否为某个App的默认主题
     *
     * @param $app
     * @return bool
     */
    public function isActive($app = null)
    {
        if (!$app){
            $app = $this->getApp();
        }
        return ThemeFacade::getCurrent($app) == $this->name;
    }

    /**
     * 获取主题作者信息的某个字段.
     *
     * @param string $type
     * @param null   $default
     *
     * @return string|null
     */
    public function getAuthorInfo($type, $default = null)
    {
        return array_get($this->author, $type, $default);
    }

    /**
     * 获取作者名称.
     *
     * @param null $default
     *
     * @return string|null
     */
    public function getAuthorName($default = null)
    {
        return $this->getAuthorInfo('name', $default);
    }

    /**
     * 获取作者邮件.
     *
     * @param null $default
     *
     * @return string|null
     */
    public function getAuthorEmail($default = null)
    {
        return $this->getAuthorInfo('email', $default);
    }

    /**
     * 获取主题的某个配置.
     *
     * @param $key
     * @param null $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (property_exists($this, $key)) {
            return $this->{$key};
        }

        return $default;
    }

    /**
     * 获取主题初始化PHP文件.
     *
     * @return array
     */
    public function getFiles()
    {
        return $this->get('files', []);
    }

    /**
     * 加载主题初始化PHP文件.
     *
     * @return void
     */
    public function boot()
    {
        foreach ($this->getFiles() as $filename) {
            $path = $this->path . '/' . $filename;
            
            if (file_exists($path)) {
                require $path;
            };
        }
    }

    /**
     * 处理__get魔术方法调用.
     *
     * @param $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * 处理__set魔术方法调用.
     *
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        if (property_exists($this, $key)) {
            $this->{$key} = $value;
        }
    }

    /**
     * 把主题对象转变成数组配置.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'author' => $this->author,
            'enabled' => $this->enabled,
            'path' => $this->path,
            'files' => $this->files,
            'app' => $this->app
        ];
    }

    /**
     * 获取主题的动态配置.
     *
     * @param  string $key
     * @param  string|null $default
     * @return string|null
     */
    public function config($key, $default = null)
    {
        $parts = explode('.', $key);

        $filename = head($parts);

        $parts = array_slice($parts, 0);

        $path = $this->path . "/config/config.php";

        if (! file_exists($path)) {
            $path = $this->path . "/config/{$filename}.php";
        }

        $key = implode('.', $parts);

        $config = file_exists($path) ? require $path : [];

        return array_get($config, $key, $default);
    }
}
