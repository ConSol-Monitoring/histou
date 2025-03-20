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

/**
Inheritate from Template for simple Templatefiles
@category Template_Class
@package histou
@author Philip Griesbacher
@license http://opensource.org/licenses/gpl-license.php GNU Public License
@link https://github.com/ConSol/histou
**/
class SimpleTemplate extends Template
{
    /**
    Expects a filename to the simple config.
    @param string $file Path to file.
    @return object.
    **/
    public function __construct($file)
    {
        $result = parser::parseSimpleTemplate($file);
        parent::__construct($file, $result[0], $result[1]);
    }
}
