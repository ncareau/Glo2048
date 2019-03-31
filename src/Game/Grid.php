<?php

namespace App\Game;

/**
 * Class Grid
 * @package App\Game
 */
class Grid
{

    /**
     * Size of playfield
     * @var int
     */
    public $size;

    /**
     * @var Cell|null[][]
     */
    public $cells;

    /**
     * Grid constructor.
     * @param $size
     * @param null $state
     */
    public function __construct($size, $state = null)
    {
        $this->size = $size;

        if ($state == null) {
            $this->loadEmpty();
        } else {
            $this->loadState($state);
        }
    }

    /**
     *
     */
    private function loadEmpty()
    {
        for ($x = 0; $x < $this->size; $x++) {

            $this->cells[$x] = [];

            for ($y = 0; $y < $this->size; $y++) {
                $this->cells[$x][$y] = new Cell($x,$y);
            }
        }
    }

    /**
     * @param Tile[] $state
     */
    private function loadState($tiles)
    {
        $this->loadEmpty();

        foreach ($tiles as $tile) {
            $this->insertTile($tile);
        }
    }

    /**
     * @return Tile|null
     */
    public function randomAvailableCell()
    {
        $cells = $this->availableCells();

        if (count($cells) > 0) {
            return $cells[rand(0, count($cells)-1)];
        }

        return null;
    }

    /**
     * @return array
     */
    public function availableCells()
    {
        $cells = [];

        foreach ($this->eachCell() as $cell) {
            if (is_null($cell->tile)) {
                $cells[] = $cell;
            }
        }

        return $cells;
    }

    /**
     * @return Cell[]|null[]
     */
    public function eachCell()
    {
        $cells = [];

        for ($x = 0; $x < $this->size; $x++) {
            for ($y = 0; $y < $this->size; $y++) {
                $cells[] = $this->cells[$x][$y];
            }
        }

        return $cells;
    }

    /**
     * @return bool
     */
    public function anyCellAvailable()
    {
        return count($this->availableCells()) > 0;
    }

    /**
     * @param $x
     * @param $y
     * @return bool
     */
    public function cellAvailable($x, $y)
    {
        return $this->cellContent($x, $y)->isEmpty();
    }

    /**
     * @param $x
     * @param $y
     * @return bool
     */
    public function cellOccupied($x, $y)
    {
        return $this->cellContent($x, $y)->hasTile();
    }

    /**
     * @param $x
     * @param $y
     * @return Cell|null
     */
    public function cellContent($x, $y)
    {
        if ($this->withinBound($x, $y)) {
            return $this->cells[$x][$y];
        } else {
            return null;
        }
    }

    /**
     * @param Tile $tile
     */
    public function insertTile(Tile $tile)
    {
        $this->cells[$tile->pos_x][$tile->pos_y]->tile = $tile;
    }

    /**
     * @param Tile $tile
     */
    public function remoteTile(Tile $tile)
    {
        $this->cells[$tile->pos_x][$tile->pos_y]->tile = null;
    }

    /**
     * @param $x
     * @param $y
     * @return bool
     */
    public function withinBound($x, $y)
    {
        return $x >= 0 && $x < $this->size && $y >= 0 && $y < $this->size;
    }

}