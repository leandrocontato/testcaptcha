<?php

class CaptchaSys
{

    private $img_width = 100;
    private $img_high = 30;
    private $font_size = 15;
    private $qtd_chars = 4;
    private $cword = "";

    function __construct($img_width = 100, $img_high = 30, $font_size = 15, $qtd_chars = 4)
    {
        $this->img_width = $img_width;
        $this->img_high = $img_high;
        $this->font_size = $font_size;
        $this->qtd_chars = $qtd_chars;
    }

    /**
     * Cria o buffer de imagem a ser exibido
     */
    private function doCreateResourceImg()
    {

        $imagem = imagecreate($this->img_width, $this->img_high);

        $_fonts[] = __DIR__ . "/fonts/Alanden_.ttf";
        $_fonts[] = __DIR__ . "/fonts/luggerbu.ttf";
        $_fonts[] = __DIR__ . "/fonts/WAVY.TTF";
        $_fonts[] = __DIR__ . "/fonts/arialbd.ttf";

        $_qtdf = sizeof($_fonts) - 1;

        $backcolor = imagecolorallocate($imagem, rand(230, 255), rand(230, 255), rand(230, 255));

        $word = substr(str_shuffle("AaBbCcDdEeFfGgHhIiJjKkLlMmNnPpQqRrSsTtUuVvYyXxWwZz23456789"), 0, ($this->qtd_chars));

        for ($i = 1; $i <= $this->qtd_chars; $i++) {
            $_pathfont = $_fonts[rand(0, $_qtdf)];
            $font_color = imagecolorallocate($imagem, rand(1, 200), rand(1, 100), rand(1, 200));
            imagettftext($imagem, $this->font_size, rand(-25, 25), (($this->font_size + 4) * $i), ($this->font_size + 7), $font_color, $_pathfont, substr($word, ($i - 1), 1));
        }

        $word = strtoupper($word);
        $word = sha1($word);
        $this->cword = $word;

        if (2 != session_status()) {
            session_set_cookie_params(0);
            session_start();
        }

        $_SESSION[session_id()]["CaptchaSys"]["cword"] = $word;

        imagepng($imagem);
        imagedestroy($imagem);
    }

    /**
     * Cria o buffer de imagem e instrui o servidor com header de imagem
     */
    public static function getImgResource()
    {
        header("Content-type: image/png");
        header("Pragma: no-cache");
        $captcha = new CaptchaSys();
        $captcha->doCreateResourceImg();
    }

    /**
     * Palavra recebida
     * @param string $word
     * @return boolean
     */
    public static function verifyCodeCaptcha($word)
    {

        if (2 == session_status()) {
            // Precisa da sessão iniciada            
            if (isset($_SESSION[session_id()]["CaptchaSys"]) && isset($_SESSION[session_id()]["CaptchaSys"]["cword"])) {

                $word = strtoupper($word);
                $word = sha1($word);

                if ($_SESSION[session_id()]["CaptchaSys"]["cword"] == $word) {
                    unset($_SESSION[session_id()]["CaptchaSys"]);
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Renderiza uma pequena parte HTML de exibição do captcha
     * @param string $url_captcha_resource
     * @param boolean $reload
     * @param string $url_img_reload
     * @return string
     */
    public static function getTagImgCaptcha($url_captcha_resource = 'captcha.php', $reload = false, $url_img_reload = 'reload.png')
    {
        if (2 != session_status()) {
            session_set_cookie_params(0);
            session_start();
        }

        $_SESSION[session_id()]["Captchasys"]["class"] = __file__;

        $html = "<div class=\"captcha\" > <img id=\"cryptogram\" src=\"$url_captcha_resource\"> </div>";

        if ($reload) {
            $_img = "<img src='$url_img_reload'>";
            $html .= "<a title='Recarregar o captcha' style=\"position:relative; cursor:pointer; margin-left: 5px; top: 5px;\" onclick=\"document.getElementById('cryptogram').src='$url_captcha_resource?rand='+Math.floor((Math.random() * 10) + 1);\" >$_img</a>";
        }
        return $html;
    }
}
