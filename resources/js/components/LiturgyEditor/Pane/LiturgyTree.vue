<!--
  - Pfarrplaner
  -
  - @package Pfarrplaner
  - @author Christoph Fischer <chris@toph.de>
  - @copyright (c) 2020 Christoph Fischer, https://christoph-fischer.org
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
    <div class="card">
        <div class="card-header">
            <div class="row" title="Klicken, um zu bearbeiten. Ziehen, um das Element im Plan zu verschieben.">
                <div class="col-8">Ablauf der Liturgie</div>
                <div class="col-4 text-right">
                    <div class="dropdown" v-if="hasDownload">
                        <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="dropdownMenuButton"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                title="Dokumente herunterladen">
                            <span class="fa fa-download"></span> Herunterladen
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <div v-for="sheet in sheets">
                                <liturgy-sheet-link :service="service" :sheet="sheet" @open="dialogs[sheet.key] = true"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <draggable :list="blocks" group="blocks" v-bind:="{ghostClass: 'ghost-block'}" class="liturgy-blocks-list"
                       @start="focusOff" @end="saveState" :disabled="!editable" handle=".handle">
                <div v-for="block,blockIndex in blocks" class="liturgy-block"
                     :class="{focused: (focusedBlock == blockIndex) && (focusedItem == null)}"
                     @click="focusBlock(blockIndex)">
                    <div class="row">
                        <div class="col-11 liturgy-block-title">
                            <span class="fa fa-bars handle mr-1" title="Klicken und ziehen, um die Position im Ablauf zu verändern"></span>
                            <span class="fa fa-chevron-circle-right" style="display: none;"></span> {{ block.title }}
                        </div>
                        <div class="col-1 text-right" v-if="editable">
                            <button @click.stop="deleteBlock(blockIndex)" class="btn btn-sm btn-danger"
                                    title="Abschnitt löschen">
                                <span class="fa fa-trash"></span>
                            </button>
                        </div>
                    </div>
                    <div class="row" v-if="editable">
                        <div class="col-12">
                            <button @click.stop="addItem(blockIndex, 'Freetext')" class="btn btn-sm btn-light"
                                    title="Freitext hinzufügen"><span class="fa fa-file"></span>
                            </button>
                            <button @click.stop="addItem(blockIndex, 'Psalm')" class="btn btn-sm btn-light"
                                    title="Psalm hinzufügen"><span class="fa fa-praying-hands"></span>
                            </button>
                            <button @click.stop="addItem(blockIndex, 'Reading')" class="btn btn-sm btn-light"
                                    title="Schriftlesung hinzufügen"><span class="fa fa-bible"></span>
                            </button>
                            <button @click.stop="addItem(blockIndex, 'Sermon')" class="btn btn-sm btn-light"
                                    title="Predigt hinzufügen"><span
                                class="fa fa-microphone-alt"></span></button>
                            <button @click.stop="addItem(blockIndex, 'Song')" class="btn btn-sm btn-light"
                                    title="Lied hinzufügen"><span class="fa fa-music"></span></button>
                            <button @click.stop="addItem(blockIndex, 'Liturgic')" class="btn btn-sm btn-light"
                                    title="Liturgischen Text hinzufügen"><span
                                class="fa fa-file-alt"></span></button>
                        </div>
                    </div>
                    <details-pane v-if="block.editing == true" :service="service" :element="block"
                                  :agenda-mode="agendaMode" :markers="markers"/>

                    <draggable :list="block.items" group="items" class="liturgy-items-list" handle=".handle"
                               v-bind:="{ghostClass: 'ghost-item'}" @start="focusOff" @end="saveState"
                               :disabled="!editable">
                        <div v-for="item,itemIndex in block.items" class="liturgy-item"
                             @click.stop="focusItem(blockIndex, itemIndex)"
                             :class="{focused: (focusedBlock == blockIndex) && (focusedItem == itemIndex)}"
                             :data-block-index="blockIndex" :data-item-index="itemIndex">
                            <div class="row item"
                                 title="Klicken, um zu bearbeiten.">
                                <div class="col-sm-3 item-title">
                                    <span class="fa data-type-icon handle mr-1" :class="icons[item.data_type]"
                                          title="Klicken und ziehen, um die Position im Ablauf zu verändern"></span>
                                    <span class="fa fa-chevron-circle-right"
                                                                       style="display: none;"></span> {{ item.title }}
                                </div>
                                <div class="col-sm-4" v-if="item.data_type == 'sermon'">
                                    <div v-if="service.sermon === null">
                                        <form-selectize v-if="sermons.length > 0" :options="sermons" id-key="id" title-key="title"
                                        label="Bestehende Predigt auswählen" :settings="sermonSelectizeSettings" @input="setSermon($event, item)"/>
                                        <inertia-link :href="route('service.sermon.editor', {service: service.slug})"
                                                      @click.stop=""
                                                      class="btn btn-success"
                                                      title="Hier klicken, um die Predigt jetzt anzulegen">
                                            Neue Predigt anlegen
                                        </inertia-link>
                                    </div>
                                    <div v-else>
                                        <inertia-link :href="route('sermon.editor', {sermon: service.sermon.id})"
                                                      @click.stop="" title="Hier klicken, um die Predigt zu bearbeiten">
                                            {{ service.sermon.title }}<span
                                            v-if="service.sermon.subtitle">: {{ service.sermon.subtitle }}</span>
                                        </inertia-link>
                                        <button class="btn btn-sm btn-light ml-1" @click="setSermon(null, item)"
                                                title="Verknüpfung mit dieser Predigt aufheben">
                                            <span class="fa fa-unlink"></span>
                                        </button>
                                        <br/>
                                        <small>{{ service.sermon.reference }}</small>
                                    </div>
                                </div>
                                <div class="col-sm-4" v-else>{{ itemDescription(item) }}
                                    <span v-if="item.data.needs_replacement" class="badge" :class="dataReplacerClass(item)">
                                        <span class="fa fa-user" :title="dataReplacerTitle(item)"></span>
                                    </span>
                                </div>
                                <div class="col-sm-3 responsible-list"
                                     @click="editResponsibles(blockIndex, itemIndex, item)">
                                    <people-pane v-if="item.editResponsibles==true" :service="service" :element="item"
                                                 :ministries="ministries"/>
                                    <div v-else>
                                        <div v-if="item.data.responsible.length > 0">
                                            <span class="badge badge-light" v-for="record in item.data.responsible"
                                                  v-html="displayResponsible(record)"/>
                                        </div>
                                        <div v-else>
                                            <div v-if="editable">
                                                <span class="fa fa-users"></span> Hier klicken, um Verantwortliche
                                                auszuwählen.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-2 text-right" v-if="editable">
                                    <button @click.stop="deleteItem(blockIndex, itemIndex)"
                                            class="btn btn-sm btn-danger" title="Element löschen">
                                        <span class="fa fa-trash"></span>
                                    </button>
                                </div>
                            </div>
                            <details-pane v-if="item.editing == true" :service="service" :element="item"
                                          :agenda-mode="agendaMode" :markers="markers" />
                        </div>
                    </draggable>
                </div>
            </draggable>
        </div>
        <div class="card-footer">
            <button class="btn btn-success" @click="addBlock"><span class="fa fa-paragraph"></span> Abschnitt
                hinzufügen...
            </button>
            <button class="btn btn-secondary" @click.prevent="modalOpen = true">Ablaufelemente importieren...</button>
        </div>
        <modal title="Elemente importieren" v-if="modalOpen" min-height="50vh"
               @close="importElements" @cancel="modalOpen = false;"
               close-button-label="Importieren" cancel-button-label="Abbrechen" max-width="800">
            <div v-if="importFrom != null">
                <selectize class="form-control source-select" v-model="importFrom" :settings="{
                                placeholder: sourceWait,
                            }">
                    <optgroup label="Vorlagen">
                        <option v-for="agenda in agendas" :value="agenda.id">
                            {{ agenda.text }}
                        </option>
                    </optgroup>
                    <optgroup label="Gottesdienste">
                        <option v-for="service in services" :value="service.id">
                            {{ service.text }})
                        </option>
                    </optgroup>
                </selectize>
            </div>
            <div v-else class="text-align: right; width: 100%; color: darkgray;">
                Importmöglichquellen werden geladen... <span class="fa fa-spin fa-spinner"></span>
            </div>
        </modal>
        <modal v-for="(sheet,sheetKey) in sheets" v-if="dialogs[sheet.key]" :title="sheet.title + ' herunterladen'"
               :key="'dlg'+sheet.key"
               @close="downloadConfiguredSheet(sheet)"
               @cancel="dialogs[sheet.key] = false"
               close-button-label="Herunterladen" cancel-button-label="Abbrechen">
                <component :is="sheet.configurationComponent" :service="service" :sheet="sheet" />
        </modal>
    </div>
