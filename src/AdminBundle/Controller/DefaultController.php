<?php

namespace AdminBundle\Controller;

use Doctrine\ORM\EntityManager;
use BlogBundle\Entity\Type;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AdminBundle\Common\ResponseTrait;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Cache\RedisCache;

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

        $data = $this->get('blog.manager')->page($typeId, $page, $limit);

        if (count($data) === 0 ||count($data['blogs']) === 0) {
            $this->wrapResult(true, $this->FETCH_BLOG_WITH_ZERO, $data);
        } else {
            $this->wrapResult(true, $this->FETCH_BLOG_OK, $data);
        }

        return $this->response;

        //return $this->render('AdminBundle:Default:index.html.twig');
    }
}
