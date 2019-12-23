<?php

namespace AppBundle\Reader;

use Doctrine\ORM\EntityRepository;

class FileReader
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
    public function getOneById($id)
    {
        return $this->repository->findOneById($id);
    }

    public function getOneByIdAndOwner($id, $owner)
    {
        return $this->repository->findOneBy(['id' => $id, 'owner' => $owner]);
    }

    public function getByOwnerAndParent($owner, $parent = null)
    {
        return $this->repository->findBy(['owner' => $owner, 'parent' => $parent], ['created' => 'DESC']);
    }

    public function getByParentAndOwnerAndName($parent, $owner, $name)
    {
        return $this->repository->findBy(['owner' => $owner, 'parent' => $parent, 'name' => $name]);
    }

    public function getByParent($parent)
    {
        return $this->repository->findBy(['parent' => $parent]);
    }

    public function getTotalUsedStorageByOwner($owner)
    {
        return $this->repository->getTotalUsedStorageByOwner($owner);
    }
}
