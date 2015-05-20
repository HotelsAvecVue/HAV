<?php

namespace Ens\JobeetBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class JobControllerTest extends WebTestCase
{
    
    /**
     * 
     */
    public function testIndex()
    {
        // Récupération des paramètres en passant par le kernel
        $kernel = static::createKernel();
        $kernel->boot();

        $max_jobs_on_homepage = $kernel->getContainer()->getParameter('max_jobs_on_homepage');
        $max_jobs_on_category = $kernel->getContainer()->getParameter('max_jobs_on_category');
        
        //echo $max_jobs_on_homepage;

        
        //Création d'un utilisateur virtuel et de la requête qu'il demande
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $this->assertEquals('Ens\JobeetBundle\Controller\JobController::indexAction', $client->getRequest()->attributes->get('_controller'));


        //Test Index : Offres expirées ne sont pas listées
        $this->assertTrue($crawler->filter('.jobs td.position:contains("Expired")')->count()==0);

        
        //Test du maximum d'offres affichées par catégorie sur l'index
        echo $this->assertTrue($crawler->filter('.category_programming tr')->count()<=$max_jobs_on_homepage);

        //Test : Affichage d'un lien vers la catégorie ss'il y a trop d'offres à afficher.
        $this->assertTrue($crawler->filter('.category_design .more_jobs')->count()==0);
        $this->assertTrue($crawler->filter('.category_programming .more_jobs')->count()==1);
        
        //Test : Jobs triés par date
        $this->assertTrue($crawler->filter('.category_programming tr')->first()->filter(sprintf('a[href*="/%d/"]',$this->getMostRecentProgrammingJob()->getId()))->count()==1);
        
        
        //Test : Offres sur la page d'accueil sont sous forme de lien cliquable
        //Récupération du premier Job // Selection du lien // Clic // Test pour voir si on récupère bien les attributs de l'offre
        $job=$this->getMostRecentProgrammingJob();
        $link=$crawler->selectLink('Maintenance')->first()->link();
        $crawler = $client->click($link);
        $this->assertEquals('Ens\JobeetBundle\Controller\JobController::showAction',$client->getRequest()->attributes->get('_controller'));
        $this->assertEquals($job->getCompanySlug(),$client->getRequest()->attributes->get('company'));
        $this->assertEquals($job->getLocationSlug(),$client->getRequest()->attributes->get('location'));
        $this->assertEquals($job->getPositionSlug(),$client->getRequest()->attributes->get('position'));
        $this->assertEquals($job->getId(),$client->getRequest()->attributes->get('id'));
        
        
        //Test : si l'utilisateur demande un job inexistant, renvoie une 404
        $crawler=$client->request('GET','/job/didou/didou-didou/1/didou');
        $this->assertTrue(404 === $client->getResponse()->getStatusCode());
        
        
    }
    
    
    /**
     * Retourne l'offre la plus récente en Programmation
     * Amélioration possible : faire la même fonction qui prend la catégorie en paramètre pour génériser la fonction en getMostRecentJob($cat)
     * @return BDObject Résultat dans la BD
     */
    public function getMostRecentProgrammingJob(){
        $kernel = static::createKernel();
        $kernel->boot();
        $em= $kernel->getContainer()->get('doctrine.orm.entity_manager');
        
        $query = $em->createQuery('SELECT j FROM JobeetBundle:Job j LEFT JOIN j.category c WHERE c.slug= :slug AND j.expires_at > :date ORDER BY j.created_at DESC');
        $query->setParameter('slug', 'programming');
        $query->setParameter('date', date('Y-m-d H:i:s', time()));
        
        $query->setMaxResults(1);
        return $query->getSingleResult();
    }
    
    /**
     * 
     * @return mixed Le résultat sous la forme de esultat SQL
     */
    public function getExpiredJob() {
        $kernel=static::createKernel();
        $kernel->boot();
        $em=$kernel->getContainer()->get('doctrine.orm.entity_manager');
        
        $query=$em->createQuery('SELECT j FROM JobeetBundle:Job j WHERE j.expires_at < :date');
        $query->setParameter('date',date('Y-m-d H:i:s'),time());
        $query->setMaxResults(1);
        return $query->getSingleResult();
    }
    
