<?php

namespace App\Controller\Gridview;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Landing page of the gridview demo. Unlike the entity pages it renders no grid,
 * just a welcome message inside the shared EasyAdmin-style shell
 * ({@see templates/gridview/layout.html.twig}). The "Gridview" logo and the
 * sidebar Dashboard item both point here (see GridviewMenuExtension).
 */
class DashboardController extends AbstractController
{
    #[Route('/gridview', name: 'gridview_dashboard')]
    public function index(): Response
    {
        return $this->render('gridview/dashboard.html.twig');
    }
}
