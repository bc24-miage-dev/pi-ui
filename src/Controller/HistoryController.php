<?php

namespace App\Controller;

use App\Entity\UserResearch;
use App\Repository\UserResearchRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HistoryController extends AbstractController
{
    #[Route('/history/{page}', name: 'app_history')]
    public function history(ManagerRegistry $managerRegistry, $page): Response
    {
        $user = $this->getUser();
        $repository = $managerRegistry->getRepository(UserResearch::class);
        $numberResearch = $repository->count(['User' => $user]);
        $history = $repository->findBy(['User' => $user], ['date'=> 'DESC'] , 10 , ($page * 10) - 10);
        
        $numberPage = ceil($numberResearch / 10);

        return $this->render('history/history.html.twig', [
            'history' => $history,
            'numberPage' => $numberPage,
            'page' => $page
        ]);

    }



}
