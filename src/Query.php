<?php


namespace Q8Intouch\Q8Query;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Q8Intouch\Q8Query\Associator\Associator;
use Q8Intouch\Q8Query\Core\Caller;
use Q8Intouch\Q8Query\Core\Exceptions\ModelNotFoundException;
use Q8Intouch\Q8Query\Core\Exceptions\NoQueryParameterFound;
use Q8Intouch\Q8Query\Core\Exceptions\NoStringMatchesFound;
use Q8Intouch\Q8Query\Core\Exceptions\NotAuthorizedException;
use Q8Intouch\Q8Query\Core\Exceptions\ParamsMalformedException;
use Q8Intouch\Q8Query\Core\Utils;
use Q8Intouch\Q8Query\Core\Validator;
use Q8Intouch\Q8Query\Filterer\Filterer;
use Q8Intouch\Q8Query\Orderer\Orderer;
use Q8Intouch\Q8Query\Scoper\Scoper;
use Q8Intouch\Q8Query\Selector\Selector;

class Query
{

    /**
     * @var array
     */
    public $params;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var string
     */
    protected $model;

    /**
     * @var string
     */
    protected $returnType;

    protected static $singleRelationPrefixes = [
        'HasOne',
        'BelongsTo'
    ];

    /**
     *
     * Query constructor.
     * @param $params array with the following pattern ['Model', {id}, ....]
     * @throws ParamsMalformedException
     */
    public function __construct($params)
    {
        $this->validator = new Validator();

        $this->validator->validateParams($params);

        if (!$this->validator->getResult() || !count($params))
            throw new ParamsMalformedException($this->validator->getMessage());
        $this->params = $params;
    }

    /**
     * @param $path
     * @return Query
     * @throws ParamsMalformedException
     */
    public static function QueryFromPathString($path)
    {
        $query = new static(explode('/', $path));
        return $query;
    }

    /**
     * @return mixed
     */
    public function getValidator()
    {
        return $this->validator;
    }


    /**
     * @return Model|Collection
     * @throws Core\Exceptions\MethodNotAllowedException
     * @throws ModelNotFoundException
     * @throws NoStringMatchesFound
     * @throws \ReflectionException
     * @throws NotAuthorizedException
     */
    public function build()
    {

        return
            $this->prefetchOperations(
                $this->authorize(
                    $this->getModelQuery()
                )
            );

    }

