<?php

use App\Http\Controllers\CooperativeController as C;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [C::class, 'login'])->name('login');
Route::post('/login', [C::class, 'authenticate'])->middleware('throttle:6,1');
Route::middleware('auth')->group(function () {
    Route::post('/logout', [C::class, 'logout']);
    Route::get('/', fn () => redirect('/dashboard'));
    Route::post('/action/member', [MemberController::class, 'save'])->name('members.save');
    Route::get('/payments/members', [PaymentController::class, 'members'])->name('payments.members');
    Route::get('/payments/members/{member}/items', [PaymentController::class, 'items'])->whereNumber('member')->name('payments.items');
    Route::post('/action/bill-component', [PaymentController::class, 'addComponent'])->name('bills.components.store');
    Route::post('/action/pay', [PaymentController::class, 'save'])->name('payments.save');
    Route::post('/action/{action}', [C::class, 'mutate']);
    Route::get('/{page}/{id?}', [C::class, 'page'])->whereNumber('id');
});
