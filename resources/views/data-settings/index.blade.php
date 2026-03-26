@extends('layouts.vertical', ['subtitle' => 'Data Settings'])

@section('css')
@vite(['node_modules/gridjs/dist/theme/mermaid.min.css'])
@endsection

@section('content')

@include('layouts.partials/page-title', ['title' => 'Data Settings', 'subtitle' => 'Setting Dashboard'])

<x-toast-notification />

<div class="row">
	<div class="col-12">
		<div class="card w-100">
			<div class="card-body">
				<div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
					<div class="d-flex gap-2">
						<!-- Space for future buttons if needed -->
					</div>
					
					<div>
						@if ($creator)
							<a href="{{ route('data-settings.create', ['menu_id' => $menuId])}}" class="btn btn-success btn-sm d-block d-sm-inline w-auto">Create</a>
						@endif
					</div>
				</div>
				
				<div id="table-data-settings"
                     data-updater="{{ $updater }}"
                     data-destroyer="{{ $destroyer }}"
					 data-menuId="{{ $menuId }}">
				</div>
			</div>
		</div>
	</div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/data-settings/index.js'])
@endsection