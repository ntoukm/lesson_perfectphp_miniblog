<?php

class View
{
    protected $base_dir;
    protected $defaults;
    protected $layout_variables = [];

    public function __construct($base_dir, $defaults = []) {
        $this->base_dir = $base_dir;
        $this->defaults = $defaults;
    }

    public function setLayoutVar($name, $value) {
        $this->layout_variables[$name] = $value; // レイアウトファイルの読み込みを行う際に渡される変数を設定する
    }

    public function render($_path, $_variables = [], $_layout = false) {
        $_file = $this->base_dir.'/'.$_path.'.php'; // 変数展開時に変数名の衝突を避けるためアンダースコアを付けている

        extract(array_merge($this->defaults, $_variables)); // 連想配列のキーを変数名、値を変数の値として展開する

        // 出力情報のバッファリング(アウトプットバッファリング)を開始する
        // バッファリング中にechoで出力された文字列は画面には直接表示されず、内部のバッファに溜め込まれる
        // バッファに格納された文字列はob_get_clean()関数等で取得可能
        ob_start();
        // バッファの自動フラッシュを制御する(0:自動フラッシュ無効)。有効だとバッファ容量を超えた際などにバッファの内容が自動的に出力される
        ob_implicit_flush(0);

        require $_file;

        $content = ob_get_clean(); // バッファ内容の取得($contentに格納)と同時にバッファをクリアする

        if ($_layout) {
            $content = $this->render($_layout, array_merge($this->layout_variables, ['_content' => $content, ]));
        }

        return $content;
    }

    public function escape($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}
