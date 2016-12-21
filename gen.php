#!/usr/bin/env php
<?php

class Block {
    /** @var int */
    public $width;
    /** @var int */
    public $height;

    public function __construct($width, $height) {
        $this->width = $width;
        $this->height = $height;
    }
}

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
        $this->x = $x;
        $this->y = $y;
        $this->width = $width;
        $this->height = $height;
        $this->used = $used;
        $this->down = $down;
        $this->right = $right;
    }

    public function __toString() {
        return "$this->x $this->y $this->width $this->height";
    }
}

class Image extends Block {
    /** @var string */
    public $filePath;
    /** @var Sprite */
    public $fit;

    public function __construct($filePath, $width, $height) {
        $this->filePath = $filePath;
        $this->width = $width;
        $this->height = $height;
    }
}

class Packer {
    /** @var Sprite */
    public $root;

    /**
     * @param Image[] $images
     */
    public function fit($images) {
        $len = count($images);
        $w = $len > 0 ? $images[0]->width : 0;
        $h = $len > 0 ? $images[0]->height : 0;
        $this->root = new Sprite(0, 0, $w, $h);
        foreach($images as $img) {
            if($node = $this->findNode($this->root, $img->width, $img->height)) {
                $img->fit = $this->splitNode($node, $img->width, $img->height);
            } else {
                $img->fit = $this->growNode($img->width, $img->height);
            }
        }
    }

    /**
     * @param Sprite $node
     * @param int $w
     * @param int $h
     *
     * @return Sprite
     */
    private function findNode($node, $w, $h) {
        if($node->used) {
            return $this->findNode($node->right, $w, $h) ?: $this->findNode($node->down, $w, $h);
        } elseif($w <= $node->width && $h <= $node->height) {
            return $node;
        }
        return null;
    }

    /**
     * @param Sprite $node
     * @param int $w
     * @param int $h
     *
     * @return Sprite
     */
    private function splitNode($node, $w, $h) {
        $node->used = true;
        $node->down = new Sprite($node->x, $node->y + $h, $node->width, $node->height - $h);
        $node->right = new Sprite($node->x + $w, $node->y, $node->width - $w, $h);
        return $node;
    }

    private function growNode($w, $h) {
        $canGrowDown = $w <= $this->root->width;
        $canGrowRight = $h <= $this->root->height;

        $shouldGrowDown = $canGrowDown && $this->root->width >= ($this->root->height + $h);
        $shouldGrowRight = $canGrowRight && $this->root->height >= ($this->root->width + $w);

        if($shouldGrowRight) {
            return $this->growRight($w, $h);
        } elseif($shouldGrowDown) {
            return $this->growDown($w, $h);
        } elseif($canGrowRight) {
            return $this->growRight($w, $h);
        } elseif($canGrowDown) {
            return $this->growDown($w, $h);
        }
        throw new Exception("Could not grow");
    }

    /**
     * @param int $w
     * @param int $h
     *
     * @throws Exception
     * @return Sprite
     */
    private function growRight($w, $h) {
        $node = new Sprite($this->root->width, 0, $w, $this->root->height);
        $this->root = new Sprite(0, 0, $this->root->width + $w, $this->root->height, true, $this->root, $node);
        return $this->splitNode($node, $w, $h);
    }

    /**
     * @param int $w
     * @param int $h
     *
     * @throws Exception
     * @return Sprite
     */
    private function growDown($w, $h) {
        $node = new Sprite(0, $this->root->height, $this->root->width, $h);
        $this->root = new Sprite(0, 0, $this->root->width, $this->root->height + $h, true, $node, $this->root);
        return $this->splitNode($node, $w, $h);
    }
}

class Program {

    private static function imageCreateFromAny($filename) {
        return imagecreatefromstring(file_get_contents($filename));
    }

    private static function imageCreateTrueColorTransparent($width, $height) {
        $im = imagecreatetruecolor($width, $height);
        imagesavealpha($im, true);
        $transColor = imagecolorallocatealpha($im, 0, 0, 0, 127);
        imagefill($im, 0, 0, $transColor);
        return $im;
    }

    public static function main() {
        /** @var Image[] $images */
        $images = [];
        $di = new DirectoryIterator('images');
        foreach($di as $f) {
            /** @var $f DirectoryIterator */
            if(!$f->isFile()) continue;
            $filePath = $f->getPathname();
            list($w, $h) = getimagesize($filePath);
            if(!$w || !$h) {
                echo "could not get width/height for $filePath -- skipping\n";
                continue;
            }
            $images[] = new Image($filePath, $w, $h);
        }
        usort($images, function ($a, $b) {
//            return max($a->width, $a->height) < max($b->width, $b->height) ? 1 : -1;
            if($a->width > $a->height) {
                $aMax = $a->width;
                $aMin = $a->height;
            } else {
                $aMin = $a->width;
                $aMax = $a->height;
            }
            if($b->width > $b->height) {
                $bMax = $b->width;
                $bMin = $b->height;
            } else {
                $bMin = $b->width;
                $bMax = $b->height;
            }
            if($aMax > $bMax) return -1;
            if($aMax < $bMax) return 1;
            if($aMin > $bMin) return -1;
            if($aMin < $bMin) return 1;
            return strcmp($a->filePath, $b->filePath);
        });
        $packer = new Packer();
        $packer->fit($images);
        $spritesheet = self::imageCreateTrueColorTransparent($packer->root->width, $packer->root->height);
        $black = imagecolorallocate($spritesheet, 0, 0, 0);
        foreach($images as $i => $img) {
            // $r = mt_rand(0, 255);
            // $g = mt_rand(0, 255);
            // $b = mt_rand(0, 255);
            // imagefilledrectangle($spritesheet, $img->fit->x, $img->fit->y, $img->fit->x+$img->width, $img->fit->y+$img->height, imagecolorallocatealpha($spritesheet, $r, $g, $b, 64));
            // imagerectangle($spritesheet, $img->fit->x, $img->fit->y, $img->fit->x+$img->width, $img->fit->y+$img->height, imagecolorallocate($spritesheet, $r, $g, $b));
            imagestring($spritesheet, 5, $img->fit->x + 2, $img->fit->y + 2, $i, $black);
            imagecopy($spritesheet, self::imageCreateFromAny($img->filePath), $img->fit->x, $img->fit->y, 0, 0, $img->width, $img->height);
        }
        $out = 'spritesheet.png';
        imagepng($spritesheet, $out);
        echo "wrote $out\n";
    }
}


if(php_sapi_name() === 'cli' && __FILE__ == realpath($argv[0])) {
    Program::main();
}