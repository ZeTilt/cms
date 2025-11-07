<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ArticleRepository $articleRepository, EventRepository $eventRepository): Response
    {
        // Get latest published articles for the homepage
        $blogArticles = $articleRepository->findPublished(5);

        // Get upcoming events for the widget
        $upcomingEvents = $eventRepository->findRecentEventsForWidget(4);

        return $this->render('home/index.html.twig', [
            'blog_articles' => $blogArticles,
            'upcoming_events' => $upcomingEvents,
        ]);
    }
}