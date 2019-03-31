<?php

namespace App\Controller;

use App\Service\GloService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(SessionInterface $session)
    {

        if($session->has('board_id') && $session->has('column_id') && $session->has('pat') ){
            return $this->redirectToRoute('play', ['id'=> $session->get('board_id')]);
        }

        return $this->render('index/index.html.twig', []);
    }

    /**
     * @Route("/play/{id}", name="play")
     */
    public function play($id)
    {
        $session = new Session();

        if(!$session->has('board_id') || !$session->has('column_id') || !$session->has('pat') ){
            return $this->redirectToRoute('index');
        }

        return $this->render('index/play.html.twig', [
            'board_id' => $id,
            'column_id' => $session->get('column_id'),
            'columnfield_id' => $session->get('columnfield_id'),
        ]);
    }

    /**
     * @Route("/move/{id}/{dir}", name="move")
     */
    public function move($id, $dir, GloService $gloService, SessionInterface $session)
    {
        $column_id = $session->get('column_id');

        if(!in_array($dir, ['up','down', 'left', 'right'])){
            throw new HttpException(500);
        }

        if($session->get('board_id') != $id){
            throw new HttpException(500);
        }

        $cards = $gloService->getCardsInColumn($column_id);

        $gloService->loadState($cards);

        $gloService->move($dir);

        $gloService->deleteColumn($column_id);

        $column_id = $gloService->newColumn('GAME');
        $session->set('column_id', $column_id);

        $cards = $gloService->generateGloCards($column_id);

        $gloService->createBatchCards($cards);

        $gloService->updateScore();

        return new JsonResponse([
            'msg' => 'success',
            'cards' => $cards
        ]);

    }

    /**
     * @Route("/start/{id}", name="start")
     */
    public function start($id, GloService $gloService, SessionInterface $session)
    {
        $column_id = $session->get('column_id');


        if($session->get('board_id') != $id){
            throw new HttpException(500);
        }

        $session->set('move', 0);
        $session->set('score', 0);

        $gloService->restartGame();

        $gloService->deleteColumn($column_id);

        $column_id = $gloService->newColumn('GAME');
        $session->set('column_id', $column_id);

        $cards = $gloService->generateGloCards($column_id);

        $gloService->createBatchCards($cards);

        return new JsonResponse([
            'msg' => 'success',
            'cards' => $cards
        ]);
    }

    /**
     * @Route("/new", name="new")
     */
    public function new(Request $request, SessionInterface $session, GloService $gloService)
    {
        $pat = addslashes($request->get('pat'));
        $session->set('pat', $pat);
        $gloService->setPat($pat);

        $record = $session->get('record');
        if(empty($record)){
            $record = 0;
        }

        $session->set('record', $record);
        $session->set('score', 0);
        $session->set('move', 0);

        //CREATE NEW GAME
        $id = $gloService->newBoard();
        $session->set('board_id',$id);

        //NEW PLAYFIELD
        $gloService->createPlayfield();

        //CREATE COLLUMN GAME
        $column_id = $gloService->newColumn('GAME');
        $session->set('column_id', $column_id);

        //CREATE INITIALS CARDS
        $gloService->createInitialCards($column_id, $record);

        return $this->redirectToRoute('play', ['id' => $id]);
    }

}


