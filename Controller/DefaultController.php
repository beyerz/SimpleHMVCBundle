<?php

namespace Beyerz\SimpleHMVCBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('BeyerzSimpleHMVCBundle:Default:index.html.twig');
    }
}
