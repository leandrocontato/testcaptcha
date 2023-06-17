<?php
if(2 != session_status()){
    session_start();
}
require_once $_SESSION[ session_id() ]["Captchasys"]["class"];
CaptchaSys::getImgResource();
