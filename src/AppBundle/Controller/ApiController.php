<?php
/**
 * Created by PhpStorm.
 * User: renshan
 * Date: 16-7-28
 * Time: 上午8:53
 */

namespace AppBundle\Controller;


use BlogBundle\Entity\Blog;
use BlogBundle\Service\BlogService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiController extends Controller
{
    private $response;

    public function __construct()
    {
        $this->response = new Response();
        $this->response->headers->set('Access-Control-Allow-Origin', '*');
        $this->response->headers->set('Content-Type', 'application/json');
    }


    public function typeAction()
    {
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();

        $qb->add('select', 't')
            ->add('from', 'BlogBundle:Type t')
            ->add('orderBy', 't.sort DESC');
        $types = $qb->getQuery()->getResult();

        $data = [];
        $data['count'] = count($types);

        $typesArr = [];
        foreach ($types as $type) {
            $tmp = [];
            $tmp['id'] = $type->getId();
            $tmp['name'] = $type->getName();
            $tmp['sort'] = $type->getSort();

            $typesArr[] = $tmp;
        }

        $data['types'] = $typesArr;

        $data = json_encode($data);
        return $this->response->setContent($data);
    }

    public function blogAction(Request $request)
    {
        $type = $request->get('type');
        $page = intval($request->get('page'));
        $limit = intval($request->get('limit'));
        $limit = $limit < 1 ? 10 : $limit;

        $type = $this->getDoctrine()->getRepository('BlogBundle:Type')->findOneBy(['id' => $type]);


        /**
         * @var $blogManager BlogService
         */
        $blogManager = $this->get('blog.manager');

        $count = $blogManager->count($type);

        $blogs = $blogManager->page($type, $page, $limit);

        $maxPage = ceil($count / $limit);


        $data = [];

        $data['page'] = $page;
        $data['maxPage'] = $maxPage;
        $data['limit'] = $limit;

        $blogsArr = [];

        /**
         * @var $blog Blog
         */
        foreach ($blogs as $blog) {
            $tmp = [];

            $tmp['id'] = $blog->getId();
            $tmp['title'] = $blog->getTitle();
            $tmp['content'] = $blog->getContent();
            $tmp['time'] = $blog->getUpdatedAt()->format('Y年m月d日 H:i');
            $tmp['type'] = [
                'id' => $blog->getType()->getId(),
                'name' => $blog->getType()->getName()
            ];

            $blogsArr[] = $tmp;
        }

        $data['blogs'] = $blogsArr;

        $data = json_encode($data);

        return $this->response->setContent($data);
    }
}