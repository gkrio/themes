
<?php
if (!function_exists('theme')) {
    /**
     * Return a specified view from current theme.
     *
     * @param string|null $view
     * @param array $data
     * @param array $mergeData
     *
     * @return \Illuminate\View\View
     */
    function theme($view = null, array $data = array(), array $mergeData = array())
    {
        $theme = app('gkr.themes');
        if (is_null($view)) {
            return $theme;
        }
        if (app('view')->exists($view)){
            return app('view')->make($view, $data, $mergeData);
        }
        if (!str_contains($view,"::")){
            throw new InvalidArgumentException("View {$view} not found.");
        }
        list($space,$file) = explode("::",$view);
        return $theme->resolve('app')->view($space,$file, $data, $mergeData);
    }
}