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

class AnalyticsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $analytics = Analytic::where('user_id', auth()->id())->first();

        return ($analytics) ? $analytics : auth()->user() . '<br/><a href="' . url('analytics/connect') . '">Connect</a>';
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
