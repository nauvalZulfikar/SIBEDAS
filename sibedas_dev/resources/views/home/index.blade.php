@extends('layouts.vertical', ['subtitle' => 'Home'])

@section('content')

@include('layouts.partials/page-title', ['title' => 'Home', 'subtitle' => 'Home'])

<div class="container-fluid bg-gradient-primary">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10 col-xl-8">
        <div class="card border-0 shadow-lg">
          <div class="card-body">
            <div class="text-center mb-4">
              <h1 class="display-4 fw-bold text-secondary mb-3">
                <i class="fas fa-building me-3"></i>SIBEDAS PBG
              </h1>
              <div class="bg-secondary" style="height: 3px; width: 80px; margin: 0 auto;"></div>
            </div>
            
            <div class="row">
              <div class="col-12">
                <div class="bg-light rounded-3 p-4 mb-4">
                  <h5 class="text-secondary fw-semibold mb-3">
                    <i class="fas fa-info-circle text-primary me-2"></i>Tentang Aplikasi
                  </h5>
                  <p class="text-secondary mb-0 lh-lg">
                    Aplikasi SIBEDAS PBG merupakan sistem pendukung yang dirancang untuk membantu pimpinan dalam melakukan pengawasan dan monitoring terhadap berkas pengajuan Persetujuan Bangunan Gedung (PBG) yang tercatat di SIMBG.
                  </p>
                </div>
                
                <div class="bg-light rounded-3 p-4">
                  <h5 class="text-secondary fw-semibold mb-3">
                    <i class="fas fa-chart-line text-success me-2"></i>Manfaat & Keunggulan
                  </h5>
                  <p class="text-secondary mb-0 lh-lg">
                    Melalui SIBEDAS PBG, pimpinan dapat memantau secara langsung status perkembangan setiap berkas, mengidentifikasi pengajuan yang belum selesai, dan memastikan tindak lanjut penyelesaian dilakukan tepat waktu. Pengawasan yang lebih terstruktur, cepat, dan akurat ini tidak hanya meningkatkan kualitas pelayanan kepada masyarakat, tetapi juga mendukung tercapainya target Pendapatan Asli Daerah (PAD) melalui optimalisasi penyelesaian berkas PBG.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection