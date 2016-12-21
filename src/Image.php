<?php namespace SpritePack;

class Image extends Block {
    /** @var string */
    public $filePath;
    /** @var Sprite */
    public $fit;

    public function __construct($filePath, $width, $height) {
        parent::__construct($width, $height);
        $this->filePath = $filePath;
    }
}
