<?php

namespace App\Controller;

use App\Entity\Images;
use App\Entity\Projects;
use App\Form\ProjectsType;
use App\Form\SearchProjectType;
use App\Repository\CategoriesRepository;
use App\Repository\ProjectsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @Route("/projects")
 */
class ProjectsController extends AbstractController
{
    /**
     * @Route("/", name="projects_index", methods={"GET", "POST"})
     * @param ProjectsRepository $projectsRepository
     * @param Request $request
     * @return Response
     */
    public function index(ProjectsRepository $projectsRepository, CategoriesRepository $categoriesRepository, Request $request): Response
    {
        $projects = $projectsRepository->findAll();
        $categories = $categoriesRepository->findAll();
        $form = $this->createForm(SearchProjectType::class);
        $search = $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $projects = $projectsRepository->search(
                $search->get('words')->getData(),
                $search->get('category')->getData(),
            );
        }

        $user = $this->getUser();

        return $this->render('projects/index.html.twig', [
            'projects' => $projects,
            'user' => $user,
            'categories' => $categories,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/new", name="projects_new", methods={"GET","POST"})
     * @param Request $request
     * @param SluggerInterface $slugger
     * @return Response
     */
    public function new(Request $request, SluggerInterface $slugger): Response
    {
        $project = new Projects();
        $user = $this->getUser();
        $form = $this->createForm(ProjectsType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // On récupère les images transmises
            $images = $form->get('images')->getData();

            // On boucle sur les images
            foreach($images as $image){
                // On génère un nouveau nom de fichier
                $fichier = md5(uniqid()) . '.' . $image->guessExtension();

                // On copie le fichier dans le dossier uploads
                $image->move(
                    $this->getParameter('uploads_directory'),
                    $fichier
                );

                // On stocke l'image dans la base de données (son nom)
                $img = new Images();
                $img->setName($fichier);
                $project->addImage($img);

            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($project);
            $entityManager->flush();

            return $this->redirectToRoute('projects_index');
            }

        return $this->render('projects/new.html.twig', [
            'project' => $project,
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{slug}-{id}", name="projects_show", requirements={"slug": "[a-z0-9\-]*"})
     * @param Projects $project
     * @param string $slug
     * @return Response
     */
    public function show(Projects $project, string $slug, CategoriesRepository $categoriesRepository): Response
    {
        $user = $this->getUser();
        $categories = $categoriesRepository->findAll();

        if ($project->getSlug() !== $slug) {
            return $this->redirectToRoute('projects_show', [
                'id' => $project->getId(),
                'slug' => $project->getSlug()
            ], 301);
        }

        return $this->render('projects/show.html.twig', [
            'project' => $project,
            'user' => $user,
            'category' => $categories
        ]);
    }

    /**
     * @Route("/youown", name="projects_perso", methods={"GET"})
     * @return Response
     */
    public function showProjectbyUser() :Response
    {
        $user = $this->getUser();
        $projectsRepository = $this->getDoctrine()->getRepository(Projects::class);

        $projects = $projectsRepository->findAll();

        return $this->render('projects/perso.html.twig',[
            'projects' => $projects,
            'user' => $user
        ]);
    }

    /**
     * @Route("/{id}/edit", name="projects_edit", methods={"GET","POST"})
     * @param Request $request
     * @param Projects $project
     * @return Response
     */
    public function edit(Request $request, Projects $project): Response
    {
        $form = $this->createForm(ProjectsType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // On récupère les images transmises
            $images = $form->get('images')->getData();

            // On boucle sur les images
            foreach($images as $image){
                // On génère un nouveau nom de fichier
                $fichier = md5(uniqid()) . '.' . $image->guessExtension();

                // On copie le fichier dans le dossier uploads
                $image->move(
                    $this->getParameter('uploads_directory'),
                    $fichier
                );

                // On stocke l'image dans la base de données (son nom)
                $img = new Images();
                $img->setName($fichier);
                $project->addImage($img);
            }

            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'Project infos updated !');
            return $this->redirectBack('projects_index');
        }

        return $this->render('projects/edit.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="projects_delete", methods={"DELETE"})
     * @param Request $request
     * @param Projects $project
     * @return Response
     */
    public function delete(Request $request, Projects $project): Response
    {
        if ($this->isCsrfTokenValid('delete'.$project->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($project);
            $entityManager->flush();
        }

        return $this->redirectToRoute('projects_index');
    }

    /**
     * @Route("/supprime/image/{id}", name="projects_delete_image", methods={"DELETE"})
     */
    public function deleteImage(Images $image, Request $request){
        $data = json_decode($request->getContent(), true);

        // On vérifie si le token est valide
        if($this->isCsrfTokenValid('delete'.$image->getId(), $data['_token'])){
            // On récupère le nom de l'image
            $nom = $image->getName();
            // On supprime le fichier
            unlink($this->getParameter('uploads_directory').'/'.$nom);

            // On supprime l'entrée de la base
            $em = $this->getDoctrine()->getManager();
            $em->remove($image);
            $em->flush();

            // On répond en json
            return new JsonResponse(['success' => 1]);
        }else{
            return new JsonResponse(['error' => 'Token Invalide'], 400);
        }
    }

    /**
     * @Route("/", name="projects_fullsearch", methods={"GET", "POST"})
     * @param ProjectsRepository $projectsRepository
     * @param Request $request
     * @return Response
     */
    public function fullsearch(ProjectsRepository $projectsRepository, Request $request): Response
    {
        $projects = $projectsRepository->findAll();
        $form = $this->createForm(SearchProjectType::class);
        $search = $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $projects = $projectsRepository->search(
                $search->get('words')->getData(),
                $search->get('category')->getData()
            );
        }

        $user = $this->getUser();

        return $this->render('projects/full-search.html.twig', [
            'projects' => $projects,
            'user' => $user,
            'form' => $form->createView()
        ]);
    }



}
