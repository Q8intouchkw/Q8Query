
# Installation 
1- Add the package repo to the repository array in the composer.json file as follows
```json
{
  "require": { 
  },
 "repositories": [
        {
            "type": "vcs",
            "url": "https://gitlab.com/q8intouch-php/q8query"
        }
  ]
}
```
2- run `composer require q8-intouch/q8query` or add it manually to the require key

### Publish config 
`php artisan vendor:publish --provider="Q8Intouch\Q8Query\Q8QueryServiceProvider" --tag="config"`



# Intro
This package provides an API interface that allows the client to fetch, filter, select and ordering any remote resource
with no extra implementation from the backend side. Once you have implemented the backend basic models and relations,
you are ready to use the package.

The following document covers
the available configs, a technical overview of the components, an API overview and various examples.



# Config 

1. The package ships with 3 modes that are interchangeable using the config file. Each mode modifies the security layer by adding or removing restrictions to the calling methods. Calling a method that doesn't follow the strict rules that are
associated with the selected mode, will throw a `Q8Intouch\Q8Query\Core\Exceptions\MethodNotAllowedException`. The rules are as follows:
    * `strict` (Recommended): The methods must have a return type specified in the PHP doc block ex: 
    ```php
     /**
         * some extra docs
         * @return HasOne // return type can be as this
         */
        public function someFunction() : HasOne // or as this 
        {...}
    ```
    If the you would like to add a doc code but has to hide the function from api calls, specify `@Hidden`
     annotation in the code block. That will prevent any api calls to this function
     and will throw at any call attempt ex: 
   ```php
    /**
        * some extra docs
        * @Hidden // using this annotation will prevent the API calls  
        ...
     */
   ```
   * `loose`: Any method can be called regardless of having a return type or not as long as it doesn't contain the annotation `@Hidden`. 
   It does some minor validations on the return type, but no protection is granted.
    **Note: Use this mode carefully as it can be used for RCE Attacks**
   * `public`: This mode will ignore any validations on return types or annotations. This means even the `@Hidden` annotation will be ignored. **use it only for testing, not recommended for production**
   
2. The package looks up for models in the only provided namespaces within the config file. If 2 models are having the same name
the package will prioritize the lookup by order of namespaces by index, where 0 index is the highest priority.

3. More documented configs are available within q8-query.php
 
 
# Technical overview: 
 
The package provides the following modules: 
- `Associator`: associate any relation with the called model
    - usage : 
    ```php
       //TODO
    ```

- `Filterer`: provides various featured filtered options.
    - features: comparison operators, scopes filters, multi-depth has filter
    - Extensions: The filterer can be extended with multiple filters by following the next steps: 
        - // TODO
    - usage: 
    ```php
    //TODO
    ```
- `OptionsReader`: provides the options available for a model by reading the appropriate annotated functions. 
    - Features:
        - fetch unhidden relations having the appropriate annotation
        ```php
        // TODO examples, + hidden
        ```
        - fetch the unhidden scopes to certain modes
        ```php
        // TODO
        ```
- `Orderer`: provides a seamless method for ordering any model with it's available attributes
    - Usage: 
       ```php
       //TODO
       ```
- `Selector`: select single attributes or a multi-depth related attributes
    - Usage:
    ```php
    //TODO
    ```
- `Query`: Acts as an interface for all the previous modules targeting the form calls(either from API or web).
 Upon calling Query module, it checks for the available get parameters and calls up the previous modules
  according to the rules that are specified within the modules 
    - Usage: 
        - // TODO
        - // TODO
- `QueryBuilder`: Builds a query that support all the previous models
    - Usage: 
       ```php
       QueryBuilder::QueryFromPathString('User') // can use also: User/1/order
           ->filter('id gt 0')
           ->associate('orders')
           ->select('id, name, orders.id, orders.track_id')
           ->order('name, desc')
           ->get(); // or paginate()
       ```
       
# API Calls 
The package ships by default with an API handler that can be called by default as `GET {domain}/Q8Query/{Model}`. 
The url can be extended the followings pattern (/{id}/{relationName})* means that the followings will be a valid urls: 
- `Q8Query/User` => fetch all the users on the system
- `Q8Query/User/1` => fetch only the with the id = 1
- `Q8Query/User/1/orders` => fetch the orders of user of an id = 1
- `Q8Query/User/1/orders/1` => fetch the order of user 1 with an id = 1
- `Q8Query/User/1/orders/1/address` => fetch the address of the order of an id = 1

*Notice* that two following models or ids are invalid and will throw `Q8Intouch\Q8Query\Core\Exceptions\ParamsMalformedException`

Invalid urls Ex: 
- `Q8Query/User/orders`
- `Q8Query/User/1/1`
- `Q8Query/User/1/orders/address`

