<div class="d-flex align-items-center">
    <a href="javascript:;" class="text-body dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
        <i class="ti ti-dots-vertical ti-sm mx-1"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-end m-0">
        @can('mark_attendance-delete')
            <a href="javascript:;" class="dropdown-item delete" data-slug="{{ $model->id }}" data-del-url="{{ route('mark_attendance.destroy', $model->id) }}">Delete</a>
        @endcan
    </div>
</div>
