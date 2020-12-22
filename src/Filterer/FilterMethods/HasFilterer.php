<?php

namespace Q8Intouch\Q8Query\Filterer\FilterMethods;


use Illuminate\Database\Eloquent\Builder;
use Q8Intouch\Q8Query\Core\Utils;
use Q8Intouch\Q8Query\Filterer\Expression;
use Q8Intouch\Q8Query\Filterer\Filterer;

class HasFilterer implements \Q8Intouch\Q8Query\Filterer\Filterable
{

    /**
     * @param Expression $expression
     * @return bool
     */
    public function validate($expression): bool
    {
        return count($expression->lexemes) > 1 && $expression->lexemes[0] == 'has';
    }

    public function filter($query, $expression)
    {
        $lexemes = $expression->lexemes;
        if (count($lexemes) == 2) {
            $query->whereHas($lexemes[1]);
            return $query;
        }
        $subLexemes = array_slice($lexemes, 1);

        $related = Utils::splitRelatedAndAttribute($lexemes[1]);
        // validate against basic rules
        $subLexemes[0] = $related[1];
        $query->{$this->getHasClosure($expression)}($related[0], function ($query) use ($related, $subLexemes) {
            (new Filterer([new Expression('and', $subLexemes)]))->filter($query);
        });
        return $query;
    }

    protected function getHasClosure($expression)
    {
        return [
            'and' => 'whereHas',
            'or' => 'orWhereHas',
        ][$expression->logical];
    }
}
