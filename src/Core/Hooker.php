<?php


namespace Q8Intouch\Q8Query\Core;


class Hooker
{
    private $hook;
        private function __construct($type, $eloquent)
        {
            $class = $this->getClass($type, $eloquent);
            if ($this->modelHasHook($class)) {
                $hookClass = $this->getHook($class);
                $this->hook = new $hookClass;
            }

        }

        public static function fromModel($model): Hooker
        {
            return new self('model', $model);
        }

        public static function fromBuilder($builder): Hooker
        {
            return new self('builder', $builder);
        }

        public static function fromClass(string $class): Hooker
        {
            return new self('class', $class);
        }

        protected function modelHasHook($class) : bool
        {
            return isset(config("q8-query.hooks")[$class]);
        }

        private function getHook($class){
            return config("q8-query.hooks")[$class];
        }

        private function getClass($type, $eloquent){
            if ($type == 'model')
                return get_class($eloquent);
            else if ($type  == 'builder')
                return get_class($eloquent->getModel());
            else return $eloquent;
        }

        public function callHookMethod($hookMethod, ...$params){
                if ($this->hook != null)
                    call_user_func_array([$this->hook, $hookMethod], $params);

        }

}
