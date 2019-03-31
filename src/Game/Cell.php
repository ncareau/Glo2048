<?php


namespace App\Game;


class Cell
{

    public $x;
    public $y;

    /**
     * @var Tile|null
     */
    public $tile;

    public function __construct($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function isEmpty(){
        return is_null($this->tile);
    }


    public function hasTile(){
        return !is_null($this->tile);
    }
}