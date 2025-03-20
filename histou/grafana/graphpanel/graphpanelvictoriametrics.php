<?php
/**
Contains types of Panels.
@category Panel_Class
@package Histou
@author Philip Griesbacher
@license http://opensource.org/licenses/gpl-license.php GNU Public License
@link https://github.com/ConSol/histou
**/
namespace histou\grafana\graphpanel;

/* label_replace({__name__=~"metrics_(value|crit)"}, "__tmp_alias", "$1", "__name__", "metrics_(.*)") */
/*
   {
      "datasource": "victoria",
      "refId": "C",
      "expr": "{fooo=\"bar\"}",
      "legendFormat": "asdf"
    }
*/

class Target extends \ArrayObject implements \JsonSerializable
{
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $r = array(
            'datasource' => $this['datasource'],
            'legendFormat' => $this['legendFormat'],
            'expr' => $this->getExpr()
        );
        return $r;
    }

    private function getExpr()
    {
        $expr =  'last_over_time({__name__=~"' . $this['measurement'] . "_(" . $this->getSelect() . ')",' . $this->getFilter() . '}[15m])';
        return 'label_replace(' . $expr . ', "__tmp_alias", "$1", "__name__", "metrics_(.*)")';
    }

    private function getSelect()
    {
        return join("|", array_map(function ($x) {
            return $x[0];
        }, $this['select']));
    }

    private function getFilter()
    {
        $filter = array();
        foreach ($this['tags'] as $key => $val) {
            if ($val === null) {
                continue;
            }
            $operator = (array_key_exists('operator', $val) ? $val['operator'] : '=');
            array_push($filter, $key . $operator . '"' . $val['value'] . '"');
        }
        return join(",", $filter);
    }
}

/**
Base Panel.
@category Panel_Class
@package Histou
@author Philip Griesbacher
@license http://opensource.org/licenses/gpl-license.php GNU Public License
@link https://github.com/ConSol/histou
**/
class GraphPanelVictoriametrics extends GraphPanel
{
    /**
    Constructor.
    @param string  $title      name of the panel.
    @param boolean $legendShow hide the legend or not
    @return object.
    **/
    public function __construct($title, $legendShow = SHOW_LEGEND, $id = -1)
    {
        parent::__construct($title, $legendShow, $id);
    }

    public function createTarget(array $filterTags = array(), $datasource = VICTORIAMETRICS_DS)
    {
        return new Target(array(
                    'measurement' => 'metrics',
                    'legendFormat' => '{{performanceLabel}}-{{__tmp_alias}}',
                    'select' => array(),
                    'tags' => $filterTags,
                    'dsType' => 'prometheus',
                    'resultFormat' => 'time_series',
                    'datasource' => $datasource,
                    ));
    }

    /**
    This creates a target with an value.
    **/
    public function genTargetSimple($host, $service, $command, $performanceLabel, $color = '#085DFF', $alias = '', $useRegex = false, $perfData = null)
    {
        return $this->genTarget($host, $service, $command, $performanceLabel, $color, $alias, $useRegex);
    }

    /**
    This creates a target with an value.
    **/
    public function genTarget($host, $service, $command, $performanceLabel, $color = 'blue', $alias = '', $useRegex = false, $customSelect = null)
    {
        if ($alias == '') {
            $alias = $performanceLabel;
        }
        if ($useRegex) {
            $target = $this->createTarget(array(
                                            'host' => array('value' => "^".$host."$", 'operator' => '=~'),
                                            'service' => array('value' => "^".$service."$", 'operator' => '=~'),
                                            'command' => $command == null ? null : array('value' => "^".$command."$", 'operator' => '=~'),
                                            'performanceLabel' => array('value' => "^".$performanceLabel."$", 'operator' => '=~')
                                            ));
        } else {
            $target = $this->createTarget(array(
                                            'host' => array('value' => $host),
                                            'service' => array('value' => $service),
                                            'command' => $command == null ? null : array('value' => $command),
                                            'performanceLabel' => array('value' => $performanceLabel)
                                            ));
        }
        return $this->addXToTarget($target, array('value'), $alias, $color, false, $customSelect, 1);
    }

    public function addWarnToTarget($target, $alias = '', $color = true, $keepAlias = false)
    {
        if ($color) {
            return $this->addXToTarget($target, array('warn', 'warn-min', 'warn-max'), $alias, 'light-yellow', $keepAlias, null, 2);
        }
        return $this->addXToTarget($target, array('warn', 'warn-min', 'warn-max'), $alias, '', $keepAlias, null, 2);
    }

    public function addCritToTarget($target, $alias = '', $color = true, $keepAlias = false)
    {
        if ($color) {
            return $this->addXToTarget($target, array('crit', 'crit-min', 'crit-max'), $alias, 'light-red', $keepAlias, null, 2);
        }
        return $this->addXToTarget($target, array('crit', 'crit-min', 'crit-max'), $alias, '', $keepAlias, null, 2);
    }

