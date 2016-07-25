<?php
/**
 * Created by PhpStorm.
 * User: renshan
 * Date: 16-7-25
 * Time: 下午5:27
 */

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AdminController extends Controller
{
    public function indexAction()
    {
       return $this->render("AppBundle:Admin:index.html.twig");
    }
}