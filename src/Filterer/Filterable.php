<?php


namespace Q8Intouch\Q8Query\Filterer;


use Illuminate\Database\Eloquent\Builder;

interface Filterable
{
    /**
     * @param $expression Expression
     * @return bool
     */
    public function validate($expression) : bool ;

    /**
     * @param $query Builder
     * @param $expression Expression
     * @return Builder
     */
    public function filter($query, $expression);
}
