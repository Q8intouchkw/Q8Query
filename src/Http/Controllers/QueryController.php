<?php

namespace Q8Intouch\Q8Query\Http\Controllers;

use App\Models\User;
use DocBlockReader\Reader;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Q8Intouch\Q8Query\Core\ModelNotFoundException;
use Q8Intouch\Q8Query\OptionsReader\OptionsReader;
use Q8Intouch\Q8Query\Query;

class QueryController extends BaseController
{
    public function get(Request $request, $url)
    {
        $paginator_key = config('paginator_size', 'per_page');
        $page_count =
            $request->has($paginator_key)
                ? $request->get($paginator_key)
                : config('paginator_default_size', 10);
        $reader = new Reader(User::class, 'employee');
        $x = $reader->getParameter("Hidden"); // 1 (with number type)
        try {
            return
                Query::QueryFromPathString($url)
                    ->paginate($page_count);//->appends($request->except($paginator_key));
        } catch (\Exception $e) {
            dd($e);
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