    /**
     * 
     */
    public function testJobForm(){
        $client=static::createClient();
        $crawler=$client->request('GET','/job/new');
        
        
        $this->assertEquals('Ens\JobeetBundle\Controller\JobController::newAction', $client->getRequest()->attributes->get('_controller'));
        
        //Simulation d'un formulaire rempli
        $form=$crawler->selectButton('Preview your job')->form(array(
            'job[company]' => 'Sensio Labs',
            'job[url]' => 'http://www.sensio.com',
            'job[file]' => __DIR__.'/../../../../../web/bundles/jobeet/images/sensio_labs.gif',
            'job[position]' => 'Developper',
            'job[location]' => 'Atlanta, USA',
            'job[description]' => 'You will work with Symfony to develop websites',
            'job[how_to_apply]' => 'Send me an email',
            'job[email]' => 'contact@sensio.com',
            'job[is_public]' => false,
        ));
        //dump($form);
        
        $client->submit($form);
        $this->assertEquals('Ens\JobeetBundle\Controller\JobController::createAction', $client->getRequest()->attributes->get('_controller'));
        
        //Si le formulaire est soumis, on suit la redirection qui devrait se faire sur previewAction()
        $client->followRedirect();
        $this->assertEquals('Ens\JobeetBundle\Controller\JobController::previewAction',$client->getRequest()->attributes->get('_controller'));
        
        //Test insertion BD
        $kernel = static::createKernel();
        $kernel->boot();
        $em=$kernel->getContainer()->get('doctrine.orm.entity_manager');
        
        $query=$em->createQuery('SELECT count(j.id)
                FROM JobeetBundle:Job j
                 WHERE j.location = :location
                     AND j.is_activated IS NULL
                     AND j.is_public = 0');
        
        $query->setParameter('location', 'Atlanta, USA');
        $this->assertTrue(0<$query->getSingleScalarResult());
        
        //Test si mauvais remplissage du formulaire
        $crawler = $client->request('GET', '/job/new');
        $form=$crawler->selectButton('Preview your job')->form(array(
            'job[company]' => 'Sensio',
            'job[position]' => 'Developper',
            'job[location]' => 'Lyon, France',
            'job[email]' => 'BLABLABLA'
        ));
        $crawler=$client->submit($form);
        
        //Test si 3 erreurs
        //$this->assertTrue($crawler2->filter('.error_list')->count()==1);
        
        //Test si erreur sur la description
        $this->assertTrue($crawler->filter('#description')->siblings()->first()->filter('.error_list')->count()==1);
        
        //Test si erreur sur le "how to apply"
        $this->assertTrue($crawler->filter('#how_to_apply')->siblings()->first()->filter('.error_list')->count()==1);
        
        //Test si erreur sur l'email
        $this->assertTrue($crawler->filter('#email')->siblings()->first()->filter('.error_list')->count()==1);
        
        
        
        
       
        
    }
    
    /**
     * 
     * @param type $values
     * @return type
     */
    public function createJob($values=array(), $publish=false){
        $client=static::createClient();
        $crawler=$client->request('GET', '/job/new');
        $form=$crawler->selectButton('Preview your job')->form(array_merge(array(
            'job[company]' => 'Sensio',
            'job[position]' => 'Admin',
            'job[location]' => 'Chalet, Pays de Galle',
            'job[email]' => 'c@c.com',
            'job[description]' => 'Admin web',
            'job[how_to_apply]' => 'Envoyez votre CV à c[at]c.com',
            'job[is_public]' => false,
            'job[url]' => 'http://www.vt.fr'
        ),$values));
        
        $client->submit($form);
        $client->followRedirect();
        
        if($publish){
            $crawler=$client->getCrawler();
            $form=$crawler->selectButton('Publish')->form();
            $client->submit($form);
            $client->followRedirect();
        }
        
        return $client;
    }

    /**
     * 
     */
    public function testPublishJob(){
        $client=  $this->createJob(array(
            'job[position]' => 'F001'
        ));
        $crawler=$client->getCrawler();
        $form=$crawler->selectButton('Publish')->form();
        $client->submit($form);
        
        $kernel= static::createKernel();
        $kernel->boot();
        $em=$kernel->getContainer()->get('doctrine.orm.entity_manager');
        
        $query=$em->createQuery('SELECT count(j.id) FROM JobeetBundle:Job j WHERE j.position = :position AND j.is_activated=1');
        $query->setParameter('position','F001');
        
        $this->assertTrue(0<$query->getSingleScalarResult());
        
        
    }
    
    
    /**
     * Test pour savoir si le formulaire de suppression d'une offre
     */
    public function testDeleteJob(){
        $client=$this->createJob(array(
            'job[position]' => 'F002'
        ));
        $crawler=$client->getCrawler();
        $form=$crawler->selectButton('Delete')->form();
        $client->submit($form);
        
        $kernel=static::createKernel();
        $kernel->boot();
        
        $em=$kernel->getContainer()->get('doctrine.orm.entity_manager');
        $query=$em->createQuery('SELECT count(j.id) FROM JobeetBundle:Job j WHERE j.position = :position AND j.is_activated=1');
        $query->setParameter('position','F002');
        
        $this->assertTrue(0==$query->getSingleScalarResult());
    }
    
    
    public function testExtendJob(){
        
        //Création d'un Job
        $client=$this->createJob(array(
            'job[position]' => 'F004'
        ), true);
        
        //Crawler = fenêtre / Objet
        $crawler=$client->getCrawler();
        
        //Test si un bouton Extend est présent sur le formulaire affiché
        $this->assertTrue($crawler->filter('input[type=submit]:contains("Extend")')->count()==0);
        
        //Création d'un Job
        $client=$this->createJob(array(
            'job[position]' => 'F005'
        ), true);
        //Kernel => simule interaction BD : récupération du job créé
        $kernel=static::createKernel();
        $kernel->boot();
        $em=$kernel->getContainer()->get('doctrine.orm.entity_manager');
        $job=$em->getRepository('JobeetBundle:Job')->findOneByPosition('F005');
        
        //Modification de l'attribut Expires_at 
        $job->setExpiresAt(new \DateTime);
        $em->flush();
        
        //Go à la page de prévisualisation du job
        $crawler=$client->request('GET', sprintf('/job/%s/%s/%s/%s', $job->getCompanySlug(), $job->getLocationSlug(), $job->getToken(), $job->getPositionSlug()));
        $crawler=$client->getCrawler();
        $form=$crawler->selectButton('Extends for 30 days')->form();
        $client->submit($form);
        
        $job=$this->getJobByPosition('F005');
        
        //Test si la date expire de nouveau dans 30 jours
        $this->assertTrue($job->getExpiresAt()->format('y/m/d')==date('y/m/d', time()+(30*86400)));
        
    }
    
    
    public function getJobByPosition($position){
        $kernel=static::createKernel();
        $kernel->boot();
        $em=$kernel->getContainer()->get('doctrine.orm.entity_manager');
        
        $query=$em->createQuery('SELECT j FROM JobeetBundle:Job j WHERE j.position = :position');
        $query->setParameter('position',$position);
        $query->setMaxResults(1);
        
        return $query->getSingleResult();  
    }
    

    
}
