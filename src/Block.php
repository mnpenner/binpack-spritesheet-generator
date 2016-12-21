<?php namespace SpritePack;

abstract class Block {
    /** @var int */
    public $width;
    /** @var int */
    public $height;

    public function __construct($width, $height) {
        $this->width = $width;
        $this->height = $height;
    }
}
