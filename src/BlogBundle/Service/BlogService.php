<?php
/**
 * Created by PhpStorm.
 * User: renshan
 * Date: 16-7-26
 * Time: 下午5:35
 */

namespace BlogBundle\Service;

use BlogBundle\Repository\BlogRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BlogService
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function page($page=1, $limit=10)
    {
        $total = $this->count();

        $maxPage = ceil($total / $limit);

        $page = $page > $maxPage ? $maxPage : $page;

        if($page < 1) {
            $page = 1;
        }

        $offset = ($page-1)*$limit;

        $em = $this->container->get('doctrine')->getManager();

        $qb = $em->createQueryBuilder();

        $qb->add('select', 'b')
            ->add('from', 'BlogBundle:Blog b')
            ->add('orderBy', 'b.id DESC')
            ->setFirstResult( $offset )
            ->setMaxResults( $limit );

        return $qb->getQuery()->getResult();
    }

    public function count()
    {
        $em = $this->container->get('doctrine')->getManager();

        $qb = $em->createQueryBuilder();
        $qb->select('count(b.id)')
            ->from('BlogBundle:Blog', 'b');

        $res = $qb->getQuery()->getResult();

        return $res[0][1];
    }

}