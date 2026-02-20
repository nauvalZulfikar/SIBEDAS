<div class="mb-3">
  <label class="form-label" for="tax_no">Tax No</label>
  <input type="text" id="tax_no" name="tax_no" class="form-control" placeholder="Enter tax_no" value="{{ old('tax_no', $data->tax_no ?? '')  }}" required>
</div>
<div class="mb-3">
  <label class="form-label" for="tax_code">Tax Code</label>
  <input type="text" id="tax_code" name="tax_code" class="form-control" placeholder="Enter tax_code" value="{{ old('tax_code', $data->tax_code ?? '')  }}" required>
</div>
<div class="mb-3">
  <label class="form-label" for="wp_name">WP Name</label>
  <input type="text" id="wp_name" name="wp_name" class="form-control" placeholder="Enter wp_name" value="{{ old('wp_name', $data->wp_name ?? '')  }}" required>
</div>
<div class="mb-3">
  <label class="form-label" for="business_name">Business Name</label>
  <input type="text" id="business_name" name="business_name" class="form-control" placeholder="Enter business_name" value="{{ old('business_name', $data->business_name ?? '')  }}" required>
</div>
<div class="mb-3">
  <label class="form-label" for="address">Address</label>
  <textarea class="form-control" id="address" name="address" rows="5" required>{{ old('address', $data->address ?? '')  }}</textarea>
</div>
<div class="mb-3">
  <label class="form-label" for="start_validity">Start Validity</label>
  <input type="date" id="start_validity" name="start_validity" class="form-control" placeholder="Enter start_validity" value="{{ old('start_validity', $data->start_validity ?? '')  }}" required>
</div>
<div class="mb-3">
  <label class="form-label" for="end_validity">End Validity</label>
  <input type="date" id="end_validity" name="end_validity" class="form-control" placeholder="Enter end_validity" value="{{ old('end_validity', $data->end_validity ?? '')  }}" required>
</div>
<div class="mb-3">
  <label class="form-label" for="tax_value">Tax Value</label>
  <input type="number" id="tax_value" name="tax_value" class="form-control" placeholder="Enter tax_value" value="{{ old('tax_value', $data->tax_value ?? '')  }}" required>
</div>
<div class="mb-3">
  <label class="form-label" for="subdistrict">Subdistrict</label>
  <input type="text" id="subdistrict" name="subdistrict" class="form-control" placeholder="Enter subdistrict" value="{{ old('subdistrict', $data->subdistrict ?? '')  }}" required>
</div>  
<div class="mb-3">
  <label class="form-label" for="village">Village</label>
  <input type="text" id="village" name="village" class="form-control" placeholder="Enter village" value="{{ old('village', $data->village ?? '')  }}" required>
</div>  