    public function addXToTarget($target, array $types, $alias, $color, $keepAlias = false, $createSelect = null, $lineWidth = 1)
    {
        foreach ($types as $type) {
            if ($keepAlias) {
                $newalias = $alias;
            } else {
                $newalias = $alias.'-'.$type;
            }
            array_push($target['select'], array($type, $newalias));
            if ($color != '') {
                $this->addAliasColor($newalias, $color, $lineWidth);
            }
        }
        return $target;
    }

    public static function createCounterSelect($name, $alias)
    {
        return array(
                    array('type' => 'field', 'params' => array($name)),
                    array('type' => 'mean', 'params' => array()),
                    array('type' => 'non_negative_derivative', 'params' => array("1s")),
                    array('type' => 'alias', 'params' => array($alias))
                    );
    }

    public static function createSelect($name, $alias)
    {
        return array($name, $alias);
        return array(
                    array('type' => 'field', 'params' => array($name)),
                    array('type' => 'mean', 'params' => array()),
                    array('type' => 'alias', 'params' => array($alias))
                    );
    }

    /**
    This creates a target for an downtime.
    **/
    public function genDowntimeTarget($host, $service, $command, $performanceLabel, $alias = '', $useRegex = false, $customSelect = null)
    {
        if ($alias == '') {
            $alias = 'downtime';
        }
        if ($useRegex) {
            $target = $this->createTarget(
                array(
                        'host' => array('value' => \histou\helper\str::genRegex($host), 'operator' => '=~'),
                        'service' => array('value' => \histou\helper\str::genRegex($service), 'operator' => '=~'),
                        'command' => $command == null ? null : array('value' => \histou\helper\str::genRegex($command), 'operator' => '=~'),
                        'performanceLabel' => array('value' => \histou\helper\str::genRegex($performanceLabel), 'operator' => '=~'),
                        'downtime' => array('value' => "true")
                    )
            );
        } else {
            $target = $this->createTarget(
                array(
                        'host' => array('value' => $host),
                        'service' => array('value' => $service),
                        'command' => $command == null ? null : array('value' => $command),
                        'performanceLabel' => array('value' => $performanceLabel),
                        'downtime' => array('value' => "true")
                    )
            );
        }
        $target = $this->addXToTarget($target, array('value'), $alias, '#EEE', true, $customSelect);
        $this->addToSeriesOverrides(
            array(
                'matcher' => array(
                    'id'      => 'byName',
                    'options' => $alias
                ),
                'properties' => array(
                    array(
                        'id'    => 'custom.drawStyle',
                        'value' => 'line'
                    ),
                    array(
                        'id'    => 'custom.lineWidth',
                        'value' => 3
                    ),
                    array(
                        'id'    => 'custom.fillOpacity',
                        'value' => 30
                    ),
                    array(
                        'id'    => 'custom.hideFrom',
                        'value' => array(
                            "legend"  => true,
                            "tooltip" => true,
                            "viz"     => false
                        )
                    )
                )
            )
        );
        return $target;
    }

    /**
    This creates a target for an forecast.
    @return Returns a target if a forcast config exists, null otherwise.
    **/
    public function genForecastTarget($host, $service, $command, $performanceLabel, $color = '#000', $alias = '', $useRegex = false, $addMethodToName = false)
    {
        $forecastConfig = \histou\template\ForecastTemplate::$config;
        if (!$forecastConfig || !array_key_exists($performanceLabel, $forecastConfig)) {
            return null;
        }
        array_push(\histou\grafana\dashboard\Dashboard::$forecast, $forecastConfig[$performanceLabel]['forecast']);
        if ($alias == '') {
            $alias = $performanceLabel.'-forecast';
        }
        if ($addMethodToName) {
            $alias .= '-'.$forecastConfig[$performanceLabel]['method'];
        }
        if ($useRegex) {
            $target = $this->createTarget(
                array(
                        'host' => array('value' => \histou\helper\str::genRegex($host), 'operator' => '=~'),
                        'service' => array('value' => \histou\helper\str::genRegex($service), 'operator' => '=~'),
                        //'command' => array('value' => \histou\helper\str::genRegex($command), 'operator' => '=~'),
                        'performanceLabel' => array('value' => \histou\helper\str::genRegex($performanceLabel), 'operator' => '=~'),
                    ),
                FORECAST_DATASOURCE_NAME
            );
        } else {
            $target = $this->createTarget(
                array(
                        'host' => array('value' => $host),
                        'service' => array('value' => $service),
                        //'command' => array('value' => $command),
                        'performanceLabel' => array('value' => $performanceLabel),
                    ),
                FORECAST_DATASOURCE_NAME
            );
        }
        $target = $this->addXToTarget($target, array('value'), $alias, $color, true);
        $this->addToSeriesOverrides(
            array(
                'matcher' => array(
                    'id'      => 'byName',
                    'options' => $alias
                ),
                'properties' => array(
                    array(
                        'id'    => 'custom.drawStyle',
                        'value' => 'points'
                    ),
                    array(
                        'id'    => 'custom.pointSize',
                        'value' => '1'
                    ),
                    array(
                        'id'    => 'custom.hideFrom',
                        'value' => array(
                            "legend"  => true,
                            "tooltip" => true,
                            "viz"     => false
                        )
                    )
                )
            )
        );
        return $target;
    }
}
