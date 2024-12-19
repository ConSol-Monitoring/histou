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

        $this->markTestIncomplete('this test needs to be reworked to new timeseries panel layout');
        return;
        $this->assertSame(2, $gpanel->toArray()['linewidth']);

        $this->assertSame(0, sizeof($gpanel->toArray()['seriesOverrides']));
        $gpanel->addRegexColor('/.*/', '#FFF');
        $this->assertSame(1, sizeof($gpanel->toArray()['fieldConfig']['overrides']));
        $this->assertSame('/.*/', $gpanel->toArray()['fieldConfig']['overrides'][0]['alias']);
        $gpanel->addRegexColor('/-value', '#FFF');
        $this->assertSame(2, sizeof($gpanel->toArray()['fieldConfig']['overrides']));
        $this->assertSame('/\/-value/', $gpanel->toArray()['fieldConfig']['overrides'][1]['alias']);

        $gpanel->addAliasColor('foo', '#123');
        $this->assertSame(1, sizeof($gpanel->toArray()['aliasColors']));
        $this->assertSame('#123', $gpanel->toArray()['aliasColors']['foo']);

        $gpanel->setleftYAxisLabel('ms');
        $this->assertSame('ms', $gpanel->toArray()['yaxes'][0]['label']);

        //Convert Unit
        //left
        $gpanel->setLeftUnit('%');
        $this->assertSame('percent', $gpanel->toArray()['yaxes'][0]['format']);
        $this->assertSame('short', $gpanel->toArray()['yaxes'][1]['format']);
        $gpanel->setLeftUnit('s');
        $this->assertSame('s', $gpanel->toArray()['yaxes'][0]['format']);
        $this->assertSame('short', $gpanel->toArray()['yaxes'][1]['format']);
        $gpanel->setLeftUnit('foo');
        $this->assertSame('short', $gpanel->toArray()['yaxes'][0]['format']);
        $this->assertSame('short', $gpanel->toArray()['yaxes'][1]['format']);
        $this->assertSame('foo', $gpanel->toArray()['yaxes'][0]['label']);

        //Y Min Max
        $gpanel->setLeftYAxisMinMax(0);
        $this->assertSame(0, $gpanel->toArray()['yaxes'][0]['min']);
        $this->assertSame(false, array_key_exists('max', $gpanel->toArray()['yaxes'][0]));
        $gpanel->setLeftYAxisMinMax(1, 2);
        $this->assertSame(1, $gpanel->toArray()['yaxes'][0]['min']);
        $this->assertSame(2, $gpanel->toArray()['yaxes'][0]['max']);

        $gpanel->setRightAxisMinMax(0);
        $this->assertSame(0, $gpanel->toArray()['yaxes'][1]['min']);
        $this->assertSame(false, array_key_exists('max', $gpanel->toArray()['yaxes'][1]));
        $gpanel->setRightAxisMinMax(1, 2);
        $this->assertSame(1, $gpanel->toArray()['yaxes'][1]['min']);
        $this->assertSame(2, $gpanel->toArray()['yaxes'][1]['max']);

        //Linewidth
        $gpanel->setLinewidth(10);
        $this->assertSame(10, $gpanel->toArray()['linewidth']);

        //Fill below
        $this->assertSame(0, sizeof($gpanel->toArray()['fieldConfig']['overrides']));
        $gpanel->fillBelowLine('foo', 1);
        $this->assertSame(1, sizeof($gpanel->toArray()['fieldConfig']['overrides']));

        //Negate Y
        $this->assertSame(1, sizeof($gpanel->toArray()['fieldConfig']['overrides']));
        $gpanel->negateY('foo');
        $this->assertSame(2, sizeof($gpanel->toArray()['fieldConfig']['overrides']));
        $this->assertSame(
            array('alias' => 'foo', 'transform' => 'negative-Y'),
            $gpanel->toArray()['fieldConfig']['overrides'][1]
        );

        //setYAxis
        $this->assertSame(2, sizeof($gpanel->toArray()['fieldConfig']['overrides']));
        $gpanel->setYAxis('foo');
        $gpanel->setYAxis('bar', 2);
        $this->assertSame(4, sizeof($gpanel->toArray()['fieldConfig']['overrides']));
        $this->assertSame(
            array('alias' => 'foo', 'yaxis' => 1),
            $gpanel->toArray()['fieldConfig']['overrides'][2]
        );
        $this->assertSame(
            array('alias' => 'bar', 'yaxis' => 2),
            $gpanel->toArray()['fieldConfig']['overrides'][3]
        );

        $target1 = $gpanel->genTargetSimple('host', 'service', 'command', 'perfLabel');
        $expected = array('measurement' => 'metrics', 'alias' => '$col',
                            'select' =>  array(array(array(
                                                        'type' => 'field',
                                                        'params' => array ('value')
                                                            ),
                                                    array (
                                                        'type' => 'mean',
                                                        'params' => array (),
                                                            ),
                                                    array (
                                                        'type' => 'alias',
                                                        'params' => array ('perfLabel-value'),
                                                            ),
                                                    ),
                                                ),
                            'tags' => array(array(
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
                                                'key' => 'command',
                                                'operator' => '=',
                                                'value' => 'command',
                                            ),
                                            array (
                                                'condition' => 'AND',
                                                'key' => 'performanceLabel',
                                                'operator' => '=',
                                                'value' => 'perfLabel',
                                            ),
                                        ),
                            'dsType' => 'influxdb', 'resultFormat' => 'time_series', 'datasource' => 'nagflux',
        );
        $this->assertSame($expected, $target1);

        $target1 = $gpanel->addWarnToTarget($target1);
        //$this->assertSame($expected, $target1);
        $expected  =   array (  array (     array ( 'type' => 'field','params' => array ('value')),
                                        array ('type' => 'mean','params' =>array ()),
                                        array ('type' => 'alias','params' =>array ('perfLabel-value')),
                                    ),
                                array (     array ('type' => 'field','params' =>array ('warn')),
                                        array ('type' => 'mean','params' =>array ()),
                                        array ('type' => 'alias','params' => array ('-warn')),
                                    ),
                                array (     array ('type' => 'field','params' =>array ('warn-min')),
                                        array ('type' => 'mean','params' =>array ()),
                                        array ('type' => 'alias','params' =>array ('-warn-min')),
                                    ),
                                array (     array ('type' => 'field','params' =>array ('warn-max')),
                                        array ('type' => 'mean','params' =>array ()),
                                        array ('type' => 'alias','params' =>array ('-warn-max')),
                                    ),
                            );
        $this->assertSame($expected, $target1['select']);
        $target2 = $gpanel->genTargetSimple('host', 'service', 'command', 'perfLabel');
        $target2 = $gpanel->addWarnToTarget($target2, 'alias123', false);
        $expected = array (
                          'measurement' => 'metrics',
                          'alias' => '$col',
                          'select' =>
                          array (
                            array (
                              array (
                                'type' => 'field',
                                'params' =>
                                array (
                                  'value',
                                ),
                              ),
                              array (
                                'type' => 'mean',
                                'params' =>
                                array (
                                ),
                              ),
                              array (
                                'type' => 'alias',
                                'params' =>
                                array (
                                 'perfLabel-value',
                                ),
                              ),
                            ),
                            array (
                              array (
                                'type' => 'field',
                                'params' =>
                                array (
                                 'warn',
                                ),
                              ),
                              array (
                                'type' => 'mean',
                                'params' =>
                                array (
                                ),
                              ),
                              array (
                                'type' => 'alias',
                                'params' =>
                                array (
                                 'alias123-warn',
                                ),
                              ),
                            ),
                            array (
                              array (
                                'type' => 'field',
                                'params' =>
                                array (
                                  'warn-min',
                                ),
                              ),
                              array (
                                'type' => 'mean',
                                'params' =>
                                array (
                                ),
                              ),
                              array (
                                'type' => 'alias',
                                'params' =>
                                array (
                                  'alias123-warn-min',
                                ),
                              ),
                            ),
                            array (
                              array (
                                'type' => 'field',
                                'params' =>
                                array (
                                  'warn-max',
                                ),
                              ),
                              array (
                                'type' => 'mean',
                                'params' =>
                                array (
                                ),
                              ),
                              array (
                                'type' => 'alias',
                                'params' =>
                                array (
                                  'alias123-warn-max',
                                ),
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
                              'key' => 'command',
                              'operator' => '=',
                              'value' => 'command',
                            ),
                            array (
                              'condition' => 'AND',
                              'key' => 'performanceLabel',
                              'operator' => '=',
                              'value' => 'perfLabel',
                            ),
                          ),
                          'dsType' => 'influxdb',
                          'resultFormat' => 'time_series',
                          'datasource' => 'nagflux',
                        );
        $this->assertSame($expected, $target2);
        $target3 = $gpanel->genTargetSimple('host', 'service', 'command', 'perfLabel');
        $target3 = $gpanel->addCritToTarget($target3);
        $expected = array(
                            array(
                                array(
                                    'type' => 'field',
                                    'params' => array(
                                        'value',
                                    ) ,
                                ) ,
                                array(
                                    'type' => 'mean',
                                    'params' => array() ,
                                ) ,
                                array(
                                    'type' => 'alias',
                                    'params' => array(
                                        'perfLabel-value',
                                    ) ,
                                ) ,
                            ) ,
                            array(
                                array(
                                    'type' => 'field',
                                    'params' => array(
                                        'crit',
                                    ) ,
                                ) ,
                                array(
                                    'type' => 'mean',
                                    'params' => array() ,
                                ) ,
                                array(
                                    'type' => 'alias',
                                    'params' => array(
                                        '-crit',
                                    ) ,
                                ) ,
                            ) ,
                            array(
                                array(
                                    'type' => 'field',
                                    'params' => array(
                                        'crit-min',
                                    ) ,
                                ) ,
                                array(
                                    'type' => 'mean',
                                    'params' => array() ,
                                ) ,
                                array(
                                    'type' => 'alias',
                                    'params' => array(
                                        '-crit-min',
                                    ) ,
                                ) ,
                            ) ,
                            array(
                                array(
                                    'type' => 'field',
                                    'params' => array(
                                        'crit-max',
                                    ) ,
                                ) ,
                                array(
                                    'type' => 'mean',
                                    'params' => array() ,
                                ) ,
                                array(
                                    'type' => 'alias',
                                    'params' => array(
                                        '-crit-max',
                                    ) ,
                                ) ,
                            ) ,
                        );
        $this->assertSame($expected, $target3['select']);
        $target4 = $gpanel->genTargetSimple('host', 'service', 'command', 'perfLabel');
        $target4 = $gpanel->addCritToTarget($target4, 'alias123', false);
        $expected = array(
                        array(
                            array(
                                'type' => 'field',
                                'params' => array(
                                    'value',
                                ) ,
                            ) ,
                            array(
                                'type' => 'mean',
                                'params' => array() ,
                            ) ,
                            array(
                                'type' => 'alias',
                                'params' => array(
                                    'perfLabel-value',
                                ) ,
                            ) ,
                        ) ,
                        array(
                            array(
                                'type' => 'field',
                                'params' => array(
                                    'crit',
                                ) ,
                            ) ,
                            array(
                                'type' => 'mean',
                                'params' => array() ,
                            ) ,
                            array(
                                'type' => 'alias',
                                'params' => array(
                                    'alias123-crit',
                                ) ,
                            ) ,
                        ) ,
                        array(
                            array(
                                'type' => 'field',
                                'params' => array(
                                    'crit-min',
                                ) ,
                            ) ,
                            array(
                                'type' => 'mean',
                                'params' => array() ,
                            ) ,
                            array(
                                'type' => 'alias',
                                'params' => array(
                                    'alias123-crit-min',
                                ) ,
                            ) ,
                        ) ,
                        array(
                            array(
                                'type' => 'field',
                                'params' => array(
                                    'crit-max',
                                ) ,
                            ) ,
                            array(
                                'type' => 'mean',
                                'params' => array() ,
                            ) ,
                            array(
                                'type' => 'alias',
                                'params' => array(
                                    'alias123-crit-max',
                                ) ,
                            ) ,
                        ) ,
                    );
        $this->assertSame($expected, $target4['select']);
        $downtime1 = $gpanel->genDowntimeTarget('host', 'service', 'command', 'perfLabel');
        $expectedDowntime = array(
                                'measurement' => 'metrics',
                                'alias' => '$col',
                                'select' => array(
                                    array(
                                        array(
                                            'type' => 'field',
                                            'params' => array(
                                                'value',
                                            ) ,
                                        ) ,
                                        array(
                                            'type' => 'mean',
                                            'params' => array() ,
                                        ) ,
                                        array(
                                            'type' => 'alias',
                                            'params' => array(
                                                'downtime',
                                            ) ,
                                        ) ,
                                    ) ,
                                ) ,
                                'tags' => array(
                                    array(
                                        'key' => 'host',
                                        'operator' => '=',
                                        'value' => 'host',
                                    ) ,
                                    array(
                                        'condition' => 'AND',
                                        'key' => 'service',
                                        'operator' => '=',
                                        'value' => 'service',
                                    ) ,
                                    array(
                                        'condition' => 'AND',
                                        'key' => 'command',
                                        'operator' => '=',
                                        'value' => 'command',
                                    ) ,
                                    array(
                                        'condition' => 'AND',
                                        'key' => 'performanceLabel',
                                        'operator' => '=',
                                        'value' => 'perfLabel',
                                    ) ,
                                    array(
                                        'condition' => 'AND',
                                        'key' => 'downtime',
                                        'operator' => '=',
                                        'value' => 'true',
                                    ) ,
                                ) ,
                                'dsType' => 'influxdb',
                                'resultFormat' => 'time_series',
                                'datasource' => 'nagflux',
                            );
        $this->assertSame($expectedDowntime, $downtime1);
        $downtime2 = $gpanel->genDowntimeTarget('host', 'service', 'command', 'perfLabel', 'alias123');
        $expectedDowntime = array(
                                'measurement' => 'metrics',
                                'alias' => '$col',
                                'select' => array(
                                    array(
                                        array(
                                            'type' => 'field',
                                            'params' => array(
                                                'value',
                                            ) ,
                                        ) ,
                                        array(
                                            'type' => 'mean',
                                            'params' => array() ,
                                        ) ,
                                        array(
                                            'type' => 'alias',
                                            'params' => array(
                                                'alias123',
                                            ) ,
                                        ) ,
                                    ) ,
                                ) ,
                                'tags' => array(
                                    array(
                                        'key' => 'host',
                                        'operator' => '=',
                                        'value' => 'host',
                                    ) ,
                                    array(
                                        'condition' => 'AND',
                                        'key' => 'service',
                                        'operator' => '=',
                                        'value' => 'service',
                                    ) ,
                                    array(
                                        'condition' => 'AND',
                                        'key' => 'command',
                                        'operator' => '=',
                                        'value' => 'command',
                                    ) ,
                                    array(
                                        'condition' => 'AND',
                                        'key' => 'performanceLabel',
                                        'operator' => '=',
                                        'value' => 'perfLabel',
                                    ) ,
                                    array(
                                        'condition' => 'AND',
                                        'key' => 'downtime',
                                        'operator' => '=',
                                        'value' => 'true',
                                    ) ,
                                ) ,
                                'dsType' => 'influxdb',
                                'resultFormat' => 'time_series',
                                'datasource' => 'nagflux',
                            );
        $this->assertSame($expectedDowntime, $downtime2);
        $this->assertSame(0, sizeof($gpanel->toArray()['targets']));
        $gpanel->addTarget($target1);
        $this->assertSame(1, sizeof($gpanel->toArray()['targets']));
        $gpanel->addTarget($downtime1);
        $this->assertSame(2, sizeof($gpanel->toArray()['targets']));
        $this->assertSame($downtime1, $gpanel->toArray()['targets'][1]);
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
