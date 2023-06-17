<?php

// if(2!=session_status()){
//     session_set_cookie_params(0);
//     session_start();
// }

include_once 'CaptchaSys.php';
$msg = '';

if (isset($_POST["wordcaptcha"])) {
    $word = $_POST["wordcaptcha"];
    if (!CaptchaSys::verifyCodeCaptcha($word)) {
        $msg = '- Código inválido';
    } else {
        $msg = '- Sucesso';
    }
} else {
    if (isset($_GET["img"])) {
        CaptchaSys::getImgResource();
        exit;
    }
}
?>
<html>
<header>
    <title>Captcha Sys</title>
</header>

<body>
    <img src="index.php?img">
    <form action="" name="form" method="post">
        <input type="text" name="wordcaptcha" />
        <input type="submit" value="Validar Captcha" />
    </form>
    <hr>
    <strong><?php echo $msg; ?></strong>
</body>

</html>