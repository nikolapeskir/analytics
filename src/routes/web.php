<?php
Route::group(['middleware' => ['web', 'auth']], function () {
    Route::group(['namespace' => 'Leanmachine\Analytics\Http\Controllers'], function() {
        Route::group(['prefix' => 'analytics'], function() {
            Route::get('/', 'AnalyticsController@index')
                ->name('ga.index');
            Route::get('authenticate', 'AnalyticsController@authenticate')
                ->name('ga.authenticate');
            Route::get('success', 'AnalyticsController@success')
                ->name('ga.success');
            Route::post('store-temporary', 'AnalyticsController@storeTemporaryNewAccount')
                ->name('ga.store-temporary');

            Route::get('user/accounts', 'AnalyticsController@getAnalyticsAccounts')
                ->name('ga.user-accounts');
            Route::post('accounts', 'AnalyticsController@getAccounts')
                ->name('ga.accounts');
            Route::post('properties', 'AnalyticsController@getProperties')
                ->name('ga.properties');
            Route::post('views', 'AnalyticsController@getViews')
                ->name('ga.views');
            Route::post('save', 'AnalyticsController@storeView')
                ->name('ga.save');
            Route::post('delete/{delete}', 'AnalyticsController@deleteView')
                ->name('ga.delete');

            Route::get('connect', 'AnalyticsController@redirectToProvider')
                ->name('ga.connect');
            Route::get('callback', 'AnalyticsController@handleProviderCallback')
                ->name('ga.callback');
            Route::get('disconnect', 'AnalyticsController@disconnect')
                ->name('ga.disconnect');
        });
    });
});
