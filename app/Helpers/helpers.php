<?php

use Illuminate\Database\ClassMorphViolationException;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\Relation;

if (! function_exists('getMorphAliasForClass')) {
    function getMorphAliasForClass($class)
    {
        $morphMap = Relation::morphMap();

        if (! empty($morphMap) && in_array($class, $morphMap)) {
            return array_search($class, $morphMap, true);
        }

        if ($class === Pivot::class) {
            return $class;
        }

        if (Relation::requiresMorphMap()) {
            throw new ClassMorphViolationException($class);
        }

        return $class;
    }
}

if (! function_exists('getClassForMorphAlias')) {
    function getClassForMorphAlias($alias)
    {
        $morphMap = Relation::morphMap();

        if (! empty($morphMap) && in_array($alias, array_keys($morphMap))) {
            return $morphMap[$alias];
        }

        if (Relation::requiresMorphMap()) {
            throw new Exception('No class for alias: '.$alias);
        }
    }
}

// accidentally referenced this func from php 8.5.
// TODO: Once upgraded to 8.5, drop this
if (! function_exists('array_first')) {
    function array_first(array $array)
    {
        foreach ($array as $value) {
            return $value;
        }

        return null;
    }
}
