@extends('layouts.vertical', ['subtitle' => 'PBG'])

@section('content')

@include('layouts.partials/page-title', ['title' => 'Create', 'subtitle' => 'PBG'])

<div class="row">
    <!-- Navigasi Step -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <button class="btn btn-outline-secondary" id="prevStep" disabled>
            ← Back
        </button>
        <h4 id="stepTitle">Step 1</h4>
        <button class="btn btn-primary" id="nextStep">
            Next → 
        </button>
    </div>
    <div class="row d-flex justify-content-center">
        <div class="col-md-8 col-lg-8 col-sm-8" id="step1">
            @include("pbg_task._form_pbg_task")
        </div>
    </div>
    <div class="row d-flex justify-content-center">
        <div id="step2" class="step-content d-none col-md-8 col-lg-8 col-sm-8">
            @include("pbg_task._form_pbg_task_retribution")
        </div>
    </div>
    <div class="row d-flex justify-content-center">
        <div id="step3" class="step-content d-none col-md-8 col-lg-8 col-sm-8">
            @include("pbg_task._form_pbg_task_index_integration")
        </div>
    </div>
    <div class="row d-flex justify-content-center">
        <div id="step4" class="step-content d-none col-md-8 col-lg-8 col-sm-8">
            @include("pbg_task._form_pbg_task_prasarana")
        </div>
    </div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/pbg-task/create.js'])
@endsection