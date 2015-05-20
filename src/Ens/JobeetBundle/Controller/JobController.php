<?php

namespace Ens\JobeetBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Ens\JobeetBundle\Entity\Job;
use Ens\JobeetBundle\Form\JobType;

/**
 * Job controller.
 *
 */
class JobController extends Controller
{

    /**
     * Lists all Job entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $categories=$em->getRepository('JobeetBundle:Category')->getWithJobs();
        
        foreach ($categories as $category) {
            $category->setActiveJobs($em->getRepository('JobeetBundle:Job')->getActiveJobs($category->getID(),$this->container->getParameter('max_jobs_on_homepage')));
            $category->setMoreJobs($em->getRepository('JobeetBundle:Job')->countActiveJobs($category->getID())-$this->container->getParameter('max_jobs_on_homepage'));
        }
        
        

        return $this->render('JobeetBundle:Job:index.html.twig', array(
            'categories' => $categories
        ));
    }
    /**
     * Creates a new Job entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Job();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($entity);
            //var_dump($em);
            //var_dump($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('ens_job_preview', array(
                'company' => $entity->getCompanySlug(),
                'location' => $entity->getLocationSlug(),
                'token' => $entity->getToken(),
                'position' => $entity->getPositionSlug()
                    )));
        }

        return $this->render('JobeetBundle:Job:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Job entity.
     *
     * @param Job $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Job $entity)
    {
        $form = $this->createForm(new JobType(), $entity, array(
            'action' => $this->generateUrl('ens_job_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Job entity.
     *
     */
    public function newAction()
    {
        $entity = new Job();
        $entity->setType('full-time');
        $form   = $this->createCreateForm($entity);

        return $this->render('JobeetBundle:Job:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Job entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('JobeetBundle:Job')->getActiveJob($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Job entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('JobeetBundle:Job:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Job entity.
     *
     */
    public function editAction($token)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('JobeetBundle:Job')->findOneByToken($token);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Job entity.');
        }
        /**if($entity->getIsActivated()){
            throw $this->createNotFoundException('Job is activated and cannot be edited');
        }*/
        
        $editForm = $this->createForm(new JobType(),$entity);
        $deleteForm = $this->createDeleteForm($token);

        return $this->render('JobeetBundle:Job:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a Job entity.
    *
    * @param Job $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    public function createEditForm(Job $entity)
    {
        $form = $this->createForm(new JobType(), $entity, array(
            'action' => $this->generateUrl('ens_job_update', array('token' => $entity->getToken())),
            'method' => 'PUT',
        ));


        return $form;
    }
    /**
     * Edits an existing Job entity.
     *
     */
    public function updateAction(Request $request, $token)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('JobeetBundle:Job')->findOneByToken($token);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Job entity.');
        }

        $deleteForm = $this->createDeleteForm($token);
        $editForm = $this->createForm(new JobType(),$entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();            

            return $this->redirect($this->generateUrl('ens_job_preview', array(
                'token' => $entity->getToken(),
                'company' => $entity->getCompanySlug(),
                'location' => $entity->getLocationSlug(),
                'position' => $entity->getPositionSlug()
                    )));
        }

        return $this->render('JobeetBundle:Job:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a Job entity.
     *
     */
    public function deleteAction(Request $request, $token)
    {
        $form = $this->createDeleteForm($token);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('JobeetBundle:Job')->findOneByToken($token);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Job entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('ens_job'));
    }

    /**
     * Creates a form to delete a Job entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    /**private function createDeleteForm($token)
    {
        return $this->createFormBuilder(array('token' => $token))
            ->setAction($this->generateUrl('ens_job_delete', array('token' => $token)))
            ->add('token','hidden')
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }
     * 
     */
    
    
    public function previewAction($token){
        $em= $this->getDoctrine()->getManager();
        $entity=$em->getRepository('JobeetBundle:Job')->findOneByToken($token);
        
        if(!$entity){
            throw $this->createNotFoundException('Unable to find Job Entity.');
        }
        
        $deleteForm=$this->createDeleteForm($entity->getId());
        $publishForm=$this->createPublishForm($entity->getToken());
        $extendForm=$this->createExtendForm($token);
        
        return $this->render('JobeetBundle:Job:show.html.twig',array(
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
            'publish_form' => $publishForm->createView(),
            'extends_form' => $extendForm->createView()
        ));
        
    }
    
    
   
    public function createPublishForm($token){
        return $this->createFormBuilder(array(
            'token' => $token
            ))
            ->add('token','hidden')
            ->getForm()
        ;
    }
    
    
    public function publishAction(Request $request, $token){
        $form=$this->createPublishForm($token);
        $form->handleRequest($request);
        
        if($form->isValid()){
            $em=$this->getDoctrine()->getManager();
            $entity=$em->getRepository('JobeetBundle:Job')->findOneByToken($token);
            
            if(!$entity){
                throw $this->createNotFoundException('Unable to find Job entity.');
            }
            
            $entity->publish();
            $em->persist($entity);
            $em->flush();
            
            //$this->getFlashBag()->set('notice','Your job is now online for 30 days');
        }
        
        return $this->redirect($this->generateUrl('ens_job_preview', array(
            'company' => $entity->getCompanySlug(),
            'location' => $entity->getLocationSlug(),
            'token' => $entity->getToken(),
            'position' => $entity->getPositionSlug()
        )));
    }
    
    
    private function createDeleteForm($token){
        return $this->createFormBuilder(array(
            'token' => $token
        ))
                ->add('token','hidden')
                ->getForm();
    }
    
    
    public function extendAction(Request $request, $token){
        $form=$this->createPublishForm($token);
        $form->handleRequest($request);
        if($form->isValid()){
            $em=$this->getDoctrine()->getManager();
            $entity=$em->getRepository('JobeetBundle:Job')->findOneByToken($token);
        }
        
        if(!$entity){
            throw $this->createNotFoundException('Unable to find Job entity');
        }
        if(!$entity->extends()){
            throw $this->createNotFoundException('Unable to extends this Job');
        }
        
        $em->persist($entity);
        $em->flush();
        
        $this->get('session')->set('notice','Your job is now extend until'.$entity->getExpiresAt()->format('m/d/y'));
        
        $this->redirect($this->generateURL('ens_job_preview', array(
            'token' => $entity->getToken(),
            'company' => $entity->getCompanySlug(),
            'location' => $entity->getLocationSlug(),
            'position' => $entity->getPositionSlug()
        )));
        
    }
    
    public function createExtendForm($token){
        return $this->createFormBuilder(array(
            'token' => $token
        ))
                ->add('token', 'hidden')
                ->getForm();
    }
    
}
