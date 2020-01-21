<?php

namespace Leanmachine\Analytics\Http\Controllers;

use Illuminate\Routing\Controller;
use Closure;
use Google_Client;
use Leanmachine\Analytics\Http\Analytics;
use Leanmachine\Analytics\Http\AnalyticsViews;
use Leanmachine\Analytics\Http\Requests\AnalyticsViewPost;

class AnalyticsController extends Controller
{
    protected $analytics;

    protected $viewId;

    public function __construct()
    {
        $this->middleware(function ($request, Closure $next) {

            $this->setAnalytics();
            $courentRoute = '/' . \Route::current()->uri;

            if ($courentRoute == '/analytics/connect'
            || $courentRoute == config('analytics.authenticate'))
                return $next($request);

            if (!$this->checkConnection())
                return redirect(config('analytics.authenticate'));

            return $next($request);
        });
    }

    private function setAnalytics()
    {
        $this->analytics = new Analytics(new Google_Client());
    }

    public function index()
    {
        $accounts = $this->getAccounts();

        return ($accounts != null)
            ? view('analytics::analytics.index', compact('accounts'))
            : 'No Accounts';
    }

    public function getAccounts()
    {
        return $this->analytics->service->getAccounts();
    }

    public function getProperties($accountId = '')
    {
        $accountId = ($accountId != '') ? $accountId : request()->accountId;

        return $this->analytics->service->getProperties($accountId);
    }

    public function getViews($accountId = '', $propertyId = '')
    {
        $accountId = ($accountId != '') ? $accountId : request()->accountId;
        $propertyId = ($propertyId != '') ? $propertyId : request()->propertyId;

        return $this->analytics->service->getManagementProfiles($accountId, $propertyId);
    }

    public function storeView(AnalyticsViewPost $request)
    {
        $data = [
            'user_id' => $this->analytics->user->id,
            'account_id' => $request->accountId,
            'property_id' => $request->propertyId,
            'view_id' => $request->viewId,
            'foreign_id' => $request->foreignId,
        ];

        $view = AnalyticsViews::where('foreign_id', $request->foreignId)->first();

        if ($view != null) {
            foreach ($data as $key => $val)
                $view->{$key} = $data[$key];

            $view->save();
        } else {
            $view = AnalyticsViews::create($data);
        }

        return $view;
    }

    public function redirectToProvider()
    {
        return redirect($this->analytics->getAuthUrl());
    }

    public function authenticate()
    {
        $connected = $this->checkConnection();

        return view('analytics::analytics.authenticate', compact('connected'));
    }

    public function handleProviderCallback()
    {
        if ($this->analytics->storeToken())
            return redirect(config('analytics.authenticate'));
    }
    private function checkConnection()
    {
        return $this->analytics->checkConnection();
    }

    public function disconnect()
    {
        $this->analytics->disconnect();

        return redirect(config('analytics.authenticate'));
    }
    
    private function getFirstProfileId()
    {
        $accounts = $this->getAccounts();
        if ($accounts != null) {

            $properties = $this->getProperties($accounts[0]['id']);
            if ($properties != null) {

                $profiles = $this->getViews($properties[0]['account_id'], $properties[0]['id']);
                if ($profiles != null) {

                    return $profiles[0]['id'];

                } else {
                    throw new Exception('No views (profiles) found for this user.');
                }
            } else {
                throw new Exception('No properties found for this user.');
            }
        } else {
            throw new Exception('No accounts found for this user.');
        }
    }

}
