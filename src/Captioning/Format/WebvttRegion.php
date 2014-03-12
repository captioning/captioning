<?php

namespace Captioning\Format;

class WebvttRegion
{
    private $id;
    private $width;
    private $lines;
    private $regionAnchor;
    private $viewportAnchor;
    private $scroll;

    public function __construct($_id = null, $_width = null, $_lines = null, $_regionAnchor = null, $_viewportAnchor = null, $_scroll = null)
    {
        $this->setId($_id);
        $this->setWidth($_width);
        $this->setLines($_lines);
        $this->setRegionAnchor($_regionAnchor);
        $this->setViewportAnchor($_viewportAnchor);
        $this->setScroll($_scroll);
    }

    public function setId($_id)
    {
        $this->id = $_id;

        return $this;
    }

    public function setWidth($_width)
    {
        $_width = intval($_width);

        if ($_width < 0) {
            $_width = 0;
        } elseif ($_width > 100) {
            $_width = 100;
        }

        $this->width = $_width.'%';

        return $this;
    }

    public function setLines($_lines)
    {
        $this->lines = intval($_lines) >= 1 ? intval($_lines) : 1;

        return $this;
    }

    public static function checkAnchorValues($_value)
    {
        $tmp = explode(',', $_value);
        if (count($tmp) !== 2) {
            return false;
        }
        $x = intval($tmp[0]);
        $y = intval($tmp[1]);

        if ($x < 0) {
            $x = 0;
        } elseif ($x > 100) {
            $x = 100;
        }
        if ($x < 0) {
            $x = 0;
        } elseif ($x > 100) {
            $x = 100;
        }

        return $x.'%,'.$y.'%';
    }

    public function setRegionAnchor($_regionAnchor)
    {
        $_value = self::checkAnchorValues($_regionAnchor);
        if (!$_value) {
            return;
            //throw new \Exception('Invalid region anchor value, must be "XX%,YY%"');
        }
        $this->regionAnchor = $_value;

        return $this;
    }

    public function setViewportAnchor($_viewportAnchor)
    {
        $_value = self::checkAnchorValues($_viewportAnchor);
        if (!$_value) {
            return;
            //throw new \Exception('Invalid viewport anchor value, must be "XX%,YY%"');
        }
        $this->viewportAnchor = $_value;

        return $this;
    }

    public function setScroll($_scroll)
    {
        if (in_array($_scroll, ['up', 'none'])) {
            $this->scroll = $_scroll;
        }

        return $this;
    }

    public static function parseFromString($_string)
    {
        $obj = new WebvttRegion();

        $tmp = explode(' ', $_string);
        if (count($tmp) == 1) {
            return false;
        }
        
        if ($tmp[0] !== 'Region:') {
            return false;
        }

        for ($i = 1; $i < count($tmp); $i++) {
            $tmp2 = explode('=', $tmp[$i]);

            if (count($tmp2) !== 2) {
                continue;
            }

            $tmp2 = array_map('trim', $tmp2);

            switch ($tmp2[0]) {
                case 'id':
                    $obj->setId($tmp2[1]);
                    break;
                case 'width':
                    $obj->setWidth($tmp2[1]);
                    break;
                case 'lines':
                    $obj->setLines($tmp2[1]);
                    break;
                case 'regionanchor':
                    $obj->setRegionAnchor($tmp2[1]);
                    break;
                case 'viewportanchor':
                    $obj->setViewportAnchor($tmp2[1]);
                    break;
                case 'scroll':
                    $obj->setScroll($tmp2[1]);
                    break;
                default:
                    break;
            }
            unset($tmp2);
        }

        return $obj;
    }

    public function __toString()
    {
        $buffer = 'Region:';
        $buffer .= !is_null($this->id)             ? ' id='.$this->id : '';
        $buffer .= !is_null($this->width)          ? ' width='.$this->width : '';
        $buffer .= !is_null($this->lines)          ? ' lines='.$this->lines : '';
        $buffer .= !is_null($this->regionAnchor)   ? ' regionanchor='.$this->regionAnchor : '';
        $buffer .= !is_null($this->viewportAnchor) ? ' viewportanchor='.$this->viewportAnchor : '';
        $buffer .= !is_null($this->scroll)         ? ' scroll='.$this->scroll : '';

        return $buffer;
    }
}
