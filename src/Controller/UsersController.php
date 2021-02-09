<?php

namespace App\Controller;

use App\Entity\Images;
use App\Entity\Users;
use App\Form\ResetPasswordRequestFormType;
use App\Form\UsersType;
use App\Repository\CalendarRepository;
use App\Repository\UsersRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @Route("/users")
 */
class UsersController extends AbstractController
{
    /**
     * @Route("/", name="users_index", methods={"GET"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function index(UsersRepository $usersRepository): Response
    {
        return $this->render('users/index.html.twig', [
            'users' => $usersRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="users_new", methods={"GET","POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function new(Request $request, SluggerInterface $slugger, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $user = new Users();
        $form = $this->createForm(UsersType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            //Encryptage
            $password = $passwordEncoder->encodePassword($user, $user->getPassword());
            $user->setPassword($password);

            /** @var UploadedFile $photoFile */
            $photoFile = $form->get('avatarUpload')->getData();

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
                $user->setAvatar($newFilename);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('users_index');
        }

        return $this->render('users/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}-{lastname}", name="users_show", methods={"GET"})
     * @param UsersRepository $usersRepository
     * @return Response
     */
    public function show(UsersRepository $usersRepository, $id, CalendarRepository $calendarRepository): Response
    {
        $calendar = $calendarRepository->findAll();
        $user = $usersRepository->find($id);
        return $this->render('users/show.html.twig', [
            'user' => $user,
            'calendars' => $calendar,
        ]);
    }

    /**
     * @Route("/profile/{id}-{lastname}", name="users_show_perso", methods={"GET"})
     */
    public function showPerso(UsersRepository $usersRepository): Response
    {
        $user = $usersRepository->findAll();
        return $this->render('users/profile_perso.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * @Route("/{id}/edit", name="users_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Users $user, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $form = $this->createForm(UsersType::class, $user, [
                'isAdmin' => $this->getUser()->isAdmin(),
                'isLogin' => true,
            ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            //Encryptage
            $password = $passwordEncoder->encodePassword($user, $user->getPassword());
            $user->setPassword($password);

            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Infos updated !');
            return $this->redirectToRoute('users_show', ['id' => $user->getId(), 'lastname' => $user->getLastname()]);
        }

        $form = $this->createForm(ResetPasswordRequestFormType::class, null, [
            'action' => $this->generateUrl('app_user_account_profile_modify_password'),
            'attr' => [
                'class' => 'mt-3'
            ]
        ]);

        return $this->render('users/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'modifyPasswordForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="users_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Users $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($user);
            $entityManager->flush();
        }

        $session = new Session();
        $session->invalidate();

        $this->addFlash('success', 'Account deleted !');
        return $this->redirectToRoute('app_logout');
    }

    /**
     * @Route("/supprime/image/{id}", name="users_delete_image", methods={"DELETE"})
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
     * @Route (name="app_user_account_profile_modify_password", methods={"GET", "POST"})
     */
    public function modifyPassword()
    {

    }
}
