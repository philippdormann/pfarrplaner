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
    <admin-layout :title="'Willkommen, '+(user.first_name ? user.first_name : user.name)+'!'">
        <template slot="navbar-left">
            <a class="btn btn-primary" :href="route('calendar')"><span class="fa fa-calendar"></span> <span class="d-none d-md-inline">Zum Kalender</span></a>&nbsp;
            <inertia-link v-if="config.wizardButtons == '1'" class="btn btn-light" :href="route('baptisms.create')"><span class="fa fa-water"></span>
                <span class="d-none d-md-inline">Taufe anlegen...</span></inertia-link>&nbsp;
            <inertia-link v-if="config.wizardButtons == '1'" class="btn btn-light" :href="route('funerals.wizard')"><span class="fa fa-cross"></span>
                <span class="d-none d-md-inline">Beerdigung anlegen...</span></inertia-link>&nbsp;
            <a v-if="config.wizardButtons == '1'" class="btn btn-light" :href="route('weddings.wizard')"><span class="fa fa-ring"></span>
                <span class="d-none d-md-inline">Trauung anlegen...</span></a>&nbsp;
        </template>
        <div v-if="settings.homeScreenConfig.showReplacements && (replacements.length > 0)" class="alert alert-info">
            <div class="text-bold">Du vertrittst aktuell:</div>
            <ul>
                <li v-for="(replacement,replacementIndex) in replacements">
                    {{ replacement.absence.user.name }} ({{ replacement.absence.reason }}, {{ moment(replacement.from).format('DD.MM.YYYY') }} - {{ moment(replacement.to).format('DD.MM.YYYY') }})
                    <div v-if="replacement.absence.replacement_notes"><small><span class="text-bold">Hinweis: </span>{{ replacement.absence.replacement_notes }}</small></div>
                </li>
            </ul>
        </div>
        <card>
            <card-header>
                <tab-headers>
                    <tab-header v-for="tab in myTabs" :title="tab.title" :id="tab.key" :key="tab.key" :active-tab="myActiveTab"
                                :count="tab.count" :badge-type="tab.badgeType" :class="'tabheader-'+tab.type"/>
                    <div class="ml-auto d-inline tab-setup">
                        <a :href="route('user.profile', {tab: 'homeScreenConfiguration'})" class="p-2 pl-3 tab-setup ml-auto"
                           title="Angezeigte Reiter konfigurieren"><span class="fa fa-cog"></span>
                            <span class="d-none d-md-inline">Anzeige</span>
                        </a>
                    </div>
                </tab-headers>
            </card-header>
            <card-body>
                <div v-if="myTabsConfig.tabs.length == 0" class="alert alert-info">
                    Dieser Startbildschirm ist noch ziemlich leer. In deinem Profil kannst du einstellen, was du hier sehen möchtest.
                    <div>
                        <a class="btn btn-primary" :href="route('user.profile', {tab: 'homeScreenConfiguration'})">Profil bearbeiten</a>
                    </div>
                </div>
                <tabs>
                    <tab v-for="tab in myTabs" :id="tab.key" :key="tab.key"  :active-tab="myActiveTab" >
                        <component v-if="tab.filled" :is="tabComponent(tab)" v-bind="tab"
                                   :user="user" :settings="settings" :config="settings.homeScreenTabsConfig.tabs[tab.index].config"/>
                    </tab>
                </tabs>
            </card-body>
        </card>
    </admin-layout>
</template>

<script>
import Card from "../components/Ui/cards/card";
import CardBody from "../components/Ui/cards/cardBody";
import TabHeaders from "../components/Ui/tabs/tabHeaders";
import TabHeader from "../components/Ui/tabs/tabHeader";
import Tabs from "../components/Ui/tabs/tabs";
import Tab from "../components/Ui/tabs/tab";
import CardHeader from "../components/Ui/cards/cardHeader";
import AbsencesTab from "../components/HomeScreen/AbsencesTab";
import BaptismsTab from "../components/HomeScreen/BaptismsTab";
import CasesTab from "../components/HomeScreen/CasesTab";
import FuneralsTab from "../components/HomeScreen/FuneralsTab";
import MissingEntriesTab from "../components/HomeScreen/MissingEntriesTab";
import NextOfferingsTab from "../components/HomeScreen/NextOfferingsTab";
import NextServicesTab from "../components/HomeScreen/NextServicesTab";
import RegistrationsTab from "../components/HomeScreen/RegistrationsTab";
import StreamingTab from "../components/HomeScreen/StreamingTab";
import WeddingsTab from "../components/HomeScreen/WeddingsTab";
export default {
    name: "HomeScreen",
    components: {CardHeader, TabHeader, TabHeaders, Tabs, Tab, CardBody, Card,
        AbsencesTab, BaptismsTab, CasesTab, FuneralsTab, MissingEntriesTab, NextOfferingsTab, NextServicesTab,
        RegistrationsTab, StreamingTab, WeddingsTab
    },
    props: ['user', 'settings', 'activeTab', 'replacements'],
    beforeMount() {
        var index = 0;
        this.myTabsConfig.tabs.forEach(function (tab, tabIndex) {
            this.myTabs[tab.type+tabIndex] = { title: '', key: tab.type+tabIndex, description: '', count: 0, tabObject: tab}
            axios.get(route('tab', tabIndex)).then(response => {
                tab = response.data;
                this.myTabs[tab.key] = tab;
                this.$forceUpdate();
            });
        }, this);
    },
    data() {
        let myTabNames = this.settings.homeScreenTabs ? this.settings.homeScreenTabs.split(',') : [];
        return {
            myUser: this.user,
            config: this.settings.homeScreenConfig || {},
            myTabNames: myTabNames,
            myTabsConfig: this.settings.homeScreenTabsConfig,
            myTabs: {},
            myActiveTab: this.activeTab || (this.settings.homeScreenTabsConfig.tabs[0] ? this.settings.homeScreenTabsConfig.tabs[0].type+'0' : null),
        }
    },
    methods: {
        tabComponent(tab) {
            return tab.type.charAt(0).toUpperCase() + tab.type.slice(1)+'Tab';
        }
    }
}
</script>

<style scoped>
.card-header {
    padding-bottom: 0 !important;
    background-color: #fcfcfc;
}

ul.nav.nav-tabs {
    margin-bottom: 0;
    border-bottom-width: 0;
}

.tab-setup a{
    font-size: .7em;
    color: #c3c3c3 !important;
}

.tab-setup a:hover {
    color: darkgray !important;
    text-decoration: none;
}

.alert a {
    text-decoration: none;
}

</style>
