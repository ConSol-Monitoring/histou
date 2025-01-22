<?php
/**
This file is used for bootstrapping the env.
PHP version 5
@category bootstrap
@package default
@author Philip Griesbacher
@license http://opensource.org/licenses/gpl-license.php GNU Public License
@link https://github.com/ConSol/histou
**/

require_once 'histou/bootstrap.php';

class MyPHPUnitFrameworkTestCase extends \PHPUnit\Framework\TestCase
{
    public function delCache(): void
    {
        $path = join(DIRECTORY_SEPARATOR, array(sys_get_temp_dir(), '.histou_cache'));
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function tearDown(): void
    {
        $path = join(DIRECTORY_SEPARATOR, array(sys_get_temp_dir(), 'histou_test'));
        if (file_exists($path)) {
            if (PHP_OS === 'Windows' || PHP_OS === 'WINNT') {
                exec("rd /s /q {$path}");
            } else {
                exec("rm -rf {$path}");
            }
        }
        $this->delCache();
    }
}
