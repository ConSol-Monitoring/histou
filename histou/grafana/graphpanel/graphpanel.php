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

/**
Base Panel.
@category Panel_Class
@package Histou
@author Philip Griesbacher
@license http://opensource.org/licenses/gpl-license.php GNU Public License
@link https://github.com/ConSol/histou
**/
abstract class GraphPanel extends \histou\grafana\Panel
{
    /**
    Constructor.
    @param string  $title      name of the panel.
    @param boolean $legendShow hide the legend or not
    @return object.
    **/
    public function __construct($title, $legendShow = SHOW_LEGEND, $id = -1)
    {
        parent::__construct($title, 'timeseries', $id);
        $this->data['targets'] = array();
        $this->data['datasource'] = "-- Mixed --";
        $this->data['fieldConfig'] = array(
            "defaults" => array(
                "custom" => array(
                    "lineInterpolation" => "linear",
                    "insertNulls" => false,
                    "spanNulls" => \histou\Basic::$spanNulls, // do not connect outages more than x minutes
                )
            ),
            "overrides" => array(
                array(
                    "matcher" => array(
                        "id" => "byValue",
                        "options" => array(
                            "op"      => "gte",
                            "reducer" => "allIsNull",
                            "value"   => 0
                        )
                    ),
                    "properties" => array(
                        array(
                            "id" => "custom.hideFrom",
                            "value" => array(
                                "legend"  => true,
                                "tooltip" => true,
                                "viz"     => false
                            )
                        )
                    )
                )
            )
        );
        $this->data['options'] = array(
            "tooltip" => array(
                "mode" => "multi"
            )
        );
        if (!$legendShow) {
            $this->data['options']['legend'] = array('showLegend' => false);
        }
    }

    /**
    Adds an array to the overrides field and checks for leading slashes.
    overrides look like this:
    {
        "matcher": {
            "id": "byName",
            "options": "total-value"
        },
        "properties": [
            {
                "id": "color",
                "value": {
                    "fixedColor": "blue",
                    "mode": "fixed"
                }
            }
        ]
    }
    **/
    public function addToSeriesOverrides(array $data)
    {
        if ($data['matcher']['id'] == 'byName' && \histou\helper\str::isRegex($data['matcher']['options'])) {
            $data['matcher']['options'] =  \histou\helper\str::makeRegex($data['matcher']['options']);
            $data['matcher']['id'] = 'byRegexp';
        }
        if (!isset($this->data['fieldConfig'])) {
            $this->data['fieldConfig'] = array();
        }
        if (!isset($this->data['fieldConfig']['overrides'])) {
            $this->data['fieldConfig']['overrides'] = array();
        }
        array_push($this->data['fieldConfig']['overrides'], $data);
    }

    /**
    Changes the color of a line.
    @param string $alias linename.
    @param string $color hexcolor.
    @return null.
    **/
    public function addAliasColor($alias, $color, $lineWidth = 1)
    {
        $this->addToSeriesOverrides(
            array(
                'matcher' => array(
                    'id'      => 'byName',
                    'options' => $alias
                ),
                'properties' => array(
                    array(
                        'id'    => 'color',
                        'value' => array(
                            'fixedColor' => $color,
                            'mode'       => 'fixed'
                        )
                    ),
                    array(
                        'id'    => 'custom.lineWidth',
                        'value' => $lineWidth
                    )
                )
            )
        );
    }

    /**
    Changes the color of a line by regex.
    @param string $regex linename.
    @param string $color hexcolor.
    @return null.
    **/
    public function addRegexColor($regex, $color, $fill = 0)
    {
        $override = array(
            'matcher' => array(
                'id'      => 'byRegexp',
                'options' => $regex
            ),
            'properties' => array(
                array(
                    'id'    => 'color',
                    'value' => array(
                        'fixedColor' => $color,
                        'mode'       => 'fixed'
                    )
                )
            )
        );

        if ($fill > 0) {
            array_push(
                $override['properties'],
                array(
                        'id'    => 'custom.fillOpacity',
                        'value' => $fill*10
                    )
            );
        }

        $this->addToSeriesOverrides($override);
    }
    /**
    Setter for leftYAxisLabel
    @param string $label label.
    @return null.
    **/
    public function setLeftYAxisLabel($label)
    {
        $this->data['fieldConfig']['defaults']['custom']['axisLabel'] = $label;
    }

    /**
    Setter for leftYAxis min max
    @param float $min min, use Null to skipp.
    @param float $max max, use Null to skipp.
    @return null.
    **/
    public function setLeftYAxisMinMax($min, $max = null)
    {
        if ($min !== null) {
            $this->data['fieldConfig']['defaults']['min'] = $min;
        }
        if ($max !== null) {
            $this->data['fieldConfig']['defaults']['max'] = $max;
        }
    }

    /**
    Tries to convert the given unit in a "grafana unit" if not possible the leftYAxisLabel will be set.
    @param string $unit unit.
    @return null.
    **/
    public function setLeftUnit($unit)
    {
        $gUnit = $this->convertUnit($unit);
        $this->data['fieldConfig']['defaults']['unit'] = $gUnit;
        if ($gUnit == 'short') {
            $this->setLeftYAxisLabel($unit);
        }
    }

