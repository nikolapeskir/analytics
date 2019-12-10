<?php
Route::group(['middleware' => ['web', 'auth']], function () {
    Route::group(['namespace' => 'Leanmachine\Analytics\Http\Controllers'], function() {
        Route::group(['prefix' => 'analytics'], function() {
            Route::get('/', 'AnalyticsController@index')->name('ga.index');
            Route::get('authenticate', 'AnalyticsController@authenticate')->name('ga.authenticate');

            Route::get('accounts', 'AnalyticsController@getAccounts')->name('ga.accounts');
            Route::post('properties', 'AnalyticsController@getProperties')->name('ga.properties');
            Route::post('views', 'AnalyticsController@getViews')->name('ga.views');
            Route::post('save', 'AnalyticsController@storeView');

            Route::get('connect', 'AnalyticsController@redirectToProvider')->name('ga.connect');
            Route::get('callback', 'AnalyticsController@handleProviderCallback')->name('ga.callback');
            Route::get('disconnect', 'AnalyticsController@disconnect')->name('ga.disconnect');
        });
    });
});