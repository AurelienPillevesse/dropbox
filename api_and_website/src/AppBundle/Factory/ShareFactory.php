<?php

namespace AppBundle\Factory;

use Doctrine\ORM\EntityManager;
use AppBundle\Entity\File;
use AppBundle\Entity\User;
use AppBundle\Entity\Folder;
use AppBundle\Entity\Share;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

class ShareFactory
{
    private $em;
    private $container;

    /**
     * ShareFactory constructor.
     * @param ContainerInterface $container
     * @param EntityManager $em
     */
    public function __construct(ContainerInterface $container, EntityManager $em)
    {
        $this->em = $em;
        $this->container = $container;
    }


    /**
     * @param Share $share
     * @param Folder $folder
     * @param User $owner
     *
     * Add shared link
     *
     */
    public function addOne(Share $share, Folder $folder, User $owner)
    {
        $share->setOwner($owner);
        $share->setFolder($folder);

        $this->em->persist($share);
        $this->em->flush();
    }
}
