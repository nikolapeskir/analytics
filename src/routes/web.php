<?php

Route::group(['namespace' => 'Leanmachine\Analytics\Http\Controllers'], function() {

    Route::get('analytics', ['uses' => 'AnalyticsController@index']);

});