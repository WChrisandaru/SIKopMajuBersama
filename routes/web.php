<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CooperativeController as C;
Route::get('/login',[C::class,'login'])->name('login');
Route::post('/login',[C::class,'authenticate'])->middleware('throttle:6,1');
Route::middleware('auth')->group(function(){
 Route::post('/logout',[C::class,'logout']);
 Route::get('/',fn()=>redirect('/dashboard'));
 Route::post('/action/{action}',[C::class,'mutate']);
 Route::get('/{page}/{id?}',[C::class,'page'])->whereNumber('id');
});
