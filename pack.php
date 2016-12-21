#!/usr/bin/env php
<?php
use SpritePack\Image;
use SpritePack\Packer;

require __DIR__ . '/vendor/autoload.php';

function ag($array, $key, $default=null) {
    return array_key_exists($key, $array) ? $array[$key] : $default;
}

class Program {

    private static function imageCreateFromAny($filename) {
        return imagecreatefromstring(file_get_contents($filename));
    }

    private static function imageCreateTrueColorTransparent($width, $height) {
        $im = imagecreatetruecolor($width, $height);
        imagesavealpha($im, true);
        $transColor = imagecolorallocatealpha($im, 0, 0, 0, 127); // 127 indicates completely transparent
        imagefill($im, 0, 0, $transColor);
        return $im;
    }

    public static function main($cliArgs) {
        $options = [];
        $args = [];
        
        $command = array_shift($cliArgs);
        
        foreach($cliArgs as $arg) {
            if($arg[0] === '-') {
                $options[ltrim($arg,'-')] = true;
            } else {
                $args[] = $arg;
            }
        }
        
        if(count($args) < 1) {
            fwrite(STDERR, "usage: $command <input-dir> [output-file]\n");
            exit(1);
        }
        
        $sourceDir = ag($args,0,'images');
        $outFile = ag($args,1,'spritesheet.png');
        
        /** @var Image[] $images */
        $images = [];
        $di = new DirectoryIterator($sourceDir);
        foreach($di as $f) {
            /** @var $f DirectoryIterator */
            if(!$f->isFile()) continue;
            $filePath = $f->getPathname();
            list($w, $h) = getimagesize($filePath);
            if(!$w || !$h) {
                fwrite(STDERR,"could not get width/height for $filePath -- skipping\n");
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
            return strnatcasecmp($a->filePath, $b->filePath);
        });
        $packer = new Packer();
        $packer->fit($images);
        $spritesheet = self::imageCreateTrueColorTransparent($packer->root->width, $packer->root->height);

        if(ag($options,'test')) {
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
        
        imagepng($spritesheet, $outFile);
        echo "packed ".number_format(count($images))." images into $outFile\n";
    }
}


if(php_sapi_name() === 'cli' && __FILE__ == realpath($argv[0])) {
    Program::main($argv);
}