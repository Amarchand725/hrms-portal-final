<div class="d-flex align-items-center">
    <a href="javascript:;"
        class="edit-btn btn btn-icon btn-label-info waves-effect me-2"
        data-toggle="tooltip" data-placement="top"
        title="Edit Designation"
        tabindex="0" aria-controls="DataTables_Table_0" type="button"
        data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddUser"
        data-edit-url="{{ route('designations.edit', $model->id) }}"
        data-url="{{ route('designations.update', $model->id) }}">
        <i class="ti ti-edit ti-xs"></i>
    </a>
    <a data-toggle="tooltip" data-placement="top" title="Delete Record" href="javascript:;" class="btn btn-icon btn-label-primary waves-effect delete" data-slug="{{ $model->id }}" data-del-url="{{ route('designations.destroy', $model->id) }}">
        <i class="ti ti-trash ti-xs"></i>
    </a>
</div>
