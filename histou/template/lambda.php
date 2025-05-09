<?php
/**
Contains types of Templates.
@category Template_Class
@package histou
@author Philip Griesbacher
@license http://opensource.org/licenses/gpl-license.php GNU Public License
@link https://github.com/ConSol/histou
**/

namespace histou\template;

trait Lambda
{
    /**
    Enables Lambdas.
    @param string $name name.
    @param string $args args.
    @return object.
    **/
    public function __call($name, $args)
    {
        return call_user_func_array($this->$name, $args);
    }
}