</template>

<script>
import draggable from 'vuedraggable'
import LiturgyBlock from "../Elements/LiturgyBlock";
import DetailsPane from "./DetailsPane";
import PeoplePane from "./PeoplePane";
import Selectize from "vue2-selectize";
import Modal from "../../Ui/modals/Modal";
import LiturgySheetLink from "../Elements/LiturgySheetLink";
import FormSelectize from "../../Ui/forms/FormSelectize";
import FullTextLiturgySheetConfiguration from "../LiturgySheets/FullTextLiturgySheetConfiguration";
import A4WordSpecificLiturgySheetConfiguration from "../LiturgySheets/A4WordSpecificLiturgySheetConfiguration";
import SongPPTLiturgySheetConfiguration from "../LiturgySheets/SongPPTLiturgySheetConfiguration";

export default {
    name: "LiturgyTree",
    components: {
        FormSelectize,
        LiturgySheetLink,
        Modal,
        LiturgyBlock,
        DetailsPane,
        PeoplePane,
        draggable,
        Selectize,
        FullTextLiturgySheetConfiguration,
        SongPPTLiturgySheetConfiguration,
        A4WordSpecificLiturgySheetConfiguration,
    },
    props: {
        service: Object,
        sheets: Object,
        agendaMode: {
            type: Boolean,
            default: false,
        },
        autoFocusBlock: {
            type: String,
            default: null,
        },
        autoFocusItem: {
            type: String,
            default: null,
        },
        ministries: {
            type: Object,
            default: [],
        },
        markers: {
            type: Object,
            default: null,
        }
    },
    /**
     * Load existing sources
     * @returns {Promise<void>}
     */
    async created() {
        const sources = await axios.get(route('liturgy.sources', this.service.slug))
        if (sources.data) {
            this.agendas = sources.data.agendas;
            this.services = sources.data.services;
            this.sourceWait = 'Ablaufelemente importieren...';
            this.importFrom = -1;
        }

        this.sermons = (await axios.get(route('liturgy.sermons', this.service.slug))).data;
    },
    mounted() {
        if (this.autoFocusItem && this.autoFocusBlock) {
            var autoFocusBlock = parseInt(this.autoFocusBlock);
            var autoFocusItem = parseInt(this.autoFocusItem);
            var foundBlock = false;
            var foundItem = false;
            this.blocks.forEach(function (block, blockIndex) {
                block.items.forEach(function (item, itemIndex) {
                    if ((block.id == autoFocusBlock) && (item.id == autoFocusItem)) {
                        foundBlock = blockIndex;
                        foundItem = itemIndex;
                    }
                }, this);
            }, this);
            if ((foundBlock !== false) && (foundItem !== false)) this.focusItem(foundBlock, foundItem);
        } else {
            if (this.autoFocusBlock) {
                var autoFocusBlock = parseInt(this.autoFocusBlock);
                var foundBlock = false;
                this.blocks.forEach(function (block, blockIndex) {
                    if (block.id == autoFocusBlock) foundBlock = blockIndex;
                }, this);
                if (foundBlock !== false) this.focusBlock(foundBlock);
            }
        }
    },
    beforeUnmount() {
        // here we need to do some dirty checking and saving!
    },
    data() {
        if (undefined != this.service.liturgy_blocks) {
            var myBlocks = this.service.liturgy_blocks;
        } else {
            var myBlocks = [];
        }
        myBlocks.forEach(function (val, idx) {
            myBlocks[idx].data_type = 'block';
            myBlocks[idx].typeDescription = 'Abschnitt';
            myBlocks[idx].editing = false;
            myBlocks[idx].items.forEach(function (val2, idx2) {
                myBlocks[idx].items[idx2].editing = false;
                myBlocks[idx].items[idx2].editResponsibles = false;
                if (undefined == myBlocks[idx].items[idx2].data.responsible) myBlocks[idx].items[idx2].data.responsible = [];
            });
        });

        var dialogs = {};
        Object.entries(this.sheets).forEach(sheet => {
            if (sheet[1].configurationComponent) dialogs[sheet[1].key] = false;
        });

        return {
            icons: {
                freetext: 'fa-file',
                psalm: 'fa-praying-hands',
                reading: 'fa-bible',
                sermon: 'fa-microphone-alt',
                song: 'fa-music',
                liturgic: 'fa-file-alt',
            },
            blocks: myBlocks,
            focusedBlock: null,
            focusedItem: null,
            editable: true,
            importFrom: null,
            services: [],
            agendas: [],
            hasDownload: (myBlocks.length > 0) && (Object.keys(this.sheets).length > 0),
            sourceWait: 'Bitte warten, Quellen werden geladen...',
            modalOpen: false,
            dialogs: dialogs,
            sermons: [],
            sermonSelectizeSettings: {
                searchField: ['title'],
            }
        }
    },
    methods: {
        addBlock() {
            this.$inertia.post('/liturgy/' + this.service.id + '/block',
                {title: 'Abschnitt ' + (this.blocks.length + 1)},
                {
                    preserveState: false
                }
            );
        },
        deleteBlock(index) {
            this.$inertia.delete(route('liturgy.block.destroy', {
                service: this.service.id,
                block: this.blocks[index].id,
            }), {
                preserveState: false
            });
        },
        saveState() {
            var i = 0;
            this.blocks.forEach(function (block) {
                block.sortable = i++;
                var j = 0;
                block.items.forEach(function (item) {
                    item.sortable = j++;
                })
            })
            this.$inertia.post(route('liturgy.save', this.service.slug), this.blocks)
        },
        addItem(blockIndex, type) {
            if (!this.editable) return false;
            var obj;
            switch (type) {
                case 'Freetext':
                    this.$inertia.post(route('liturgy.item.store', {
                        service: this.service.id,
                        block: this.blocks[blockIndex].id
                    }), {
                        title: 'Freier Text',
                        data_type: 'freetext',
                        data: {description: ''},
                    }, {preserveState: false});
                    break;
                case 'Liturgic':
                    this.$inertia.post(route('liturgy.item.store', {
                        service: this.service.id,
                        block: this.blocks[blockIndex].id
                    }), {
                        title: 'Liturgischer Text',
                        data_type: 'liturgic',
                        data: {id: -1, title: '', text: ''}
                    }, {preserveState: false});
                    break;
                case 'Psalm':
                    this.$inertia.post(route('liturgy.item.store', {
                        service: this.service.id,
                        block: this.blocks[blockIndex].id
                    }), {
                        title: 'Psalmgebet',
                        data_type: 'psalm',
                    }, {preserveState: false});
                    break;
                case 'Reading':
                    this.$inertia.post(route('liturgy.item.store', {
                        service: this.service.id,
                        block: this.blocks[blockIndex].id
                    }), {
                        title: 'Schriftlesung',
                        data_type: 'reading',
                        data: {reference: ''},
                    }, {preserveState: false});
                    break;
                case 'Sermon':
                    this.$inertia.post(route('liturgy.item.store', {
                        service: this.service.id,
                        block: this.blocks[blockIndex].id
                    }), {
                        title: 'Predigt',
                        data_type: 'sermon',
                    }, {preserveState: false});
                    break;
                case 'Song':
                    this.$inertia.post(route('liturgy.item.store', {
                        service: this.service.id,
                        block: this.blocks[blockIndex].id
                    }), {
                        title: 'Lied',
                        data_type: 'song',
                    }, {preserveState: false});
                    break;
            }
            var index = this.blocks[blockIndex].items.push(obj);
        },
        deleteItem(blockIndex, itemIndex) {
            this.$inertia.delete(route('liturgy.item.destroy', {
                service: this.service.id,
                block: this.blocks[blockIndex].id,
                item: this.blocks[blockIndex].items[itemIndex].id,
            }), {preserveState: false});
        },
        focusBlock(blockIndex) {
            if (!this.editable) return false;
            if (this.focusedBlock == blockIndex) {
                this.blocks[blockIndex].editing = false;
                this.focusOff();
            } else {
                this.focusOff();
                this.blocks[blockIndex].editing = true;
                this.focusedBlock = blockIndex;
                this.focusedItem = null;
                this.updateFocus(this.blocks[blockIndex]);
            }
        },
        focusItem(blockIndex, itemIndex) {
            if (!this.editable) return false;
            if ((this.focusedBlock == blockIndex) && (this.focusedItem == itemIndex)) {
                this.blocks[blockIndex].items[itemIndex].editing = false;
                this.focusOff();
            } else {
                this.focusOff();
                this.blocks[blockIndex].items[itemIndex].editing = true;
                this.focusedBlock = blockIndex;
                this.focusedItem = itemIndex;
                this.updateFocus(this.blocks[blockIndex].items[itemIndex]);
            }
        },
        focusOff() {
            this.focusedBlock = this.focusedItem = null;
            this.updateFocus(null);
        },
        itemDescription(item) {
            switch (item.data_type) {
                case 'freetext':
                    if (null === item.data.description) return '';
                    if (undefined === item.data.description) return '';
                    return item.data.description.length > 40 ? item.data.description.substr(0, 40) + '...' : item.data.description;
                case 'liturgic':
                    return item.data.title;
                case 'psalm':
                    if (undefined == item.data.psalm) return '';
                    var title = item.data.psalm.title;
                    if (item.data.psalm.reference) {
                        title = item.data.psalm.reference + ' ' + title;
                    }
                    if (item.data.psalm.songbook_abbreviation) {
                        title = item.data.psalm.songbook_abbreviation + ' ' + title;
                    } else if (item.data.psalm.songbook) {
                        title = item.data.psalm.songbook + ' ' + title;
                    }
                    return title;
                case 'sermon':
                    return;
                case 'reading':
                    return item.data.reference;
                case 'song':
                    if (undefined == item.data.song) return '';
                    var title = item.data.song.title;
                    if (item.data.song.reference) {
                        title = item.data.song.reference + ' ' + title;
                    }
                    if (item.data.song.songbook_abbreviation) {
                        title = item.data.song.songbook_abbreviation + ' ' + title;
                    } else if (item.data.song.songbook) {
                        title = item.data.song.songbook + ' ' + title;
                    }
                    if (item.data.verses) {
                        title = title + ', ' + item.data.verses;
                    }
                    return title;
            }
            return '';
        },
        updateFocus(object) {
            this.editable = (object === null);
            this.$emit('update-focus', this.focusedBlock, this.focusedItem, object);
        },
        save() {
            this.$inertia.post(route('liturgy.save', {service: this.service}), {blocks: this.blocks}, {preserveState: true});
        },
        editResponsibles(blockIndex, itemIndex, item) {
            this.editable = false;
            this.focusItem(blockIndex, itemIndex);
            item.editResponsibles = true;
        },
        displayResponsible(record) {
            var title = '';
            if (typeof record != 'string') return;
            var tmp = record.split(':');
            if (tmp[0] == 'user') {
                this.service.participants.forEach(function (person) {
                    if (person.id == tmp[1]) title = '<span class="fa fa-user-check"></span> ' + person.name;
                });
                return title;
            } else if (tmp[0] == 'ministry') {
                switch (tmp[1]) {
                    case 'pastors':
                        return '<span class="fa fa-users"></span> Pfarrer*in';
                    case 'organists':
                        return '<span class="fa fa-users"></span> Organist*in';
                    case 'sacristans':
                        return '<span class="fa fa-users"></span> Mesner*in';
                }
                return "<span class=\"fa fa-users\"></span> " + tmp[1];
            } else {
                return '<span class="fa fa-user-times"></span> '+tmp[1];
            }
        },
        importElements() {
            if (this.importFrom == -1) return;
            this.$inertia.post(route('liturgy.import', {service: this.service, source: this.importFrom}), {}, {
                preserveState: false,
            })
        },
        downloadConfiguredSheet(sheet) {
            document.getElementById('frm'+sheet.key).submit();
            this.dialogs[sheet.key] = false;
        },
        dataReplacerTitle(item) {
            if (!item.data.needs_replacement) return '';
            var t = 'Dieses Element wird mit Hilfe von persönlichen Daten ';
            var error = '';
            var replacerObject = null;
            this.service[item.data.needs_replacement+'s'].forEach(obj => {
                if (obj.id == item.data.replacement) replacerObject = obj;
            })
            switch (item.data.needs_replacement) {
                case 'funeral':
                    t += 'für eine Bestattung';
                    if (!item.data.replacement) {
                        error = 'Es ist noch keine Bestattung ausgewählt!';
                        if (this.service.funerals.length == 0) error += ' Dem Gottesdienst sind keine Bestattungen zugeordnet!';
                    } else {
                        t +=' ('+replacerObject.buried_name+')';
                    }
                    break;
                case 'baptism':
                    t += 'für eine Taufe';
                    if (!item.data.replacement) {
                        error = 'Es ist noch keine Taufe ausgewählt!';
                        if (this.service.baptisms.length == 0) error += ' Dem Gottesdienst sind keine Taufen zugeordnet!';
                    } else {
                        t +=' ('+replacerObject.candidate_name+')';
                    }
                    break;
                case 'wedding':
                    t += 'für eine Trauung';
                    if (!item.data.replacement) {
                        error = 'Es ist noch keine Trauung ausgewählt!';
                        if (this.service.weddings.length == 0) error += ' Dem Gottesdienst sind keine Trauungen zugeordnet!';
                    } else {
                        t +=' ('+replacerObject.spouse1_name+' / '+replacerObject.spouse2_name+')';
                    }
                    break;
            };
            t += ' angepasst.'+(error ? ' '+error : '');
            return (t)
        },
        dataReplacerClass(item) {
            if (!item.data.needs_replacement) return '';
            if (!item.data.replacement) return 'badge-danger';
            return 'badge-success';
        },
        setSermon(e, item) {
            this.service.sermon_id = e;
            axios.patch(route('service.update', this.service.slug), {sermon_id: e ?? null});
            if (e) {
                this.sermons.forEach(sermon => {
                    if (sermon.id == e) this.service.sermon = sermon;
                });
            } else {
                this.service.sermon = null;
            }
            item.editing = false;
            this.focusedItem = null;
            this.focusedBlock = null;
        }
    }
}
</script>

