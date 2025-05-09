<?php
/**
Contains types of Panels.
@category Panel_Factory
@package Histou
@author Philip Griesbacher
@license http://opensource.org/licenses/gpl-license.php GNU Public License
@link https://github.com/ConSol/histou
**/
namespace histou\grafana\graphpanel;

/**
Base Panel.
@category Panel_Factory
@package Histou
@author Philip Griesbacher
@license http://opensource.org/licenses/gpl-license.php GNU Public License
@link https://github.com/ConSol/histou
**/
class GraphPanelFactory
{
    /**
    Returns depending on the Database_Type a graphpanel.
    @returns a graphPanel
    **/
    public static function generatePanel($title, $legendShow = SHOW_LEGEND, $id = -1)
    {
        if (DATABASE_TYPE == INFLUXDB) {
            return new \histou\grafana\graphpanel\GraphPanelInfluxdb($title, $legendShow, $id);
        } elseif (DATABASE_TYPE == VICTORIAMETRICS) {
            return new \histou\grafana\graphpanel\GraphPanelVictoriametrics($title, $legendShow, $id);
        } else {
            throw new \InvalidArgumentException("The given Database is unkown:".DATABASE_TYPE);
        }
    }
}
