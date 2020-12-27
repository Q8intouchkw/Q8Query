<?php


namespace Q8Intouch\Q8Query\Filterer\FilterMethods;


use Q8Intouch\Q8Query\Core\Utils;
use Q8Intouch\Q8Query\Filterer\Expression;
use Q8Intouch\Q8Query\Filterer\Filterer;

abstract class ExistenceMethodBase implements \Q8Intouch\Q8Query\Filterer\Filterable
{

    /**
     * @param Expression $expression
     * @return bool
     */
    public function validate($expression): bool
    {
        return count($expression->lexemes) > 1;
    }

    public function filter($query, $expression)
    {
        $lexemes = $expression->lexemes;
        if (count($lexemes) == 2) {
            $query->{$this->getConstrainClosure($expression)}($lexemes[1]);
            return $query;
        }
        $subLexemes = array_slice($lexemes, 1);

        $related = Utils::splitRelatedAndAttribute($lexemes[1]);
        // validate against basic rules
        $subLexemes[0] = $related[1];
        $query->{$this->getConstrainClosure($expression)}($related[0], function ($query) use ($related, $subLexemes) {
            (new Filterer([new Expression('and', $subLexemes)]))->filter($query);
        });
        return $query;
    }

    protected abstract function getConstrainClosure($expression);
}
