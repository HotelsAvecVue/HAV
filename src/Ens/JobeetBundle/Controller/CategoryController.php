<?php

namespace Ens\JobeetBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Ens\JobeetBundle\Entity\Category;
use Ens\JobeetBundle\Form\CategoryType;

/**
 * Category controller.
 *
 */
class CategoryController extends Controller
{
    
    /**
     * 
     * @param type $slug
     * @param type $page
     * @return type
     */
    public function showAction($slug, $page){
        $em=$this->getDoctrine()->getManager();
        $category=$em->getRepository('JobeetBundle:Category')->findOneBySlug($slug);
        
        if(!$category){
            throw $this->createNotFoundException('Unable to find Category Entity');
        }
        
        //Récupération du nbr max de jobs à afficher
        $max_jobs=$this->container->getParameter('max_jobs_on_category');
        
        //Compte le nbr de jobs dans la BD
        $jobs_count=$em->getRepository('JobeetBundle:Job')->countActiveJobs($category->getID());
        
        //Renvoie à la vue les paramètres relatifs à la page courante pour pagination.
        $pagination=array(
            'page'  =>  $page,
            'jobs_count'    =>  $jobs_count,
            'max_jobs'  => $max_jobs,
            'route' =>  'cat_show',
            'last_page'   => ceil($jobs_count/$max_jobs)
        );
        
        //Renvoie à la vue les jobs actifs n fonction de la page.
        $category->setActiveJobs($em->getRepository('JobeetBundle:Job')->getActiveJobs($category->getID(),$this->container->getParameter('max_jobs_on_category'),$page));
        return $this->render('JobeetBundle:Category:show.html.twig', array(
            'category'  =>  $category,
            'pagination'    =>  $pagination
        ));
    }
 
}
