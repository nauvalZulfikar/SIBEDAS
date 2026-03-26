<div class="card">
    <form id="step1Form">
        @csrf
        <div class="card-body">
          <h5 class="card-title mb-2">PBG Task</h5>
          <div class="mb-3">
              <label class="form-label" for="uuid">UUID</label>
              <input type="string" id="uuid" name="uuid" class="form-control" placeholder="Enter UUID">
          </div>
          <div class="mb-3">
              <label class="form-label" for="name">Name</label>
              <input type="string" id="name" name="name" class="form-control" placeholder="Enter name">
          </div>
          <div class="mb-3">
              <label class="form-label" for="owner_name">Owner Name</label>
              <input type="string" id="owner_name" name="owner_name" class="form-control" placeholder="Enter ornwe name">
          </div>
          <div class="mb-3">
              <label class="form-label" for="application_type">Application Type</label>
              <input type="number" id="application_type" name="application_type" class="form-control" placeholder="Enter application type">
          </div>
          <div class="mb-3">
              <label class="form-label" for="application_type_name">Application Type Name</label>
              <input type="string" id="application_type_name" name="application_type_name" class="form-control" placeholder="Enter application type name">
          </div>
          <div class="mb-3">
              <label class="form-label" for="condition">Condition</label>
              <input type="string" id="condition" name="condition" class="form-control" placeholder="Enter condition">
          </div>
          <div class="mb-3">
              <label class="form-label" for="registration_number">Registration Number</label>
              <input type="string" id="registration_number" name="registration_number" class="form-control" placeholder="Enter registration number">
          </div>
          <div class="mb-3">
              <label class="form-label" for="document_number">Document Number</label>
              <input type="string" id="document_number" name="document_number" class="form-control" placeholder="Enter document number">
          </div>
          <div class="mb-3">
              <label class="form-label" for="address">Address</label>
              <input type="string" id="address" name="address" class="form-control" placeholder="Enter address">
          </div>
          <div class="mb-3">
              <label class="form-label" for="status">Status</label>
              <input type="number" id="status" name="status" class="form-control" placeholder="Enter status">
          </div>
          <div class="mb-3">
              <label class="form-label" for="status_name">Status Name</label>
              <input type="string" id="status_name" name="status_name" class="form-control" placeholder="Enter status name">
          </div>
          <div class="mb-3">
              <label class="form-label" for="slf_status">SLF Status</label>
              <input type="string" id="slf_status" name="slf_status" class="form-control" placeholder="Enter slf status">
          </div>
          <div class="mb-3">
              <label class="form-label" for="slf_status_name">SLF Status Name</label>
              <input type="string" id="slf_status_name" name="slf_status_name" class="form-control" placeholder="Enter slf status name">
          </div>
          <div class="mb-3">
              <label class="form-label" for="function_type">Function Type</label>
              <input type="string" id="function_type" name="function_type" class="form-control" placeholder="Enter function type">
          </div>
          <div class="mb-3">
              <label class="form-label" for="consultation_type">Consultation Type</label>
              <input type="string" id="consultation_type" name="consultation_type" class="form-control" placeholder="Enter cosultation type">
          </div>
          <div class="mb-3">
              <label class="form-label" for="due_date">Due Date</label>
              <input type="string" id="due_date" name="due_date" class="form-control" placeholder="Enter due date">
          </div>
          <div class="mb-3">
              <label class="form-label" for="land_certificate_phase">Land Certificate Phase</label>
              <input type="boolean" id="land_certificate_phase" name="land_certificate_phase" class="form-control" placeholder="Enter land certificate phase">
          </div>
          <div class="mb-3">
              <label class="form-label" for="task_created_at">Task Created At</label>
              <input type="string" id="task_created_at" name="task_created_at" class="form-control" placeholder="Enter task created at">
          </div>
        </div>
    </form>
</div>