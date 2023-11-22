@extends('admin.layouts.app')
@section('title', $data['title'].' - '. appName())
@push('styles')
    <link href="{{ asset('public/admin/assets/vendor/css/pages/page-profile.css') }}" rel="stylesheet" />
@endpush
@section('content')
    @php
        $begin = new DateTime($data['year'] . "-" . ((int)$data['month'] - 1) . "-26");
        $end = new DateTime($data['year'] . "-" . (int)$data['month'] . '-' . date('d'));

        $employeeData = [];

        foreach ($data['employees'] as $filter_employee) {
            $employeeData[$filter_employee->id] = [
                'shift_id' => null,
            ];

            if (!empty($filter_employee->userWorkingShift)) {
                $employeeData[$filter_employee->id]['shift'] = $filter_employee->userWorkingShift->workShift;
            } else {
                if (isset($filter_employee->departmentBridge->department->departmentWorkShift->workShift) && !empty($filter_employee->departmentBridge->department->departmentWorkShift->workShift->id)) {
                    $employeeData[$filter_employee->id]['shift'] = $filter_employee->departmentBridge->department->departmentWorkShift->workShift;
                }
            }
        }
    @endphp

    @php
        $total_late_in_employees = [];
        $total_half_days_employees = [];
        $total_absent_employees = [];
    @endphp

    @for ($i = $begin; $i <= $end; $i->modify('+1 day'))
        @php
            $day = date("D", strtotime($i->format("Y-m-d")));
        @endphp

        @if ($day != 'Sat' && $day != 'Sun')
            @php
                $attendanceData = collect([]);
            @endphp

            @foreach ($data['employees'] as $filter_employee)
                @php
                    $shift = $employeeData[$filter_employee->id]['shift'];

                    if (!empty($shift)) {
                        $next = date("Y-m-d", strtotime('+1 day ' . $i->format("Y-m-d")));
                        $response = getAttandanceSingleRecord($filter_employee->id, $i->format("Y-m-d"), $next, 'all', $shift);

                        $attendanceData->push($response['type']);
                    }
                @endphp
            @endforeach

            @php
                $total_late_in_employees[] = $attendanceData->filter(function($value) {
                    return $value === 'lateIn';
                })->count();
                $total_half_days_employees[] = $attendanceData->filter(function($value) {
                    return $value === 'lasthalf' || $value === 'firsthalf';
                })->count();
                $total_absent_employees[] = $attendanceData->filter(function($value) {
                    return $value === 'absent';
                })->count();
            @endphp
        @endif
    @endfor

    @php
        $total_regular_todays = [];
        $total_late_in_todays = [];
        $total_half_day_todays = [];
        $total_absent_todays = [];
        $total_team_todays = '';

        foreach($data['employees'] as $employee_member){
            $current_date = date("Y-m-d");
            if(date("H")>=8){
                $next_date = date("Y-m-d", strtotime($current_date.'+1 day'));
            }else{
                $current_date = date("Y-m-d", strtotime($current_date.'-1 day'));
                $next_date = date("Y-m-d", strtotime($current_date.'+1 day'));
            }

            if(!empty($employee_member->userWorkingShift)){
                $shift = $employee_member->userWorkingShift->workShift;
            }else{
                if(isset($employee_member->departmentBridge->department->departmentWorkShift->workShift) && !empty($employee_member->departmentBridge->department->departmentWorkShift->workShift->id)){
                    $shift = $employee_member->departmentBridge->department->departmentWorkShift->workShift;
                }
            }

            if(!empty($shift)){
                $attendance_single_record  = getAttandanceSingleRecord($employee_member->id, $current_date, $next_date,'all', $shift);

                if($attendance_single_record['type']=='lateIn' || $attendance_single_record['type']=='earlyout'){
                    $attendance_date = '';
                    if(!empty($attendance_single_record['attendance_date']->in_date)){
                        $attendance_date = date('d F Y', strtotime($attendance_single_record['attendance_date']->in_date));
                    }else if(!empty($attendance_single_record['attendance_date'])){
                        $attendance_date = date('d F Y', strtotime($attendance_single_record['attendance_date']));
                    }
                    $total_late_in_todays[] = [
                        'employee' => $attendance_single_record['user']->first_name. ' '. $attendance_single_record['user']->last_name,
                        'punchIn' => $attendance_single_record['punchIn'],
                        'punchOut' => $attendance_single_record['punchOut'],
                        'date' => $attendance_date,
                        'type' => $attendance_single_record['type'],
                    ];
                }else if($attendance_single_record['type']=='lasthalf'){
                    $punch_out = '';
                    if($attendance_single_record['punchOut'] != ''){
                        $punch_out = $attendance_single_record['punchOut'];
                    }
                    $attendance_date = '';
                    if(!empty($attendance_single_record['attendance_date']->in_date)){
                        $attendance_date = date('d F Y', strtotime($attendance_single_record['attendance_date']->in_date));
                    }
                    $total_half_day_todays[] = [
                        'employee' => $attendance_single_record['user']->first_name. ' '. $attendance_single_record['user']->last_name,
                        'punchIn' => $attendance_single_record['punchIn'],
                        'punchOut' => $punch_out,
                        'date' => $attendance_date,
                        'type' => $attendance_single_record['type'],
                    ];
                }else if($attendance_single_record['type']=='firsthalf'){
                    $total_half_day_todays[] = [
                        'employee' => $attendance_single_record['user']->first_name. ' '. $attendance_single_record['user']->last_name,
                        'punchIn' => $attendance_single_record['punchIn'],
                        'punchOut' => $attendance_single_record['punchOut'],
                        'date' => date('d F Y', strtotime($attendance_single_record['attendance_date']->in_date)),
                        'type' => $attendance_single_record['type'],
                    ];
                }else if($attendance_single_record['type']=='absent'){
                    $total_absent_todays[] = [
                        'employee' => $attendance_single_record['user']->first_name. ' '. $attendance_single_record['user']->last_name,
                        'type' => $attendance_single_record['type'],
                        'date' => date('d F Y', strtotime($attendance_single_record['attendance_date'])),
                    ];
                }else if($attendance_single_record['type']=='regular'){
                    $total_regular_todays[] = [
                        'employee' => $attendance_single_record['user']->first_name. ' '. $attendance_single_record['user']->last_name,
                        'punchIn' => $attendance_single_record['punchIn'],
                        'punchOut' => $attendance_single_record['punchOut'],
                        'date' => date('d F Y', strtotime($attendance_single_record['attendance_date']->in_date)),
                        'type' => $attendance_single_record['type'],
                    ];
                }
            }
        }
        $total_team_todays = array_merge($total_late_in_todays, $total_half_day_todays, $total_absent_todays, $total_regular_todays);

    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4 d-md-block d-none">
                    <div class="user-profile-header-banner">
                        @if(isset($data['user']->profile->coverImage) && !empty($data['user']->profile->coverImage) && $data['user']->profile->coverImage->status==1)
                            <img src="{{ asset('public/admin/assets/img/pages') }}/{{ $data['user']->profile->coverImage->image }}" alt="Banner image" class="rounded-top img-fluid">
                        @else
                            <img src="{{ asset('public/admin/assets/img/pages/default.png') }}" alt="Banner image" class="rounded-top img-fluid">
                        @endif
                    </div>
                    <div class="user-profile-header d-flex flex-column flex-sm-row text-sm-start text-center mb-4">
                        <div class="flex-shrink-0 mt-n2 mx-sm-0 mx-auto">
                            @if(isset($data['user']->profile->profile) && !empty($data['user']->profile->profile))
                                <img src="{{ asset('public/admin/assets/img/avatars') }}/{{ $data['user']->profile->profile }}" alt="user image" class="d-block h-auto ms-0 ms-sm-4 rounded user-profile-img">
                            @else
                                <img src="{{ asset('public/admin/assets/img/avatars/default.png') }}" alt="user image" class="d-block h-auto ms-0 ms-sm-4 rounded user-profile-img">
                            @endif
                        </div>
                        <div class="flex-grow-1 mt-3 mt-sm-5 mb-4">
                            <div class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-4 flex-md-row flex-column gap-4">
                                <div class="user-profile-info">
                                    <h4 class="text-capitalize">{{ $data['user']->first_name }} {{ $data['user']->last_name }}</h4>
                                    <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-2">
                                        <li class="list-inline-item" data-toggle="tooltip" data-placement="top" title="Designation">
                                            <i class="ti ti-id-badge-2"></i>
                                            @if(isset($data['user']->jobHistory->designation) && !empty($data['user']->jobHistory->designation->title))
                                                {{ $data['user']->jobHistory->designation->title }}
                                            @else
                                                -
                                            @endif
                                        </li>
                                        <li class="list-inline-item">
                                            <i class="ti ti-send"></i> {{ $data['user']->email??'-' }}
                                        </li>
                                        <li class="list-inline-item d-xl-block d-none">
                                            <i class="ti ti-calendar"></i> Joined
                                            @if(isset($data['user']->jobHistory) && !empty($data['user']->jobHistory->joining_date))
                                                {{ date('F Y', strtotime($data['user']->jobHistory->joining_date)) }}
                                            @else
                                                -
                                            @endif
                                        </li>
                                    </ul>
                                </div>
                                <a href="{{ route('profile.edit') }}" class="btn btn-primary waves-effect waves-light">
                                <i class="ti ti-user-check me-1"></i>View Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 mb-4 col-lg-4 col-12">
                <div class="card admin-welcome position-relative">
                    <div class="d-flex align-items-end row">
                        <div class="col-12">
                            <div class="card-body">
                                <h5 class="card-title mb-3 text-capitalize h6 text-truncate">{{ $data['user']->first_name }} {{ $data['user']->last_name }},  Welcome to Your Realm of Leadership: 🏆</h5>
                                <p class="mb-4">
                                    Your vision and leadership spark digital transformation. Let's explore endless opportunities, chart new horizons, and shape a revolutionary future. 🚀🌟🤝
                                </p>
                                <a href="{{ route('profile.edit') }}" class="btn btn-primary">View Profile</a>
                            </div>
                        </div>
                        <div class="col-5 text-center text-sm-left">
                            <div class="card-body pb-0 px-0 px-md-4 welcome-image">
                                <img src="{{ asset('public/admin/assets/img/illustrations/pencil-rocket.png') }}" class="position-absolute" height="140" alt="Pencil Rocket" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8 col-12">
                <div class="row">
                    <div class="col-lg-4 col-md-4 col-12 mb-md-4 mt-md-0 mt-4">
                        <a href="javascript:;" @if(count($total_late_in_todays) > 0) data-bs-toggle="modal" data-bs-target="#AdminTeamlateinModal" class="admin-late-in-box" @endif data-latein="{{ json_encode($total_late_in_todays) }}">
                            <div class="card">
                                <div class="card-body pb-0">
                                    <div class="card-icon">
                                        <span class="badge bg-label-warning rounded-pill p-2">
                                        <i class="ti ti-credit-card ti-sm"></i>
                                        </span>
                                    </div>
                                    <h5 class="card-title mb-0 mt-2 h6 text-truncate">Today Late-In Employees</h5>
                                    <small class="text-muted">{{count($total_late_in_todays)}} Late-in</small>
                                </div>
                                <div id="lateinSummary" data-late-in-summary="{{ json_encode($total_late_in_employees) }}"></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-4 col-12 mt-md-0 mt-4">
                        <a href="javascript:;" @if(count($total_half_day_todays) > 0) data-bs-toggle="modal" data-bs-target="#AdminTeamhalfdayModal" class="admin-half-day-box" @endif data-halfday="{{ json_encode($total_half_day_todays) }}">
                            <div class="card">
                                <div class="card-body pb-0">
                                    <div class="card-icon">
                                        <span class="badge bg-label-danger rounded-pill p-2">
                                        <i class="ti ti-credit-card ti-sm"></i>
                                        </span>
                                    </div>
                                    <h5 class="card-title mb-0 mt-2 h6 text-truncate">Today Half Day Employees</h5>
                                    <small class="text-muted">{{count($total_half_day_todays)}} Half Day</small>
                                </div>
                                <div id="halfdaySummary" data-half-day-summary="{{ json_encode($total_half_days_employees) }}"></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-4 col-12 mb-md-4 mt-md-0 mt-4">
                        <a href="javascript:;" @if(count($total_absent_todays) > 0) data-bs-toggle="modal" data-bs-target="#AdminTeamabsentModal" class="admin-absent-dates-box" @endif data-absent="{{ json_encode($total_absent_todays) }}">
                            <div class="card">
                                <div class="card-body pb-0">
                                    <div class="card-icon">
                                        <span class="badge bg-label-danger rounded-pill p-2">
                                        <i class="ti ti-credit-card ti-sm"></i>
                                        </span>
                                        </div>
                                    <h5 class="card-title mb-0 mt-2 h6 text-truncate">Today Absent Employees</h5>
                                    <small class="text-muted">{{count($total_absent_todays)}} Absent</small>
                                </div>
                                <div id="absentSummary" data-absent-summary="{{ json_encode($total_absent_employees) }}"></div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="row">
                    <div class="col-lg-4 d-md-block d-none">
                        <div class="card">
                            <h5 class="card-header">News & Update</h5>
                            <div class="card-body admin-news scroll-right pb-0">
                                <ul class="timeline mb-0">
                                    @foreach ($data['announcements'] as $announcement)
                                        <li class="timeline-item timeline-item-transparent">
                                            <span class="timeline-point timeline-point-primary"></span>
                                            <div class="timeline-event">
                                                <div class="timeline-header border-bottom mb-3 pb-2">
                                                    <h6 class="mb-0">{{ $announcement->title }}</h6>
                                                    <span class="text-primary">{{ date('d M', strtotime($announcement->created_at)) }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between flex-wrap mb-2">
                                                    <div>{!! $announcement->description !!}</div>
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="row mt-lg-0 mt-4">
                            <div class="col-lg-6 col-md-6">
                                <div class="col-12 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="card-title">
                                                    <h5 class="m-0 me-2 text-truncate">Today Summary</h5>
                                                </div>
                                                <div>
                                                    <div class="time d-xl-block d-none">
                                                        <span class="hms"></span>
                                                        <span class="ampm"></span>
                                                        <br>
                                                        <span class="date"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <h4 class="card-title mb-1">
                                                {{ $data['punchedIn_date'] }}
                                                <!--This is for WFH Users checkout button-->
                                                @if(isset($data['user']->hasWFHEmployee) && !empty($data['user']->hasWFHEmployee))
                                                    <a href="{{ route('user.wfh_checkout') }}" class="btn btn-primary btn-sm">Checkout</a>
                                                @endif
                                            </h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-5">
                                                    <div class="d-flex gap-2 align-items-center mb-2">
                                                    <span class="badge bg-label-info p-1 rounded"
                                                        ><i class="ti ti-arrow-bar-to-down ti-xs"></i
                                                        ></span>
                                                    <p class="mb-0 clock-in">Check In</p>
                                                    </div>
                                                    <h5 class="mb-0 pt-1 text-nowrap">
                                                        {{ $data['punchedIn_time'] }}
                                                    </h5>
                                                </div>
                                                <div class="col-2">
                                                    <div class="divider divider-vertical">
                                                    </div>
                                                </div>
                                                <div class="col-5  text-end">
                                                    <div class="d-flex gap-2 justify-content-end align-items-center mb-2">
                                                    <p class="mb-0 clock-out">Check Out</p>
                                                    <span class="badge bg-label-danger p-1 rounded"><i class="ti ti-arrow-bar-up ti-xs"></i></span>
                                                    </div>
                                                    <h5 class="mb-0 pt-1 text-nowrap ms-lg-n3 ms-xl-0">
                                                        {{ $data['punchedOut_time'] }}
                                                    </h5>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mt-4">
                                                <div class="progress w-100" style="height: 8px">
                                                    <div class="progress-bar bg-info" style="width: {{ $data['check_in_to_current_duration_of_shift'] }}%"
                                                    role="progressbar" aria-valuenow="{{ $data['check_in_to_current_duration_of_shift'] }}"
                                                    aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" title="Time: {{ $data['currentDateTime'] }}">
                                                    </div>
                                                    <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $data['remaining_duration_shift'] }}%"
                                                    aria-valuenow="{{ $data['remaining_duration_shift'] }}" aria-valuemin="0" aria-valuemax="100"
                                                    data-toggle="tooltip" title="End Shift: {{ $data['endShiftTime'] }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="card h-100">
                                        <div class="card-header d-flex justify-content-between pb-0">
                                            <div class="card-title">
                                                <h5 class="m-0 me-2">Discrepancy & Leaves</h5>
                                                <small class="text-muted">This Month</small>
                                            </div>
                                        </div>
                                        <div class="nav-align-top">
                                            <div class="card-body admin-discrepancy-body pb-3 pt-1">
                                                <ul class="nav nav-tabs nav-fill" role="tablist">
                                                    <li class="nav-item">
                                                        <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-justified-discrepancy" aria-controls="navs-justified-discrepancy" aria-selected="true">
                                                            Discrepancy
                                                        </button>
                                                    </li>
                                                    <li class="nav-item">
                                                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-justified-leaves" aria-controls="navs-justified-leaves" aria-selected="false">
                                                            Leaves
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="tab-content p-0 pt-2 pb-3">
                                                <div class="tab-pane fade show active" id="navs-justified-discrepancy" role="tabpanel">
                                                    <div class="table-responsive text-nowrap scroll-bottom input-checkbox admin-discrepancy-team-scroll scroll-right">
                                                        <div class="text-end mb-3 pe-3">
                                                            <a
                                                                href="javascript:;"
                                                                data-show-url="{{ route('team.attendance.get-discrepancies') }}"
                                                                data-toggle="tooltip"
                                                                data-placement="top"
                                                                title="Employees Discrepancies"
                                                                @if(count($data['current_month_discrepancies']) > 0)
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#teamdiscrepancyModal"
                                                                    class="btn btn-primary waves-effect waves-light btn-sm view-modal-btn"
                                                                @else
                                                                    class="btn btn-primary waves-effect waves-light btn-sm"
                                                                @endif
                                                                >
                                                                View All
                                                            </a>
                                                            <button data-modal-id="navs-justified-discrepancy" class="btn btn-primary btn-sm approve-btn" data-status-type="status" disabled data-status-url="{{ route('user.discrepancy.status') }}">Approve</button>
                                                        </div>
                                                        <table class="table table-striped">
                                                            <thead>
                                                                <tr>
                                                                    <th>
                                                                        <div>
                                                                            <input class="form-check-input select-all" type="checkbox" />
                                                                        </div>
                                                                    </th>
                                                                    <th>Employee</th>
                                                                    <th>Attendance</th>
                                                                    <th>Type</th>
                                                                    <th>Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="table-border-bottom-0">
                                                                @foreach ($data['current_month_discrepancies'] as $discrepancy)
                                                                    <tr>
                                                                        <td>
                                                                            <div>
                                                                                <input
                                                                                    @if($discrepancy->status==1)
                                                                                        disabled checked class="form-check-input"
                                                                                    @else
                                                                                        class="form-check-input checkbox"
                                                                                    @endif
                                                                                    type="checkbox"
                                                                                    data-type="{{ $discrepancy->type }}"
                                                                                    value="{{ $discrepancy->id }}"
                                                                                />
                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <div class="d-flex justify-content-start align-items-center user-name">
                                                                                <div class="avatar-wrapper">
                                                                                    <div class="avatar me-2">
                                                                                        @if(isset($discrepancy->hasEmployee->profile) && !empty($discrepancy->hasEmployee->profile->profile))
                                                                                            <img src="{{ asset('public/admin/assets/img/avatars') }}/{{ $discrepancy->hasEmployee->profile->profile }}" alt="Avatar" class="rounded-circle">
                                                                                        @else
                                                                                            <img src="{{ asset('public/admin/default.png') }}" alt="Avatar" class="rounded-circle">
                                                                                        @endif
                                                                                    </div>
                                                                                </div>
                                                                                <div class="d-flex flex-column">
                                                                                    <span class="emp_name fw-semibold text-truncate">
                                                                                        @if(isset($discrepancy->hasEmployee) && !empty($discrepancy->hasEmployee->first_name))
                                                                                            {{ $discrepancy->hasEmployee->first_name }} {{ $discrepancy->hasEmployee->last_name }}
                                                                                        @else
                                                                                        -
                                                                                        @endif
                                                                                    </span>
                                                                                    <small class="emp_post text-truncate text-muted">
                                                                                        @if(isset($discrepancy->hasEmployee->jobHistory->designation) && !empty($discrepancy->hasEmployee->jobHistory->designation->title))
                                                                                            {{ $discrepancy->hasEmployee->jobHistory->designation->title }}
                                                                                        @else
                                                                                        -
                                                                                        @endif
                                                                                    </small>
                                                                                </div>
                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <span class="text-primary fw-semibold">{{ date('d M, Y', strtotime($discrepancy->date)) }}</span>
                                                                        </td>
                                                                        <td>
                                                                            <span data-toggle="tooltip" data-placement="top" @if(isset($discrepancy->hasAttendance)) title="PUNCH TIME: {{ date('h:i A', strtotime($discrepancy->hasAttendance->in_date)) }}" @endif class="badge bg-label-primary" text-capitalized="">{{ Str::ucfirst($discrepancy->type) }}</span>
                                                                        </td>
                                                                        <td>
                                                                            <div class="text-end pe-4">
                                                                                <a href="javascript:;"
                                                                                    data-toggle="tooltip"
                                                                                    data-placement="top"
                                                                                    title="Discrepancy Details"
                                                                                    type="button"
                                                                                    class="btn btn-info btn-sm waves-effect show"
                                                                                    data-show-url="{{ route('user.discrepancy.show', $discrepancy->id) }}"
                                                                                    tabindex="0" aria-controls="DataTables_Table_0"
                                                                                    data-bs-toggle="modal"
                                                                                    data-bs-target="#discrepancyModal">
                                                                                    <i class="ti ti-eye ti-sm"></i>
                                                                                </a>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="navs-justified-leaves" role="tabpanel">
                                                    <div class="tab-pane fade show active" id="navs-justified-new" role="tabpanel">
                                                        <div class="table-responsive text-nowrap scroll-bottom input-checkbox admin-discrepancy-team-scroll scroll-right">
                                                            <div class="text-end mb-3 pe-3">
                                                                <a
                                                                    href="javascript:;"
                                                                    data-show-url="{{ route('team.attendance.get-leaves') }}"
                                                                    data-toggle="tooltip"
                                                                    data-placement="top"
                                                                    title="Employees Leaves"
                                                                    @if(count($data['current_month_leave_requests']) > 0)
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#teamleavesModal"
                                                                        class="btn btn-primary waves-effect waves-light btn-sm view-modal-btn"
                                                                    @else
                                                                        class="btn btn-primary waves-effect waves-light btn-sm"
                                                                    @endif
                                                                    >
                                                                    View All
                                                                </a>
                                                                <button data-modal-id="navs-justified-leaves" class="btn btn-primary btn-sm approve-btn" data-status-type="status" disabled data-status-url="{{ route('user.discrepancy.status') }}">Approve</button>
                                                            </div>
                                                            <table class="table table-striped">
                                                                <thead>
                                                                    <tr>
                                                                        <th>
                                                                            <div>
                                                                                <input class="form-check-input select-all" type="checkbox"/>
                                                                            </div>
                                                                        </th>
                                                                        <th>Employee</th>
                                                                        <th>Date</th>
                                                                        <th>Behavior</th>
                                                                        <th>Duration</th>
                                                                        <th>Action</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="table-border-bottom-0">
                                                                    @foreach ($data['current_month_leave_requests'] as $current_month_leave_request)
                                                                        <tr>
                                                                            <td>
                                                                                <div>
                                                                                    <input
                                                                                        @if($current_month_leave_request->status==1)
                                                                                            disabled checked class="form-check-input"
                                                                                        @else
                                                                                            class="form-check-input checkbox"
                                                                                        @endif
                                                                                        type="checkbox"
                                                                                        data-type="{{ $current_month_leave_request->behavior_type }}"
                                                                                        value="{{ $current_month_leave_request->id }}"
                                                                                    />
                                                                                </div>
                                                                            </td>
                                                                            <td>
                                                                                <div class="d-flex justify-content-start align-items-center user-name">
                                                                                    <div class="avatar-wrapper">
                                                                                        <div class="avatar me-2">
                                                                                            @if(isset($current_month_leave_request->hasEmployee->profile) && !empty($current_month_leave_request->hasEmployee->profile->profile))
                                                                                                <img src="{{ asset('public/admin/assets/img/avatars') }}/{{ $current_month_leave_request->hasEmployee->profile->profile }}" alt="Avatar" class="rounded-circle">
                                                                                            @else
                                                                                                <img src="{{ asset('public/admin') }}/default.png" alt="Avatar" class="rounded-circle">
                                                                                            @endif
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="d-flex flex-column">
                                                                                        <span class="emp_name fw-semibold text-truncate">
                                                                                            @if(isset($current_month_leave_request->hasEmployee) && !empty($current_month_leave_request->hasEmployee))
                                                                                                {{ $current_month_leave_request->hasEmployee->first_name }} {{ $current_month_leave_request->hasEmployee->last_name }}
                                                                                            @else
                                                                                            -
                                                                                            @endif
                                                                                        </span>
                                                                                        <small class="emp_post text-truncate text-muted">
                                                                                            @if(isset($current_month_leave_request->hasEmployee->jobHistory->designation) && !empty($current_month_leave_request->hasEmployee->jobHistory->designation->title))
                                                                                                {{ $current_month_leave_request->hasEmployee->jobHistory->designation->title }}
                                                                                            @else
                                                                                            -
                                                                                            @endif
                                                                                        </small>
                                                                                    </div>
                                                                                </div>
                                                                            </td>
                                                                            <td><span class="text-primary fw-semibold">{{ date('d M, Y', strtotime($current_month_leave_request->start_at)) }}</span></td>
                                                                            <td>
                                                                                @if($current_month_leave_request->behavior_type == 'absent')
                                                                                    <span class="badge bg-label-danger me-1">Absent</span>
                                                                                @elseif($current_month_leave_request->behavior_type == 'lasthalf')
                                                                                    <span class="badge bg-label-warning me-1">Last Half</span>
                                                                                @elseif($current_month_leave_request->behavior_type == 'firsthalf')
                                                                                    <span class="badge bg-label-info me-1">First Half</span>
                                                                                @else
                                                                                    <span class="badge bg-label-primary me-1">{{ $current_month_leave_request->behavior_type }}</span>
                                                                                @endif
                                                                            </td>
                                                                            <td><span class="badge bg-label-danger me-1"> {{ $current_month_leave_request->duration }}</span></td>
                                                                            <td>
                                                                                <div class="text-end pe-4">
                                                                                    <a
                                                                                        href="javascript:;"
                                                                                        data-toggle="tooltip"
                                                                                        data-placement="top"
                                                                                        title="Leave Details"
                                                                                        type="button"
                                                                                        class="btn btn-secondary btn-primary btn-sm mx-3 show"
                                                                                        data-show-url="{{ route('user_leaves.show', $current_month_leave_request->id) }}"
                                                                                        data-bs-toggle="modal"
                                                                                        data-bs-target="#leavesModal">
                                                                                        <i class="ti ti-eye ti-sm"></i>
                                                                                    </a>
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-12 mb-4 mt-md-0 mt-4">
                                        <div class="card summary-graph">
                                            <div class="card-header d-xl-flex align-items-center justify-content-between">
                                                <div>
                                                    <h5 class="card-title mb-0" id="modal-label">All Employees Summary</h5>
                                                    <small class="text-muted">Today</small>
                                                </div>
                                                <div class="dropdown d-none d-sm-flex mt-xl-0 mt-2">
                                                    <a
                                                        href="javascript:;"
                                                        data-today-summary="{{ json_encode($total_team_todays) }}"
                                                        data-toggle="tooltip"
                                                        data-placement="top"
                                                        title="All Employees Summary"
                                                        @if(count($total_team_todays) > 0)
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#adminteamfilterModal"
                                                            class="btn btn-primary waves-effect waves-light btn-sm admin-team-summary-box"
                                                        @else
                                                            class="btn btn-primary waves-effect waves-light btn-sm"
                                                        @endif
                                                        >
                                                        View All
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div id="teamChart"
                                                    data-regular="{{ count($total_regular_todays) }}"
                                                    data-late-in="{{ count($total_late_in_todays) }}"
                                                    data-absent="{{ count($total_absent_todays) }}"
                                                    data-half-day="{{ count($total_half_day_todays) }}"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h5 class="card-header mt-1">My Team</h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="text-end mt-4 pe-4">
                                                        <a
                                                            href="javascript:;"
                                                            @if(count($data['team_members'])>0)
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#teamModal"
                                                                class="btn btn-primary waves-effect waves-light btn-sm view-modal-btn"
                                                            @else
                                                                class="btn btn-primary waves-effect waves-light btn-sm"
                                                            @endif
                                                            data-toggle="tooltip"
                                                            data-placement="top"
                                                            title="My Team"
                                                            data-show-url="{{ route('employees.get-team-members', $data['user']->id) }}"
                                                        >
                                                        View All
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="table-responsive">
                                                <div class="admin-team-scroll scroll-right">
                                                    <table class="table table-striped">
                                                    <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Status</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody class="table-border-bottom-0">
                                                        @php $counter = 1; @endphp
                                                        @foreach ($data['team_members'] as $team_member)
                                                            @if(!empty($team_member))
                                                                <tr>
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
                                                                            <span class="emp_name fw-semibold text-truncate">{{ $team_member->first_name }} {{ $team_member->last_name }}</span>
                                                                            <small class="emp_post text-muted">
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
                                                                    @if(isset($team_member->employeeStatus->employmentStatus) && !empty($team_member->employeeStatus->employmentStatus->name))
                                                                        @if($team_member->employeeStatus->employmentStatus->name=='Terminated')
                                                                            <span class="badge bg-label-dagner me-1">Terminated</span>
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
                                                    </tbody>
                                                </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.dashboards.admin-pop-up-modals')
    @include('admin.dashboards.pop-up-modals')
