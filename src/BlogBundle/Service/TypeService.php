<?php
/**
 * Created by PhpStorm.
 * User: renshan
 * Date: 16-8-3
 * Time: 下午5:08
 */

namespace BlogBundle\Service;


use BlogBundle\Entity\Type;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TypeService
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param $typeId
     * @return Type|null
     */
    public function getType($typeId)
    {
        return $this->container->get('doctrine')->getRepository('BlogBundle:Type')->findOneBy(['id' => $typeId]);
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        $qb = $this->container->get('doctrine')->getManager()->createQueryBuilder();

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

        return $data;
    }

    /**
     * @param $name
     * @param $sort
     * @return Type|bool
     */
    public function createType($name, $sort)
    {
        if ($name == '') {
            return false;
        }

        if ($sort == '') {
            $sort = 1;
        }

        $_type = $this->container->get('doctrine')->getRepository('BlogBundle:Type')->findOneBy(['name' => $name]);

        if ($_type) {
           return $_type;
        }

        $type = new Type();
        $type->setName($name);
        $type->setSort($sort);

        $em = $this->container->get('doctrine')->getManager();
        $em->psersist($type);
        $em->flush();

        return $type;
    }

    public function getBlogNumber($typeId)
    {
        $type = $this->getType($typeId);
        if (!$type) {
            return 0;
        }

        /**
         * @var $qb QueryBuilder
         */
        $qb = $this->container->get('doctrine')->getManager()->createQueryBuilder();

        $qb->select('count(b.id)')
            ->from('BlogBundle:Blog', 'b')
            ->where('b.type = ?1')
            ->setParameter(1, $type);

       $result = $qb->getQuery()->getResult();

        return $result[0][1];

    }
}