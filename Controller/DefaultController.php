<?php

namespace BiberLtd\Bundle\ProductManagementBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('BiberLtdProductManagementBundle:Default:index.html.twig', array('name' => $name));
    }
}
