<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Ens\JobeetBundle\Utils;

/**
 * Description of Jobeet
 *
 * @author Grotte
 */
class Jobeet {
    
    
    /**
     * Formate une chaine de caractère afin de lui retirer des caractères non compatibles avec l'URL (" ", ",", etc.)
     * @param String $text Le texte à formater
     * @return String Le texte formaté
     */
    static public function slugify($text) {

        //Cas d'un texte vide
        //Replace les caractères non lettrés (lettres + chiffres) par des "-"
        $text = preg_replace('#[^\\pL\d]+#u', '-', $text);

        $text =trim($text, '-');
        
        if(function_exists('iconv')){
            $text=  iconv('UTF-8', 'us-ascii//TRANSLIT', $text);
        }
        
        $text=  strtolower($text);

        $text=  preg_replace('#[^-\w]+#', '', $text);
        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

}
