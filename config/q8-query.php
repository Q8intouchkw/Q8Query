<?php

return [

    /**
     * this array contain the namespaces that holds the models
     * the lookup is prioritized by ordering where the first namespace is highest priority
     */
    "namespaces" =>
        [
            "App\Models"
        ],

    /**
     * url grouped by prefix ex: www.example.com/{url_prefix}/User
     */
    "url_prefix" => 'Q8Query',

    /**
     * param key used for filtering ex:  www.example.com/User?{filterer}=name eq "sss"
     */
    "filterer" => 'filter',

    /**
     * param key used for associating related models ex:  www.example.com/User?associate=orders
     */
    "associator" => 'associate',

    /**
     * param key used for selecting related attributes and models ex:  www.example.com/User?select=name, orders.name,
     */
    'selector' => 'select',
    /**
     * param key used for selecting related attributes and models ex:  www.example.com/User?select=name, orders.name,
     */
    'orderer' => 'order_by',

    /**
     *  param key used for ordering models
     */

    /**
     * tokens key words and are replaceable to suit any project
     * ex: `www.example.com/User?filter=name {eq} "ssss"`
     * the following array allows to replace the `eq` in the past example with any keyword that suits the project
     *
     * Cautions: dont replace it with an invalid url characters like {=, ?, &) as it will cause the url to crash
     */
    "tokens" => [
        'or' => 'or',
        'and' => 'and',
        '='   => 'eq',
        '!='  => 'ne',
        '>'   => 'gt',
        '>='   => 'ge',
        '<'   => 'lt',
        '<='   => 'le',
        'has' => 'has',
        'like' => 'contain',
        'scope' => 'scope',
    ],

    /**
     * values:
     * 'strict': only call the unhidden relations having the appropriate annotation || return type
     * 'loose': fetch any unhidden relation regardless having an annotation or not
     * 'public': call any relation regardless hidden or not. plus no return type is specified
     */
    "caller-mode" => 'strict',

    /**
     * param key for number of results per page
     */
    "paginator_size" => 'per_page',

    /**
     * param key for default paginator size
     */
    "paginator_default_size" => 10,

    /**
     * param key used for scoping related models ex:  www.example.com/User?scope=active(3)
     */
    "scoper" => 'scope',

    "middleware" => 'auth:api',

];
