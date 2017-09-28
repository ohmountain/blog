<?php

namespace BlogBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;

use BlogBundle\Entity\PrivatePost;

class PrivateBlog
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function create(string $title, string $body): PrivatePost
    {
        $post = new PrivatePost();

        $post->setTitle($title);
        $post->setBody($body);

        $post->setCreatedAt(new \DateTime());
        $post->setUpdatedAt(new \DateTime());

        $em = $this->container->get('doctrine')->getManager();

        $em->persist($post);
        $em->flush();

        return $post;
    }

    public function getByTitle(string $title)
    {
        return $this->container->get("doctrine")->getManager()->getRepository("BlogBundle\Entity\PrivatePost")->findBy(["title" => $title]);
    }

    public function getById(string $id)
    {
        return  $this->container->get("doctrine")->getManager()->getRepository("BlogBundle\Entity\PrivatePost")->find($id);
    }
}