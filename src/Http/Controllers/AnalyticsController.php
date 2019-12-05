<?php

namespace Leanmachine\Analytics\Http\Controllers;

use Illuminate\Routing\Controller;
// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
// use App\Http\Requests;
// use Session;
use Auth;
use App\User;
use Leanmachine\Analytics\Http\Analytic;
use Google_Client;
use Google_Service_Analytics;
use Google_Service_AnalyticsReporting;
use Google_Service_AnalyticsReporting_DateRange;
use Google_Service_AnalyticsReporting_Metric;
use Google_Service_AnalyticsReporting_ReportRequest;
use Google_Service_AnalyticsReporting_GetReportsRequest;

class AnalyticsController extends Controller
{
    private $user;

    private $client;

    private $authClient;

    private $analytics;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = auth()->user();

            return $next($request);
        });

        $this->client = new Google_Client();
        $this->client->setAuthConfig(config('analytics'));

        $this->middleware(function ($request, $next) {
            $this->authClient = $this->authenticateClient();
            $this->analytics = ($this->authClient)
                ? new Google_Service_Analytics($this->authClient)
                : null;

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // dd($this->user);
        // $client = $this->authenticateClient();
        // $this->analytics = new Google_Service_Analytics($this->authenticateClient());
        dd($this->getAnalyticsProperties());

        return ($this->authClient)
            ? $this->user->name . "<br />Connected <br /><pre>".print_r($this->getReport())."</pre>"
            : $this->user->name . '<br /><a href="' . url('analytics/connect') . '">Connect</a>';
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Redirect the user to the Google authentication
     */
    public function redirectToProvider()
    {
        // $client = new Google_Client();
        // $client->setAuthConfig(config('analytics'));
        $this->client->setRedirectUri(config('app.url') . env('GOOGLE_ANALYTICS_CALLBACK_URI'));
        $this->client->addScope(Google_Service_Analytics::ANALYTICS_READONLY);
        $this->client->addScope(Google_Service_Analytics::ANALYTICS_MANAGE_USERS);
        $this->client->setAccessType("offline");
        $this->client->setApprovalPrompt('force');

        return redirect($this->client->createAuthUrl());
    }

    /**
     * Obtain the user information from Google.
     */
    public function handleProviderCallback()
    {
        if (!isset(request()->code))
            return redirect()->route('ga.connect');

        // $client = new Google_Client();
        // $client->setAuthConfig(config('analytics'));
        $this->client->authenticate(request()->code);

        if (!$token = $this->client->getAccessToken())
            return false;

        $token['user_id'] = $this->user->id;

        if (Analytic::where('user_id', $this->user->id)) {
            $userToken = new Analytic;
            foreach ($token as $key => $val)
                $userToken->{$key} = $token[$key];

            $userToken->save();
        } else {
            Analytic::create($token);
        }

        return redirect('analytics');
        // ->route('ga.index', $user->id);
    }

    public function authenticateClient()
    {
        if (!$analytics = Analytic::where('user_id', $this->user->id)->first())
            return false;

        $analyticsArray = $analytics->attributesToArray();

        // $client = new Google_Client();
        // $client->setAuthConfig(config('analytics'));

        if (isset($analytics->access_token))
            $this->client->setAccessToken($analyticsArray);

        if ($this->client->isAccessTokenExpired()) {
            $this->client->fetchAccessTokenWithRefreshToken($analyticsArray['refresh_token']);
            $token = $this->client->getAccessToken();
            foreach ($token as $key => $val)
                $analytics->{$key} = $token[$key];

            $analytics->save();
        }

        return ($this->client) ? $this->client : false;
    }

    private function getFirstProfileId()
    {
        // Get the user's first view (profile) ID.
        $analytics = new Google_Service_Analytics($this->authClient);

        // Get the list of accounts for the authorized user.
        $accounts = $analytics->management_accounts->listManagementAccounts();

        if (count($accounts->getItems()) > 0) {
            $items = $accounts->getItems();
            $firstAccountId = $items[0]->getId();

            // Get the list of properties for the authorized user.
            $properties = $analytics->management_webproperties
                ->listManagementWebproperties($firstAccountId);

            if (count($properties->getItems()) > 0) {
                $items = $properties->getItems();
                $firstPropertyId = $items[0]->getId();

                // Get the list of views (profiles) for the authorized user.
                $profiles = $analytics->management_profiles
                    ->listManagementProfiles($firstAccountId, $firstPropertyId);

                if (count($profiles->getItems()) > 0) {
                    $items = $profiles->getItems();

                    // Return the first view (profile) ID.
                    return $items[0]->getId();

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

    public function getAnalyticsProperties()
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
    }

    private function getReport()
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
    }

    function printResults($reports)
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
    }

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
