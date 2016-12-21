#!/usr/bin/env php
<?php
use SpritePack\Image;
use SpritePack\Packer;

require __DIR__ . '/vendor/autoload.php';

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

    public static function main($args) {
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

        $test = in_array('--test', $args);

        if($test) {
            $black = imagecolorallocate($spritesheet, 0, 0, 0);
            foreach($images as $i => $img) {
                $r = mt_rand(0, 255);
                $g = mt_rand(0, 255);
                $b = mt_rand(0, 255);
                imagefilledrectangle($spritesheet, $img->fit->x, $img->fit->y, $img->fit->x + $img->width - 1, $img->fit->y + $img->height - 1, imagecolorallocatealpha($spritesheet, $r, $g, $b, 64));
                imagerectangle($spritesheet, $img->fit->x, $img->fit->y, $img->fit->x + $img->width - 1, $img->fit->y + $img->height - 1, imagecolorallocate($spritesheet, $r, $g, $b));
                imagestring($spritesheet, 5, $img->fit->x + 2, $img->fit->y + 2, $i, $black);
            }
        } else {
            foreach($images as $i => $img) {
                imagecopy($spritesheet, self::imageCreateFromAny($img->filePath), $img->fit->x, $img->fit->y, 0, 0, $img->width, $img->height);
            }
        }


        $out = 'spritesheet.png';
        imagepng($spritesheet, $out);
        echo "wrote $out\n";
    }
}


if(php_sapi_name() === 'cli' && __FILE__ == realpath($argv[0])) {
    Program::main($argv);
}