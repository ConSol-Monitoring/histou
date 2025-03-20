<?php

namespace tests\grafana;

class GraphpanelInfluxdbTest extends \MyPHPUnitFrameworkTestCase
{
    public function init()
    {
        $_GET['host'] = 'host';
        \histou\Basic::parsIni('histou.ini.example');
        \histou\Basic::parsArgs();
    }

    public function testCreateGraphPanelInfluxdb()
    {
        $this->init();
        $gpanel = \histou\grafana\graphpanel\GraphPanelFactory::generatePanel('gpanel');

        $this->assertSame(1, sizeof($gpanel->toArray()['fieldConfig']['overrides']));
        $gpanel->addRegexColor('/.*/', '#FFF');
        $this->assertSame(2, sizeof($gpanel->toArray()['fieldConfig']['overrides']));
        $this->assertSame('/.*/', $gpanel->toArray()['fieldConfig']['overrides'][1]['matcher']['options']);
        $gpanel->addRegexColor('/-value', '#FFF');
        $this->assertSame(3, sizeof($gpanel->toArray()['fieldConfig']['overrides']));
        $this->assertSame('/-value', $gpanel->toArray()['fieldConfig']['overrides'][2]['matcher']['options']);

        $gpanel->addAliasColor('foo', '#123');
        $this->assertSame(4, sizeof($gpanel->toArray()['fieldConfig']['overrides']));
        $this->assertSame('#123', $gpanel->toArray()['fieldConfig']['overrides'][3]['properties'][0]['value']['fixedColor']);

        $gpanel->setleftYAxisLabel('ms');
        $this->assertSame('ms', $gpanel->toArray()['fieldConfig']['defaults']['custom']['axisLabel']);

        //Convert Unit
        //left
        $gpanel->setLeftUnit('%');
        $this->assertSame('percent', $gpanel->toArray()['fieldConfig']['defaults']['unit']);
        $gpanel->setLeftUnit('s');
        $this->assertSame('s', $gpanel->toArray()['fieldConfig']['defaults']['unit']);
        $gpanel->setLeftUnit('foo');
        $this->assertSame('short', $gpanel->toArray()['fieldConfig']['defaults']['unit']);

        //Y Min Max
        $gpanel->setLeftYAxisMinMax(0);
        $this->assertSame(0, $gpanel->toArray()['fieldConfig']['defaults']['min']);
        $this->assertSame(false, array_key_exists('max', $gpanel->toArray()['fieldConfig']['defaults']));
        $gpanel->setLeftYAxisMinMax(1, 2);
        $this->assertSame(1, $gpanel->toArray()['fieldConfig']['defaults']['min']);
        $this->assertSame(2, $gpanel->toArray()['fieldConfig']['defaults']['max']);

        //Linewidth
        $gpanel->setLinewidth(10);
        $this->assertSame(10, $gpanel->toArray()['linewidth']);

        //Fill below
        $this->assertSame(4, sizeof($gpanel->toArray()['fieldConfig']['overrides']));
        $gpanel->fillBelowLine('foo', 1);
        $this->assertSame(5, sizeof($gpanel->toArray()['fieldConfig']['overrides']));

        //Negate Y
        $this->assertSame(5, sizeof($gpanel->toArray()['fieldConfig']['overrides']));
        $gpanel->negateY('foo');
        $this->assertSame(6, sizeof($gpanel->toArray()['fieldConfig']['overrides']));

        //setYAxis
        $this->assertSame(6, sizeof($gpanel->toArray()['fieldConfig']['overrides']));
        $gpanel->setYAxis('foo');
        $gpanel->setYAxis('bar', 2);
        $this->assertSame(8, sizeof($gpanel->toArray()['fieldConfig']['overrides']));

        $target1 = $gpanel->genTargetSimple('host', 'service', 'command', 'perfLabel');
    }

    public function testCreateGraphPanelInfluxdbRegex()
    {
        $this->init();
        $gpanel = \histou\grafana\graphpanel\GraphPanelFactory::generatePanel('gpanel');
        $target = $gpanel->genTargetSimple('host', 'service', 'command', 'perfLabel', '000', '', true);
        $this->assertSame('=~', $target['tags'][0]['operator']);
        $this->assertSame('/^host$/', $target['tags'][0]['value']);
        $downtime = $gpanel->genDowntimeTarget('host', 'service', 'command', 'perfLabel', '', true);
        $this->assertSame('=~', $downtime['tags'][0]['operator']);
        $this->assertSame('/^host$/', $downtime['tags'][0]['value']);
    }

