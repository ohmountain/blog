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
     * @param $type
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function page($type, $page=1, $limit=10)
    {
        $total = $this->count();

        $limit = $limit < 1 ? 10 : $limit;

        $maxPage = ceil($total / $limit);

        $page = $page > $maxPage ? $maxPage : $page;

        if($page < 1) {
            $page = 1;
        }

        $offset = ($page-1)*$limit;

        $em = $this->container->get('doctrine')->getManager();
        $qb = $em->createQueryBuilder();

        if($type instanceof Type) {
            $qb->add('select', 'b')
                ->add('from', 'BlogBundle:Blog b')
                ->where('b.trash = false')
                ->where('b.type = ?1')
                ->add('orderBy', 'b.id DESC')
                ->setFirstResult( $offset )
                ->setMaxResults( $limit )
                ->setParameter(1, $type);
        } else {
            $qb->add('select', 'b')
                ->add('from', 'BlogBundle:Blog b')
                ->where('b.trash = false')
                ->add('orderBy', 'b.id DESC')
                ->setFirstResult( $offset )
                ->setMaxResults( $limit );
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param null $type
     * @return integer
     */
    public function count($type=null)
    {
        $em = $this->container->get('doctrine')->getManager();

        $qb = $em->createQueryBuilder();

        if ($type instanceof Type) {
            $qb->select('count(b.id)')
                ->from('BlogBundle:Blog', 'b')
                ->where('b.type = ?1')
                ->setParameter(1, $type);
        } else {
            $qb->select('count(b.id)')
                ->from('BlogBundle:Blog', 'b');
        }


        $res = $qb->getQuery()->getResult();

        return $res[0][1];
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