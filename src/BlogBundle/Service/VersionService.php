<?php
/**
 * Created by PhpStorm.
 * User: renshan
 * Date: 16-8-1
 * Time: 上午11:09
 */

namespace BlogBundle\Service;


use BlogBundle\Entity\Version;
use BlogBundle\Entity\Blog;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class VersionService
{
    protected $container;
    protected $request;

    public function __construct(ContainerInterface $container, RequestStack $requestStack)
    {
        $this->container    = $container;
        $this->request      = $requestStack->getCurrentRequest();
    }

    /**
     * @param Blog $blog
     * @return Version
     */
    public function create(Blog $blog)
    {
        $title      = $this->request->get('title');
        $content    = $this->request->get('content');

        $version_number = count($blog->getVersions()) + 1;

        $timeZone = new \DateTimeZone("Asia/Shanghai");
        $now = new \DateTime('now');
        $now->setTimezone($timeZone);

        $version    = new Version();
        $version->setTitle($title);
        $version->setContent($content);
        $version->setCreatedAt($now);
        $version->setUpdatedAt($now);
        $version->setVersion($version_number);

        $version->setBelongsTo($blog);

        $em = $this->container->get('doctrine')->getManager();
        $em->persist($version);
        $em->flush();

        return $version;
    }

    /**
     * @param Blog $blog
     * @return Version
     */
    public function getVersions(Blog $blog)
    {
        $rep = $this->container->get('doctrine')->getRepository("BlogBundle:Version");

        $versions = $rep->findBy([
            'belongsTo' => $blog
        ]);

        return $versions;
    }

    /**
     * @param $versionId
     * @return Version
     */
    public function getVersion($versionId)
    {
        return $this->container->get('doctrine')->getRepository("BlogBundle:Version")->find($versionId);
    }
}