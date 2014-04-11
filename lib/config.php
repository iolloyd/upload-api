<?php

function config($configFile) {
    $info = parse_ini_file($configFile, true);

    return function($key) use ($info) {
        if (isset($info[$key])) {
            return $info[$key];
        }
        return "";
    };
}

