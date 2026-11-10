<?php

namespace Models\Validators;

class BoardValidator
{

    public function isValid($grid)
    {

        //todo - need to add validation that each row has no repeated values
        // try count($grid[]) == count(array_unique($grid[]))
        return !array_diff(array_keys($grid), ['b','i','n','g','o'])
            && !array_diff($grid['b'], range(1,15))
            && count($grid['b']) == 5
            && !array_diff($grid['i'], range(16,30))
            && count($grid['i']) == 5
            && !array_diff($grid['n'], ['free',null,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45])
            && count($grid['n']) == 5
            && !array_diff($grid['g'], range(46,60))
            && count($grid['g']) == 5
            && !array_diff($grid['o'], range(61,75))
            && count($grid['o']) == 5;
    }

}