<div class="row">
    <div class="col-md-6">
        <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
        <input type="text" id="email" name="email" value="{{ $data['user_email'] }}" class="form-control" placeholder="Enter email" />
        <div class="fv-plugins-message-container invalid-feedback"></div>
        <span id="email_error" class="text-danger error"></span>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="salary">Salary <span class="text-danger">*</span></label>
        <input type="number" id="salary" name="salary" value="{{ $data['expected_salary'] }}" class="form-control" placeholder="Enter salary" />
        <div class="fv-plugins-message-container invalid-feedback"></div>
        <span id="salary_error" class="text-danger error"></span>
    </div>
</div>
<div class="row mt-2">
    <div class="col-md-6">
        <label class="form-label" for="designation_id">Designation</label>
        <select class="form-select select2" id="designation_id" name="designation_id">
            <option value="" selected>Select designation</option>
            @foreach ($data['designations'] as $designation)
                <option value="{{ $designation->id }}">{{ $designation->title }}</option>
            @endforeach
        </select>
        <div class="fv-plugins-message-container invalid-feedback"></div>
        <span id="designation_id_error" class="text-danger error"></span>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="custom_designation">Custom Designation</label>
        <input type="text" id="custom_designation" value="" name="custom_designation" class="form-control" placeholder="Enter custom designation" />
        <div class="fv-plugins-message-container invalid-feedback"></div>
        <span id="custom_designation_error" class="text-danger error"></span>
    </div>
</div>
<div class="row mt-2">
    <div class="col-md-6">
        <label class="form-label" for="department_id">Department</label>
        <select class="form-select select2" id="department_id" name="department_id">
            <option value="" selected>Select department</option>
            @foreach ($data['departments'] as $department_id)
                <option value="{{ $department_id->id }}" {{ $data['model']->manager_id==$department_id->manager_id?'selected':'' }}>{{ $department_id->name }} - {{ $department_id->manager->first_name??'' }} {{ $department_id->manager->last_name??'' }}</option>
            @endforeach
        </select>
        <div class="fv-plugins-message-container invalid-feedback"></div>
        <span id="department_id_error" class="text-danger error"></span>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="working_shift_id">Shift <span class="text-danger">*</span></label>
        <select class="form-select select2" id="working_shift_id" name="working_shift_id">
            <option value="" selected>Select shift</option>
            @foreach ($data['shifts'] as $shift)
                <option value="{{ $shift->id }}">{{ $shift->name }}</option>
            @endforeach
        </select>
        <div class="fv-plugins-message-container invalid-feedback"></div>
        <span id="working_shift_id_error" class="text-danger error"></span>
    </div>
</div>
<div class="row mt-2">
    <div class="col-md-12">
        <label class="form-label" for="joining_date">Joining Date <span class="text-danger">*</span></label>
        <input type="date" class="form-control" id="joining_date" name="joining_date">
        <div class="fv-plugins-message-container invalid-feedback"></div>
        <span id="joining_date_error" class="text-danger error"></span>
    </div>
</div>

<div class="row mt-2">
    <div class="col-md-12">
        <label class="form-label" for="is_vehicle">Vehicle</label>
        <select class="form-select select2 is_vehicle" id="is_vehicle" name="is_vehicle">
            <option value="1">Yes</option>
            <option value="0" selected>No</option>
        </select>
        <div class="fv-plugins-message-container invalid-feedback"></div>
        <span id="is_vehicle_error" class="text-danger error"></span>
    </div>
    <span class="vehicle-content"></span>
</div>

<div class="col-12 col-md-12 mt-2">
    <label class="form-label" for="note">Note ( <small>Optional</small> )</label>
    <textarea class="form-control" rows="5" name="note" id="note" placeholder="Enter note"></textarea>
    <div class="fv-plugins-message-container invalid-feedback"></div>
    <span id="note_error" class="text-danger error"></span>
</div>
