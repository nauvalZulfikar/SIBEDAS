@extends('layouts.vertical', ['subtitle' => 'Google Sheets'])

@section('css')
@vite(['node_modules/gridjs/dist/theme/mermaid.min.css'])
@endsection

@section('content')

@include('layouts.partials/page-title', ['title' => 'Data', 'subtitle' => 'Google Sheets'])

<x-toast-notification />

<div class="row">
	<div class="col-12">
		<div class="card w-100">
			<div class="card-body">
				<div class="d-flex flex-wrap justify-content-end align-items-center mb-2">
					<!-- @if ($user_menu_permission['allow_create'])
						<a href="#" class="btn btn-success btn-sm d-block d-sm-inline w-auto">Create</a>
					@endif -->
				</div>
				<div id="table-data-google-sheets"
                     data-updater="{{ $user_menu_permission['allow_update'] }}"
                     data-destroyer="{{ $user_menu_permission['allow_destroy'] }}">
				</div>
			</div>
		</div>
	</div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/data/google-sheet/index.js'])
@endsection