    /**
    Try to convert the given unit in a grafana unit.
    @param string $label unit.
    @return string if found a grafanaunit.
    **/
    private function convertUnit($unit)
    {
        switch ($unit) {
            //Time
            case 'ns':
            case 'µs':
            case 'ms':
            case 's':
            case 'm':
            case 'h':
            case 'd':
                return $unit;
            //Data
            case 'b':
                return 'bits';
                break;
            case 'B':
                return 'bytes';
            case 'KB':
            case 'KiB':
            case 'kiB':
            case 'kB':
                return 'kbytes';
            case 'MB':
            case 'MiB':
            case 'miB':
            case 'mB':
                return 'mbytes';
            case 'GB':
            case 'GiB':
            case 'giB':
            case 'gB':
                return 'gbytes';
            case 'Bps':
            case 'BPS':
            case 'BpS':
                return 'Bps'; # bytes/sec(SI)
            case 'binBps':
                return 'binBps'; # bytes/sec(IEC)
            case 'bps':
            case 'binbps':
                return $unit;
            case '%':
            case 'percent':
            case 'pct':
            case 'pct.':
            case 'pc':
                return 'percent';
            default:
                return 'short';
        }
    }

    /**
    Setter for Linewidth
    @param int $width Linewidth.
    @return null.
    **/
    public function setLinewidth($width)
    {
        $this->data['linewidth'] = $width;
    }

    /**
    Fills the area below a line.
    @param string $alias     name of the query.
    @param int    $intensity intensity of the color.
    @return null.
    **/
    public function fillBelowLine($alias, $intensity)
    {
        $this->addToSeriesOverrides(
            array(
                'matcher' => array(
                    'id'      => 'byName',
                    'options' => $alias
                ),
                'properties' => array(
                    array(
                        'id'    => 'custom.fillOpacity',
                        'value' => $intensity*10
                    )
                )
            )
        );
    }

    /**
    Negates the Y Axis.
    @param string $alias     name of the query.
    @return null.
    **/
    public function negateY($alias)
    {
        $this->addToSeriesOverrides(
            array(
                'matcher' => array(
                    'id'      => 'byName',
                    'options' => $alias
                ),
                'properties' => array(
                    array(
                        'id'    => 'custom.transform',
                        'value' => 'negative-Y'
                    )
                )
            )
        );
    }

    /**
    Display the values on the left or right y axis, left = 1 right = 2.
    @param string $alias name of the query.
    @return null.
    **/
    public function setYAxis($alias, $number = 1)
    {
        switch ($number) {
            case "1":
                $number = "left";
                break;
            case "2":
                $number = "right";
                break;
        }
        $this->addToSeriesOverrides(
            array(
                'matcher' => array(
                    'id'      => 'byName',
                    'options' => $alias
                ),
                'properties' => array(
                    array(
                        'id'    => 'custom.axisPlacement',
                        'value' => $number
                    )
                )
            )
        );
    }

    /**
    Stacks certain series.
    @param string $alias name of the query.
    @return null.
    **/
    public function stack($alias)
    {
        $this->addToSeriesOverrides(
            array(
                'matcher' => array(
                    'id'      => 'byName',
                    'options' => $alias
                ),
                'properties' => array(
                    array(
                        'id'    => 'custom.stacking',
                        'value' => array(
                            "group" => "A",
                            "mode"  => "normal"
                        )
                    )
                )
            )
        );
    }

    public function setLegend(
        $show = SHOW_LEGEND,
        $values = false,
        $min = false,
        $max = false,
        $current = false,
        $total = false,
        $avg = false,
        $alignAsTable = false,
        $rightSide = false,
        $hideEmpty = true
    ) {
        $this->data['legend'] = array(
                                'show' =>  $show,
                                'values' =>  $values,
                                'min' =>  $min,
                                'max' =>  $max,
                                'current' =>  $current,
                                'total' =>  $total,
                                'avg' =>  $avg,
                                "alignAsTable" => $alignAsTable,
                                "rightSide" => $rightSide,
                                "hideEmpty" => $hideEmpty
                            );
    }

    /**
    Adds the target to the dashboard.
    **/
    public function addTarget($target)
    {
        if (!empty($target)) { //TODO:check
            array_push($this->data['targets'], $target);
        }
    }

    /**
    This creates a target with an value.
    **/
    abstract public function genTargetSimple($host, $service, $command, $performanceLabel, $color = '#085DFF', $alias = '', $useRegex = false, $perfData = null);

    /**
    Adds the warning lines to an query.
    **/
    abstract public function addWarnToTarget($target, $alias = '', $color = true);

    /**
    Adds the critical lines to an query.
    **/
    abstract public function addCritToTarget($target, $alias = '', $color = true);

    /**
    This creates a target for an downtime.
    **/
    abstract public function genDowntimeTarget($host, $service, $command, $performanceLabel, $alias = '', $useRegex = false);

    /**
    This creates a target for an forecast.
    **/
    abstract public function genForecastTarget($host, $service, $command, $performanceLabel, $color = '#000', $alias = '', $useRegex = false);
}
