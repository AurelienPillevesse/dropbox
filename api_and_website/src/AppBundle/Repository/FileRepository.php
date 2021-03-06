<?php

namespace AppBundle\Repository;

use AppBundle\Entity\User;

/**
 * FileRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class FileRepository extends \Doctrine\ORM\EntityRepository
{
    public function getTotalUsedStorageByOwner(User $owner) {
        $usedStorage = $this->createQueryBuilder('f')
                    ->andWhere('f.owner = :owner')
                    ->setParameter('owner', $owner)
                    ->select('SUM(f.size)')
                    ->getQuery()
                    ->getSingleScalarResult();

        if($usedStorage == null) {
            return 0;
        }

        return $usedStorage;
    }
}
