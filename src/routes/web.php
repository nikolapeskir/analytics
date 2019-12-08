<?php
Route::group(['middleware' => ['web', 'auth']], function () {
    Route::group(['namespace' => 'Leanmachine\Analytics\Http\Controllers'], function() {

        Route::get('analytics', ['uses' => 'AnalyticsController@index'])->name('ga.index');
        Route::get('analytics/authenticate', 'AnalyticsController@authenticate')->name('ga.authenticate');
        Route::get('analytics/accounts', ['uses' => 'AnalyticsController@getAccounts'])->name('ga.accounts');
        Route::post('analytics/properties', ['uses' => 'AnalyticsController@getProperties'])->name('ga.properties');
        Route::post('analytics/views', ['uses' => 'AnalyticsController@getViews'])->name('ga.views');
        // Route::get('analytics/create', ['uses' => 'AnalyticsController@create']);
        Route::get('analytics/connect', ['uses' => 'AnalyticsController@redirectToProvider'])->name('ga.connect');
        Route::get('analytics/callback', ['uses' => 'AnalyticsController@handleProviderCallback'])->name('ga.callback');
        Route::get('analytics/disconnect', ['uses' => 'AnalyticsController@disconnect'])->name('ga.disconnect');

    });
});