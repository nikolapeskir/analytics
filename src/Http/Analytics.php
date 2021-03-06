<?php

namespace Leanmachine\Analytics\Http;

use Exception;
use Carbon\Carbon;
use Google_Client;
use Google_Service_Analytics;
use Illuminate\Support\Collection;
use Leanmachine\Analytics\Http\Analytic;
use Leanmachine\Analytics\Http\AnalyticViews;

class Analytics
{
    public $client;

    public $service;

    protected $viewId;

    public $user;

    public $name;

    public function __construct(Google_Client $client)
    {
        $this->name = session('account_name');

        $this->user = auth()->user();

        $this->client = $client;
        $this->client->setAuthConfig(config('analytics'));

        $this->service = ($this->authenticateClient() != false || isset(request()->code))
            ? new AnalyticsClient($this)
            : null;
    }

    public function authenticateClient()
    {
        if (!$analytics = $this->getUserToken())
            return null;

        $analyticsArray = $analytics->attributesToArray();

        if (isset($analytics->access_token))
            $this->client->setAccessToken($analyticsArray);

        if ($this->client->isAccessTokenExpired()) {
            $this->client->fetchAccessTokenWithRefreshToken($analyticsArray['refresh_token']);
            $token = $this->client->getAccessToken();

            foreach ($token as $key => $val)
                $analytics->{$key} = $token[$key];

            $analytics->save();
        }

        return ($this->client) ? $this->client : 'false';
    }

    public function getAuthUrl()
    {
        $this->client->setRedirectUri(config('analytics.web.redirect_uris')[0]);
        $this->client->addScope(Google_Service_Analytics::ANALYTICS_READONLY);
        $this->client->addScope(Google_Service_Analytics::ANALYTICS_MANAGE_USERS);
        $this->client->setAccessType("offline");
        $this->client->setApprovalPrompt('force');

        return $this->client->createAuthUrl();
    }

    public function storeToken()
    {
        if (!isset(request()->code))
            return redirect()->route('ga.connect');

        $this->client->authenticate(request()->code);

        if (!$token = $this->client->getAccessToken())
            throw new Exception("Unable to connect to your account. Please try again.", 400);

        $accounts = $this->service->getAccounts();

        if (! is_array($accounts))
            throw new Exception("Your Google account is not connected to Google Analytics.", 400);

        $token['user_id'] = $this->user->id;
        $token['name'] = $this->name;

        Analytic::create($token);

        return true;
    }

    public function checkConnection()
    {
        return ($this->service !== null) ? true : false;
    }

    public function getAnalyticsAccounts()
    {
        return Analytic::select('id', 'name')
            ->where('user_id', auth()->id())
            ->get();
    }

    public function getUserToken()
    {
        $userToken = Analytic::when(! request()->filled('analyticId'), function ($query) {
            $query->where('user_id', auth()->id());
        })
            ->when(request()->filled('analyticId'), function ($query) {
                $query->where('id', request('analyticId'));
            })
            ->first();

        return ( $userToken != null)
            ? $userToken
            : false;
    }

    public function disconnect()
    {
        if (!$analytics = Analytic::where('user_id', $this->user->id))
            return null;

        $analytics->delete();

        if ($analyticsViews = AnalyticsViews::where('user_id', $this->user->id))
            $analyticsViews->delete();
    }

    public function setViewId(string $viewId)
    {
        $this->viewId = $viewId;

        return $this;
    }

    public function getViewById(string $foreignId)
    {
        if (!$view = AnalyticsViews::where('foreign_id', $foreignId)
            ->where('user_id', $this->user->id)
            ->first()
        )
            return null;

        return $view;
    }

    public function fetchVisitorsAndPageViews(Period $period): Collection
    {
        $response = $this->performQuery(
            $period,
            'ga:users,ga:pageviews',
            ['dimensions' => 'ga:date,ga:pageTitle']
        );

        return collect($response['rows'] ?? [])->map(function (array $dateRow) {
            return [
                'date' => Carbon::createFromFormat('Ymd', $dateRow[0]),
                'pageTitle' => $dateRow[1],
                'visitors' => (int) $dateRow[2],
                'pageViews' => (int) $dateRow[3],
            ];
        });
    }

    public function fetchTotalVisitorsAndPageViews(Period $period): Collection
    {
        $response = $this->performQuery(
            $period,
            'ga:users,ga:pageviews',
            ['dimensions' => 'ga:date']
        );

        return collect($response['rows'] ?? [])->map(function (array $dateRow) {
            return [
                'date' => Carbon::createFromFormat('Ymd', $dateRow[0]),
                'visitors' => (int) $dateRow[1],
                'pageViews' => (int) $dateRow[2],
            ];
        });
    }

    public function fetchMostVisitedPages(Period $period, int $maxResults = 20): Collection
    {
        $response = $this->performQuery(
            $period,
            'ga:pageviews',
            [
                'dimensions' => 'ga:pagePath,ga:pageTitle',
                'sort' => '-ga:pageviews',
                'max-results' => $maxResults,
            ]
        );

        return collect($response['rows'] ?? [])
            ->map(function (array $pageRow) {
                return [
                    'url' => $pageRow[0],
                    'pageTitle' => $pageRow[1],
                    'pageViews' => (int) $pageRow[2],
                ];
            });
    }

    public function fetchTopReferrers(Period $period, int $maxResults = 20): Collection
    {
        $response = $this->performQuery($period,
            'ga:pageviews',
            [
                'dimensions' => 'ga:fullReferrer',
                'sort' => '-ga:pageviews',
                'max-results' => $maxResults,
            ]
        );

        return collect($response['rows'] ?? [])->map(function (array $pageRow) {
            return [
                'url' => $pageRow[0],
                'pageViews' => (int) $pageRow[1],
            ];
        });
    }

    public function fetchUserTypes(Period $period): Collection
    {
        $response = $this->performQuery(
            $period,
            'ga:sessions',
            [
                'dimensions' => 'ga:userType',
            ]
        );

        return collect($response->rows ?? [])->map(function (array $userRow) {
            return [
                'type' => $userRow[0],
                'sessions' => (int) $userRow[1],
            ];
        });
    }

    public function fetchTopBrowsers(Period $period, int $maxResults = 10): Collection
    {
        $response = $this->performQuery(
            $period,
            'ga:sessions',
            [
                'dimensions' => 'ga:browser',
                'sort' => '-ga:sessions',
            ]
        );

        $topBrowsers = collect($response['rows'] ?? [])->map(function (array $browserRow) {
            return [
                'browser' => $browserRow[0],
                'sessions' => (int) $browserRow[1],
            ];
        });

        if ($topBrowsers->count() <= $maxResults)
            return $topBrowsers;

        return $this->summarizeTopBrowsers($topBrowsers, $maxResults);
    }

    protected function summarizeTopBrowsers(Collection $topBrowsers, int $maxResults): Collection
    {
        return $topBrowsers
            ->take($maxResults - 1)
            ->push([
                'browser' => 'Others',
                'sessions' => $topBrowsers->splice($maxResults - 1)->sum('sessions'),
            ]);
    }

    public function performQuery(Period $period, string $metrics, array $others = [])
    {
        return $this->service->performQuery(
            $this->viewId,
            $period->startDate,
            $period->endDate,
            $metrics,
            $others
        );
    }

    public function getAnalyticsService(): Google_Service_Analytics
    {
        return $this->service->getAnalyticsService();
    }
}
