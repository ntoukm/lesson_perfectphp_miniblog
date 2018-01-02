<?php

class Router
{
    protected $routes;

    public function __construct($definitions) {
        $this->routes = $this->compileRoutes($definitions);
    }

    public function compileRoutes($definitions) {
        $routes = array();

        foreach ($definitions as $url => $params) {
            $tokens = explode('/', ltrim($url, '/')); // URLをスラッシュごとに分割
            foreach ($tokens as $i => $token) {
                if (0 === strpos($token, ':')) { // 分割した値の中にコロンで始まる文字列があるか
                    $name = substr($token, 1);
                    $token = '(?P<'.$name.'>[^/]+)';
                }
                $token[$i] = $token;
            }

            $pattern = '/'.implode('/', $tokens); // 分割したURLを再度連結する
            $routes[$pattern] = $params;
        }

        return $routes;
    }

    public function resolve($path_info) {
        if ('/' !== substr($path_info, 0, 1)) { // 先頭がスラッシュでない場合はスラッシュを付与する
            $path_info = '/'.$path_info;
        }

        foreach ($this->routes as $pattern => $params) {
            if (preg_match('#^'.$pattern.'$#', $path_info, $matches)) {
                $params = array_merge($params, $matches);

                return $params;
            }
        }

        return false;
    }
}
