<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route('/test/language', name: 'test_language')]
    public function testLanguage(): Response
    {
        return $this->render('test_language.html.twig');
    }
}