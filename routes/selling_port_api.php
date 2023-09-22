<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Http\Controllers\SellingPortController;
use App\Http\Controllers\ContractController;

Route::post('register-selling-port',[SellingPortController::class, 'registerSellingPort']);
Route::post('login-selling-port',[SellingPortController::class, 'LoginSellingPort'])->name('Login');

Route::group( ['middleware' => ['auth:selling-port-api', 'check-scope-selling-port', 'scopes:sellingports'] ],function(){
        Route::get('display-request',[SellingPortController::class, 'displayMySellingPortRequest']);
        Route::post('add-request-to-company',[SellingPortController::class, 'addRequestToCompany']);
        //إضافة طلب عقد
        // Route::post('add-request-contract',[ContractController::class, 'addRequestContract']);
        // Route::delete('delete-my-request/{SellingPortOrderId}',[SellingPortController::class, 'deleteSellingPortOrder'])
        // ->middleware('is-selling-port-order-delete');
        Route::get('display-types',[SellingPortController::class, 'displayOutputTypes']);
        Route::post('add-rating/{RequestId}',[SellingPortController::class, 'addRatingToRequest'])->middleware('check-My-sales-request');
        ///////////// profiles and editions ////////////////////
        Route::get('display-my-profile',[SellingPortController::class, 'displayMyProfile']);
        Route::post('edit-my-profile',[SellingPortController::class, 'editMyProfile']);

        /////////////// NOTIFICATION PART //////////////////
        Route::get('display-selling-port-notification',[SellingPortController::class, 'displaySellingPortNotification']);
        Route::get('display-selling-port-notification-switch-state',[SellingPortController::class, 'displaySellingPortNotificationSwitchState']);
        
        
});

