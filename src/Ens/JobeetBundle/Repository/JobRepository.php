<?php

namespace Ens\JobeetBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
/**
 * JobRepository
 *
 * Classe de méthodes relatives à la BD - Utilisées dans le Model
 * repository methods below.
 */
class JobRepository extends EntityRepository
{

    /**
    * Renvoie les offres d'emplois actives
    * @param $category_id
    * @param $max
    *
    */
    public function getActiveJobs($category_id=null, $max=null, $page=null) {
        $qb=$this->createQueryBuilder('j')
                ->where('j.expires_at > :date')
                ->setParameter('date', date('Y-m-d H:i:s', time()))
                ->andWhere('j.is_activated = :activated')
                ->setParameter('activated', 1)
                ->orderBy('j.expires_at','DESC');


        if($max){
            $qb->setMaxResults($max);
        }

        if($page>1){
            $qb->setFirstResult(($page-1)*$max);
        }
        
        if($category_id){
            $qb->andWhere('j.category = :category_id')
                    ->setParameter('category_id', $category_id);

        }
        
        //$query=$qb->getQuery();
        $pagin= new Paginator($qb);
        return $pagin;
        //return $query->getResult();
    }


    /**
    * Renvoie l'offre demandée, si elle est active. Sinon, 404
    *
    */
    public function getActiveJob($id){
        $qb=$this->createQueryBuilder('j')
                ->where('j.id = :id')
                ->setParameter('id',$id)
                ->andWhere('j.expires_at > :date')
                ->setParameter('date',date('Y-m-d H:i:s', time()))
                ->andWhere('j.is_activated = :activated')
                ->setParameter('activated', 1)
                ->setMaxResults(1)
                ->getQuery();

        try{
            $job=$qb->getSingleResult();
        }
        catch(\Doctrine\Orm\NoResultException $e){
            $job=null;
        }

        return $job;

    }
    
    
    public function countActiveJobs($category_id = null){
        $qb = $this->createQueryBuilder('j')
                ->select('count(j.id)')
                ->where('j.expires_at > :date')
                ->setParameter('date', date('Y-m-d H:i:s',time()))
                ->andWhere('j.is_activated = :activated')
                ->setParameter('activated', 1);
        
        if($category_id){
            $qb->andWhere('j.category = :category_id')
                    ->setParameter('category_id', $category_id);
        }
        
        $query= $qb->getQuery();
        return $query->getSingleScalarResult();
    }
    
    
    public function cleanup($days){
        $query=$this->createQueryBuilder('j')
                ->delete()
                ->where('j.is_activated IS NULL')
                ->andWhere('j.created_at < :created_at')
                ->setParameter('created_at', date('Y-m-d',time()-86400*$days))
                ->getQuery();
        
        return $query->execute();
                
        
    }

}
