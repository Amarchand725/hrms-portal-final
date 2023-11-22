<table class="table table-bordered">
    <tr>
        <th>Employee</th>
        <td>
            <div class="d-flex justify-content-start align-items-center user-name">
                <div class="avatar-wrapper">
                    <div class="avatar avatar-sm me-3">
                        @if(!empty($model->hasEmployee->profile->profile))
                            <img src="{{ asset('public/admin/assets/img/avatars') }}/{{ $model->hasEmployee->profile->profile }}" alt="Avatar" class="rounded-circle">
                        @else
                            <img src="{{ asset('public/admin/default.png') }}" alt="Avatar" class="rounded-circle">
                        @endif
                    </div>
                </div>
                <div class="d-flex flex-column">
                    <a href="{{ route('employees.show', $model->hasEmployee->slug) }}" class="text-body text-truncate">
                        <span class="fw-semibold">{{ Str::ucfirst($model->hasEmployee->first_name??'') }} {{ Str::ucfirst($model->hasEmployee->last_name??'') }}</span>
                    </a>
                    <small class="text-muted">{{ $model->hasEmployee->email??'-' }}</small>
                </div>
            </div>
        </td>
    </tr>
    <tr>
        <th>Department</th>
        <td>
            @if(!empty($model->hasEmployee->departmentBridge->department->name))
                {{ $model->hasEmployee->departmentBridge->department->name }}
            @else
                '-'
            @endif
        </td>
    </tr>
    <tr>
        <th>Created At</th>
        <td>{{ date('d F Y', strtotime($model->date)) }}</td>
    </tr>
    <tr>
        <td colspan="2">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Attachment</th>
                    </tr>
                </thead>
                <tbody>
                    @if(isset($model->hasAttachments) && !empty($model->hasAttachments) && sizeof($model->hasAttachments) > 0)
                        @foreach($model->hasAttachments as $attachment)
                            <tr>
                                <td>{{ $attachment->title }}</td>
                                <td>
                                    <img src="{{ asset('public/admin/assets/document_attachments') }}/{{ $attachment->attachment }}" style="width:200px" alt="">
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </td>
    </tr>
</table>
