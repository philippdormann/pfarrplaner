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

use App\Absence;
use App\Approval;
use App\Events\AbsenceApproved;
use App\Events\AbsenceDemanded;
use App\Events\AbsenceRejected;
use App\Replacement;
use App\Service;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Class AbsenceController
 * @package App\Http\Controllers
 */
class AbsenceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function getDays($start)
    {
        $end = $start->copy()->addMonth(1)->subSecond(1);
        $holidays = $this->getHolidays($start, $end);
        $days = [];
        while ($start <= $end) {
            $day = ['day' => $start->day, 'holiday' => false, 'date' => $start->copy()];
            foreach ($holidays as $holiday) {
                $day['holiday'] = $day['holiday'] || (($start >= $holiday['start']) && ($start <= $holiday['end']));
            }
            $days[$start->day] = $day;
            $start->addDay(1);
        }
        return $days;
    }

    private function getStart($year, $month = null)
    {
        if (!$month) {
            list($year, $month) = explode('-', $year);
        }
        return new Carbon($year . '-' . $month . '-01 0:00:00');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, $year = 0, $month = 0)
    {
        if (false !== ($r = $this->redirectIfMissingParameters($request, 'absences.index', $year, $month))) {
            return $r;
        }

        $start = $this->getStart($year, $month);
        $days = $this->getDays($start->copy());

        $years = Absence::select(DB::raw('YEAR(absences.from) as year'))->distinct()->get()->pluck('year')->sort();

        return Inertia::render('Absences/Planner', compact('start', 'days', 'year', 'month', 'years'));
    }

    public function users()
    {
        $users = Auth::user()->getViewableAbsenceUsers();
        foreach ($users as $key => $user) {
            $user->canEdit = false;
            if (($user->id == Auth::user()->id)
                || (Auth::user()->hasPermissionTo('fremden-urlaub-bearbeiten')
                    && (!$user->hasRole('Pfarrer*in'))
                    && (count(Auth::user()->writableCities->intersect($user->homeCities))))
            ) {
                $user->canEdit = true;
            }
        }
        return response()->json($users);
    }

    public function days($start, User $user)
    {
        $start = $this->getStart($start);
        $end = $start->copy()->addMonth(1)->subDay(1);
        $days = $this->getDays($start->copy());

        $absences = Absence::where('user_id', $user->id)
            ->where('to', '>=', $start)
            ->where('from', '<=', $end)
            ->get();

        // Find out whether current user is a replacement for this absence
        if ($user->id != Auth::user()->id) {
            foreach ($absences as $absence) {
                $absence->replacing = false;
                /** @var Replacement $replacement */
                foreach ($absence->replacements as $replacement) {
                    if ($replacement->users->pluck('id')->contains(Auth::user()->id)) {
                        $absence->replacing = true;
                    }
                }
            }
        }

        foreach ($days as $index => $day) {
            $days[$index]['services'] = Service::atDate($days[$index]['date'])
                ->userParticipates($user)
                ->count();
            $days[$index]['busy'] = ($days[$index]['services'] > 0);
            $days[$index]['absent'] = false;
            $days[$index]['absence'] = null;
            $days[$index]['duration'] = 0;
            $days[$index]['show'] = true;
        }


        foreach ($absences as $absence) {
            $index = ($absence->from < $start ? 1 : $absence->from->day);
            $days[$index]['absence'] = $absence;
            $days[$index]['duration'] = $absence->to->diff($days[$index]['date'])->days + 1;
            $endIndex = ($absence->to > $end ? $end->day : $absence->to->day);
            for ($i = $index; $i <= $endIndex; $i++) {
                $days[$i]['absent'] = true;
                if ($i > $index) {
                    $days[$i]['show'] = false;
                }
            }
        }

        return response()->json($days);
    }

    /**
     * @param Request $request
     * @param $route
     * @param $year
     * @param $month
     * @return bool|RedirectResponse
     */
    protected function redirectIfMissingParameters(Request $request, $route, $year, $month)
    {
        $defaultMonth = Carbon::now()->month;
        $defaultYear = Carbon::now()->year;

        $initialYear = $year;
        $initialMonth = $month;


        if ($month == 13) {
            $year++;
            $month = 1;
        }
        if (($year > 0) && ($month == 0)) {
            $year--;
            $month = 12;
        }

        if ((!$year) || (!$month) || (!is_numeric($month)) || (!is_numeric($year)) || (!checkdate($month, 1, $year))) {
            $year = $defaultYear;
            $month = $defaultMonth;
        }

        if (($year == $initialYear) && ($month == $initialMonth)) {
            return false;
        }

        $data = compact('month', 'year');
        $slave = $request->get('slave', 0);
        if ($slave) {
            $data = array_merge($data, compact('slave'));
        }

        return redirect()->route($route, $data);
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     * @return array
     * @throws Exception
     */
    private function getHolidays(Carbon $start, Carbon $end)
    {
        try {
            $raw = json_decode(file_get_contents('https://ferien-api.de/api/v1/holidays/BW'), true);
        } catch (Exception $e) {
            return [];
        }
        $holidays = [];
        foreach ($raw as $holiday) {
            $holiday['start'] = new Carbon($holiday['start']);
            $holiday['end'] = (new Carbon($holiday['end']))->subSecond(1);
            $holiday['name'] = ucfirst($holiday['name']);
            if (($holiday['start'] <= $end) && ($holiday['end'] >= $start)) {
                $holidays[] = $holiday;
            }
        }
        return $holidays;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create($year, $month, User $user, $day = 1)
    {
        $users = User::all();
        $absence = Absence::create(
            [
                'user_id' => $user->id,
                'reason' => 'Urlaub',
                'from' => Carbon::create($year, $month, $day, 0, 0, 0),
                'to' => Carbon::create($year, $month, $day, 0, 0, 0),
            ]
        );
        return redirect()->route('absences.edit', $absence->id);
    }

    /**
     * @param Absence $absence
     * @param $data
     */
    protected function setupReplacements(Absence $absence, $data)
    {
        $absence->load('replacements');
        if (count($absence->replacements)) {
            foreach ($absence->replacements as $replacement) {
                $replacement->delete();
            }
        }
        foreach ($data as $replacementData) {
            $replacement = new Replacement(
                [
                    'absence_id' => $absence->id,
                    'from' => max(Carbon::createFromFormat('d.m.Y', $replacementData['from']), $absence->from),
                    'to' => min(Carbon::createFromFormat('d.m.Y', $replacementData['to']), $absence->to),
                ]
            );
            $replacement->save();
            if (isset($replacementData['users'])) {
                foreach ($replacementData['users'] as $id => $userData) {
                    $replacementData['users'][$id] = $userData['id'];
                }
                $replacement->users()->sync($replacementData['users']);
            }
            $replacementIds[] = $replacement->id;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param Absence $absence
     * @return Response
     */
    public function show(Absence $absence)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Absence $absence
     * @return Response
     */
    public function edit(Request $request, Absence $absence)
    {
        $absence->load('replacements');
        $year = $month = null;
        if ($request->has('startMonth')) {
            list($month, $year) = explode('-', $request->get('startMonth'));
        }
        if (!$year) {
            $year = date('Y');
        }
        if (!$month) {
            $year = date('m');
        }
        $users = User::all();
        return Inertia::render('Absences/AbsenceEditor', compact('absence', 'month', 'year', 'users'));
        //return view('absences.edit', compact('absence', 'month', 'year', 'users'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Absence $absence
     * @return Response
     */
    public function update(Request $request, Absence $absence)
    {
        $absence->update($this->validateRequest($request));

        $this->setupReplacements($absence, $request->get('replacements') ?: []);
        return redirect()->route(
            'absences.index',
            ['month' => $absence->from->format('m'), 'year' => $absence->from->format('Y')]
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Absence $absence
     * @return Response
     */
    public function destroy(Request $request, Absence $absence)
    {
        $absence->delete();
        return redirect()->route(
            'absences.index',
            ['month' => $request->get('month'), 'year' => $request->get('year')]
        );
    }

    /**
     * @param Request $request
     * @param Absence $absence
     * @return RedirectResponse
     */
    public function approve(Request $request, Absence $absence)
    {
        $approval = Approval::create(
            [
                'status' => 'approved',
                'user_id' => Auth::user()->id,
                'absence_id' => $absence->id,
            ]
        );
        $approval->save();

        $remaining = count($absence->user->approvers) - count(
                $absence->approvals()->where('status', 'approved')->get()
            );
        if ($remaining == 0) {
            $absence->status = 'approved';
            $absence->save();
            $success = 'Damit sind alle erforderlichen Genehmigungen vorhanden. Der Urlaub wurde in den Kalender eingetragen.';
        } else {
            $success = sprintf('Es fehlen noch %d weitere Genehmigungen.', $remaining);
        }

        event(new AbsenceApproved($absence, $approval));

        return redirect()->route('approvals.index')->with(
            'success',
            'Du hast den Urlaubsantrag genehmigt. ' . $success
        );
    }

    /**
     * @param Request $request
     * @param Absence $absence
     * @return RedirectResponse
     * @throws Exception
     */
    public function reject(Request $request, Absence $absence)
    {
        event(new AbsenceRejected($absence));
        $absence->delete();
        return redirect()->route('approvals.index')->with('success', 'Du hast den Urlaubsantrag abgelehnt.');
    }

    /**
     * Validate request data
     * @param Request $request
     * @return array
     */
    protected function validateRequest(Request $request)
    {
        $rules = [
            'from' => 'required|date_format:d.m.Y',
            'to' => 'required|date_format:d.m.Y',
            'reason' => 'required|string',
            'replacement_notes' => 'nullable|string',
        ];
        if ($request->route()->getName() == 'absences.store') {
            $rules['user_id'] = 'required|exists:users,id';
        }
        $data = $request->validate($rules);
        $data['from'] = Carbon::createFromFormat('d.m.Y', $data['from'])->setTime(0, 0, 0);
        $data['to'] = Carbon::createFromFormat('d.m.Y', $data['to'])->setTime(23, 59, 59);
        return $data;
    }
}
