<?php

namespace Q8Intouch\Q8Query\Filterer\FilterMethods;


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
