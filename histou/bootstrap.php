<?php
/**
This file is used for bootstrapping the env.
@category bootstrap
@package default
@author Philip Griesbacher
@license http://opensource.org/licenses/gpl-license.php GNU Public License
@link https://github.com/ConSol/histou
**/

spl_autoload_register(function ($className) {
    $file = strtolower(str_replace('\\', DIRECTORY_SEPARATOR, $className)).'.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
