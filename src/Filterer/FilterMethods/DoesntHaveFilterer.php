<?php

namespace Q8Intouch\Q8Query\Filterer\FilterMethods;


use Illuminate\Database\Eloquent\Builder;
use Q8Intouch\Q8Query\Core\Utils;
use Q8Intouch\Q8Query\Filterer\Expression;
use Q8Intouch\Q8Query\Filterer\Filterer;

class DoesntHaveFilterer extends ExistenceMethodBase
{

    public function validate($expression): bool
    {
        return parent::validate($expression) && $expression->lexemes[0] == 'doesntHave' ;
    }

    protected function getConstrainClosure($expression)
    {
        return [
            'and' => 'whereDoesntHave',
            'or' => 'orWhereDoesntHave',
        ][$expression->logical];
    }

}