<style scoped>


.liturgy-block {
    border-top: solid 1px darkgray;
    padding: 3px 5px;
    margin: 5px;
    cursor: pointer;
}

.liturgy-block.focused {
    box-shadow: 0 0 5px rgba(81, 203, 238, 1);
    border: 1px solid rgba(81, 203, 238, 1);
}

.liturgy-blocks-list .liturgy-block:first-child {
    border-top: 0;
}


.liturgy-items-list {
    min-height: 10px;
}

.liturgy-block-title {
    font-weight: bold;
    font-size: 1.4em;
    color: rgb(145, 45, 125);
}

.liturgy-item {
    border-top: dotted 1px gray;
    padding: 3px 0;
    margin: 3px 0px;
    cursor: pointer;
}

.liturgy-item.focused .item-title {
    padding-left: 13px;
    font-weight: bold;
}

.liturgy-item.focused .item-title span.fa,
.liturgy-block.focused .liturgy-block-title span.fa {
    display: inline !important;
}

.liturgy-item.focused {
    box-shadow: 0 0 5px rgba(81, 203, 238, 1);
    border: 1px solid rgba(81, 203, 238, 1);
}


.liturgy-items-list .liturgy-item:first-child {
    border-top: 0;
}

.responsible-list {
    color: gray;
}

.source-select {
    text-align: left;
}

.ghost-item {

}

.handle {
    cursor: move;
}

.item .handle {
    color: gray;
}

.data-type-icon {
    color: gray;
}

</style>
