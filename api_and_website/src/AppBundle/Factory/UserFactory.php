<?php

namespace AppBundle\Factory;

use Doctrine\ORM\EntityManager;
use AppBundle\Entity\File;
use AppBundle\Entity\User;
use AppBundle\Entity\Folder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

class UserFactory
{
    private $em;
    private $container;


    /**
     * UserFactory constructor.
     * @param ContainerInterface $container
     * @param EntityManager $em
     */
    public function __construct(ContainerInterface $container, EntityManager $em)
    {
        $this->em = $em;
        $this->container = $container;
    }


    /**
     * @param User $user
     *
     * Update user used storage
     *
     */
    public function updateStorage(User $user)
    {
        $user->setUsedStorage($this->container->get('file_reader')->getTotalUsedStorageByOwner($user));
        $this->em->persist($user);
        $this->em->flush();
    }
}
