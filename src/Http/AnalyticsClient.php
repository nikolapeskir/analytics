<?php

namespace Leanmachine\Analytics\Http;

use DateTime;
use Google_Client;
use Google_Service_Analytics;
use Illuminate\Contracts\Cache\Repository;
use Leanmachine\Analytics\Http\Analytic;
use Leanmachine\Analytics\Http\Analytics;
use App\User;

class AnalyticsClient
{
    public $client;

    public $service;

    protected $cache;

    protected $cacheLifeTimeInMinutes = 0;

    // public function __construct(Google_Service_Analytics $service, Repository $cache)
    public function __construct(Analytics $analytics)
    {
        $this->client = $analytics->client;

        $this->user = $analytics->user;

        $this->service = new Google_Service_Analytics($this->client);
    }

    public function setCacheLifeTimeInMinutes(int $cacheLifeTimeInMinutes)
    {
        $this->cacheLifeTimeInMinutes = $cacheLifeTimeInMinutes * 60;

        return $this;
    }

    public function getAccounts()
    {
        try {
            $accounts = $this->service
                ->management_accounts
                ->listManagementAccounts()
                ->getItems();
        } catch (\Google_Service_Exception $e) {
            return null;
        }

        if ($accounts == null)
            return null;

        foreach ($accounts as $account)
            $collection[] = [
                'id' => $account->getId(),
                'name' => $account->getName()
            ];

        return $collection;
    }

    public function getProperties($accountId)
    {
        $properties = $this->service
            ->management_webproperties
            ->listManagementWebproperties($accountId)
            ->getItems();

        if ($properties == null)
            return null;

        foreach ($properties as $propertie)
            $collection[] = [
                'id' => $propertie->getId(),
                'account_id' => $propertie->getAccountId(),
                'name' => $propertie->getName()
            ];

        return $collection;
    }

    public function getManagementProfiles($accountId, $propertyId)
    {
        $profiles = $this->service
            ->management_profiles
            ->listManagementProfiles($accountId, $propertyId)
            ->getItems();

        if ($profiles == null)
            return null;

        foreach ($profiles as $profile)
            $collection[] = [
                'id' => $profile->getId(),
                'account_id' => $profile->getAccountId(),
                'property_id' => $profile->getWebPropertyId(),
                'website' => $profile->getWebsiteUrl(),
                'name' => $profile->getName()
            ];

        return $collection;
    }

    public function performQuery(string $viewId, DateTime $startDate, DateTime $endDate, string $metrics, array $others = [])
    {
        // $cacheName = $this->determineCacheName(func_get_args());

        // if ($this->cacheLifeTimeInMinutes == 0)
        //     $this->cache->forget($cacheName);

        // return $this->cache->remember($cacheName, $this->cacheLifeTimeInMinutes, function () use ($viewId, $startDate, $endDate, $metrics, $others) {

            $result = $this->service->data_ga->get(
                "ga:{$viewId}",
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
                $metrics,
                $others
            );

            while ($nextLink = $result->getNextLink()) {
                if (isset($others['max-results']) && count($result->rows) >= $others['max-results']) {
                    break;
                }
                $options = [];
                parse_str(substr($nextLink, strpos($nextLink, '?') + 1), $options);
                $response = $this->service->data_ga->call('get', [$options], 'Google_Service_Analytics_GaData');
                if ($response->rows) {
                    $result->rows = array_merge($result->rows, $response->rows);
                }
                $result->nextLink = $response->nextLink;
            }

            return $result;
        // });
    }

    public function getAnalyticsService(): Google_Service_Analytics
    {
        return $this->service;
    }

    // protected function determineCacheName(array $properties): string
    // {
    //     return 'spatie.laravel-analytics.'.md5(serialize($properties));
    // }
}
