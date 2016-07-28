<?php
/**
 * Created by PhpStorm.
 * User: renshan
 * Date: 16-7-26
 * Time: 下午5:35
 */

namespace BlogBundle\Service;

use BlogBundle\Entity\Type;
use BlogBundle\Repository\BlogRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BlogService
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

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

}