<div class="d-flex justify-content-start align-items-center user-name">
    <div class="avatar-wrapper">
        <div class="avatar avatar-sm me-3">
            @if(!empty($employee->hasEmployee->profile->profile))
                <img src="{{ asset('public/admin/assets/img/avatars') }}/{{ $employee->hasEmployee->profile->profile }}" alt="Avatar" class="rounded-circle img-avatar">
            @else
                <img src="{{ asset('public/admin/default.png') }}" alt="Avatar" class="rounded-circle img-avatar">
            @endif
        </div>
    </div>
    <div class="d-flex flex-column">
        <a href="{{ route('employees.show', $employee->hasEmployee->slug) }}" class="text-body text-truncate">
            <span class="fw-semibold">{{ $employee->hasEmployee->first_name??'' }} {{ $employee->hasEmployee->last_name??'' }}</span>
        </a>
        <small class="text-muted">{{ $employee->hasEmployee->email??'-' }}</small>
    </div>
</div>
