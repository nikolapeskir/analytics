<?php

Route::group(['namespace' => 'Leanmachine\Analytics\Http\Controllers'], function() {

    Route::get('analytics', ['uses' => 'AnalyticsController@index']);
    Route::get('analytics/create', ['uses' => 'AnalyticsController@create']);
    Route::get('analytics/connect', ['uses' => 'AnalyticsController@redirectToProvider']);
    Route::get('analytics/callback', ['uses' => 'AnalyticsController@handleProviderCallback']);

});