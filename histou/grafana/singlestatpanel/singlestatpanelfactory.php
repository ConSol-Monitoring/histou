<?php
/**
Contains types of Panels.
@category Panel_Factory
@package Histou
@author Philip Griesbacher
@license http://opensource.org/licenses/gpl-license.php GNU Public License
@link https://github.com/ConSol/histou
**/
namespace histou\grafana\singlestatpanel;

/**
Base Panel.
@category Panel_Factory
@package Histou
@author Philip Griesbacher
@license http://opensource.org/licenses/gpl-license.php GNU Public License
@link https://github.com/ConSol/histou
**/
class SinglestatPanelFactory
{
    /**
    Returns depending on the Database_Type a singlestatPanel.
    @returns a singlestatPanel
    **/
    public static function generatePanel($title, $id = -1)
    {
        if (DATABASE_TYPE == INFLUXDB) {
            return new \histou\grafana\singlestatpanel\SinglestatPanelInfluxdb($title, $id);
        } else {
            throw new \InvalidArgumentException("The given Database is unkown:".DATABASE_TYPE);
        }
    }
}
