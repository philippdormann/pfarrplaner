<!--
  - Pfarrplaner
  -
  - @package Pfarrplaner
  - @author Christoph Fischer <chris@toph.de>
  - @copyright (c) 2021 Christoph Fischer, https://christoph-fischer.org
  - @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
  - @link https://github.com/pfarrplaner/pfarrplaner
  - @version git: $Id$
  -
  - Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
  -
  - Pfarrplaner is based on the Laravel framework (https://laravel.com).
  - This file may contain code created by Laravel's scaffolding functions.
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation, either version 3 of the License, or
  - (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="next-services-tab">
        <h2>{{ title }}</h2>
        <p>Angezeigt werden die Gottesdienste der nächsten 2 Monate.</p>
        <div class="alert alert-info" v-if="services.length == 0">Es sind keine anzuzeigenden Gottesdienste vorhanden.</div>
        <fake-table :columns="[2,4,4,2]" :headers="['Gottesdienst', 'Liturgie', 'Infos zum Gottesdienst', '']"
                    collapsed-header="Gottesdienste" v-if="services.length > 0">
            <div v-for="(service,serviceIndex) in services" :key="serviceIndex"
                 :class="{'stripe-odd': (serviceIndex %2 ==0)}" class="row mb-3 p-1">
                <div class="col-md-2">
                    <basic-info :service="service" />
                </div>
                <div class="col-md-4">
                    <liturgical-info :service="service"/>
                </div>
                <div class="col-md-4">
                    <details-info :service="service" />
                </div>
                <div class="col-md-2 text-right">
                    <inertia-link class="btn btn-light" title="Im Kalender ansehen"
                                  :href="route('calendar', moment(service.day.date).format('YYYY-MM'))">
                        <span class="fa fa-calendar"></span>
                    </inertia-link>
                    <inertia-link v-if="service.isEditable"
                                  class="btn btn-primary" title="Gottesdienst bearbeiten"
                                  :href="route('service.edit', service.slug)">
                        <span class="fa fa-edit"></span>
                    </inertia-link>
                    <inertia-link v-if="service.isEditable"
                                  class="btn btn-light" title="Liturgie bearbeiten"
                                  :href="route('liturgy.editor', service.slug)">
                        <span class="fa fa-th-list"></span>
                    </inertia-link>
                    <inertia-link v-if="service.isEditable"
                                  class="btn btn-light" title="Predigt bearbeiten"
                                  :href="route('service.sermon.editor', service.slug)">
                        <span class="fa fa-microphone"></span>
                    </inertia-link>
                </div>
            </div>

        </fake-table>

    </div>
</template>

<script>
import LiturgicalInfo from "../Service/LiturgicalInfo";
import DetailsInfo from "../Service/DetailsInfo";
import FakeTable from "../Ui/FakeTable";
import BasicInfo from "../Service/BasicInfo";
export default {
    name: "NextServicesTab",
    components: {BasicInfo, FakeTable, DetailsInfo, LiturgicalInfo},
    props: ['title', 'description', 'user', 'settings', 'services']
}
</script>

<style scoped>

</style>
