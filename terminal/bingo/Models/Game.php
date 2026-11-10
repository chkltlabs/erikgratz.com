<?php

namespace Models;

class Game {

    protected $winCon;
    protected $players;
    protected $calledNums;
    public function __construct($playerCount, $boardsPerPlayer, $winCon = 'any')
    {
        $this->calledNums = [];
        for($i = $playerCount; $i > 0; $i--){
            $this->players[] = new Player($boardsPerPlayer);
        }
    }

    public function findWinningPlayer(){
        foreach($this->players as $p){
            if($p->hasWon()){
                return $p->toString();
            }
        }
        return false;
    }

    public function toString(){
        foreach ($this->players as $p){
            echo $p->toString();
        }
    }

    public function getNextNumber(){
        $range = range(1,75);
        if(!count($this->calledNums)){
            $num = $range[array_rand($range)];
        }else{
            $diff = array_diff( $range, $this->calledNums);
            $num = $diff[array_rand($diff)];
        }
        array_push($this->calledNums, $num);
        return $num;
    }

    public function play(){
        while(!$winner = $this->findWinningPlayer()){
            $num = $this->getNextNumber();
            foreach($this->players as $p){
                $p->addNumCalled($num);
            }
        }
        print $winner;
//        $this->toString();
    }

}