### Available Params
The following default parameters can be used for various respond modifications. Each parameter is parsed differently 
due to some security concerns. So, some of the followings will throw if the passed parameters doesn't match the exact required
format while some will just ignore the illegal characters and continue with the parsing nonetheless
- `filter`: the filter method can be used to filter the results either with direct comparison operators or related models comparisons. 
    - Usage: expression (logical expression)*
        - expression: each expression is considered as a filter option. Mainly there are 2 types of tokens;
            - simple comparison tokens which are straightforward as indicated in the tokens table 
            - complex filterers: may consist of multiple formats 
                - `has`: filter by related models as fetching users having orders, or users having orders.id greater than 1
                - `scope`: call a custom complex filters which are implemented on the backend as fetching active users
                    - **Notice**: the scopes have to be implemented within the model following laravel standard scopes format
                    
                    Ex: defining a method for `/User?filter=scope active(3)` that fetches the active users within the last 3 days 
                ```php
                /**
                 * @param Builder $query
                 * @return Builder
                 */
                public function scopeActive(Builder $query, $days)
                {...}
                ```      
        | Token        | Default           | Syntax:  | Example |
        | :-------------: |:-------------:| -----| -----|
        | =      | eq | attribute `eq` value | id eq 1
        | !=      | ne      |  attribute `'ne'` value | id ne 1 
        | \>      | gt      |  attribute `'gt'` value | id gt 1 
        | >=      | ge      |  attribute `'ge'` value | id ge 1 
        | <      | lt      |  attribute `'lt'` value | id lt 100 
        | <=      | le      |  attribute `'le'` value | id le 100 
        | has      |   has    | `'has'` relation'.'attribute [token value]| 1- has order.id <br> 2- has order.id gt 1
        | like      | contains      |  attribute `'contains'` value | name contains 'lorem epsom'
        | scope      | scope      |   `'scope'` function['('(values,)*')' | 1- scope active <br> 2- scope active() <br> 3- scope locationBetween(12,33)
        
        - logical: the logical operators are used to include multiple expressions per filter
        
        | Token        | Default           | Example:  |
        | :-------------: |:-------------:| -----|
        | or      | or | expression `or` expression |
        | and      | and      |   expression `and` expression  |

- `associate`: associate multi-layered comma-separated related models with respond
   ex: 
   - ?associate=orders.address, employee, 

- `select`: select certain attributes either from the same model or related model.
 **However, all of the primary keys and foreign must be selected in case of the selecting from related models.**
    ex: 
    - ?select=name, id, order.user_id, order.id, order.track_id
  
- `order_by`: syntax: `column ',' {asc|desc}` 
    ex: ?order_by=id, desc
    
- `scope`: the acts as a utility parameter which is used to call a different to certain queries. Ex: a complex order method, 
a dynamic select, or a filterer. However, don't get confused by the `filter` as they are having the same keyword and
 act similarly in some cases. 
    - syntax: `(function['('(values,)*')'(','))*`
    - ex: 
        - ?scope=orderByUserMessages(), attachMessagesCount(1), attachUserWorkingHours("DD:HH:MM")
        - ?scope=orderByUserMessages
    - **NOTES**: 
        - scope only works on queries which means that fetching a single object as `User/1` won't work and the backend server
        will just ignore the `scope` parameter in this case 
 
# Examples
- fetch all users
    - `/Q8Query/User`
- fetch the user with an id = 1
    - `/Q8Query/User/1`
- fetch the user's orders
    - `/Q8Query/User/1/orders`
- fetch the user's with an id greater than 10
    - `Q8Query/User?filter=id gt 10`
- fetch the users having the name 'Loren Epsom' 
    - `Q8Query/User?filter=name eq 'Loren Epsom'`
- fetch the users having 'Loren Epsom' in their name
    - `Q8Query/User?filter=name contains 'Loren Epsom'`
- fetch the users having at least 1 order
    - `Q8Query/User?filter=has orders`
- fetch the users having at least 1 order review on their orders
    - `Q8Query/User?filter=has orders.order_review`
- fetch the users having order track id contains 'Loren Epsom' 
    - `Q8Query/User?filter=has orders.track_id contains 'loren epsom'`
- fetch the users where there id = 1 or id >= 20
    - `Q8Query/User?filter=id eq 1 or id ge 20`
- fetch the active users within the last 3 days and id < 100
    - `Q8Query/User?filter=scope active(3) or id lt 1`
- fetch users with their orders addresses and city details
    - `Q8Query/User?associate=order.address, city`
- fetch users names and their orders track_ids only
    - `Q8Query/User?select=name, id, order.user_id, order.id, order.track_id`
- fetch users having id > 10 along side there orders
    - `Q8Query/User?filter=id gt 10&associate=orders`
- fetch users having id > 3 ordered by id in descending order
    - `Q8Query/User?filter=id gt 3&order_by=id, desc`
- fetch users and attach working hours in day:hours:minutes format
    - `Q8Query/User?scope=attachUserWorkingHours("DD:HH:MM")` (by using sub select)

# Authentication

By default, The package will require the user to be authenticated with the API guard. 
However, this behaviour can be changed by specifying a custom middleware within the `config` files.
```php
return [
    ... 
    "middleware" => 'auth:api' // nullable 
];
```
Moreover, the authentication can be disabled completely by setting the middleware to `null` within the config file.

# Authorization
Authorization is handled by specifying a policy for the required model within `AuthServiceProvider`:
```PHP
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        \App\Models\Post::class => \App\Policies\PostPolicy::class,
    ];
```

The supported methods currently are:
- `view`
- `viewAny`

ex `PostPolicy` for `Post` ModelClass:
```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Post;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the post.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Post  $post
     * @return mixed
     */
    public function view(User $user, Post $post)
    {
        return $user->type == 'editor';
    }
}
```

*Important*: The default behaviour of the package is to proceed with any request unless a policy is specified for the
requested model. so, if there is no policy for `Post` class, the `q8query` will just proceed with the request without
interruption

# Features:

1. fetch models by URL schema
2. fetch a certain model by id
3. fetch related models by specifying a related name
4. filter using logic operators
5. filter using Comparison operators
6. associate a related model
7. select certain attributes from the model
8. select related model's attributes
9. fetch available related models on options request 
10. support pagination
11. fetch related model by relation type i.e:  if a one to one relation: object is returned instead of an array 
12. add a strict mode for fetching only annotated relations && filters
13. filter using scopes as: functionName(...params)
14. fetch options for a certain model scopes
15. order by any attribute
# Not supported yet: 
- grouping operator for filterer
- limit the count of the related object on select, associate
# running tests
for running the tests use:  `vendor/bin/phpunit` within the package directory
or config phpstrom by using the config file `phpunit.xml` within the package dir
