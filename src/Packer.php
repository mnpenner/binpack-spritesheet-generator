<?php namespace SpritePack;

use Exception;

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
