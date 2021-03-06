<?php

namespace App\Entity\Repository;

/**
 * FreeCompanyRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class FreeCompanyRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Find the total
     *
     * @return mixed
     */
    public function findTotal()
    {
        $qb = $this->createQueryBuilder('fc')
            ->select('count(fc.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
