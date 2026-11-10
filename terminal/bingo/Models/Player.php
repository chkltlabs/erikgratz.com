<?php

namespace Models;

class Player{

    protected $boards;
    private $name;

    public function __construct($boardCount)
    {
        $this->setName();
        for($i = $boardCount; $i > 0; $i--){
            $this->boards[] = new Board();
        }
    }

    public function hasWon(){
        foreach($this->boards as $b){
            if($b->hasWon()){
                return true;
            }
        }
        return false;
    }

    public function addNumCalled($num){
        foreach($this->boards as $b){
            $b->addNumCalled($num);
        }
    }

    public function toString(){
        //drop name
        $rtn = '';
        $rtn .= $this->name;
//        $rtn .= "\n";
        //drop boards->toString()
        foreach($this->boards as $b){
            $rtn .= $b->toString();
        }

        return $rtn;
    }

    private function setName(){
        $firstname = array(
            'Johnathon',
            'Anthony',
            'Erasmo',
            'Raleigh',
            'Nancie',
            'Tama',
            'Camellia',
            'Augustine',
            'Christeen',
            'Luz',
            'Diego',
            'Lyndia',
            'Thomas',
            'Georgianna',
            'Leigha',
            'Alejandro',
            'Marquis',
            'Joan',
            'Stephania',
            'Elroy',
            'Zonia',
            'Buffy',
            'Sharie',
            'Blythe',
            'Gaylene',
            'Elida',
            'Randy',
            'Margarete',
            'Margarett',
            'Dion',
            'Tomi',
            'Arden',
            'Clora',
            'Laine',
            'Becki',
            'Margherita',
            'Bong',
            'Jeanice',
            'Qiana',
            'Lawanda',
            'Rebecka',
            'Maribel',
            'Tami',
            'Yuri',
            'Michele',
            'Rubi',
            'Larisa',
            'Lloyd',
            'Tyisha',
            'Samatha',
        );

        $lastname = array(
            'Mischke',
            'Serna',
            'Pingree',
            'Mcnaught',
            'Pepper',
            'Schildgen',
            'Mongold',
            'Wrona',
            'Geddes',
            'Lanz',
            'Fetzer',
            'Schroeder',
            'Block',
            'Mayoral',
            'Fleishman',
            'Roberie',
            'Latson',
            'Lupo',
            'Motsinger',
            'Drews',
            'Coby',
            'Redner',
            'Culton',
            'Howe',
            'Stoval',
            'Michaud',
            'Mote',
            'Menjivar',
            'Wiers',
            'Paris',
            'Grisby',
            'Noren',
            'Damron',
            'Kazmierczak',
            'Haslett',
            'Guillemette',
            'Buresh',
            'Center',
            'Kucera',
            'Catt',
            'Badon',
            'Grumbles',
            'Antes',
            'Byron',
            'Volkman',
            'Klemp',
            'Pekar',
            'Pecora',
            'Schewe',
            'Ramage',
        );

        $this->name = $firstname[rand ( 0 , count($firstname) -1)];
        $this->name .= ' ';
        $this->name .= $lastname[rand ( 0 , count($lastname) -1)];

        return $this->name;
    }
}