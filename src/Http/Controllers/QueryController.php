<?php

namespace Q8Intouch\Q8Query\Http\Controllers;

use App\Models\User;
use DocBlockReader\Reader;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Q8Intouch\Q8Query\Core\Exceptions\ModelNotFoundException;
use Q8Intouch\Q8Query\OptionsReader\OptionsReader;
use Q8Intouch\Q8Query\Query;
use Q8Intouch\Q8Query\QueryBuilder;

class QueryController extends BaseController
{
    public function get(Request $request, $url)
    {
        $paginator_key = config('paginator_size', 'per_page');
        $page_count =
            $request->has($paginator_key)
                ? $request->get($paginator_key)
                : config('paginator_default_size', 10);

        try {
            return
                Query::QueryFromPathString($url)
                    ->withRequest($request)
                    ->paginate($page_count);//->appends($request->except($paginator_key));
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function options(Request $request, $resource)
    {
        try {
            return OptionsReader::createFromModelString($resource)->extractOptions();
        } catch (ModelNotFoundException $e) {
            dd($e);
        }
    }
}
