<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Resource;
use App\Form\SearchType;
use App\Entity\UserResearch;



#[Route('/search')]
class SearchController extends AbstractController
{
#[Route('/', name: 'app_search')]
    public function search(Request $request, ManagerRegistry $doctrine): Response
    {
            $form = $this->createForm(SearchType::class);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                $id = $data->getId();

                $resource = $doctrine->getRepository(Resource::class)->find($id);

                return $this->redirect($this->generateUrl('app_search_result', ['id' => $id]));
            }
            return $this->render('search/search.html.twig', [
                'form' => $form->createView()
            ]);
    }

    #[Route('/{id}', name: 'app_search_result')]
    public function result(int $id, ManagerRegistry $doctrine, Request $request): Response
    {
        $form = $this->createForm(SearchType::class);
        $form -> handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $repository = $doctrine->getRepository(UserResearch::class);
            $history = $repository->findBy(['User' => $user]);
            if (count($history) > 0) {
                $history[0]->setDate(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                $entityManager = $doctrine->getManager();
                $entityManager->persist($history[0]);
                $entityManager->flush();
            }
            else{
                $userResearch = new UserResearch();
                $userResearch->setUser($user);
                $userResearch->setResource($doctrine->getRepository(Resource::class)->find($id));
                $userResearch->setDate(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                $entityManager = $doctrine->getManager();
                $entityManager->persist($userResearch);
                $entityManager->flush();
            }

            $data = $form->getData();
            $id = $data->getId();
            return $this->redirect($this->generateUrl('app_search_result', ['id' => $id]));
        }

        $resource = $doctrine->getRepository(Resource::class)->find($id);
        if (!$resource) {
            $this->addFlash('error', 'No resource found');
            return $this->redirectToRoute('app_search');
        }
        return $this->render('search/result.html.twig', [
            'form' => $form -> createView(),
            'resource' => $resource
        ]);
    }
}




