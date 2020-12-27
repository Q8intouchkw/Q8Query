<?php

namespace Q8Intouch\Q8Query\Filterer\FilterMethods;


use Illuminate\Database\Eloquent\Builder;
use Q8Intouch\Q8Query\Core\Utils;
use Q8Intouch\Q8Query\Filterer\Expression;
use Q8Intouch\Q8Query\Filterer\Filterer;

class HasFilterer extends ExistenceMethodBase
{

    public function validate($expression): bool
    {
        return parent::validate($expression) && $expression->lexemes[0] == 'has' ;
    }

    protected function getConstrainClosure($expression)
    {
        return [
            'and' => 'whereHas',
            'or' => 'orWhereHas',
        ][$expression->logical];
    }

}
