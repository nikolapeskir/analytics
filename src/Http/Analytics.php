<?php

namespace Leanmachine\Analytics\Http;

use Carbon\Carbon;
use Google_Client;
use Google_Service_Analytics;
use Illuminate\Support\Collection;
// use Illuminate\Support\Traits\Macroable;
use Leanmachine\Analytics\Http\Analytic;

class Analytics
{
    // use Macroable;

    public $client;

    public $service;

    protected $viewId;

    public $user;

    public function __construct(Google_Client $client)
    {
        $this->user = auth()->user();

        $this->client = $client;
        $this->client->setAuthConfig(config('analytics'));

        $this->service = ($this->authenticateClient() != false || isset(request()->code))
            ? new AnalyticsClient($this)
            : null;
    }

    public function authenticateClient()
    {
        if (!$analytics = Analytic::where('user_id', $this->user->id)->first())
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

        return true;
    }

    public function disconnect()
    {
        if (!$analytics = Analytic::where('user_id', $this->user->id))
            return null;

        $analytics->delete();
    }

    public function setViewId(string $viewId)
    {
        $this->viewId = $viewId;

        return $this;
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
