<?php

namespace App\Game;

/**
 * Class Tile
 * @package App\Game
 */
class Tile
{

    public $pos_x;
    public $pos_y;
    public $prev_x;
    public $prev_y;
    public $value;
    public $merged_from;

    public function __construct(int $x, int $y, $value = 2)
    {
        $this->pos_x = $x;
        $this->pos_y = $y;
        $this->value = $value;
    }

    public function savePosition(){
        $this->prev_x = $this->pos_x;
        $this->prev_y = $this->pos_y;
    }

    public function updatePosition($x, $y){
        $this->pos_x = $x;
        $this->pos_y = $y;
    }

}