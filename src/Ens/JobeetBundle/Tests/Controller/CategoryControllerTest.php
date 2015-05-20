<?php

namespace Ens\JobeetBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CategoryControllerTest extends WebTestCase
{
    
    public function testShow()
    {
        // Create a new client to browse the application
        $client = static::createClient();

        // Create a new entry in the database
        $crawler = $client->request('GET', '/category/index');
        $this->assertEquals('Ens\JobeetBundle\Controller\CategoryController::showAction',$client->getRequest()->attributes->get('_controller'));
        $this->assertTrue(200===$client->getResponse()->getStatusCode());
    }    
        
    
}
