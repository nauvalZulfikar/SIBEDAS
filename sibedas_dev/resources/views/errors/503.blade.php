@extends('layouts.base', ['subtitle' => 'Service Unavailable - 503'])

@section('body-attribuet')
class="authentication-bg"
@endsection

@section('content')

<div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5">
     <div class="container">
          <div class="row justify-content-center">
               <div class="col-xl-6">
                    <div class="card auth-card">
                         <div class="card-body p-0">
                              <div class="row align-items-center g-0">
                                   <div class="col">
                                        <div class="mx-auto mb-4 text-center">
                                             <img src="{{ asset('images/simbg-dputr.png') }}" alt="auth" height="250" class="mt-5 mb-3" />

                                             <h2 class="fs-22 lh-base">Service Unavailable!</h2>
                                             <p class="text-muted mt-1 mb-4">Our site is currently undergoing scheduled maintenance.<br /> Please check back later.</p>

                                        </div>
                                   </div> <!-- end col -->
                              </div> <!-- end row -->
                         </div> <!-- end card-body -->
                    </div> <!-- end card -->
               </div> <!-- end col -->
          </div> <!-- end row -->
     </div>
</div>
@endsection
