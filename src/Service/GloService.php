<?php


namespace App\Service;


use App\Game\Game;
use App\Game\Tile;
use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GloService
{

    // POS_DATE[y][x]
    const POS_DATE = [
        0 => [
            0 => '2019-04-02',
            1 => '2019-04-03',
            2 => '2019-04-04',
            3 => '2019-04-05'
        ],
        1 => [
            0 => '2019-04-09',
            1 => '2019-04-10',
            2 => '2019-04-11',
            3 => '2019-04-12'
        ],
        2 => [
            0 => '2019-04-16',
            1 => '2019-04-17',
            2 => '2019-04-18',
            3 => '2019-04-19'
        ],
        3 => [
            0 => '2019-04-23',
            1 => '2019-04-24',
            2 => '2019-04-25',
            3 => '2019-04-26'
        ],
    ];

    // DATE_POS
    const DATE_POS = [
        '2019-04-02' => ['x' => 0, 'y' => 0],
        '2019-04-03' => ['x' => 1, 'y' => 0],
        '2019-04-04' => ['x' => 2, 'y' => 0],
        '2019-04-05' => ['x' => 3, 'y' => 0],
        '2019-04-09' => ['x' => 0, 'y' => 1],
        '2019-04-10' => ['x' => 1, 'y' => 1],
        '2019-04-11' => ['x' => 2, 'y' => 1],
        '2019-04-12' => ['x' => 3, 'y' => 1],
        '2019-04-16' => ['x' => 0, 'y' => 2],
        '2019-04-17' => ['x' => 1, 'y' => 2],
        '2019-04-18' => ['x' => 2, 'y' => 2],
        '2019-04-19' => ['x' => 3, 'y' => 2],
        '2019-04-23' => ['x' => 0, 'y' => 3],
        '2019-04-24' => ['x' => 1, 'y' => 3],
        '2019-04-25' => ['x' => 2, 'y' => 3],
        '2019-04-26' => ['x' => 3, 'y' => 3]
    ];

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var Game
     */
    private $game;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $board_id;

    public function __construct(SessionInterface $session)
    {
        $pat = $session->get('pat');

        $this->client = new Client([
            'base_uri' => 'https://gloapi.gitkraken.com/v1/glo/',
            'headers' => [
                'Authorization' => 'Bearer '.$pat,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]
        ]);

        $this->session = $session;

        $this->board_id = $session->get('board_id');
    }

    public function loadState($cards)
    {
        $tiles = [];

        foreach($cards as $card){
            $value = intval($card['name']);
            $date = new Datetime($card['due_date']);

            $date = $date->format('Y-m-d');

            if($value != 0){

                $pos = self::DATE_POS[$date];

                $tiles[] = new Tile($pos['x'], $pos['y'], $value);
            }
        }

        $record = $this->session->get('record',0);
        $score = $this->session->get('score',0);
        $moves = $this->session->get('move',0);

        $this->game = new Game($score, $record, $moves, $tiles);
    }

    public function restartGame()
    {

        $record = $this->session->get('record');

        $this->game = new Game(0, $record, 0);
        $this->game->addStartTiles();
    }

    public function newColumn($name)
    {
        $input_column = json_encode([
            'name' => $name,
            'position' => 0
        ]);

        $response_column = $this->client->request('POST', 'boards/' . $this->board_id . '/columns', [
            'body' => $input_column
        ]);

        if ($response_column->getStatusCode() != 201) {
            throw new HttpException($response_column->getStatusCode());
        }

        $column = json_decode($response_column->getBody(), true);
        $column_id = $column['id'];

        return $column_id;
    }

    public function newBoard(){
        $input = json_encode([
            'name' => 'Glo2048'
        ]);

        $response = $this->client->request('POST', 'boards', [
            'body' => $input
        ]);

        if ($response->getStatusCode() != 201) {
            throw new HttpException($response->getStatusCode());
        }

        $board = json_decode($response->getBody(), true);

        $board_id = $board['id'];

        $this->board_id = $board_id;

        return $board_id;
    }

    public function getCardsInColumn($column_id)
    {
        $body = [
            'fields' => [
                'name', 'board_id', 'column_id', 'due_date'
            ]
        ];

        $response_cards = $this->client->request('GET', 'boards/' . $this->board_id . '/columns/'.$column_id.'/cards', [
            'body' => json_encode($body)
        ]);

        $cards = json_decode($response_cards->getBody(), true);

        return $cards;
    }

    public function createBatchCards($cards)
    {
        $response_cards = $this->client->request('POST', 'boards/' . $this->board_id . '/cards/batch', [
            'body' => json_encode($cards)
        ]);

        $cards = json_decode($response_cards->getBody(), true);

        return $cards;
    }

    public function deleteColumn($column_id)
    {
        try {
            $this->client->request('DELETE', 'boards/' . $this->board_id . '/columns/' . $column_id);
        } catch (GuzzleException $e) {
            //We don't care if it doesn't exist. yet. todo
//            dump($e)
        }
    }

    public function generateGloCards($column_id)
    {
        $glo_cards = [
            "cards" => [
                [
                    'name' => 'Score: '.  $this->game->score,
                    'position' => 0,
                    'column_id' => $column_id,
                    'due_date' => '2019-04-06'
                ],
                [
                    'name' => 'Record: '. $this->game->record,
                    'position' => 0,
                    'column_id' => $column_id,
                    'due_date' => '2019-04-13'
                ],
                [
                    'name' => '# of moves: '. $this->game->moves,
                    'position' => 0,
                    'column_id' => $column_id,
                    'due_date' => '2019-04-20'
                ]
            ],
            "send_notifications" => false
        ];

        foreach ($this->game->grid->eachCell() as $cell) {

            $tile = $cell->tile;

            if (!is_null($tile)) {

                $glo_cards['cards'][] = [
                    'name' => ''.$tile->value,
                    'position' => 0,
                    'column_id' => $column_id,
                    'due_date' => self::POS_DATE[$tile->pos_y][$tile->pos_x]
                ];
            }

        }

        return $glo_cards;
    }

    public function move($dir)
    {
        switch($dir){
            case 'up':
                $this->game->move(0);
                break;

            case 'right':
                $this->game->move(1);
                break;

            case 'down':
                $this->game->move(2);
                break;

            case 'left':
                $this->game->move(3);
                break;
        }
    }

    public function createPlayfield(){

        //CREATE COLLUMN PLAYFIELD

        $input_columnfield = json_encode([
            'name' => 'PLAYFIELD',
            'position' => 0
        ]);

        $response_columnfield = $this->client->request('POST', 'boards/' . $this->board_id . '/columns', [
            'body' => $input_columnfield
        ]);

        if ($response_columnfield->getStatusCode() != 201) {
            echo 'error';
        }

        $column_field = json_decode($response_columnfield->getBody(), true);

//        dump($column_field);

        $columnfield_id = $column_field['id'];

        $this->session->set('columnfield_id',$columnfield_id);

        //CREATE PLAYFIELD

        $input_playfield_cards = json_encode([
            "cards" => [
                [
                    'name' => '|------------------------',
                    'position' => 0,
                    'column_id' => $columnfield_id,
                    'due_date' => '2019-04-06'
                ],
                [
                    'name' => '|------------------------',
                    'position' => 0,
                    'column_id' => $columnfield_id,
                    'due_date' => '2019-04-13'
                ],
                [
                    'name' => '|------------------------',
                    'position' => 0,
                    'column_id' => $columnfield_id,
                    'due_date' => '2019-04-20'
                ],
                [
                    'name' => '|------------------------',
                    'position' => 0,
                    'column_id' => $columnfield_id,
                    'due_date' => '2019-04-27'
                ],
                [
                    'name' => '----------------------------------------------------',
                    'position' => 0,
                    'column_id' => $columnfield_id,
                    'due_date' => '2019-05-03'
                ],
                [
                    'name' => '----------------------------------------------------',
                    'position' => 0,
                    'column_id' => $columnfield_id,
                    'due_date' => '2019-05-02'
                ],
                [
                    'name' => '----------------------------------------------------',
                    'position' => 0,
                    'column_id' => $columnfield_id,
                    'due_date' => '2019-05-01'
                ],
                [
                    'name' => '----------------------------------------------------',
                    'position' => 0,
                    'column_id' => $columnfield_id,
                    'due_date' => '2019-04-30'
                ],
                [
                    'name' => '-----------------------|',
                    'position' => 0,
                    'column_id' => $columnfield_id,
                    'due_date' => '2019-04-08'
                ],
                [
                    'name' => '-----------------------|',
                    'position' => 0,
                    'column_id' => $columnfield_id,
                    'due_date' => '2019-04-15'
                ],
                [
                    'name' => '-----------------------|',
                    'position' => 0,
                    'column_id' => $columnfield_id,
                    'due_date' => '2019-04-22'
                ],
                [
                    'name' => '-----------------------|',
                    'position' => 0,
                    'column_id' => $columnfield_id,
                    'due_date' => '2019-04-01'
                ],
            ],
            "send_notifications" => false
        ]);

        $response_playfield_cards = $this->client->request('POST', 'boards/' . $this->board_id . '/cards/batch', [
            'body' => $input_playfield_cards
        ]);

        return json_decode($response_playfield_cards->getBody(), true);
    }

    public function createInitialCards($column_id, $record){

        $input_cards = json_encode([
            "cards" => [
                [
                    'name' => 'Press',
                    'position' => 0,
                    'column_id' => $column_id,
                    'due_date' => '2019-04-10'
                ],
                [
                    'name' => 'start',
                    'position' => 0,
                    'column_id' => $column_id,
                    'due_date' => '2019-04-11'
                ],
                [
                    'name' => 'when',
                    'position' => 0,
                    'column_id' => $column_id,
                    'due_date' => '2019-04-17'
                ],
                [
                    'name' => 'ready.',
                    'position' => 0,
                    'column_id' => $column_id,
                    'due_date' => '2019-04-18'
                ],
                [
                    'name' => 'Score: 0',
                    'position' => 0,
                    'column_id' => $column_id,
                    'due_date' => '2019-04-06'
                ],
                [
                    'name' => 'Record: '. $record,
                    'position' => 0,
                    'column_id' => $column_id,
                    'due_date' => '2019-04-13'
                ],
                [
                    'name' => '# of moves: 0',
                    'position' => 0,
                    'column_id' => $column_id,
                    'due_date' => '2019-04-20'
                ]
            ],
            "send_notifications" => false
        ]);

        $response_cards = $this->client->request('POST', 'boards/' . $this->board_id . '/cards/batch', [
            'body' => $input_cards
        ]);

        $cards = json_decode($response_cards->getBody(), true);

//        dump($cards);
    }

    public function setPat(string $pat)
    {
        $this->client = new Client([
            'base_uri' => 'https://gloapi.gitkraken.com/v1/glo/',
            'headers' => [
                'Authorization' => 'Bearer '.$pat,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]
        ]);

    }

    public function updateScore()
    {
        $this->session->set('move', $this->game->moves);
        $this->session->set('score', $this->game->score);
        $this->session->set('record', $this->game->record);
    }

}