<?php
/**
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) 2020 Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarrplaner/pfarrplaner
 * @version git: $Id$
 *
 * Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Http\Controllers;

use App\City;
use App\Facades\Settings;
use App\HomeScreen\Tabs\HomeScreenTabFactory;
use App\Location;
use App\Misc\VersionInfo;
use App\Service;
use App\Services\RedirectorService;
use App\User;
use App\UserSetting;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Inertia\Inertia;

/**
 * Class HomeController
 * @package App\Http\Controllers
 */
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Route /
     */
    public function root()
    {
        if (Auth::user()) {
            return redirect()->route('home');
        }
        return redirect()->route('login');
    }

    /**
     * Show the application dashboard.
     *
     * @return Renderable
     */
    public function index($activeTab = '')
    {
        RedirectorService::saveCurrentRoute();
        // check if the user still has a temp password
        if (Hash::check('testtest', Auth::user()->password)) return redirect()->route('password.edit', ['from' => 'home']);

        $user = Auth::user()->load(['userSettings', 'roles', 'permissions']);
        $settings = Settings::all($user);

        return Inertia::render('HomeScreen', compact('user', 'settings', 'activeTab'));
    }

    /**
     * @return Application|Factory|View
     */
    public function showChangePassword()
    {
        return view('auth.passwords.change');
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function changePassword(Request $request)
    {
        $rules = [
            'current_password' => 'required|hash:' . Auth::user()->password,
            'new_password' => 'required|string|min:6|confirmed|not_current_password|notIn:testtest',
            'new_password_confirmation' => 'required',
        ];
        $firstPassword = (Hash::check('testtest', Auth::user()->password));
        if ((!$request->has('current_password')) && $firstPassword) {
            unset($rules['current_password']);
        }
        $validatedData = $request->validate($rules);
        //Change Password
        Auth::user()->update(['password' => $validatedData['new_password']]);
        if ($firstPassword) return redirect()->route('home')->with("success", "Dein Passwort wurde geändert.");
        return redirect()->back()->with("success", "Dein Passwort wurde geändert.");
    }

    public function connect()
    {
        /*
        $user = Auth::user();
        $token = $user->getToken();
        $cities = Auth::user()->visibleCities;
        $name = explode(' ', Auth::user()->name);
        $name = end($name);
        return view('ical.connect', ['user' => $user, 'token' => $token, 'cities' => $cities, 'name' => $name]);
        */
    }

    /**
     * @return Application|Factory|View
     */
    public function whatsnew()
    {
        $messages = VersionInfo::getMessages()->sortByDesc('date');
        Auth::user()->setSetting('new_features', Carbon::now());
        return view('whatsnew', compact('messages'));
    }

    /**
     * @param $counter
     * @return JsonResponse
     */
    public function counters($counter)
    {
        $data = [];
        switch ($counter) {
            case 'users':
                $count = count(User::where('password', '!=', '')->get());
                break;
            case 'services':
                $count = count(
                    Service::whereHas(
                        'day',
                        function ($query) {
                            $query->where('date', '>=', Carbon::now());
                        }
                    )->get()
                );
                break;
            case 'locations':
                $count = count(Location::all());
                break;
            case 'cities':
                $count = count(City::all());
                break;
            case 'online':
                $data['users'] = visitor()->onlineVisitors(User::class);
                $count = count($data['users']);
                break;
        }
        return response()->json(compact('count', 'data'));
    }

    public function about()
    {
        // get version
        $packageConfig = json_decode(file_get_contents(base_path('package.json')), true);
        $version = $packageConfig['version'];
        $date = (new Carbon(filemtime(base_path('package.json'))))->setTimeZone('Europe/Berlin');
        $changelog = file_get_contents(base_path('CHANGELOG.md'));
        $env = App::environment();

        return Inertia::render('About', compact('version', 'date', 'changelog', 'env'));
    }


    public function tab($tabIndex)
    {
        $user = Auth::user();
        $config = $user->getSetting('homeScreenTabsConfig') ?? [];
        $tab = HomeScreenTabFactory::getOne($config['tabs'][$tabIndex], $tabIndex);
        return response()->json($tab);
    }
}
