<?php

namespace Q8Intouch\Q8Query\Associator;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Q8Intouch\Q8Query\Core\Caller;
use Q8Intouch\Q8Query\Core\Defaults;
use Q8Intouch\Q8Query\Core\Exceptions\NoQueryParameterFound;
use Q8Intouch\Q8Query\Core\Exceptions\NoStringMatchesFound;

class Associator
{

    /**
     * @var array
     */
    private $related;

    /**
     * @var boolean
     */
    private $aggressiveLoading;

    /**
     * Associator constructor.
     * @param $related array
     * @param $aggressiveLoading boolean if true it will load all data with ignoring the limits
     */
    public function __construct($related, $aggressiveLoading = false)
    {
        $this->related = $related;
        $this->aggressiveLoading = $aggressiveLoading;
    }

    /**
     * create a filterer using request
     * if null is passed the filtered uses the globals
     *
     * @param Request|null $request
     * @return Associator
     * @throws NoQueryParameterFound
     * @throws NoStringMatchesFound
     */
    public static function createFromRequest(Request $request = null)
    {
        if (!$request)
            $request = Request::createFromGlobals();
        $associatorKey = static::getKey();
        if (!$request->has($associatorKey)) {
            throw new NoQueryParameterFound('Param: ' . $associatorKey . " wasn't found");
        }
        return static::createFromString($request->query($associatorKey));
    }

    protected static function getKey()
    {
        return config('q8-query.associator', 'associate');
    }

    /**
     * create a filterer using a native string without the filter parameter
     * ex: name eq "some string" and id ne 1 or id eq 1
     * the prefix `filter=` is not needed and will cause an exception
     *
     * @param string $s
     * @return Associator
     * @throws NoStringMatchesFound
     */
    public static function createFromString(string $s)
    {


        $related = static::extractParamsFromString($s);
        return new Associator($related);
    }

    /**
     * @param $s
     * @return mixed
     * @throws NoStringMatchesFound
     */
    public static function extractParamsFromString($s)
    {
        if (!preg_match_all(Defaults::$relatedModelRegex, $s, $matches))
            throw new NoStringMatchesFound("No string delimiters found please check the docs");
        return $matches[0];
    }


    /**
     * @param $eloquent Builder
     * @throws \Q8Intouch\Q8Query\Core\Exceptions\MethodNotAllowedException
     * @throws \ReflectionException
     */
    public function associateBuilder($eloquent)
    {
        // TODO call method if it is having an annotation
        foreach ($this->related as $relation) {
            // check first if authorized/having annotation
            $this->associateAggressively($eloquent, $relation);
        }

    }

    /**
     * @param $eloquent
     * @param $relation
     * @throws \ReflectionException
     * @throws \Q8Intouch\Q8Query\Core\Exceptions\MethodNotAllowedException
     */
    protected function associateAggressively($eloquent, $relation)
    {
        if ((new Caller($eloquent))->authorizeCallOrThrow($relation))
            $eloquent->with($relation);
    }

    /**
     * @param $model Model
     * @throws \Q8Intouch\Q8Query\Core\Exceptions\MethodNotAllowedException
     * @throws \ReflectionException
     */
    public function associateModel($model)
    {
        foreach ($this->related as $relation) {
            if ((new Caller($model))->authorizeCallOrThrow($relation))
                $model->load($relation);
        }
    }

}