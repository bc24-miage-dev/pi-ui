<?php

namespace App\Controller;

use App\Entity\Resource;
use App\Entity\ResourceName;
use App\Form\EquarrisseurAnimalAbattageFormType;
use App\Form\ResourceOwnerChangerType;
use App\Repository\ResourceRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pro/equarrisseur')]
class EquarrisseurController extends AbstractController
{
    #[Route('/', name: 'app_equarrisseur_index')]
    public function index(): Response
    {
        return $this->render('pro/equarrisseur/index.html.twig');
    }

    #[Route('/acquisition', name: 'app_equarrisseur_acquire')]
    public function acquisition(Request $request, ManagerRegistry $doctrine): Response
    {
        $this->denyAccessUnlessGranted('ROLE_EQUARRISSEUR');

        $form = $this->createForm(ResourceOwnerChangerType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $id = $data->getId();

            $resource = $doctrine->getRepository(Resource::class)->find($id);
            if (!$resource || $resource->getResourceName()->getResourceCategory()->getCategory() != 'ANIMAL') {
                $this->addFlash('error', 'Ce tag NFC ne correspond pas à un animal');
                return $this->redirectToRoute('app_equarrisseur_acquire');
            }

            $resource->setCurrentOwner($this->getUser());
            $entityManager = $doctrine->getManager();
            $entityManager->persist($resource);
            $entityManager->flush();
            $this->addFlash('success', 'L\'animal a bien été enregistré');
            return $this->redirectToRoute('app_equarrisseur_acquire');
        }
        return $this->render('pro/equarrisseur/acquire.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/list/{category}', name: 'app_equarrisseur_list')]
    public function list(ManagerRegistry $doctrine, String $category) : Response
    {
        $user = $this->getUser();
        $this->denyAccessUnlessGranted( attribute: 'ROLE_EQUARRISSEUR');
        $repository = $doctrine->getRepository(Resource::class);
        $resources = $repository->findByOwnerAndResourceCategory($user, strtoupper($category));
        return $this->render('pro/equarrisseur/list.html.twig',
            ['resources' => $resources ]
        );
    }

    #[Route('/specific/{id}', name: 'app_equarrisseur_job')]
    public function job(ManagerRegistry $doctrine, $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_EQUARRISSEUR');

        $resource = $doctrine->getRepository(Resource::class)->find($id);
        if (!$resource || $resource->getCurrentOwner() != $this->getUser()){
            $this->addFlash('error', 'Ressource introuvable');
            return $this->redirectToRoute('app_equarrisseur_list');
        }
        if ($resource->getResourceName()->getResourceCategory()->getCategory() == 'ANIMAL'){
            return $this->render('pro/equarrisseur/job.html.twig', [
                'resource' => $resource,
                'category' => 'ANIMAL'
            ]);
        } else {
            return $this->render('pro/equarrisseur/job.html.twig', [
                'resource' => $resource,
                'category' => 'CARCASSE'
            ]);
        }
    }

    #[Route('/equarrir/{id}', name: 'app_equarrisseur_equarrir')]
    public function equarrir(ManagerRegistry $doctrine, Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_EQUARRISSEUR');

        $resource = $doctrine->getRepository(Resource::class)->find($id);
        if (!$resource || $resource->getCurrentOwner() != $this->getUser() ||
            $resource->getResourceName()->getResourceCategory()->getCategory() != 'ANIMAL'){

            $this->addFlash('error', 'Il y a eu un problème, veuillez contacter un administrateur');
            return $this->redirectToRoute('app_equarrisseur_list');
        }

        $newCarcasse = $this->createChildResource($doctrine, $resource);
        $newCarcasse->setResourceName($doctrine->getRepository(ResourceName::class)
            ->findOneBy(['name' => 'Carcasse de ' . $resource->getResourceName()->getName()]));

        $resource->setIsLifeCycleOver(true);

        $form = $this->createForm(EquarrisseurAnimalAbattageFormType::class, $newCarcasse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $doctrine->getManager();
            $entityManager->persist($resource);
            $entityManager->persist($newCarcasse);
            $entityManager->flush();
            $this->addFlash('success', 'L\'animal a bien été abattu, une carcasse a été créée');
            return $this->redirectToRoute('app_equarrisseur_index');
        }
        return $this->render('pro/equarrisseur/equarrir.html.twig', [
            'form' => $form->createView()
        ]);

    }

    #[Route('/decoupe/{id}', name: 'app_equarrisseur_decoupe')]
    public function decoupe(ManagerRegistry $doctrine, Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_EQUARRISSEUR');

        $resource = $doctrine->getRepository(Resource::class)->find($id);
        if (!$resource || $resource->getCurrentOwner() != $this->getUser() ||
            $resource->getResourceName()->getResourceCategory()->getCategory() != 'CARCASSE'){

            $this->addFlash('error', 'Il y a eu un problème, veuillez contacter un administrateur');
            return $this->redirectToRoute('app_equarrisseur_list');
        }

        $newHalfCarcasse = $this->createChildResource($doctrine, $resource);
        $newHalfCarcasse->setResourceName($doctrine->getRepository(ResourceName::class)
            ->findOneBy(['name' => 'Demi-' . $resource->getResourceName()->getName()]));

        $newHalfCarcasse2 = $this->createChildResource($doctrine, $resource);
        $newHalfCarcasse2->setResourceName($doctrine->getRepository(ResourceName::class)
            ->findOneBy(['name' => 'Demi-' . $resource->getResourceName()->getName()]));

        $resource->setIsLifeCycleOver(true);

        //Classic form because two different entities must be processed at once
        if ($request->isMethod('POST')) {
            $newHalfCarcasse->setId($request->request->get('tag1'));
            $newHalfCarcasse2->setId($request->request->get('tag2'));
            $newHalfCarcasse->setWeight($request->request->get('weight1'));
            $newHalfCarcasse2->setWeight($request->request->get('weight2'));

            $entityManager = $doctrine->getManager();
            $entityManager->persist($newHalfCarcasse);
            $entityManager->persist($newHalfCarcasse2);
            $entityManager->flush();

            $this->addFlash('success', 'Cette carcasse a bien été découpée');
            return $this->redirectToRoute('app_equarrisseur_index');
        }
        return $this->render('pro/equarrisseur/slice.html.twig');
    }


    private function createChildResource(ManagerRegistry $doctrine, Resource $resource): Resource
    {
        $newChildResource = new Resource();
        $newChildResource->setCurrentOwner($this->getUser());
        $newChildResource->setDate(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
        $newChildResource->setIsLifeCycleOver(false);
        $newChildResource->setIsContamined(false);
        $newChildResource->setPrice(0);
        $newChildResource->setOrigin($this->getUser()->getProductionSite());
        $newChildResource->setDescription('');
        $newChildResource->addComponent($resource);
        return $newChildResource;
    }
}
