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

class GraphPanelVictoriametricsTarget extends \ArrayObject implements \JsonSerializable
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
