<?php

namespace tests\grafana;

class SinglestatpanelTest extends \MyPHPUnitFrameworkTestCase
{
    public function testCreateWrongGraphPanel()
    {
        define('INFLUXDB', 'influxdb');
        define('SHOW_LEGEND', false);
        define("DATABASE_TYPE", 'foo');

        $this->expectException('\InvalidArgumentException');
        $d = \histou\grafana\singlestatpanel\SinglestatPanelFactory::generatePanel('d1');
    }
}
