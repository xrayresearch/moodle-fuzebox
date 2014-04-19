<?php

function _check_required_config_keys($config) {
    $_required_keys = array('fuze.url');
    foreach ($_required_keys as $key) {
        if (!array_key_exists($key, $config)) {
            return false;
        }
    }
    return true;
}

function load_config($filePathName) {
    if (!is_file($filePathName)) {
        return false;
    }
    $config = parse_ini_file('./fuze.ini');
    if (!is_array($config)) {
        return false;
    }
    if (!_check_required_config_keys($config)) {
        return false;
    }
    return $config;
}

function init_session() {
    session_start();
    if (!isset($_SESSION['has_partner'])) {
        $_SESSION['has_partner'] = false;
    }
    if (!isset($_SESSION['has_user'])) {
        $_SESSION['has_user'] = false;
    }
}

function getFuzeClient($url) {
    if (!$_SESSION['has_partner']) {
        throw new Fuze_Exception("Partner key pair unavailable");
    }
    $partnerKey = $_SESSION['partner_key'];
    $encryptionKey = $_SESSION['encryption_key'];
    $client = new Fuze_Client($url, $partnerKey, $encryptionKey);
    if ($_SESSION['has_user']) {
        $client->setUserSession($_SESSION['user_token']);
    }
    return $client;
}

function _checkboxValue($name) {
    if (isset($_POST[$name]) && !empty($_POST[$name])) {
        return true;
    }
    return false;
}

function _getPostParam($key, $default = null) {
    if (!array_key_exists($key, $_POST)) {
        return $default;
    }
    return $_POST[$key];
}

