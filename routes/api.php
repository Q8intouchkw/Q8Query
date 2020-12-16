<?php

use Illuminate\Support\Facades\Route;
use Q8Intouch\Q8Query\Http\Controllers\QueryController;
Route::middleware(config('q8-query.middleware'))->group(function (){
    Route::get('/{resource}', QueryController::class.'@get')
        ->where('resource', '.*');


    Route::options('/{resource}', QueryController::class.'@options');
});