    /**
     * @return Builder|Model
     * @throws Core\Exceptions\MethodNotAllowedException
     * @throws ModelNotFoundException
     * @throws \ReflectionException
     */
    public function getModelQuery()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->attachQueriesFromParams(
            $this->getModel()::query()
        );
    }

    /**
     * @return Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|object|null
     * @throws Core\Exceptions\MethodNotAllowedException
     * @throws ModelNotFoundException
     * @throws NoStringMatchesFound
     * @throws \ReflectionException
     */
    public function get()
    {
        return
            $this->postFetchOperations(
                $this->fetchIfBuilder(
                    $this->build()
                )
            );
    }

    /**
     *
     * @return boolean
     */
    public function isSingleObjectPath()
    {
        return !(count($this->params) % 2);
    }

    /**
     * @return string
     * @throws ModelNotFoundException
     */
    public function getModel()
    {
        if (!$this->model)
            return Utils::getModel($this->params[0]);
        else
            return $this->model;
    }

    /**
     * attach queries according url segments
     *
     * @param $model Model
     * @return Builder|Model
     * @throws Core\Exceptions\MethodNotAllowedException
     * @throws \ReflectionException
     */
    public function attachQueriesFromParams($model)
    {
        for ($i = 1; $i < count($this->params); $i++) {
            $model = $this->updateQueryByParamSection($i, $model);
        }
        return $model;
    }


    /**
     * @param $index
     * @param $query Builder|Model
     * @return Builder|Model
     * @throws \ReflectionException
     * @throws Core\Exceptions\MethodNotAllowedException
     */
    protected function updateQueryByParamSection($index, $query)
    {
        return $index % 2
            ? $query->whereKey($this->params[$index])->first()
            : (new Caller($query))->call($this->params[$index], $this->returnType);
    }

    /**
     * @noinspection PhpDocRedundantThrowsInspection
     * @param $eloquent
     * @return mixed
     * @throws Core\Exceptions\MethodNotAllowedException
     * @throws NoStringMatchesFound
     * @throws \ReflectionException
     */
    protected function prefetchOperations($eloquent)
    {
        if ($eloquent instanceof Model) {
            $this->tryExecuteQuery(
                Selector::class,
                'createFromRequest',
                'selectFromModel',
                $eloquent);
            $this->tryExecuteQuery(
                Associator::class,
                'createFromRequest',
                'associateModel',
                $eloquent);
        } else {
            $this->tryExecuteQuery(
                Filterer::class,
                'createFromRequest',
                'filter',
                $eloquent
            );
            $this->tryExecuteQuery(
                Orderer::class,
                'createFromRequest',
                'order',
                $eloquent
            );
            $this->tryExecuteQuery(
                Associator::class,
                'createFromRequest',
                'associateBuilder',
                $eloquent
            );
            $this->tryExecuteQuery(
                Selector::class,
                'createFromRequest',
                'selectFromQuery',
                $eloquent
            );
            $this->tryExecuteQuery(
                Scoper::class,
                'createFromRequest',
                'scope',
                $eloquent
            );
        }
        return $eloquent;
    }

    /**
     * @param $class
     * @param $method
     * @param $tailMethod
     * @param array $args
     * @return mixed
     */
    protected function tryExecuteQuery($class, $method, $tailMethod, ...$args)
    {
        try {
            return call_user_func_array([$class::{$method}(), $tailMethod], $args);
        } catch (NoQueryParameterFound $e) {
        }
    }

    /**
     * @param Model|array $model
     * @return array|Model
     */
    protected function postFetchOperations($model)
    {
        return $model;
    }

    /**
     * @param Builder|Model $model
     * @return Builder|Builder[]|\Illuminate\Database\Eloquent\Collection|Model
     */
    protected function fetchIfBuilder($model)
    {
        return $this->isModel($model) ? $model : $model->get();
    }

    /**
     * @param null $perPage
     * @param array $columns
     * @param string $pageName
     * @param null $page
     * @return Builder|Builder[]|\Illuminate\Database\Eloquent\Collection|Model
     * @throws Core\Exceptions\MethodNotAllowedException
     * @throws ModelNotFoundException
     * @throws NoStringMatchesFound
     * @throws \ReflectionException
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        return
            $this->paginateIfBuilder(
                $this->build(),
                $perPage,
                $columns,
                $pageName,
                $page
            );
    }

    /**
     * @param Builder|Model $model
     * @param array $args
     * @return Builder|Builder[]|\Illuminate\Database\Eloquent\Collection|Model
     * @throws ModelNotFoundException
     */
    protected function paginateIfBuilder($model, ...$args)
    {
        if (!$model)
            throw new ModelNotFoundException('The required model cant be found or not directly related to the nested resource');
        if ($this->isModel($model))
            return $model;
        else return $this->isSingleRelation()
            ? $model->first()
            : call_user_func_array([$model, 'paginate'], $args);
    }

    protected function isModel($eloquent)
    {
        return $eloquent instanceof Model;
    }

    protected function isSingleRelation()
    {
        return in_array(substr(strrchr($this->returnType, "\\"), 1), self::$singleRelationPrefixes);
    }

    /**
     * @param $eloquent Model|Builder
     * @return Model|Builder
     * @throws NotAuthorizedException
     */
    protected function authorize($eloquent)
    {
        $result = true;
        if ($this->checkableEloquent($eloquent))
            $result = Gate::check($this->getAuthAbility($eloquent), $this->getModelInstance($eloquent));

        if (!$result)
            throw new NotAuthorizedException('Auth user not allowed to view this resource', 1);
        return $eloquent;
    }

    /**
     * @param $eloquent Model|Builder
     * @return Model
     */
    protected function getModelInstance($eloquent): Model
    {
        return $eloquent instanceof Model ? $eloquent : $eloquent->getModel();
    }

    /**
     * @param $eloquent Model|Builder
     * @return string
     */
    protected function getAuthAbility($eloquent): string
    {
        return $eloquent instanceof Model ? 'view' : 'viewAny';
    }

    /**
     * by default the package ignores the authorization if middleware is set to null
     * or the model doesnt have a policy. so this function checks for both :)
     * @param $eloquent
     * @return bool
     */
    protected function checkableEloquent($eloquent): bool
    {
        return
            config('q8-query.middleware') != null
            && Gate::getPolicyFor($this->getModelInstance($eloquent)) != null;
    }
}
