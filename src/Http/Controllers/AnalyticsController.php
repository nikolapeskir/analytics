<?php

namespace Leanmachine\Analytics\Http\Controllers;

use Illuminate\Routing\Controller;
// use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
// use App\Http\Requests;
// use Session;
use Auth;
use App\User;
use Carbon\Carbon;
use Leanmachine\Analytics\Http\Analytic;
use Illuminate\Contracts\Cache\Repository;
use Google_Client;
use Google_Service_Analytics;
use Google_Service_AnalyticsReporting;
use Google_Service_AnalyticsReporting_DateRange;
use Google_Service_AnalyticsReporting_Metric;
use Google_Service_AnalyticsReporting_ReportRequest;
use Google_Service_AnalyticsReporting_GetReportsRequest;
use Leanmachine\Analytics\Http\Analytics;
use Leanmachine\Analytics\Http\AnalyticsClient;
use Leanmachine\Analytics\Http\Period;
use Closure;

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
        // dd($this->getFirstProfileId());
        /*$accounts = $this->getAccounts();
        $properties = $this->getProperties($accounts[1]['id']);
        $profiles = $this->getViews($properties[0]['account_id'], $properties[0]['id']);

        $service = $this->analytics->setViewId($profiles[0]['id']);
        $startDate = Carbon::now()->subYear();
        $endDate = Carbon::now();
        $period = Period::create($startDate, $endDate);*/

        /*$analyticsData = $service->performQuery(
            Period::create($startDate, $endDate),
            'ga:users',
            [
                'metrics' => 'ga:users',
                'dimensions' => 'ga:keyword, ga:landingPagePath, ga:date',
                'sort' => '-ga:date',
                'filters' => 'ga:keyword==tracker',
                'segment' => 'gaid::-5'
            ]
        );
        dd($analyticsData);*/

        // dd($service->fetchMostVisitedPages($period));

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

    public function redirectToProvider()
    {
        return redirect($this->analytics->getAuthUrl());
    }

    // Consumer can adjust
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
        return ($this->analytics->service !== null) ? true : false;
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

    /*private function getAnalyticsProperties()
    {
        $views = [];

        $accounts = $this->analytics->management_accounts->listManagementAccounts();

        $accountsNumber = count($accounts->getItems());

        for ($i=0; $i < $accountsNumber; $i++) {

            if ($accountsNumber > 0) {

                $items = $accounts->getItems();
                $accountId = $items[$i]->getId();

                $webProperties = $this->analytics->management_webproperties->listManagementWebproperties($accountId);

                $webPropertiesNumber = count($webProperties->getItems());

                for ($j=0; $j < $webPropertiesNumber; $j++) {

                    if ($webPropertiesNumber > 0) {

                        $items = $webProperties->getItems();
                        $webpropertyId = $items[$j]->getId();
                        $webpropertyName = $items[$j]->getName();

                        $views[$i][$j]["name"] = $webpropertyName;
                        $views[$i][$j]["id"] = $webpropertyId;

                        $profiles = $this->analytics->management_profiles->listManagementProfiles($accountId, $webpropertyId);

                        $profilesNumber = count($profiles->getItems());

                        for ($l=0; $l < $profilesNumber; $l++) {

                            if ($profilesNumber > 0) {

                                $items = $profiles->getItems();

                                $profileId = $items[$l]->getId();
                                $profileName = $items[$l]->getName();
                                $profileWebsiteUrl = $items[$l]->getWebsiteUrl();

                                $views[$i][$j]["profile"][$l]["profile_id"] = $profileId;
                                $views[$i][$j]["profile"][$l]["profile_name"] = $profileName;
                                $views[$i][$j]["profile"][$l]["profile_website"] = $profileWebsiteUrl;

                            } else {
                                throw new Exception('No profiles found for this user.');
                            }
                        }
                    } else {
                        throw new Exception('No webproperties found for this user.');
                    }
                }
            } else {
                throw new Exception('No accounts found for this user.');
            }

        }

        return $views;
    }*/

    /*private function getReport()
    {
        $analytics = new Google_Service_AnalyticsReporting($this->authClient);
        // Replace with your view ID, for example XXXX.
        $VIEW_ID = $this->getFirstProfileId();

        // Create the DateRange object.
        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate("7daysAgo");
        $dateRange->setEndDate("today");

        // Create the Metrics object.
        $sessions = new Google_Service_AnalyticsReporting_Metric();
        $sessions->setExpression("ga:sessions");
        $sessions->setAlias("sessions");

        // Create the ReportRequest object.
        $request = new Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($VIEW_ID);
        $request->setDateRanges($dateRange);
        $request->setMetrics(array($sessions));

        $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests( array( $request) );

        return $analytics->reports->batchGet( $body );
    }*/

    /*function printResults($reports)
    {
        for ( $reportIndex = 0; $reportIndex < count( $reports ); $reportIndex++ ) {
            $report = $reports[ $reportIndex ];
            $header = $report->getColumnHeader();
            $dimensionHeaders = $header->getDimensions();
            $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
            $rows = $report->getData()->getRows();

            for ( $rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
                $row = $rows[ $rowIndex ];
                $dimensions = $row->getDimensions();
                $metrics = $row->getMetrics();
                for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
                    print($dimensionHeaders[$i] . ": " . $dimensions[$i] . "\n");
                }

                for ($j = 0; $j < count($metrics); $j++) {
                    $values = $metrics[$j]->getValues();
                    for ($k = 0; $k < count($values); $k++) {
                        $entry = $metricHeaders[$k];
                        print($entry->getName() . ": " . $values[$k] . "\n");
                    }
                }
            }
        }
    }*/

    /*public function disconnect(Google_Client $client, GoogleAnalytics $googleAnalytics)
    {
        $user = auth()->user();

        $googleAnalytics->disconnectUser();

        try {
            $token = (array) json_decode($user->google_access_token);
            $client->revokeToken($token);
        } catch (Exception $e) {
            return back()->withError($e->getMessage());
        }

        $user->ga_connected = 'false';
        $user->google_access_token = null;
        $user->save();

        Cache::forget($googleAnalytics->analyticsCache);
        Cache::forget($googleAnalytics->analyticsProfilePermissionsCache);

        return redirect('/users');
    }*/
}
