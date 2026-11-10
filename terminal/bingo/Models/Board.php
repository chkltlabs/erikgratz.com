<?php

namespace Models;

use Models\Validators\BoardValidator;

class Board {

    protected $grid;
    protected $winningSets;
    protected $calledNums;

    public function __construct($grid = null){
        $this->calledNums = ['free'];
        if(is_array($grid)){
            $this->validator = new BoardValidator();
            if($this->validator->isValid($grid)){
                $this->grid = $grid;
            }
        }else{
            $this->grid = self::generateGrid();
        }
        $this->calculateWinningSets();
    }

    public static function generateGrid(){
        //should return a bingo board in the form of a 2d array,
        // keyed on strings 'b','i','n','g','o'.
        // May also want to make it pass validation
        $bRange = range(1,15);
        $iRange = range(16,30);
        $nRange = range(31,45);
        $gRange = range(46,60);
        $oRange = range(61,75);
        shuffle($bRange);
        shuffle($iRange);
        shuffle($nRange);
        shuffle($gRange);
        shuffle($oRange);
        $nSet = array_slice($nRange, 0,5);
        sort($nSet);
        $nSet[2] = 'free';
        $b = array_slice($bRange,0, 5);
        $i = array_slice($iRange,0, 5);
        $n = $nSet;
        $g = array_slice($gRange,0, 5);
        $o = array_slice($oRange,0, 5);
        sort($b);
        sort($i);
        sort($g);
        sort($o);
        return [
            'b' => $b,
            'i' => $i,
            'n' => $n,
            'g' => $g,
            'o' => $o,
        ];
    }

    public function calculateWinningSets(){
        $this->winningSets = [
            'colb' => [],
            'coli' => [],
            'coln' => [],
            'colg' => [],
            'colo' => [],
            'row1' => [],
            'row2' => [],
            'row3' => [],
            'row4' => [],
            'row5' => [],
            'diag1' => [],
            'diag2' => [],
        ];
        foreach($this->grid as $key => $column){
            $this->winningSets['col' . $key] = $column;
            array_push($this->winningSets['row1'], $column[0]);
            array_push($this->winningSets['row2'], $column[1]);
            array_push($this->winningSets['row3'], $column[2]);
            array_push($this->winningSets['row4'], $column[3]);
            array_push($this->winningSets['row5'], $column[4]);
            switch ($key){
                case 'b':
                    array_push($this->winningSets['diag1'], $column[0]);
                    array_push($this->winningSets['diag2'], $column[4]);
                    break;
                case 'i':
                    array_push($this->winningSets['diag1'], $column[1]);
                    array_push($this->winningSets['diag2'], $column[3]);
                    break;
                case 'n':
                    array_push($this->winningSets['diag1'], $column[2]);
                    array_push($this->winningSets['diag2'], $column[2]);
                    break;
                case 'g':
                    array_push($this->winningSets['diag1'], $column[3]);
                    array_push($this->winningSets['diag2'], $column[1]);
                    break;
                case 'o':
                    array_push($this->winningSets['diag1'], $column[4]);
                    array_push($this->winningSets['diag2'], $column[0]);
                    break;
            }
        }
    }

    public function addNumCalled($num){
        array_push($this->calledNums, $num);
    }

    public function hasWon(){
        foreach($this->winningSets as $winKey => $array){
            if(!array_diff($array, $this->calledNums)){
                return $winKey;
            }
        }
        return false;
    }

    public function toString(){
        $rtn = "\n";
//        foreach($this->grid as $letter => $row){
//            $rtn .= $letter;
//            foreach($row as $key => $val){
//                if(in_array($val, $this->calledNums)){
//                    $row[$key] = "\033[32m $val \033[0m";
//                }
//            }
//            $rtn .= implode(',', $row);
//            $rtn .= "\n";
//        }
        $rtn .= "\033[31m b, i, n, g, o\033[0m\n";
        foreach(range(0,4) as $key){
            foreach(['b','i','n','g','o'] as $letter) {
                $num = $this->grid[$letter][$key];
                if ($num == 'free'){
                    $rtn .= "\033[32mfr\033[0m";
                }else{
                    if ($num < 10) {
                        $rtn .= ' ';
                    }
                    if (in_array($num, $this->calledNums)){
                        $rtn .= "\033[32m$num\033[0m";
                    }else{
                        $rtn .= $num;
                    }
                }
                $rtn .= ',';
            }
            $rtn .= "\n";
        }
        if($winKey = $this->hasWon()){
            $rtn .= "\033[32m BINGO! $winKey \033[0m \n";
        }
        $rtn .= "\n";
        return $rtn;
    }

}