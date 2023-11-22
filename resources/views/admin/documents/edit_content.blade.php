<input type="hidden" id="id" name="document_id" value="{{ $model->id }}">
<div class="row mt-2">
    <div class="col-md-12">
        <label class="form-label" for="employee">Employees <span class="text-danger">*</span></label>

        <select id="employee" name="employee" class="form-select select2">
            <option value="" selected>Select Status</option>
            @if(isset($employees))
                @foreach ($employees as $employee)
                    @php
                        $designation = '';
                        if(!empty($employee->jobHistory->designation->title)) {
                            $designation = '( '. $employee->jobHistory->designation->title. ' )';
                        }
                    @endphp
                    <option value="{{ $employee->slug }}" {{ $model->user_id==$employee->id?"selected":"" }}>{{ $employee->first_name }} {{ $employee->last_name }} {{ $designation }}</option>
                @endforeach
            @endif
        </select>
        <div class="fv-plugins-message-container invalid-feedback"></div>
        <span id="employee_error" class="text-danger error"></span>
    </div>
</div>
<div class="row mt-2">
    <div class="row mt-3 border-top py-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h5 class="card-title">Education Documents</h5>
            <div class="btn-wrapper">
                <button type="button" data-val="2" class="btn btn-label-primary btn-sm add-more-btn"><i class="fa fa-plus"></i></button>
            </div>
        </div>

        <!--document_data-->
        <span class="document_data">
            <div class="row mt-2">
                <div class="col-md-4 mt-3">
                    <label class="form-label" for="title">Title <span class="text-danger">*</span></label>
                    <input type="text" id="title" name="titles[]" class="form-control" placeholder="Enter name" />
                    <div class="fv-plugins-message-container invalid-feedback"></div>
                    <span id="titles_error" class="text-danger error"></span>
                </div>
                <div class="col-md-5 mt-3">
                    <label class="form-label" for="attachments">Attachment <span class="text-danger">*</span></label>
                    <input type="file" id="attachments" name="attachments[]" data-val="1" class="form-control input-file" />
                    <div class="fv-plugins-message-container invalid-feedback"></div>
                    <span id="attachments_error" class="text-danger error"></span>
                </div>
                <div class="col-md-3 mt-3">
                    <label class="form-label">Preview</label>
                    <div class="preview-container-1"></div>
                </div>
            </div>
        </span>
        <span id="add-more-data">
            @if(isset($model->hasAttachments) && !empty($model->hasAttachments) && sizeof($model->hasAttachments) > 0)
                @foreach($model->hasAttachments as $attachment)
                    @php $random = rand(); @endphp
                    <span class="document_data" id="id-{{ $attachment->id }}">
                        <div class="row mt-4 w-full border-top py-2 position-relative">
                            <div class="close-btn-wrapper position-absolute d-flex flex-row-reverse mb-3">
                                <button type="button" class="btn btn-label-primary btn-sm del-btn" data-slug="{{ $attachment->id }}" data-del-url="{{ route('document_attachment.destroy', $attachment->id) }}" style="margin-left:2px"><i class="fa fa-close icon-close"></i></button>
                                <button type="button" class="btn btn-label-primary btn-sm update-btn" data-slug="{{ $attachment->id }}" data-url="{{ route('document_attachment.update', $attachment->id) }}" style="margin-left:2px">Update</button>
                            </div>
                            <div class="col-md-4 mt-3">
                                <input type="text" id="title" name="title" class="form-control" value="{{ $attachment->title }}" placeholder="Enter name" />
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                <span id="title_error" class="text-danger error"></span>
                            </div>
                            <div class="col-md-3 mt-3">
                                <div class="preview-container-{{ $random }}">
                                    <img style="width:50%" src="{{ asset('admin/assets/document_attachments') }}/{{ $attachment->attachment }}" alt="">
                                </div>
                            </div>
                        </div>
                    </span>
                @endforeach
            @endif
        </span>
    </div>
</div>

