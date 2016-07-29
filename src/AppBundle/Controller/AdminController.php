<?php
/**
 * Created by PhpStorm.
 * User: renshan
 * Date: 16-7-25
 * Time: 下午5:27
 */

namespace AppBundle\Controller;

use BlogBundle\BlogBundle;
use BlogBundle\Entity\Blog;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use cebe\markdown\GithubMarkdown;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BlogBundle\Service\BlogService;

class AdminController extends Controller
{
    public function indexAction(Request $request)
    {
        $types = $this->getDoctrine()->getRepository('BlogBundle:Type')->findAll();

        /**
         * @var $manager BlogService
         */
        $manager = $this->get('blog.manager');

        $blogs = $manager->page(0);

        return $this->render("AppBundle:Admin:index.html.twig", ['types' => $types, 'blogs' => $blogs]);
    }


    public function postAction(Request $request)
    {
        $title = $request->get('title');
        $content = $request->get('content');

        $title = base64_decode($title);
        $content = base64_decode($content);

        $type = $request->get('type');

        $type = $this->getDoctrine()->getRepository('BlogBundle:Type')->find($type);

        $em = $this->getDoctrine()->getManager();

        $parser = new GithubMarkdown();

        $content = $parser->parse($content);

        $timeZone = new \DateTimeZone("Asia/Shanghai");

        $now = new \DateTime('now');
        $now->setTimezone($timeZone);

        $blog = new Blog();
        $blog->setTitle($title);
        $blog->setContent($content);
        $blog->setCreatedAt($now);
        $blog->setUpdatedAt($now);
        $blog->setType($type);
        $blog->setTrash(false);

        $em->persist($blog);
        $em->flush();

        $response= new Response();
        $response->headers->set('Content-Type', 'application/json');

        if($blog->getId()) {
            $data = [
                'success' => true,
                'blog' => [
                    'id'        => $blog->getId(),
                    'type'      => [
                        'id'    => $type->getId(),
                        'name'  => $type->getName()
                    ],
                    'title'     => $blog->getTitle(),
                    'createdAt' => $blog->getCreatedAt()->setTimezone($timeZone)->format("Y年m月d日 H:i")
                ]
            ];
        } else {
            $data = [
                'success' => false,
                'blog'    => []
            ];
        }

        return
            $response->setContent(json_encode($data));
    }

    private function getBlogs($page, $count)
    {

    }
}