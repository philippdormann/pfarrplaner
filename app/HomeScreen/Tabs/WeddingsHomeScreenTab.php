<?php
/*
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

namespace App\HomeScreen\Tabs;


use App\Service;
use App\Wedding;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class WeddingsHomeScreenTab extends AbstractHomeScreenTab
{
    protected $title = 'Trauungen';
    protected $description = 'Zeigt die anstehenden Trauungen';
    protected $query = null;

    public function __construct($config = [])
    {
        $this->setDefaultConfig($config, ['mine' => 0, 'newestFirst' => 0, 'excludeProcessed' => 0]);
        parent::__construct($config);
        $this->query = $this->buildQuery();
    }

    public function getTitle(): string
    {
        if ($this->config['mine']) return 'Meine Trauungen';
        return parent::getTitle();
    }

    public function getCount()
    {
        return $this->query->count();
    }

    public function getContent($data = [])
    {
        $data['weddings'] = $this->query->get();
        return parent::getContent($data);
    }

    public function toArray($data = [])
    {
        $data['weddings'] = $this->query->get();
        $data['count'] = count($data['weddings']);
        return parent::toArray($data);
    }


    /**
     * Build the query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildQuery() {
        $start = Carbon::now()->setTime(0, 0, 0);
        $end = Carbon::now()->addMonth(2);

        $order = $this->config['newestFirst'] ? 'DESC' : 'ASC';

        $query =  Wedding::with(['service', 'service.day'])
            ->select(['weddings.*'])
            ->join('services', 'services.id', 'weddings.service_id')
            ->join('days', 'days.id', 'services.day_id')
            ->whereHas('service', function($service) {
                $service->startingFrom(Carbon::now()->subWeeks(2));
                if ($this->config['mine']) {
                    $service->whereHas(
                        'participants',
                        function ($query) {
                            $query->where('user_id', Auth::user()->id);
                        }
                    );
                } else {
                    $service->whereIn('city_id', Auth::user()->writableCities->pluck('id'));
                }
            })->orderBy('days.date', $order)
            ->orderBy('time', $order);

        if ($this->config['excludeProcessed']) $query->where('processed', '!=', 1);

        return $query;
    }


}
