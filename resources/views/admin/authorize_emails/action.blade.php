<div class="d-flex align-items-center">
    <a href="javascript:;"
        data-toggle="tooltip"
        data-placement="top"
        title="Edit Authorize Email"
        data-edit-url="{{ route('authorize_emails.edit', $model->id) }}"
        data-url="{{ route('authorize_emails.update', $model->id) }}"
        class="btn btn-icon btn-label-info waves-effect me-2 edit-btn"
        type="button"
        tabindex="0" aria-controls="DataTables_Table_0"
        type="button" data-bs-toggle="modal"
        data-bs-target="#offcanvasAddAnnouncement"
        fdprocessedid="i1qq7b">
        <i class="ti ti-edit ti-xs"></i>
    </a>
    <a href="javascript:;" class="delete btn btn-icon btn-label-primary waves-effect" data-slug="{{ $model->id }}" data-del-url="{{ route('authorize_emails.destroy', $model->id) }}">
        <i class="ti ti-trash ti-xs"></i>
    </a>
</div>
