<?php

namespace Leanmachine\Analytics\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
// use App\Http\Controllers\Controller;
use Illuminate\Routing\Controller;
use Google_Client;
use Google_Service_Analytics;

class AnalyticsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $config = config('analytics');

        return $config['web'];
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
        // dd(config('analytics'));
        // Create the client object and set the authorization configuration from JSON file.
        $client = new Google_Client();
        $client->setAuthConfig(config('analytics'));
        $client->setRedirectUri(config('analytics.web.redirect_uris'));
        $client->addScope(Google_Service_Analytics::ANALYTICS_READONLY);
        $client->addScope(Google_Service_Analytics::ANALYTICS_MANAGE_USERS);
        $client->setAccessType("offline");
        $client->setApprovalPrompt('force');
        $authUrl = $client->createAuthUrl();

        return redirect($authUrl);
    }

    /**
     * Obtain the user information from Google.
     */
    public function handleProviderCallback()
    {
        // Handle authorization flow from the server.
        if (!isset(request()->code))
            return redirect()->route('ga.auth');

        $user = auth()->user();
        $user->code = request()->code;
        $user->ga_connected = 'true';
        $user->save();

        // ProcessGoogleAuthentication::dispatch(
        //     request()->code,
        //     $user->id
        // )->onQueue('ga-views');

        return redirect()->route('users.show', $user->id);
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
