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
