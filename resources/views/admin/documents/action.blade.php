<div class="d-flex align-items-center">
    <a href="javascript:;" class="text-body dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
        <i class="ti ti-dots-vertical ti-sm mx-1"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-end m-0">
        <a href="#"
            class="dropdown-item show"
            tabindex="0" aria-controls="DataTables_Table_0"
            type="button"
            data-bs-toggle="modal"
            data-bs-target="#details-modal"
            data-toggle="tooltip"
            data-placement="top"
            title="Document Attachments"
            data-show-url="{{ route('documents.show', $model->id) }}"
            >
            View Details
        </a>
        @can('documents-edit')
            <a href="#"
                data-toggle="tooltip"
                data-placement="top"
                title="Edit Attachments"
                data-edit-url="{{ route('documents.edit', $model->id) }}"
                data-url="{{ route('documents.update_document') }}"
                class="dropdown-item edit-btn"
                type="button"
                tabindex="0"
                aria-controls="DataTables_Table_0"
                type="button"
                data-bs-toggle="modal"
                data-bs-target="#offcanvasAddAnnouncement"
                >
                Edit
            </a>
        @endcan
        @can('documents-delete')
            <a href="javascript:;" class="dropdown-item delete" data-slug="{{ $model->id }}" data-del-url="{{ route('documents.destroy', $model->id) }}">Delete</a>
        @endcan
    </div>
</div>
