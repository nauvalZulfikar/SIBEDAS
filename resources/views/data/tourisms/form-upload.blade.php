@extends('layouts.vertical', ['subtitle' => 'File Upload'])

@section('content')

@include('layouts.partials/page-title', ['title' => 'Form', 'subtitle' => 'File Uploads'])
<input type="hidden" id="menuId" value="{{ $menuId ?? 0 }}">
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Upload Data Pariwisata</h5>
                    <button id="downloadtemptourisms" class="btn btn-secondary">Download Template</button>
                </div>
            </div>

            <div class="card-body">

                <div class="mb-3">

                    <div class="dropzone">
                        <form action="" method="post" enctype="multipart/form-data">
                            <div class="fallback">
                                <input id="file-dropzone"type="file" name="file" multiple/>
                            </div>
                        </form>
                        <div class="dz-message needsclick">
                            <i class="h1 bx bx-cloud-upload"></i>
                            <h3>Drop files here or click to upload.</h3>
                            <p class="card-subtitle">  
                                Please upload a file with the extension <strong>.xls or .xlsx</strong> with a maximum size of <strong>10 MB</strong>.  
                                <br>
                            </p>
                        </div>
                    </div>

                    <ul class="list-unstyled mb-0" id="dropzone-preview">
                        <li class="mt-2" id="dropzone-preview-list">
                            <!-- This is used as the file preview template -->
                            <div class="border rounded">
                                <div class="d-flex align-items-center p-2">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="avatar-sm bg-light rounded">
                                            <img data-dz-thumbnail class="img-fluid rounded d-block" src="#"
                                                alt="" />
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="pt-1">
                                            <h5 class="fs-14 mb-1" data-dz-name>&nbsp;
                                            </h5>
                                            <p class="fs-13 text-muted mb-0" data-dz-size></p>
                                            <strong class="error text-danger" data-dz-errormessage></strong>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 ms-3">
                                        <button data-dz-remove class="btn btn-sm btn-danger">Delete</button>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <!-- end dropzon-preview -->
                </div>
                <div class="d-flex justify-content-end">
                    <button id="submit-upload" class="btn btn-primary">Upload Files</button>
                </div>
            </div> <!-- end card body -->
        </div> <!-- end card -->
    </div> <!-- end col -->
</div> <!-- end row -->

<div class="toast-container position-fixed end-0 top-0 p-3">
    <div id="toastUploadTourisms" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <div class="auth-logo me-auto">
            </div>
            <small class="text-muted"></small>
            <button type="button" class="btn-close" data-bs-dismiss="toast"
                aria-label="Close"></button>
        </div>
        <div class="toast-body">
        </div>
    </div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/data/tourisms/form-upload.js'])
@endsection