    public function testGenForecastTarget()
    {
        $this->init();
        $gpanel = \histou\grafana\graphpanel\GraphPanelFactory::generatePanel('gpanel');
        $target = $gpanel->genForecastTarget('host', 'service', 'command', 'perfLabel', '000', '', true);
        $this->assertSame(null, $target);
        \histou\template\ForecastTemplate::$config = array (
                                                                'size' =>  array (
                                                                    'method' => 'SimpleLinearRegression',
                                                                    'forecast' => '20m',
                                                                ),
                                                                'time' =>  array (
                                                                    'method' => 'SimpleLinearRegression',
                                                                    'forecast' => '30m',
                                                                ),
                                                            );
        $target = $gpanel->genForecastTarget('host', 'service', 'command', 'size')->getArrayCopy();
        $expected = array(
                    'measurement' => 'metrics',
                    'alias' => '$col',
                    'select' =>
                            array (
                                array (
                                    array (
                                        'type' => 'field',
                                        'params' => array ('value'),
                                    ),
                                    array (
                                        'type' => 'mean',
                                        'params' => array (),
                                    ),
                                    array (
                                        'type' => 'alias',
                                        'params' => array ('size-forecast'),
                                    ),
                                ),
                            ),
                    'tags' =>
                            array (
                                array (
                                'key' => 'host',
                                'operator' => '=',
                                'value' => 'host',
                                ),
                                array (
                                'condition' => 'AND',
                                'key' => 'service',
                                'operator' => '=',
                                'value' => 'service',
                                ),
                                array (
                                'condition' => 'AND',
                                'key' => 'performanceLabel',
                                'operator' => '=',
                                'value' => 'size',
                                ),
                            ),
                    'dsType' => 'influxdb',
                    'resultFormat' => 'time_series',
                    'datasource' => 'nagflux_forecast',
                    'groupBy' =>
                            array (
                                array (
                                    'params' => array ( '$__interval' ),
                                    'type' => 'time'
                                    ),
                                array (
                                    'params' => array ( 'linear' ),
                                    'type' => 'fill'
                                )
                            )
        );
        $this->assertSame($expected, $target);
        $this->assertSame(array("20m"), \histou\grafana\dashboard\Dashboard::$forecast);
        $target = $gpanel->genForecastTarget('host', 'service', 'command', 'time', '000', '', true, true)->getArrayCopy();
        $expected = array (
                          'measurement' => 'metrics',
                          'alias' => '$col',
                          'select' =>
                                array (
                                    array (
                                    array (
                                        'type' => 'field',
                                        'params' => array ('value'),
                                    ),
                                    array (
                                        'type' => 'mean',
                                        'params' =>
                                        array (),
                                    ),
                                    array (
                                        'type' => 'alias',
                                        'params' =>
                                        array ('time-forecast-SimpleLinearRegression'),
                                    ),
                                    ),
                                ),
                          'tags' =>
                                array (
                                    array (
                                    'key' => 'host',
                                    'operator' => '=~',
                                    'value' => '/^host$/',
                                    ),
                                    array (
                                    'condition' => 'AND',
                                    'key' => 'service',
                                    'operator' => '=~',
                                    'value' => '/^service$/',
                                    ),
                                    array (
                                    'condition' => 'AND',
                                    'key' => 'performanceLabel',
                                    'operator' => '=~',
                                    'value' => '/^time$/',
                                    ),
                          ),
                          'dsType' => 'influxdb',
                          'resultFormat' => 'time_series',
                          'datasource' => 'nagflux_forecast',
                          'groupBy' => array(
                            array(
                                'params' => array(
                                    '$__interval'
                                ),
                                'type' => 'time'
                                ),
                            array(
                                'params' => array(
                                    0 => 'linear'
                                ),
                                'type' => 'fill'
                            )
                        )

                        );
        $this->assertSame($expected, $target);
        $this->assertSame(array("20m" , "30m"), \histou\grafana\dashboard\Dashboard::$forecast);
    }

    public function testGraphPanelInfluxdbStack()
    {
        $this->init();
        $gpanel = \histou\grafana\graphpanel\GraphPanelFactory::generatePanel('gpanel');
        $gpanel->stack("foo.*");
        $this->assertSame(
            array(
                'matcher' => array (
                    'id' => 'byName',
                    'options' => 'foo.*'
                ),
                'properties' => array (
                    array (
                        'id' => 'custom.stacking',
                        'value' => array (
                            'group' => 'A',
                            'mode' => 'normal'
                        )
                    )
                )
            ),
            $gpanel->toArray()['fieldConfig']['overrides'][1]
        );
    }
    public function testGraphPanelInfluxdbSetLegend()
    {
        $this->init();
        $gpanel = \histou\grafana\graphpanel\GraphPanelFactory::generatePanel('gpanel');
        $gpanel->setLegend(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
        $this->assertSame(
            array (
                                  'show' => 1,
                                  'values' => 2,
                                  'min' => 3,
                                  'max' => 4,
                                  'current' => 5,
                                  'total' => 6,
                                  'avg' => 7,
                                  'alignAsTable' => 8,
                                  'rightSide' => 9,
                                  'hideEmpty' => 10,
                            ),
            $gpanel->toArray()['legend']
        );
    }
}
