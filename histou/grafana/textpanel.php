<?php
/**
Contains types of Panels.
@category Panel_Class
@package Histou
@author Philip Griesbacher
@license http://opensource.org/licenses/gpl-license.php GNU Public License
@link https://github.com/ConSol/histou
**/
namespace histou\grafana;

/**
Base Panel.
@category Panel_Class
@package Histou
@author Philip Griesbacher
@license http://opensource.org/licenses/gpl-license.php GNU Public License
@link https://github.com/ConSol/histou
**/
class TextPanel extends Panel
{
    const MARKDOWN = 'markdown';
    const TEXT = 'text';
    const HTML = 'html';

    /**
    Constructor.
    @param string $title name of the panel.
    @return object.
    **/
    public function __construct($title, $id = -1)
    {
        parent::__construct($title, 'text', $id);
    }

    /**
    Setter for Mode
    @param int $mode Markdown,text,html.
    @return null.
    **/
    public function setMode($mode)
    {
        $this->data['mode'] = $mode;
    }

    /**
    Setter for Content
    @param int $content content.
    @return null.
    **/
    public function setContent($content)
    {
        $this->data['content'] = $content;
        $this->data['options']['content'] = $content;
    }
}
