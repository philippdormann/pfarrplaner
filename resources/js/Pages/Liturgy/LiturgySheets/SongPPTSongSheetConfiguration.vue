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
    <liturgy-sheet-configuration-layout :title="sheetConfig.title" :service="service"
                                        :sheet-config="sheetConfig">
        <fieldset>
            BLA BLA
            <legend>Folgende Inhalte mit einschließen:</legend>
            <form-check label="Komplette Liedtexte" v-model="myConfig.includeSongTexts" name="config[includeSongTexts]"/>
            <form-check label="Komplette Schriftlesungen" v-model="myConfig.includeFullReadings" name="config[includeFullReadings]"/>
        </fieldset>
    </liturgy-sheet-configuration-layout>
</template>

<script>
import LiturgySheetConfigurationLayout from "./LiturgySheetConfigurationLayout";
import FormCheck from "../../../components/Ui/forms/FormCheck";

export default {
    name: "SongPPTSongSheetConfiguration",
    components: {FormCheck, LiturgySheetConfigurationLayout},
    props: ['service', 'sheetConfig', 'config'],
    data() {
        return {
            myConfig: this.config,
        }
    },
}
</script>

<style scoped>

</style>
