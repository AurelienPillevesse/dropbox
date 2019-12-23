<?php

namespace AppBundle\Reader;

use Doctrine\ORM\EntityRepository;

class ShareReader
{
    private $repository;

    /**
     * MemeReader constructor.
     *
     * @param EntityRepository $repository
     */
    public function __construct(EntityRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param $id
     *
     * @return null|object
     */
    public function getOneByHash($hash)
    {
        return $this->repository->findOneBy(['hash' => $hash]);
    }

    public function getByOwnerAndFolder($owner, $folder)
    {
        return $this->repository->findBy(['owner' => $owner, 'folder' => $folder]);
    }
}
