<div class="card">
    <form id="step3Form">
        @csrf
        <div class="card-body">
            <h5 class="card-title mb-2">PBG Task Indeks Integration</h5>
            <div class="mb-3">
                <label class="form-label" for="pbg_task_uid">PBG Task UID</label>
                <input type="string" id="pbg_task_uid" name="pbg_task_uid" class="form-control" placeholder="Enter pbg task uid">
            </div>
            <div class="mb-3">
                <label class="form-label" for="indeks_fungsi_bangunan">Indeks Fungsi Bangunan</label>
                <input type="string" id="indeks_fungsi_bangunan" name="indeks_fungsi_bangunan" class="form-control" placeholder="Enter indeks fungsi bangunan">
            </div>
            <div class="mb-3">
                <label class="form-label" for="indeks_parameter_kompleksitas">Indeks parameter kompleksitas</label>
                <input type="string" id="indeks_parameter_kompleksitas" name="indeks_parameter_kompleksitas" class="form-control" placeholder="Enter detail updated at">
            </div>
            <div class="mb-3">
                <label class="form-label" for="indeks_parameter_permanensi">Indeks Parameter Permanensi</label>
                <input type="string" id="indeks_parameter_permanensi" name="indeks_parameter_permanensi" class="form-control" placeholder="Enter indeks parameter permanensi">
            </div>
            <div class="mb-3">
                <label class="form-label" for="indeks_parameter_ketinggian">Indeks Parameter Ketinggian</label>
                <input type="string" id="indeks_parameter_ketinggian" name="indeks_parameter_ketinggian" class="form-control" placeholder="Enter indeks parameter ketinggian">
            </div>
            <div class="mb-3">
                <label class="form-label" for="faktor_kepemilikan">Faktor Kepemilikan</label>
                <input type="string" id="faktor_kepemilikan" name="faktor_kepemilikan" class="form-control" placeholder="Enter faktor kepemilikan">
            </div>
            <div class="mb-3">
                <label class="form-label" for="indeks_terintegrasi">Indeks Terintegrasi</label>
                <input type="number" id="indeks_terintegrasi" name="indeks_terintegrasi" class="form-control" placeholder="Enter indeks terintegrasi">
            </div>
            <div class="mb-3">
                <label class="form-label" for="total">Total</label>
                <input type="string" id="total" name="total" class="form-control" placeholder="Enter total">
            </div>
        </div>
    </form>
</div>