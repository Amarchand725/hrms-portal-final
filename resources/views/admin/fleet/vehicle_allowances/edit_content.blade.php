<div class="col-md-12 mt-2">
    <label class="form-label" for="user">User <span class="text-danger">*</span></label>
    <select name="user" id="user" class="form-select select2">
        <option value="" selected>Select User</option>
        @foreach ($data['users'] as $user)
            <option value="{{ $user->id }}" {{ $data['model']->user_id==$user->id?"selected":"" }}>{{ $user->first_name }} {{ $user->last_name }}</option>
        @endforeach
    </select>
    <div class="fv-plugins-message-container invalid-feedback"></div>
    <span id="user_error" class="text-danger error"></span>
</div>
<div class="col-md-12 mt-2">
    <label class="form-label" for="vehicle">Vehicle <span class="text-danger">*</span></label>
    <input type="text" id="vehicle" name="vehicle" value="{{ $data['model']->vehicle }}" class="form-control" placeholder="Enter vehicle name"/>
    <div class="fv-plugins-message-container invalid-feedback"></div>
    <span id="vehicle_error" class="text-danger error"></span>
</div>
<div class="col-md-12 mt-2">
    <label class="form-label" for="allowance">Allwonce (PKR) <span class="text-danger">*</span></label>
    <input type="number" id="allowance" name="allowance" value="{{ $data['model']->allowance }}" class="form-control" placeholder="Enter allowance"/>
    <div class="fv-plugins-message-container invalid-feedback"></div>
    <span id="allowance_error" class="text-danger error"></span>
</div>
<div class="col-md-12 mt-2">
    <label class="form-label" for="effective_date">Effective Date <span class="text-danger">*</span></label>
    <input type="date" id="effective_date" name="effective_date" value="{{ $data['model']->effective_date }}" class="form-control"/>
    <div class="fv-plugins-message-container invalid-feedback"></div>
    <span id="effective_date_error" class="text-danger error"></span>
</div>

<div class="col-12 col-md-12 mt-2">
    <label class="form-label" for="note">Note</label>
    <textarea class="form-control" rows="5" name="note" id="note" placeholder="Enter note here....">{{ $data['model']->note }}</textarea>
    <div class="fv-plugins-message-container invalid-feedback"></div>
    <span id="note_error" class="text-danger error"></span>
</div>
<script>
    $('select').select2();
</script>