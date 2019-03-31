<?php

namespace App\Game;

class Game
{


    /**
     * @var int
     */
    public $size;

    /**
     * @var int
     */
    public $startTiles = 2;

    /**
     * @var Grid
     */
    public $grid;

    public $score;

    public $record;

    public $moves;


    public function __construct(int $score, int $record, int $moves, $tiles = null, int $size = 4, int $startTiles = 2)
    {
        $this->size = $size;
        $this->startTiles = $startTiles;

        $this->score = $score;
        $this->record = $record;
        $this->moves = $moves;

        $this->setup($tiles);
    }

    private function setup($tiles)
    {
        $this->grid = new Grid($this->size, $tiles);
    }


    public function restart()
    {
        //todo
    }

    public function lostGame()
    {
        //todo
    }


    public function addStartTiles()
    {
        for ($i = 0; $i < $this->startTiles; $i++) {
            $this->addRandomTile();
        }
    }

    public function addRandomTile()
    {
        if ($this->grid->anyCellAvailable()) {
            $value = rand(0, 10) <= 9 ? 2 : 4;

            $cell = $this->grid->randomAvailableCell();
            $tile = new Tile($cell->x, $cell->y, $value);

            $this->grid->insertTile($tile);
        }

    }

    public function prepareTiles()
    {
        foreach ($this->grid->eachCell() as $cell) {
            /** @var $cell Cell */
            if ($cell->hasTile()) {
                $cell->tile->merged_from = null;
                $cell->tile->savePosition();
            }

        }
    }

    public function moveTile(Tile $tile, Cell $cell)
    {

        $this->grid->cells[$tile->pos_x][$tile->pos_y]->tile = null;
        $this->grid->cells[$cell->x][$cell->y]->tile = $tile;

        $tile->updatePosition($cell->x, $cell->y);
    }

    public function move($direction)
    {

        // 0: Up  1: Right  2: Down  3: Left

//        if($this->gameTerminated()){
//            return;
//        }

        $cell = null;
        $tile = null;

        $vector = $this->moveVector($direction);
        $traversals = $this->buildTraversals($vector);
        $moved = false;

        $this->prepareTiles();

        //Traverse the grid
        foreach ($traversals['x'] as $x) {
            foreach ($traversals['y'] as $y) {
                $cell = $this->grid->cellContent($x, $y);
                $tile = $cell->tile;
                if (!is_null($tile)) {
                    $position = $this->findFarthestPosition($cell, $vector);
                    $next_cell = $this->grid->cellContent($position['next']->x, $position['next']->y);

                    if (!is_null($next_cell) && !is_null($next_cell->tile) && $next_cell->tile->value == $tile->value && !$next_cell->tile->merged_from) {
                        $next = $next_cell->tile;
                        $merged = new Tile($next->pos_x, $next->pos_y, $tile->value * 2);
                        $merged->merged_from = [$tile, $next];

                        $this->grid->insertTile($merged);
                        $this->grid->remoteTile($tile);

                        $tile->updatePosition($next->pos_x, $next->pos_y);

                        $this->score += $merged->value;
                        if ($this->score > $this->record) {
                            $this->record = $this->score;
                        }

                        //CHECK WIN CONDITION
                    } else {
                        $this->moveTile($tile, $position['farthest']);

                        if (!($cell->x == $tile->pos_x && $cell->y == $tile->pos_y)) {
                            $moved = true; //tile moved from its original cell.
                        }
                    }
                }
            }
        }

        if ($moved) {
            $this->addRandomTile();

            $this->moves++;

            if (!$this->movesAvailable()) {
//                GAME IS OVER
            }
        }
    }

    /**
     * @param $direction
     * @return Vector
     */
    public function moveVector($direction)
    {

        $map = [
            0 => new Vector(0, -1), // Up
            1 => new Vector(1, 0), // Right
            2 => new Vector(0, 1), // Down
            3 => new Vector(-1, 0), // Left
        ];

        return $map[$direction];
    }

    public function buildTraversals(Vector $vector)
    {
        $traversals = [
            'x' => [],
            'y' => []
        ];

        for ($pos = 0; $pos < $this->size; $pos++) {
            $traversals['x'][] = $pos;
            $traversals['y'][] = $pos;
        }

        if ($vector->x == 1) {
            $traversals['x'] = array_reverse($traversals['x']);
        }

        if ($vector->y == 1) {
            $traversals['y'] = array_reverse($traversals['y']);
        }

        return $traversals;
    }

    public function findFarthestPosition(Cell $cell, Vector $vector)
    {
        $previous = null;

        do {
            $previous = $cell;

//            $cell = $this->grid->cells[$previous->x + $vector->x][$previous->y + $vector->y]; //TODO probably broken
            $cell = new Cell($previous->x + $vector->x, $previous->y + $vector->y);
        } while (
            $this->grid->withinBound($cell->x, $cell->y) && $this->grid->cellAvailable($cell->x, $cell->y)
        );

        return [
            'farthest' => $previous,
            'next' => $cell
        ];
    }

    public function movesAvailable()
    {
        return $this->grid->anyCellAvailable() || $this->tileMatchesAvailable();
    }

    public function tileMatchesAvailable()
    {

        $tile = null;

        for ($x = 0; $x < $this->size; $x++) {
            for ($y = 0; $y < $this->size; $y++) {
                $tile = $this->grid->cellContent($x, $y)->tile;
                if (!is_null($tile)) {
                    for ($dir = 0; $dir < 4; $dir++) {
                        $vector = $this->moveVector($dir);
//                        $cell = $this->grid->cells[$x + $vector->x][$y + $vector->y];

                        $other = $this->grid->cellContent($x + $vector->x, $y + $vector->y)->tile;

                        if (!is_null($other) && $other->value == $tile->value) {
                            return true; // These two tiles can be merged
                        }
                    }

                }
            }
        }

        return false;
    }

}

