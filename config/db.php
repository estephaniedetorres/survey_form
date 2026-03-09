<?php

$conn = new mysqli("localhost","root","","survey_form");

if($conn->connect_error){
    die("Connection failed");
}

function getAppBasePath() {
    $scriptName = $_SERVER['SCRIPT_NAME'];
    if (preg_match('#^(.*?)/(?:admin|public|config)/#', $scriptName, $m)) {
        return $m[1];
    }
    return rtrim(dirname($scriptName), '/\\');
}

function getSurveyUrl($token) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $base = getAppBasePath();
    return $protocol . '://' . $host . $base . '/public/survey.php?token=' . $token;
}

function getNetworkSurveyUrl($token) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $lanIp = gethostbyname(gethostname());
    $port = $_SERVER['SERVER_PORT'];
    $portStr = ($port == 80 || $port == 443) ? '' : ':' . $port;
    $base = getAppBasePath();
    return $protocol . '://' . $lanIp . $portStr . $base . '/public/survey.php?token=' . $token;
}

?>