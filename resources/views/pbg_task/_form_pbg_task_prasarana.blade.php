<div class="card">
    <form id="step4Form">
        @csrf
        <div class="card-body">
            <h5 class="card-title mb-2">PBG Task Prasarana</h5>
            <div class="mb-3">
                <label class="form-label" for="pbg_task_uid">PBG Task UID</label>
                <input type="string" id="pbg_task_uid" name="pbg_task_uid" class="form-control" placeholder="Enter pbg task uid">
            </div>
            <div class="mb-3">
                <label class="form-label" for="prasarana_id">Prasarana ID</label>
                <input type="string" id="prasarana_id" name="prasarana_id" class="form-control" placeholder="Enter prasarana id">
            </div>
            <div class="mb-3">
                <label class="form-label" for="prasarana_type">Prasarana Type</label>
                <input type="string" id="prasarana_type" name="prasarana_type" class="form-control" placeholder="Enter prasarana type">
            </div>
            <div class="mb-3">
                <label class="form-label" for="building_type">Building Type</label>
                <input type="string" id="building_type" name="building_type" class="form-control" placeholder="Enter building type">
            </div>
            <div class="mb-3">
                <label class="form-label" for="total">Total</label>
                <input type="string" id="total" name="total" class="form-control" placeholder="Enter total">
            </div>
            <div class="mb-3">
                <label class="form-label" for="quantity">Quantity</label>
                <input type="string" id="quantity" name="quantity" class="form-control" placeholder="Enter quantity">
            </div>
            <div class="mb-3">
                <label class="form-label" for="unit">Unit</label>
                <input type="number" id="unit" name="unit" class="form-control" placeholder="Enter unit">
            </div>
            <div class="mb-3">
                <label class="form-label" for="index_prasarana">Index Prasarana</label>
                <input type="string" id="index_prasarana" name="index_prasarana" class="form-control" placeholder="Enter indeks prasarana">
            </div>
        </div>
    </form>
</div>