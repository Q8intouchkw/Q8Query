<?php

namespace Q8Intouch\Q8Query\Filterer\FilterMethods;


class DoesntHaveFilterer extends ExistenceMethodBase
{

    public function validate($expression): bool
    {
        return parent::validate($expression) && $expression->lexemes[0] == 'doesntHave';
    }

    protected function getConstrainClosure($expression)
    {
        return [
            'and' => 'whereDoesntHave',
            'or' => 'orWhereDoesntHave',
        ][$expression->logical];
    }

}
