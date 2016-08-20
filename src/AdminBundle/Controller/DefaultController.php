<?php

namespace AdminBundle\Controller;

use BlogBundle\Entity\Blog;
use BlogBundle\Entity\Version;
use cebe\markdown\GithubMarkdown;
use Doctrine\ORM\EntityManager;
use BlogBundle\Entity\Type;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AdminBundle\Common\ResponseTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Cache\RedisCache;

class DefaultController extends Controller
{

    use ResponseTrait;

    public function indexAction()
    {
        return $this->render('AdminBundle:Default:index.html.twig');
    }

    /**
     * @return Response
     * 获得所有分类
     */
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

    /**
     * @param int $typeId
     * @param int $page
     * @param int $limit
     * @return Response
     * 获取文章
     */
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

    /**
     * @param $id
     * @return Response
     * 返回blogId对应的所有版本
     */
    public function preEditAction($id)
    {
        $blogManager = $this->getDoctrine()->getRepository('BlogBundle:Blog');

        /**
         * @var $blog Blog
         */
        $blog = $blogManager->find($id);

        if (!$blog) {
            $this->wrapResult(true, $this->FETCH_BLOG_FAIL, []);

            return $this->response;
        }

        $type = $blog->getType();

        $typeArr = [
            'id' => $type->getId(),
            'name' => $type->getName()
        ];

        $versions = $this->container->get('version.manager')->getVersions($blog);

        $versionsArr = [];

        /**
         * @var $version Version
         */
        foreach ($versions as $version) {
            $tmp = [];
            $tmp['id'] = $version->getId();
            $tmp['version'] = $version->getVersion();
            $tmp['title'] = $version->getTitle();
            $tmp['content'] = $version->getContent();

            $versionsArr[] = $tmp;
        }


        $blogArr = [
            'id' => $blog->getId(),
            'title' => $blog->getTitle(),
            'version' => $blog->getVersion()->getId(),
            'type' => $typeArr,
            'versions' => $versionsArr
        ];

        $this->wrapResult(true, $this->FETCH_BLOG_OK, $blogArr);

        return $this->response;
    }

    /**
     * @param Request $request
     * @return Response
     * 发布文章
     */
    public function postAction(Request $request)
    {
        if (strtoupper($request->getMethod()) === "POST") {

            $title = $request->get('title');
            $type = $request->get('type');
            $content = $request->get('content');

            $typeId = $type;
            $type = $this->get('type.manager')->getType($type);

            if (!$type) {
                return $this->response->setContent(json_encode([
                    'ok' => false,
                    'message' => 'Type dose not exists'
                ]));
            }

            $parser = new GithubMarkdown();
            $parsedContent = $parser->parse($content);

            $timezone = new \DateTimeZone("Asia/Shanghai");
            $datetime = new \DateTime();
            $datetime->setTimezone($timezone);

            $blog = new Blog();
            $blog->setContent($parsedContent);
            $blog->setCreatedAt($datetime);
            $blog->setTitle($title);
            $blog->setTrash(false);
            $blog->setType($type);
            $blog->setUpdatedAt($datetime);

            $em = $this->getDoctrine()->getManager();
            $em->persist($blog);
            $em->flush();

            $version = new Version();
            $version->setTitle($title);
            $version->setContent($content);
            $version->setCreatedAt($datetime);
            $version->setUpdatedAt($datetime);
            $version->setVersion(1);
            $version->setBelongsTo($blog);

            $em->persist($version);
            $em->flush();

            $blog->setVersion($version);
            $em->persist($blog);
            $em->flush();

            $allBlogCache = 'blog_of_type_0';
            $typeCache = 'blog_of_type_' . $typeId;

            // 清除redis缓存
            $this->get('cache.manager')->clearAll();

            return $this->response->setContent(json_encode([
                'ok' => true,
                'message' => 'Blog Created'
            ]));
        }

        $types = $this->get('type.manager')->getTypes();

        return $this->render('AdminBundle:Default:post.html.twig', ['types' => $types]);
    }

    /**
     * @param $blogId
     * @return Response
     * 把文章移入回收站
     */
    public final function deleteBlogAction($blogId)
    {
        $blogManager = $this->get('blog.manager');

        $success = $blogManager->removeBlog($blogId);

        if (!$success) {
            return $this->response->setContent(json_encode([
                'ok' => false,
                'message' => 'Blog dose not exists'
            ]));
        }

        return $this->response->setContent(json_encode([
            'ok' => true,
            'message' => 'Blog has deleted'
        ]));
    }

    public function postNewVersionAction(Request $request)
    {
        $blogId = $request->get('blog_id');

        $blog = $this->get('blog.manager')->getBlog($blogId);

        if (!$blog) {
            $this->response->setContent(json_encode([
                'ok' => false,
                'message' => 'Blog dost not exists'
            ]));
        }

        $versionService = $this->get('version.manager');
        $version = $versionService->create($blog);

        return $this->response->setContent(json_encode([
            'ok' => true,
            'message' => 'New version has created'
        ]));

    }

    /**
     * @param Request $request
     * @return Response
     * 切换版本
     */
    public function changeBlogVersionAction(Request $request)
    {
        $blogId = $request->get('blog_id');
        $versionId = $request->get('version_id');

        $blogManager = $this->get('blog.manager');
        $blog = $blogManager->getBlog($blogId);

        $versionManager = $this->get('version.manager');
        $version = $versionManager->getVersion($versionId);

        $success = $blogManager->switchVersion($blog, $version);

        if ($success) {

            $this->get('cache.manager')->clearAll();

            return $this->response->setContent(json_encode([
                'ok' => true,
                'message' => 'Version has changed'
            ]));
        }

        return $this->response->setContent(json_encode([
            'ok' => false,
            'message' => 'Version has not changed'
        ]));
    }
}
