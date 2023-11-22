@php $counter = 1; @endphp
@foreach ($team_members as $team_member)
    @if(!empty($team_member))
        <tr>
            <td>{{ $counter++ }}</td>
            <td>
                <div class="d-flex justify-content-start align-items-center user-name">
                    <div class="avatar-wrapper">
                        <div class="avatar me-2">
                            @if(isset($team_member->profile) && !empty($team_member->profile->profile))
                                <img src="{{ asset('public/admin/assets/img/avatars') }}/{{ $team_member->profile->profile }}" alt="Avatar" class="rounded-circle">
                            @else
                                <img src="{{ asset('public/admin/assets/img/avatars/default.png') }}" alt="Avatar" class="rounded-circle">
                            @endif
                        </div>
                    </div>
                    <div class="d-flex flex-column">
                        <span class="emp_name fw-semibold text-truncate">
                            {{ $team_member->first_name }} {{ $team_member->last_name }}
                            (
                                @if(isset($team_member->profile) && !empty($team_member->profile->employment_id))
                                   {{ $team_member->profile->employment_id }}
                                @else
                                    -
                                @endif
                            )
                        </span>
                        <small class="emp_post text-truncate text-muted">
                            @if(isset($team_member->jobHistory->designation) && !empty($team_member->jobHistory->designation->title))
                                {{ $team_member->jobHistory->designation->title }}
                            @else
                            -
                            @endif
                        </small>
                    </div>
                </div>
            </td>
            <td>
                <span class="badge bg-label-primary">{{ $team_member->getRoleNames()->first(); }}</span>
            </td>
            <td>
                @if(Auth::user()->hasRole('Admin'))
                    @if(isset($team_member->hasManagerDepartment) && !empty($team_member->hasManagerDepartment->name))
                        <span class="text-primary fw-semibold">{{ $team_member->hasManagerDepartment->name }}</span>
                    @else
                        '-'
                    @endif
                @else
                    @if(isset($team_member->departmentBridge->department) && !empty($team_member->departmentBridge->department))
                        <span class="text-primary fw-semibold">{{ $team_member->departmentBridge->department->name }}</span>
                    @else
                        '-'
                    @endif
                @endif
            </td>
            <td>
                @if(isset($team_member->userWorkingShift->workShift) && !empty($team_member->userWorkingShift->workShift->name))
                    <span class="fw-semibold">{{ $team_member->userWorkingShift->workShift->name }}</span>
                @else
                    '-'
                @endif
            </td>
            <td>
                @if(isset($team_member->employeeStatus->employmentStatus) && !empty($team_member->employeeStatus->employmentStatus->name))
                    @if($team_member->employeeStatus->employmentStatus->name=='Terminated')
                        <span class="badge bg-label-danger me-1">Terminated</span>
                    @elseif($team_member->employeeStatus->employmentStatus->name=='Permanent')
                        <span class="badge bg-label-success me-1">Permanent</span>
                    @elseif($team_member->employeeStatus->employmentStatus->name=='Probation')
                        <span class="badge bg-label-warning me-1">Probation</span>
                    @endif
                @else
                    -
                @endif
            </td>
        </tr>
    @endif
@endforeach
