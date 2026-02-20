<div class="mb-3">
    <label class="form-label" for="nomor_pelanggan">Nomor Pelanggan</label>
    <input type="text" id="nomor_pelanggan" name="nomor_pelanggan" class="form-control" placeholder="Enter nomor_pelanggan" value="{{ old('nomor_pelanggan', $data->nomor_pelanggan ?? '')  }}" required>
</div>
<div class="mb-3">
    <label class="form-label" for="kota_pelayanan">Kota Pelayanan</label>
    <input type="text" id="kota_pelayanan" name="kota_pelayanan" class="form-control" placeholder="Enter kota_pelayanan" value="{{ old('kota_pelayanan', $data->kota_pelayanan ?? '')  }}" required>
</div>
<div class="mb-3">
    <label class="form-label" for="nama">Nama</label>
    <input type="text" id="nama" name="nama" class="form-control" placeholder="Enter nama" value="{{ old('nama', $data->nama ?? '')  }}" required>
</div>
<div class="mb-3">
    <label class="form-label" for="alamat">Alamat</label>
    <textarea class="form-control" id="alamat" name="alamat" rows="5" required>{{ old('alamat', $data->alamat ?? '')  }}</textarea>
</div>
<div class="mb-3">
    <label class="form-label" for="latitude">Latitude</label>
    <input type="text" id="latitude" name="latitude" class="form-control" placeholder="Enter latitude" value="{{ old('latitude', $data->latitude ?? '')  }}" required>
</div>
<div class="mb-3">
    <label class="form-label" for="longitude">Longitude</label>
    <input type="text" id="longitude" name="longitude" class="form-control" placeholder="Enter longitude" value="{{ old('longitude', $data->longitude ?? '')  }}" required>
</div>