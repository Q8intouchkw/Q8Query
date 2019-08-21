<?php


namespace Q8Intouch\Q8Query\Core;


use DocBlockReader\Reader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Q8Intouch\Q8Query\Core\Exceptions\MethodNotAllowedException;

class Caller
{
    protected $reflection;
    protected $object;

    /**
     * Caller constructor.
     * @param $object
     * @throws \ReflectionException
     */
    public function __construct($object)
    {
        $this->object = $object;
        $class = $this->getModelClass($object);
        $this->reflection = new \ReflectionClass($class);
    }

    /**
     * @param $method
     * @param $returnType
     * @return mixed|null
     * @throws \ReflectionException
     * @throws MethodNotAllowedException
     */
    public function call($method, &$returnType = null)
    {

        // check if strict mode enabled
        if ($this->authorizeCallOrThrow($method)) {

            return $this->object->{$method}();
        }
    }


    /**
     * @param $eloquent Model|Builder
     * @return string
     */
    protected function getModelClass($eloquent)
    {
        return $eloquent instanceof Builder ? get_class($eloquent->getModel()) : get_class($eloquent);
    }

    /**
     * @param $method
     * @param $reader Reader
     * @return bool|mixed|null
     * @throws \ReflectionException
     */
    protected function getReturnType($method, $reader)
    {
        return $this->reflection->getMethod($method)->getReturnType() ?: $reader->getParameter('return');
    }

    /**
     * @param $method
     * @return bool
     * @throws \ReflectionException
     */
    public function authorizeCall($method)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $reader = new Reader($this->reflection->getName(), $method);

        // check for return/types && if not hidden before calling
        $returnType = $this->getReturnType($method, $reader);

        // fetch call mode
        $mode = config('q8-query.caller-mode', 'strict');

        return !$reader->getParameter('Hidden') && (($mode == 'strict' && $returnType) || $mode == 'loss');
    }

    /**
     * @param $method
     * @return mixed
     * @throws MethodNotAllowedException
     * @throws \ReflectionException
     */
    public function authorizeCallOrThrow ($method)
    {
        if ( $this->authorizeCall($method))
            return true;

        throw new MethodNotAllowedException("Your are not authorized to call the following: {$method}");
    }
}