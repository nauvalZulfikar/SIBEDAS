@extends('layouts.vertical', ['subtitle' => 'Create'])

@section('content')

@include('layouts.partials/page-title', ['title' => 'Data Settings', 'subtitle' => 'Setting Dashboard'])

<x-toast-notification />
<input type="hidden" id="menuId" value="{{ $menuId ?? 0 }}">
<div class="row d-flex justify-content-center">
	<div class="col-lg-6">
		<div class="card">
			<div class="card-body">
				<form id="formUpdateBusinessIndustries" action="{{ route('api-business-industries.update', $data->id) }}" method="POST">
					@csrf
					@method('PUT')
                    <div class="mb-3">
                        <label for="nama_kecamatan" class="form-label">Nama Kecamatan</label>
                        <input type="text" id="nama_kecamatan" class="form-control" name="nama_kecamatan" value="{{ $data->nama_kecamatan }}">
                    </div>

                    <div class="mb-3">
                        <label for="nama_kelurahan" class="form-label">Nama Kelurahan</label>
                        <input type="text" id="nama_kelurahan" class="form-control" name="nama_kelurahan" value="{{ $data->nama_kelurahan }}">
                    </div>

                    <div class="mb-3">
                        <label for="nop" class="form-label">NOP</label>
                        <input type="text" id="nop" class="form-control" name="nop" value="{{ $data->nop }}">
                    </div>

                    <div class="mb-3">
                        <label for="nama_wajib_pajak" class="form-label">Nama Wajib Pajak</label>
                        <input type="text" id="nama_wajib_pajak" class="form-control" name="nama_wajib_pajak" value="{{ $data->nama_wajib_pajak }}">
                    </div>

                    <div class="mb-3">
                        <label for="alamat_wajib_pajak" class="form-label">Alamat Wajib Pajak</label>
                        <input type="text" id="alamat_wajib_pajak" class="form-control" name="alamat_wajib_pajak" value="{{ $data->alamat_wajib_pajak }}">
                    </div>

                    <div class="mb-3">
                        <label for="alamat_objek_pajak" class="form-label">Alamat Objek Pajak</label>
                        <input type="text" id="alamat_objek_pajak" class="form-control" name="alamat_objek_pajak" value="{{ $data->alamat_objek_pajak }}">
                    </div>

                    <div class="mb-3">
                        <label for="luas_bumi" class="form-label">Luas Bumi</label>
                        <input type="number" id="luas_bumi" class="form-control" name="luas_bumi" value="{{ $data->luas_bumi }}">
                    </div>

                    <div class="mb-3">
                        <label for="luas_bangunan" class="form-label">Luas Bangunan</label>
                        <input type="number" id="luas_bangunan" class="form-control" name="luas_bangunan" value="{{ $data->luas_bangunan }}">
                    </div>

                    <div class="mb-3">
                        <label for="njop_bumi" class="form-label">NJOP Bumi</label>
                        <input type="number" id="njop_bumi" class="form-control" name="njop_bumi" value="{{ $data->njop_bumi }}">
                    </div>

                    <div class="mb-3">
                        <label for="njop_bangunan" class="form-label">NJOP Bangunan</label>
                        <input type="number" id="njop_bangunan" class="form-control" name="njop_bangunan" value="{{ $data->njop_bangunan }}">
                    </div>

                    <div class="mb-3">
                        <label for="ketetapan" class="form-label">Ketetapan</label>
                        <input type="text" id="ketetapan" class="form-control" name="ketetapan" value="{{ $data->ketetapan }}">
                    </div>

                    <div class="mb-3">
                        <label for="tahun_pajak" class="form-label">Tahun Pajak</label>
                        <input type="number" id="tahun_pajak" class="form-control" name="tahun_pajak" value="{{ $data->tahun_pajak }}">
                    </div>

                    <button class="btn btn-primary me-1" type="button" id="btnUpdateBusinessIndustries">
                        <span id="spinner" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        Update
                    </button>
				</form>
			</div>
		</div>
	</div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/business-industries/update.js'])
@endsection