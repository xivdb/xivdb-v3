<?php

namespace App\Entity\Repository;

/**
 * SyncServerRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SyncServerRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Get the lowest populated server
     */
    public function getLowestPopulatedServer($names)
    {
        $qbs = $this->createQueryBuilder('ss');
        
        $qbs->select('ss')
            ->where('ss.name IN (:names)')
            ->setParameter('names', $names)
            ->orderBy('ss.count', 'asc');
        
        return $qbs->getQuery()->getResult()[0];
    }
    
    /**
     * Get server population
     */
    public function getServerPopulation($names)
    {
        $qbs = $this->createQueryBuilder('ss');
    
        $qbs->select('ss')
            ->where('ss.name IN (:names)')
            ->setParameter('names', $names)
            ->orderBy('ss.name', 'asc');
    
        return $qbs->getQuery()->getResult();
    }
}
