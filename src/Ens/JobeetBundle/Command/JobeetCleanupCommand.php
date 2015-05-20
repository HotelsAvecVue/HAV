<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Ens\JobeetBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Ens\JobeetBundle\Entity\Job;

/**
 * Description of JobeetCleanupCommand
 *
 * @author dragesco
 */
class JobeetCleanupCommand extends ContainerAwareCommand {
    
    protected function configure(){
        $this->setName('jobeet:cleanup')
                ->setDescription('Cleanup Jobeet database')
                ->addArgument('days',  InputArgument::OPTIONAL, 'The email', 90);
            
                
    }
    
    
    protected function execute(InputInterface $input, OutputInterface $output){
        $days=$input->getArgument('days');
        $em=$this->getContainer()->get('doctrine')->getManager();
        $nb=$em->getRepository('JobeetBundle:Job')->cleanup($days);
        
        $output->writeln(sprintf('Removed %d stale jobs', $nb));
    }
}
