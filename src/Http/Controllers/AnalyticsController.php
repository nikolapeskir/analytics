<?php

namespace Leanmachine\Analytics\Http\Controllers;

use Illuminate\Routing\Controller;
// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
// use App\Http\Requests;
// use Session;
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
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $client = $this->authenticateClient();
        $reports = $this->getReport($client);

        // dd($this->printResults($reports));

        return ($client) ?
            'Connected <br />' . $this->printResults($reports)
            : auth()->user()->name . '<br/><a href="' . url('analytics/connect') . '">Connect</a>';
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
        $client = new Google_Client();
        $client->setAuthConfig(config('analytics'));
        $client->setRedirectUri(config('app.url') . env('GOOGLE_ANALYTICS_CALLBACK_URI'));
        $client->addScope(Google_Service_Analytics::ANALYTICS_READONLY);
        $client->addScope(Google_Service_Analytics::ANALYTICS_MANAGE_USERS);
        $client->setAccessType("offline");
        $client->setApprovalPrompt('force');

        return redirect($client->createAuthUrl());
    }

    /**
     * Obtain the user information from Google.
     */
    public function handleProviderCallback()
    {
        if (!isset(request()->code))
            return redirect()->route('ga.connect');

        $client = new Google_Client();
        $client->setAuthConfig(config('analytics'));
        $client->authenticate(request()->code);

        if (!$token = $client->getAccessToken())
            return false;

        if (!$user = User::where(auth()->id()))
            return false;

        $token['user_id'] = auth()->id();

        if (Analytic::where('user_id', auth()->id())) {
            $userToken = new Analytic;
            foreach ($token as $key => $val)
                $userToken->{$key} = $token[$key];

            $userToken->save();
        } else {
            // dd($token);
            Analytic::create($token);
        }

        return redirect('analytics');
            // ->route('ga.index', $user->id);
    }

    private function authenticateClient()
    {
        if (!$analytics = Analytic::where('user_id', auth()->id())->first())
            return false;

        $analyticsArray = $analytics->attributesToArray();

        $client = new Google_Client();
        $client->setAuthConfig(config('services.google.analytics'));

        if (isset($analytics->access_token))
            $client->setAccessToken($analyticsArray);

        // if ($client->isAccessTokenExpired()) {
        //     $client->fetchAccessTokenWithRefreshToken($analytics->refresh_token);
        //     $analytics->access_token = json_encode($client->getAccessToken());
        //     $analytics->save();
        // }

        return ($client) ? $client : false;
    }

    private function getFirstProfileId($client)
    {
        // Get the user's first view (profile) ID.
        $analytics = new Google_Service_Analytics($client);

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

    private function getReport($client)
    {
        $analytics = new Google_Service_AnalyticsReporting($client);
        // Replace with your view ID, for example XXXX.
        $VIEW_ID = $this->getFirstProfileId($client);

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
