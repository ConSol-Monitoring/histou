<?php
/**
This file is used for bootstrapping the env.
@category bootstrap
@package default
@author Philip Griesbacher
@license http://opensource.org/licenses/gpl-license.php GNU Public License
@link https://github.com/ConSol/histou
**/

function getPath()
{
    return substr(realpath(dirname(__FILE__)), 0, -6);
}

spl_autoload_register('histou_autoload');

function histou_autoload($className)
{
    $file = strtolower(str_replace('\\', DIRECTORY_SEPARATOR, $className)).'.php';
    if (file_exists($file)) {
        require_once $file;
    }
}
