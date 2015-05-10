<?php

namespace Captioning\Format;

class WebvttRegion
{
    const ANCHOR_TYPE_REGION = 1;
    const ANCHOR_TYPE_VIEWPORT = 2;

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
            return null;
        }
        $x = intval($tmp[0]);
        $y = intval($tmp[1]);

        $x = $x < 0 ? 0 : ($x > 100 ? 100 : $x);
        $y = $y < 0 ? 0 : ($y > 100 ? 100 : $y);

        return $x.'%,'.$y.'%';
    }

    public function setRegionAnchor($_regionAnchor)
    {
        return $this->setAnchor($_regionAnchor, self::ANCHOR_TYPE_REGION);
    }

    public function setViewportAnchor($_viewportAnchor)
    {
        return $this->setAnchor($_viewportAnchor, self::ANCHOR_TYPE_VIEWPORT);
    }

    public function setScroll($_scroll)
    {
        if (in_array($_scroll, array('up', 'none'))) {
            $this->scroll = $_scroll;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return integer
     */
    public function getLines()
    {
        return $this->lines;
    }

    /**
     * @return string
     */
    public function getRegionAnchor()
    {
        return $this->regionAnchor;
    }

    /**
     * @return string
     */
    public function getScroll()
    {
        return $this->scroll;
    }

    /**
     * @return string
     */
    public function getViewportAnchor()
    {
        return $this->viewportAnchor;
    }

    /**
     * @return string
     */
    public function getWidth()
    {
        return $this->width;
    }

    public static function parseFromString($_string)
    {
        $obj = new self();

        $tmp = explode(' ', $_string);
        if (count($tmp) == 1 || $tmp[0] !== 'Region:') {
            throw new \InvalidArgumentException('Unable to parse the string as WebvttRegion');
        }

        $tmpCount = count($tmp);
        for ($i = 1; $i < $tmpCount; $i++) {
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

    private function setAnchor($anchor, $anchorType)
    {
        if (null === $anchor) {
            return;
        }
        $_value = self::checkAnchorValues($anchor);
        if (null === $_value) {
            throw new \Exception('Invalid anchor value, must be "XX%,YY%", "'.$anchor.'" given.');
        }
        switch ($anchorType) {
            case self::ANCHOR_TYPE_REGION:
                $this->regionAnchor = $_value;
                break;
            case self::ANCHOR_TYPE_VIEWPORT:
                $this->viewportAnchor = $_value;
                break;
            default:
                throw new \RuntimeException('Invalid anchor type supplied');
        }

        return $this;
    }
}
