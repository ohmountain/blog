<?php
/**
 * Created by PhpStorm.
 * User: renshan
 * Date: 16-7-26
 * Time: 下午5:35
 */

namespace BlogBundle\Service;

use BlogBundle\Entity\Blog;
use BlogBundle\Entity\Type;
use BlogBundle\Entity\Version;
use Doctrine\Common\Cache\RedisCache;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use cebe\markdown\GithubMarkdown;

class BlogService
{
    protected $container;
    protected $request;

    public function __construct(ContainerInterface $container, RequestStack $requestStack)
    {
        $this->container = $container;
        $this->request   = $requestStack->getCurrentRequest();
    }

    /**
     * @param $typeId
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function page($typeId, $page=1, $limit=10)
    {


        $typeCacheKey = 'type_' . $typeId;

        $cacheDriver = $this->container->get('cache.manager');

        $type = unserialize($cacheDriver->get($typeCacheKey));

        if (!$type) {
            $type = $this->container->get('doctrine')->getRepository('BlogBundle:Type')->findOneBy(['id' => $typeId]);
        }


        if (!$type && intval($typeId) !== 0) {
            return [];
        }

        $cacheDriver->set($typeCacheKey, serialize($type));

        $total = $this->count($type);

        $limit = $limit < 1 ? 10 : $limit;

        $maxPage = ceil($total / $limit);

        $page = $page > $maxPage ? $maxPage : $page;

        if($page < 1) {
            $page = 1;
        }

        $offset = ($page-1)*$limit;


        if ($type instanceof Type) {
            $cacheKey = 'blog_type_' . $type->getId() . '_page_' . $page . '_limit_' . $limit;
        } else {
            $cacheKey = 'blog_type_all_page_' . $page . '_limit_' . $limit;
        }



        $result = $cacheDriver->get($cacheKey);

        if ($result) {
            return $result;
        }

        $em = $this->container->get('doctrine')->getManager();
        $qb = $em->createQueryBuilder();


        if($type instanceof Type) {
            $qb->select('b.id, b.title, b.createdAt')
                ->addSelect('t.id as type_id, t.name as type_name')
                ->addSelect('v.id as version_id v.name as version_number')
                ->add('from', 'BlogBundle:Blog b')
                ->where('b.type = ?1')
                ->join('b.type', 't', 'WITH', 't.id = b.type')
                ->join('b.version', 'v', 'v.id = b.version')
                ->setFirstResult( $offset )
                ->setMaxResults( $limit )
                ->setParameter(1, $type);
        } else {
            $qb->select('b.id, b.title, b.createdAt')
                ->addSelect('t.id as type_id, t.name as type_name')
                ->addSelect('v.id as version_id, v.version as version_number')
                ->add('from', 'BlogBundle:Blog b')
                ->join('b.type', 't', 'WITH', 't.id = b.type')
                ->join('b.version', 'v', 'v.id = b.version')
                ->setFirstResult( $offset )
                ->setMaxResults( $limit );
        }

        $blogs = $qb->getQuery()->getArrayResult();

        $data = [
            'total' => $total,
            'page' => $page,
            'maxPage' => $maxPage,
            'blogs' => $blogs
        ];

        $cacheDriver->set($cacheKey, $data);

        return $data;
    }

    /**
     * @param null $type
     * @return integer
     */
    public function count($type)
    {
        if ($type == null) {
            $countKey = 'count_type_all';
        } else {
            $countKey = "count_type_{$type->getId()}";
        }

        $count = $this->container->get('cache.manager')->get($countKey);

        if (!$count) {

            $em = $this->container->get('doctrine')->getManager();

            $qb = $em->createQueryBuilder();

            if ($type instanceof Type) {
                $qb->select('count(b.id)')
                    ->from('BlogBundle:Blog', 'b')
                    ->where('b.type = ?1')
                    ->setParameter(1, $type->getId());
            } else {
                $qb->select('count(b.id)')
                    ->from('BlogBundle:Blog', 'b');
            }


            $res = $qb->getQuery()->getResult();

            $count = $res[0][1];

            $this->container->get('cache.manager')->set($countKey, $count);

        }

        return $count;
    }

    /**
     * @return Blog
     */
    public function create()
    {
        $title = $this->request->get('title');
        $content = $this->request->get('content');

        $type = $this->request->get('type');

        $type = $this->container->get('doctrine')->getRepository('BlogBundle:Type')->find($type);

        $em = $this->container->get('doctrine')->getManager();

        $parser = new GithubMarkdown();

        $parsed = $parser->parse($content);

        $timeZone = new \DateTimeZone("Asia/Shanghai");

        $now = new \DateTime('now');
        $now->setTimezone($timeZone);

        $blog = new Blog();
        $blog->setTitle($title);
        $blog->setContent($parsed);
        $blog->setCreatedAt($now);
        $blog->setUpdatedAt($now);
        $blog->setType($type);
        $blog->setTrash(false);

        $em->persist($blog);
        $em->flush();

        /**
         * @var $versionManager VersionService
         */
        $versionManager = $this->container->get('version.manager');
        $version = $versionManager->create($blog);
        $blog->setVersion($version);

        $em->persist($blog);
        $em->flush($blog);

        return $blog;
    }

    /**
     * @param Blog $blog
     * @param Version $version
     * @return bool
     */
    public function switchVersion(Blog $blog, Version $version)
    {
        $em = $this->container->get('doctrine')->getManager();

        $_blog = $version->getBelongsTo();

        // 传入的版本不属于这条blog
        if($_blog->getId() !== $blog->getId()) {
            return false;
        }

        $blog->setVersion($version);

        $parser = new GithubMarkdown();
        $parsed = $parser->parse($version->getContent());

        $blog->setContent($parsed);

        $em->persist($blog);
        $em->flush();

        return true;

    }

}