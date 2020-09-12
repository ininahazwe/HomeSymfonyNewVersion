<?php

namespace App\Controller;

use App\Entity\Projects;
use App\Form\ProjectsType;
use App\Repository\ProjectsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @Route("/")
 */
class ProjectsController extends AbstractController
{
    /**
     * @Route("/", name="projects_index", methods={"GET"})
     */
    public function index(ProjectsRepository $projectsRepository): Response
    {
        $user = $this->getUser();

        return $this->render('projects/index.html.twig', [
            'projects' => $projectsRepository->findAll(),
            'user' => $user
        ]);
    }

    /**
     * @Route("/projects/new", name="projects_new", methods={"GET","POST"})
     */
    public function new(Request $request, SluggerInterface $slugger): Response
    {
        $project = new Projects();
        $user = $this->getUser();
        $form = $this->createForm(ProjectsType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile $photoFile */
            $photoFile = $form->get('photoUpload')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $photoFile->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $project->setPhoto($newFilename);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($project);
                $entityManager->flush();

                return $this->redirectToRoute('projects_index');
            }
        }

        return $this->render('projects/new.html.twig', [
            'project' => $project,
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/projects/{slug}-{id}", name="property.show", requirements={"slug": "[a-z0-9\-]*"})
     * @param Projects $project
     * @return Response
     */
    public function show(Projects $project, string $slug): Response
    {
        $user = $this->getUser();

        if ($project->getSlug() !== $slug) {
            return $this->redirectToRoute('projects/show.html.twig', [
                'id' => $project->getId(),
                'slug' => $project->getSlug()
            ], 301);
        }

        return $this->render('projects/show.html.twig', [
            'project' => $project,
            'user' => $user
        ]);
    }

    /**
     * @Route("/projects/youown", name="projects_perso", methods={"GET"})
     */
    public function showProjectbyUser(Request $request) :Response
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
     * @Route("/projects/{id}/edit", name="projects_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Projects $project): Response
    {
        $form = $this->createForm(ProjectsType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'Project successfully updated');

            return $this->redirectToRoute('projects_index');
        }

        return $this->render('projects/edit.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/projects/{id}", name="projects_delete", methods={"DELETE"})
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

}