@endsection
@push('js')
    <script src="{{ asset('public/admin/assets/js/custom-dashboard.js') }}" defer></script>
    <script>
        function updateTime() {
         var dateInfo = new Date();

         /* time */
         var hr,
           _min = (dateInfo.getMinutes() < 10) ? "0" + dateInfo.getMinutes() : dateInfo.getMinutes(),
           sec = (dateInfo.getSeconds() < 10) ? "0" + dateInfo.getSeconds() : dateInfo.getSeconds(),
           ampm = (dateInfo.getHours() >= 12) ? "PM" : "AM";

         // replace 0 with 12 at midnight, subtract 12 from hour if 13–23
         if (dateInfo.getHours() == 0) {
           hr = 12;
         } else if (dateInfo.getHours() > 12) {
           hr = dateInfo.getHours() - 12;
         } else {
           hr = dateInfo.getHours();
         }

         var currentTime = hr + ":" + _min + ":" + sec;

         // print time
         document.getElementsByClassName("hms")[0].innerHTML = currentTime;
         document.getElementsByClassName("ampm")[0].innerHTML = ampm;

         /* date */
         var dow = [
             "Sunday",
             "Monday",
             "Tuesday",
             "Wednesday",
             "Thursday",
             "Friday",
             "Saturday"
           ],
           month = [
             "January",
             "February",
             "March",
             "April",
             "May",
             "June",
             "July",
             "August",
             "September",
             "October",
             "November",
             "December"
           ],
           day = dateInfo.getDate();

         // store date
         var currentDate = dow[dateInfo.getDay()] + ", " + month[dateInfo.getMonth()] + " " + day;

         document.getElementsByClassName("date")[0].innerHTML = currentDate;
       };

       // print time and date once, then update them every second
       updateTime();
       setInterval(function() {
         updateTime()
       }, 1000);
    </script>
@endpush
