<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Ens\JobeetBundle\Tests\Utils;

use Ens\JobeetBundle\Utils\Jobeet;

class JobeetTest extends \PHPUnit_Framework_TestCase {

    public function testSlugify() {

        if (function_exists('iconv')) {
            $this->assertEquals('sensio', Jobeet::slugify('Sensio'));
            $this->assertEquals('sensio-labs', Jobeet::slugify('sensio labs'));
            $this->assertEquals('sensio-labs', Jobeet::slugify('sensio   labs'));
            $this->assertEquals('paris-france', Jobeet::slugify('paris,france'));
            $this->assertEquals('sensio', Jobeet::slugify('  sensio'));
            $this->assertEquals('sensio', Jobeet::slugify('sensio  '));
            $this->assertEquals('n-a', Jobeet::slugify(''));
            $this->assertEquals('n-a', Jobeet::slugify(' - '));
            $this->assertEquals('developpeur-web', Jobeet::slugify('Développeur web'));
        }
    }

}
