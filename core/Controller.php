<?php

abstract class Controller
{
    protected $controller_name;
    protected $action_name;
    protected $application;
    protected $request;
    protected $response;
    protected $session;
    protected $db_manager;
    protected $auth_actions = []; // ログインが必要なアクションを指定するための変数。trueを指定すると全てのアクションがログイン必須として扱われる

    public function __construct($application) {
        // get_class()でオブジェクトのクラス名を取得し、後ろ10文字("Controller")を取り除いて小文字に変換する
        $this->controller_name = strtolower(substr(get_class($this), 0, -10));

        $this->application = $application;
        $this->request     = $application->getRequest();
        $this->response    = $application->getResponse();
        $this->session     = $application->getSession();
        $this->db_manager  = $application->getDbManager();
    }

    public function run($action, $params = []) {
        $this->action_name = $action;

        $action_method = $action.'Action';
        if (!method_exists($this, $action_method)) { // オブジェクト自身に$action_methodが存在するかチェックする
            $this->forward404();
        }

        if ($this->needsAuthentication($action) && !$this->session->isAuthenticated()) {
            throw new UnauthorizedActionException();
        }

        $content = $this->$action_method($params); // アクションの実行
        return $content;
    }

    public function render($variables = [], $template = null, $layout = 'layout') {
        // viewに渡すデフォルト値の設定
        $defaults = [
            'request'  => $this->request,
            'base_url' => $this->request->getBaseUrl(),
            'session'  => $this->session,
        ];

        $view = new View($this->application->getViewDir(), $defaults);

        if (is_null($template)) {
            $template = $this->action_name;
        }

        $path = $this->controller_name.'/'.$template;

        return $view->render($path, $variables, $layout);
    }

    // $auth_actionsプロパティの値を元に、指定のアクションがログイン必須か判定する
    protected function needsAuthentication($action) {
        if ($this->auth_actions === true
            || (is_array($this->auth_actions) && in_array($action, $this->auth_actions))) {
            return true;
        }

        return false;
    }

    protected function forward404() {
        throw new HttpNotFoundException('Forwarded 404 page from '.$this->controller_name.'/'.$this->action_name);
    }

    protected function redirect($url) {
        if (!preg_match('#https?://#', $url)) {
            $protocol = $this->request->isSsl() ? 'https://' : 'http://';
            $host     = $this->request->getHost();
            $base_url = $this->request->getBaseUrl();

            $url = $protocol.$host.$base_url.$url;
        }

        $this->response->setStatusCode(302, 'Found');
        $this->response->setHttpHeader('Location', $url);
    }

    protected function generateCsrfToken($form_name) {
        $key = 'csrf_tokens/'.$form_name;
        $tokens = $this->session->get($key, []);
        // トークンを複数個保持する(複数画面を開いた場合への対応)。最大10個で、それを超える場合はarray_shift()で古いものから削除する
        if (count($tokens) >= 10) {
            array_shift($tokens);
        }

        $token = sha1($form_name.session_id().microtime()); // トークンの生成
        $tokens[] = $token;

        $this->session->set($key, $tokens);

        return $token;
    }

    protected function checkCsrfToken($form_name, $token) {
        $key = 'csrf_tokens/'.$form_name;
        $tokens = $this->session->get($key, []);

        // 指定のトークンが格納されているかを判定。格納されていれば削除する(1度利用したトークンは浮揚のため)
        if (false !== ($pos = array_search($token, $tokens, true))) {
            unset($tokens[$pos]);
            $this->session->set($key, $tokens);

            return true;
        }

        return false;
    }
}
