@extends('layouts.app')

@section('title', 'Herzlich willkommen')

@section('content')
    @component('components.container')
        <h1>Willkommen, {{ $user->name }}!</h1>
        <a class="btn btn-primary btn-lg" href="{{ route('calendar') }}"><span class="fa fa-calendar"></span> Zum Kalender</a>
        <hr />

        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item active">
                <a class="nav-link active" href="#services" role="tab" data-toggle="tab">Nächste Gottesdienste @if($services->count())<span class="badge badge-primary">{{ $services->count() }}</span> @endif </a>
            </li>
            @canany(['gd-kasualien-lesen', 'gd-kasualien-bearbeiten'])
            <li class="nav-item">
                <a class="nav-link" href="#baptisms" role="tab" data-toggle="tab">Taufen @if($baptisms->count())<span class="badge badge-primary">{{ $baptisms->count() }}</span> @endif </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#funerals" role="tab" data-toggle="tab">Beerdigungen @if($funerals->count())<span class="badge badge-primary">{{ $funerals->count() }}</span> @endif </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#weddings" role="tab" data-toggle="tab">Trauungen @if($weddings->count())<span class="badge badge-primary">{{ $weddings->count() }}</span> @endif </a>
            </li>
            @endcanany
        </ul>

        <div class="tab-content">
            <br />
            <div role="tabpanel" class="tab-pane fade in active show" id="services">
                <h2>Nächste Gottesdienste</h2>
                <p>Angezeigt werden die Gottesdienste der nächsten 2 Monate</p>
                <div class="table-responsive">
                    <table class="table table-striped" width="100%">
                        <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Zeit</th>
                            <th>Ort</th>
                            <th>Details</th>
                            @canany(['gd-opfer-lesen','gd-opfer-bearbeiten'])
                                <th>Opfer</th>
                            @endcanany
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($services as $service)
                            <tr class="@if($service->day->date < \Carbon\Carbon::now())past @else future @endif">
                                <td>{{ $service->day->date->format('d.m.Y') }}</td>
                                <td>{{ $service->timeText() }}</td>
                                <td>{{ $service->locationText() }}</td>
                                <td>
                                    @include('partials.service.details', ['service' => $service])
                                </td>
                                @canany(['gd-opfer-lesen','gd-opfer-bearbeiten'])
                                <td>
                                        {{ $service->offering_goal }}<br />
                                        <small>
                                        Zähler 1: {{ $service->offering_counter1 }}<br />
                                        Zähler 2: {{ $service->offering_counter2 }}</small>
                                </td>
                                @endcanany
                                <td>
                                    @include('partials.service.edit-rites-block', ['service', $service])
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @canany(['gd-kasualien-lesen', 'gd-kasualien-bearbeiten'])
            <div role="tabpanel" class="tab-pane fade" id="baptisms">
                <h2>Taufen</h2>
                <p>Angezeigt werden alle bereits bekannten Taufen in den nächsten 365 Tagen.</p>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Zeit</th>
                            <th>Ort</th>
                            <th>Details</th>
                            <th>Täufling</th>
                            <th>Erstkontakt</th>
                            <th>Taufgespräch</th>
                            <th>Anmeldung</th>
                            <th>Urkunden</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($baptisms as $service)
                            @foreach($service->baptisms as $baptism)
                                <tr class="@if($service->day->date < \Carbon\Carbon::now())past @else future @endif">
                                    @if ($loop->first)
                                        <td rowspan="{{ $service->baptisms->count() }}">{{ $service->day->date->format('d.m.Y') }}</td>
                                        <td rowspan="{{ $service->baptisms->count() }}">{{ $service->timeText() }}</td>
                                        <td rowspan="{{ $service->baptisms->count() }}">{{ $service->locationText() }}</td>
                                        <td rowspan="{{ $service->baptisms->count() }}">
                                            @include('partials.service.details', ['service' => $service])
                                        </td>
                                    @endif
                                    @include('partials.baptism.details', ['baptism' => $baptism])
                                    @if ($loop->first)
                                        <td rowspan="{{ $service->baptisms->count() }}">
                                            @include('partials.service.edit-rites-block', ['service', $service])
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div role="tabpanel" class="tab-pane fade" id="funerals">
                <h2>Beerdigungen</h2>
                <p>Angezeigt werden alle bereits bekannten Beerdigungen.</p>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Zeit</th>
                            <th>Ort</th>
                            <th>Details</th>
                            <th>Person</th>
                            <th>Bestattungsart</th>
                            <th>Trauergespräch</th>
                            <th></th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($funerals as $service)
                            @foreach($service->funerals as $funeral)
                                <tr class="@if($service->day->date < \Carbon\Carbon::now())past @else future @endif">
                                    @if ($loop->first)
                                        <td rowspan="{{ $service->funerals->count() }}">{{ $service->day->date->format('d.m.Y') }}</td>
                                        <td rowspan="{{ $service->funerals->count() }}">{{ $service->timeText() }}</td>
                                        <td rowspan="{{ $service->funerals->count() }}">{{ $service->locationText() }}</td>
                                        <td rowspan="{{ $service->funerals->count() }}">
                                            @include('partials.service.details', ['service' => $service])
                                        </td>
                                    @endif
                                    @include('partials.funeral.details', ['funeral' => $funeral])
                                    <td>
                                        @hasrole('Kirchenregisteramt')
                                        <a class="btn btn-sm btn-secondary" href="{{ route('funeral.form', $funeral->id) }}" title="Formular für Kirchenregisteramt"><span class="fa fa-file-pdf"></span></a>
                                        @endhasrole
                                    </td>
                                    @if ($loop->first)
                                        <td>
                                            @include('partials.service.edit-rites-block', ['service', $service])
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div role="tabpanel" class="tab-pane fade" id="weddings">
                <h2>Trauungen</h2>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Zeit</th>
                            <th>Ort</th>
                            <th>Ehepartner 1</th>
                            <th>Ehepartner 2</th>
                            <th>Traugespräch</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($weddings as $service)
                            @foreach($service->weddings as $wedding)
                                <tr class="@if($service->day->date < \Carbon\Carbon::now())past @else future @endif">
                                    @if ($loop->first)
                                        <td rowspan="{{ $service->weddings->count() }}">{{ $service->day->date->format('d.m.Y') }}</td>
                                        <td rowspan="{{ $service->weddings->count() }}">{{ $service->timeText() }}</td>
                                        <td rowspan="{{ $service->weddings->count() }}">{{ $service->locationText() }}</td>
                                        <td rowspan="{{ $service->weddings->count() }}">
                                            @include('partials.service.details', ['service' => $service])
                                        </td>
                                    @endif
                                    @include('partials.wedding.details', ['wedding' => $wedding])
                                    @if ($loop->first)
                                        <td rowspan="{{ $service->weddings->count() }}">
                                            @include('partials.service.edit-rites-block', ['service', $service])
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endcanany
        </div>
    @endcomponent
@endsection