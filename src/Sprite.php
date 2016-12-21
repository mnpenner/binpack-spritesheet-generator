<?php namespace SpritePack;

class Sprite extends Block {
    /** @var int */
    public $x;
    /** @var int */
    public $y;
    /** @var bool */
    public $used;
    /** @var Sprite */
    public $down;
    /** @var Sprite */
    public $right;

    public function __construct($x, $y, $width, $height, $used = false, $down = null, $right = null) {
        parent::__construct($width, $height);
        $this->x = $x;
        $this->y = $y;
        $this->used = $used;
        $this->down = $down;
        $this->right = $right;
    }

    public function __toString() {
        return "$this->x $this->y $this->width $this->height";
    }
}
