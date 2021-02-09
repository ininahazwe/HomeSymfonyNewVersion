<?php

namespace App\Controller;

use App\Repository\CalendarRepository;
use App\Repository\CategoriesRepository;
use App\Repository\ProjectsRepository;
use App\Repository\UsersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    /**
     * @Route("/admin", name="admin")
     */
    public function index(ProjectsRepository $projects, CategoriesRepository $categoriesRepository, CalendarRepository $calendarRepository, UsersRepository $usersRepository)
    {
        return $this->render('admin/index.html.twig', [
            'project' => $projects->findAll(),
            'category' => $categoriesRepository->findAll(),
            'calendar' => $calendarRepository->findAll(),
            'user' => $usersRepository->findAll()
        ]);
    }
}
