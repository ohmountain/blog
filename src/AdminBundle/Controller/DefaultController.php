<?php

namespace AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AdminBundle\Common\ResponseTrait;

class DefaultController extends Controller
{

    use ResponseTrait;

    public function indexAction()
    {
        return $this->render('AdminBundle:Default:index.html.twig');
    }

    public function getTypeAction()
    {
        $data = $this->get('type.manager')->getTypes();

        $this->wrapResult(true, $this->FETCH_TYPE_OK, $data);

        return $this->response;
    }

    public function getTypeNumberAction()
    {
        $number = $this->get('type.manager')->getBlogNumber(1);

        $this->wrapResult(true, $this->FETCH_BLOG_OK, ['number' => $number]);

        return $this->response;
    }

    public function getBlogAction($typeId=0, $page=1, $limit=20)
    {

    }
}
