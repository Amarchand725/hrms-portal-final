@extends('admin.layouts.app')
@section('title', $title .' - '. appName())
@php
    use App\Http\Controllers\AttendanceController;
    use Carbon\Carbon;
@endphp

@section('content')
    <input type="hidden" id="current_user_slug" value="{{ $user->slug }}" >
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="card mb-4">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card-header">
                            <h4 class="fw-bold mb-0"><span class="text-muted fw-light">Home /</span> {{ $title }} of month: {{ date('F, Y', mktime(0, 0, 0, $month, 1, $year)); }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    @php
                        $navDate = $year.'-'.$month.'-01';
                        $prevMonth=strtotime($navDate.' -1 month');
                        $nextMonth=strtotime($navDate.' +1 month');
                    @endphp

                    <div class="d-flex align-items-center justify-content-end">
                        <button class="btn btn-primary waves-effect waves-light" data-joining-date="{{ $user_joining_date }}" data-current-month="{{ $currentMonth }}" id="Slipbutton">Select Month<i class="ti ti-chevron-down ms-2"></i></button>
                    </div>
                </div>
                <div class="card-header d-flex justify-content-between align-items-center row">
                    <div class="col-md-8">
                        <span class="card-title mb-0">
                            <a href="{{ route('employees.show', $user->slug) }}" class="text-body text-truncate">
                                <div class="d-flex align-items-center">
                                    @if(isset($user->profile) && !empty($user->profile->profile))
                                        <img src="{{ asset('public/admin/assets/img/avatars') }}/{{ $user->profile->profile }}" style="width:40px !important; height:40px !important" alt class="h-auto rounded-circle" />
                                    @else
                                        <img src="{{ asset('public/admin') }}/default.png" style="width:40px !important; height:40px !important" alt class="h-auto rounded-circle" />
                                    @endif
                                    <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                        <div class="mx-3">
                                            <div class="d-flex align-items-center">
                                                <h6 class="mb-0 me-1 text-capitalize">{{ $user->first_name }} {{ $user->last_name }}</h6>
                                            </div>
                                            <small class="text-muted">
                                                @if(isset($user->jobHistory->designation->title) && !empty($user->jobHistory->designation->title))
                                                    {{ $user->jobHistory->designation->title }}
                                                @else
                                                    -
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </span>
                    </div>
                    <div class="col-md-4">
                        @if(isset($employees) && !empty($employees))
                        <div class="row">
                            <div class="col-lg-12 col-md-12">
                                <select class="select2 form-select" id="redirectDropdown" onchange="redirectPage(this)">
                                    <option value="" selected>Select employee</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ URL::to('user/attendance/summary/'.$month.'/'.$year.'/'.$employee->slug) }}" {{ $user->slug==$employee->slug?'selected':'' }}>{{ $employee->first_name }} {{ $employee->last_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="card-header border-top">
                    @php
                        $statistics = AttendanceController::getAttandanceCount($user->id, $year."-".((int)$month-1)."-26", $year."-".(int)$month."-25",'all', $shift);

                        $shiftStart = date("H:i:s", strtotime('-6 hours '.$shift->start_time));
                        $shiftEnd = date("H:i:s", strtotime('+6 hours '.$shift->end_time));

                        $start_time = explode(':', $shiftStart);
                        $end_time = explode(':', $shiftEnd);

                        $last_month = date('m');
                        if ($last_month == $month) {
                            $last_month = date('m', strtotime('-1 month'));
                        }

                        $currentMonthStart = Carbon::create($year, $last_month, 26, $start_time[0], $start_time[1], 0); // e.g: Start time 21:00:00
                        $currentMonthEnd = Carbon::create($year, $month, 26, $end_time[0], $end_time[1], 0); // e.g: End time 6:00:00 AM
                        $isCurrentTimeInRange = now()->between($currentMonthStart, $currentMonthEnd);
                    @endphp

                    <div class="card mb-4">
                        <div class="card-widget-separator-wrapper">
                            <div class="card-body card-widget-separator">
                                <div class="row">
                                    <div class="col-sm-2 col-lg-2">
                                        <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-3 pb-sm-0">
                                            <div>
                                              <h4 class="mb-2">{{$statistics['totalDays']}}</h4>
                                              <p class="mb-0 fw-medium">Working Days</p>
                                            </div>
                                            <span class="avatar p-2 me-lg-4">
                                              <span class="avatar-initial bg-label-secondary rounded"><i class="ti-md ti ti-calendar-stats text-primary"></i></span>
                                            </span>
                                        </div>
                                        <hr class="d-none d-sm-block d-lg-none me-4">
                                    </div>
                                    <div class="col-sm-2 col-lg-2">
                                        <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-3 pb-sm-0">
                                            <div>
                                                <h4 class="mb-2">{{$statistics['workDays']}}</h4>
                                                <p class="mb-0 fw-medium">Regular</p>
                                            </div>
                                            <span class="avatar p-2 me-lg-4">
                                              <span class="avatar-initial bg-label-secondary rounded"><i class="ti-md ti ti-square-check text-primary"></i></span>
                                            </span>
                                        </div>
                                        <hr class="d-none d-sm-block d-lg-none me-4">
                                    </div>
                                    <div class="col-sm-2 col-lg-2">
                                        <span
                                            @if(count($statistics['lateInDates']) > 0 && $isCurrentTimeInRange) data-bs-toggle="modal" data-bs-target="#teamlateinModal" class="late-in-box" @endif
                                            @if(Auth::user()->slug != $user->slug) data-user="false" @else data-user="true" @endif
                                            data-user-id="{{ $user->id }}"
                                            data-latein="{{ json_encode($statistics['lateInDates']) }}"
                                        >
                                            <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-3 pb-sm-0">
                                                <div>
                                                  <h4 class="mb-2">{{$statistics['lateIn']}}</h4>
                                                  <p class="mb-0 fw-medium">Late In</p>
                                                </div>
                                                <span class="avatar p-2 me-lg-4">
                                                  <span class="avatar-initial bg-label-secondary rounded"><i class="ti-md ti ti-arrow-bar-to-down text-primary"></i></span>
                                                </span>
                                            </div>
                                        </span>
                                        <hr class="d-none d-sm-block d-lg-none me-4">
                                    </div>
                                    <div class="col-sm-2 col-lg-2">
                                        <span
                                            @if(count($statistics['earlyOutDates']) > 0 && $isCurrentTimeInRange) data-bs-toggle="modal" data-bs-target="#teamEarlyOutModal" class="early-out-box" @endif
                                            @if(Auth::user()->slug != $user->slug) data-user="false" @else data-user="true" @endif
                                            data-user-id="{{ $user->id }}"
                                            data-earlyOut="{{ json_encode($statistics['earlyOutDates']) }}"
                                        >
                                            <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-3 pb-sm-0">
                                                <div>
                                                  <h4 class="mb-2">{{$statistics['earlyOut']}}</h4>
                                                  <p class="mb-0 fw-medium">Early Out</p>
                                                </div>
                                                <span class="avatar p-2 me-lg-4">
                                                  <span class="avatar-initial bg-label-secondary rounded"><i class="ti-md ti ti-arrow-bar-to-up text-primary"></i></span>
                                                </span>
                                            </div>
                                        </span>
                                        <hr class="d-none d-sm-block d-lg-none me-4">
                                    </div>
                                    <div class="col-sm-2 col-lg-2">
                                        <span
                                            @if(count($statistics['halfDayDates']) > 0 && $isCurrentTimeInRange) data-bs-toggle="modal" data-bs-target="#teamhalfdayModal" class="half-day-box" @endif
                                            @if(Auth::user()->slug != $user->slug) data-user="false" @else data-user="true" @endif
                                            @if($remaining_filable_leaves > 1) data-remaining-leaves="true" @else data-remaining-leaves="false" @endif
                                            data-user-id="{{ $user->id }}"
                                            data-halfday="{{ json_encode($statistics['halfDayDates']) }}">
                                            <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-3 pb-sm-0">
                                                <div>
                                                  <h4 class="mb-2">{{$statistics['halfDay']}}</h4>
                                                  <p class="mb-0 fw-medium">Half Day</p>
                                                </div>
                                                <span class="avatar p-2 me-lg-4">
                                                  <span class="avatar-initial bg-label-secondary rounded"><i class="ti-md ti ti-square-half text-primary"></i></span>
                                                </span>
                                            </div>
                                        </span>
                                        <hr class="d-none d-sm-block d-lg-none me-4">
                                    </div>
                                    <div class="col-sm-2 col-lg-2">
                                        <span
                                            @if(count($statistics['absent_dates']) > 0 && $isCurrentTimeInRange) data-bs-toggle="modal" data-bs-target="#teamabsentModal" class="absent-dates-box" @endif
                                            @if(Auth::user()->slug != $user->slug) data-user="false" @else data-user="true" @endif
                                            @if($remaining_filable_leaves > 0) data-remaining-leaves="true" @else data-remaining-leaves="false" @endif
                                            data-user-id="{{ $user->id }}"
                                            data-absent="{{ json_encode($statistics['absent_dates']) }}">
                                            <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-3 pb-sm-0">
                                                <div>
                                                  <h4 class="mb-2">{{$statistics['absent']}}</h4>
                                                  <p class="mb-0 fw-medium">Absents</p>
                                                </div>
                                                <span class="avatar p-2 me-lg-4">
                                                  <span class="avatar-initial bg-label-secondary rounded"><i class="ti-md ti ti-clock-off text-primary"></i></span>
                                                </span>
                                            </div>
                                        </span>
                                        <hr class="d-none d-sm-block d-lg-none me-4">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <table class="attendance-table table table-border">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Shift Time</th>
                                <th>Punched In</th>
                                <th>Punched Out</th>
                                <th>Status</th>
                                <th>Working Hours</th>
                                @if(Auth::user()->hasRole('Admin'))
                                    <th>Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $begin = new DateTime($year."-".((int)$month-1)."-26");

                                if((isset(Auth::user()->employeeStatus->start_date) && !empty(Auth::user()->employeeStatus->start_date)) && Auth::user()->employeeStatus->start_date > $begin->format('Y-m-d')){
                                    $start_date = new DateTime(Auth::user()->employeeStatus->start_date);
                                }
                                $end   = new DateTime($year."-".(int)$month."-25");
                            @endphp
                            @for($i = $begin; $i <= $end; $i->modify('+1 day'))
                                @php
                                    $day=date("D", strtotime($i->format("Y-m-d")));
                                    $next=date("Y-m-d", strtotime('+1 day '.$i->format("Y-m-d")));
                                    $reponse = AttendanceController::getAttandanceSingleRecord($user->id,$i->format("Y-m-d"),$next,'all',$shift);
                                @endphp
                                @if($reponse!=null)
                                    @php
                                        $applied = userAppliedLeaveOrDiscrepency($user->id, $reponse['type'], $i->format("Y-m-d"));
                                    @endphp
                                    @if(!empty($reponse['attendance_id']))
                                        @php
                                            $attendance_adjustment = attendanceAdjustment($user->id, $reponse['attendance_id']);
                                        @endphp
                                    @endif
                                    <tr class="{{$day}}">
                                        <td>{{$i->format("d-m-Y")}}</td>
                                        <td>{{$reponse['shiftTiming']}}</td>
                                        <td>
                                            @if($day!='Sat' && $day!='Sun')
                                                <span class="punchedin d-block mb-2">{{$reponse['punchIn']}}</span>
                                                @if($isCurrentTimeInRange && ($begin->format('Y-m-d') >= $start_date->format('Y-m-d')))
                                                    @if(empty($applied))
                                                        @php
                                                            $currentDate = date('Y-m-d'); // Current date in 'Y-m-d' format
                                                            $midnightTimestamp = strtotime($currentDate . ' 00:00:00'); // Midnight timestamp
                                                        @endphp
                                                        @if(isset($attendance_adjustment) && !empty($attendance_adjustment->mark_type) && ($i->format("Y-m-d") <= date('Y-m-d')))
                                                            @if($attendance_adjustment->mark_type=='lateIn' || $attendance_adjustment->mark_type=='firsthalf' || $attendance_adjustment->mark_type=='absent')
                                                                @php $type_label = ''; $apply_date='' @endphp
                                                                @if($attendance_adjustment=='firsthalf' || $attendance_adjustment=='absent')
                                                                    @php
                                                                        $type_label = 'Apply Leave';
                                                                        $apply_date = date('d-m-Y', strtotime($attendance_adjustment->hasAttendance->in_date));
                                                                    @endphp
                                                                @else
                                                                    @php
                                                                        $type_label = 'Apply Discrepency';
                                                                        $apply_date = $attendance_adjustment->hasAttendance->id;
                                                                    @endphp
                                                                @endif

                                                                <a href="#"
                                                                    class="badge bg-label-danger"
                                                                    id="custom-add-btn"
                                                                    tabindex="0" aria-controls="DataTables_Table_0"
                                                                    type="button" data-bs-toggle="modal"
                                                                    data-bs-target="#discrepency-or-leave-modal"
                                                                    data-toggle="tooltip"
                                                                    data-placement="top"
                                                                    title="{{ $type_label }}"
                                                                    data-url="{{ route('user_leaves.store') }}"
                                                                    data-user="{{ $user->slug }}"
                                                                    data-date='{{$apply_date}}'
                                                                    data-type="{{ $attendance_adjustment->mark_type }}"
                                                                    data-leave-types="{{ json_encode($leave_types) }}"
                                                                    >
                                                                    @if($attendance_adjustment->mark_type!="lateIn")
                                                                        Leave
                                                                    @else
                                                                        Discrepency
                                                                    @endif
                                                                </a>
                                                            @endif
                                                        @elseif(strtotime($i->format("Y-m-d")) <= $midnightTimestamp)
                                                            @if($reponse['type']=='lateIn' || $reponse['type']=='firsthalf' || $reponse['type']=='absent')
                                                                @php $type_label = ''; $apply_date='' @endphp
                                                                @if($reponse['type']=='firsthalf' || $reponse['type']=='absent')
                                                                    @php
                                                                        $type_label = 'Apply Leave';
                                                                        $apply_date = $i->format("d-m-Y");
                                                                    @endphp
                                                                @else
                                                                    @php
                                                                        $type_label = 'Apply Discrepency';
                                                                        $apply_date = $reponse['attendance_date']->id??'';
                                                                    @endphp
                                                                @endif
                                                                @if(isset($user->jobHistory->userEmploymentStatus) && !empty($user->jobHistory->userEmploymentStatus) && $reponse['type']=='lateIn')
                                                                    <a href="#"
                                                                        class="badge bg-label-danger"
                                                                        id="custom-add-btn"
                                                                        tabindex="0" aria-controls="DataTables_Table_0"
                                                                        type="button" data-bs-toggle="modal"
                                                                        data-bs-target="#discrepency-or-leave-modal"
                                                                        data-toggle="tooltip"
                                                                        data-placement="top"
                                                                        title="{{ $type_label }}"
                                                                        data-url="{{ route('user_leaves.store') }}"
                                                                        data-user="{{ $user->slug }}"
                                                                        data-date='{{$apply_date}}'
                                                                        data-type="{{ $reponse['type'] }}"
                                                                        data-leave-types="{{ json_encode($leave_types) }}"
                                                                        >
                                                                        Discrepency
                                                                    </a>
                                                                @elseif($remaining_filable_leaves > 0)
                                                                    <a href="#"
                                                                        class="badge bg-label-danger"
                                                                        id="custom-add-btn"
                                                                        tabindex="0" aria-controls="DataTables_Table_0"
                                                                        type="button" data-bs-toggle="modal"
                                                                        data-bs-target="#discrepency-or-leave-modal"
                                                                        data-toggle="tooltip"
                                                                        data-placement="top"
                                                                        title="{{ $type_label }}"
                                                                        data-url="{{ route('user_leaves.store') }}"
                                                                        data-user="{{ $user->slug }}"
                                                                        data-date='{{$apply_date}}'
                                                                        data-type="{{ $reponse['type'] }}"
                                                                        data-leave-types="{{ json_encode($leave_types) }}"
                                                                        >
                                                                        Leave
                                                                    </a>
                                                                @endif
                                                            @endif
                                                        @endif
                                                    @elseif($reponse['type']=='lateIn' || $reponse['type']=='firsthalf' || $reponse['type']=='absent')
                                                        @if($applied->status==1)
                                                            <span class="badge bg-label-success" title="Approved: {{ date('d F Y h:i A', strtotime($applied->updated_at)) }}">Approved</span>
                                                        @elseif($applied->status==2)
                                                            <span class="badge bg-label-danger" title="Rejected: {{ date('d F Y h:i A', strtotime($applied->updated_at)) }}">Rejected</span>
                                                        @else
                                                            <span class="badge bg-label-warning" title="Applied At: {{ date('d F Y h:i A', strtotime($applied->created_at)) }}">Pending</span>
                                                        @endif
                                                    @endif
                                                @endif
                                            @elseif(isset($user->employeeStatus->employmentStatus) && $user->employeeStatus->employmentStatus->name=='Permanent' && ($begin->format('Y-m-d') >= $start_date->format('Y-m-d')))
                                                @if(!empty($applied))
                                                    @if($applied->status==1)
                                                        <span class="badge bg-label-success" title="Approved: {{ date('d F Y h:i A', strtotime($applied->updated_at)) }}">Approved</span>
                                                    @elseif($applied->status==2)
                                                        <span class="badge bg-label-danger" title="Rejected: {{ date('d F Y h:i A', strtotime($applied->updated_at)) }}">Rejected</span>
                                                    @else
                                                        <span class="badge bg-label-warning" title="Applied At: {{ date('d F Y h:i A', strtotime($applied->created_at)) }}">Pending</span>
                                                    @endif
                                                @else
                                                    @if($i->format("Y-m-d") <= date('Y-m-d'))
                                                        @if($day=='Sat')
                                                            @php
                                                                $date = Carbon::createFromFormat('Y-m-d', $i->format("Y-m-d"));
                                                                $nextDate = $date->copy()->addDays(2);
                                                                $secondNextDate = $nextDate->copy()->addDay();
                                                                $previousDate = $date->copy()->subDay();
                                                            @endphp
                                                        @else
                                                            @php
                                                                $date = Carbon::createFromFormat('Y-m-d', $i->format("Y-m-d"));
                                                                $nextDate = $date->copy()->addDay();
                                                                $secondNextDate = $nextDate->copy()->addDay();

                                                                $previousDate = $date->copy()->subDays(2);
                                                            @endphp
                                                        @endif
                                                        @if(checkAttendance($user->id, date('Y-m-d', strtotime($nextDate)), date('Y-m-d', strtotime($secondNextDate)), $shift) && checkAttendance($user->id, date('Y-m-d', strtotime($previousDate)), $i->format("Y-m-d"), $shift))
                                                            <a href="#"
                                                                class="badge bg-label-danger"
                                                                id="custom-add-btn"
                                                                tabindex="0" aria-controls="DataTables_Table_0"
                                                                type="button" data-bs-toggle="modal"
                                                                data-bs-target="#discrepency-or-leave-modal"
                                                                data-toggle="tooltip"
                                                                data-placement="top"
                                                                title="Sandwitch Leave"
                                                                data-url="{{ route('user_leaves.store') }}"
                                                                data-user="{{ $user->slug }}"
                                                                data-date='{{$date}}'
                                                                data-type="absent"
                                                                data-leave-types="{{ json_encode($leave_types) }}"
                                                                >
                                                                Leave
                                                            </a>
                                                        @else
                                                            {{'-'}}
                                                        @endif
                                                    @else
                                                        {{'-'}}
                                                    @endif
                                                @endif
                                            @else
                                                {{'-'}}
                                            @endif
                                        </td>

                                        <td>
                                            @if($day!='Sat' && $day!='Sun')
                                                <span class="punchedin d-block mb-2">{{$reponse['punchOut']}}</span>
                                                @if($isCurrentTimeInRange)
                                                    @if(empty($applied))
                                                        @if(isset($attendance_adjustment) && !empty($attendance_adjustment->mark_type))
                                                            @if($attendance_adjustment->mark_type=='lasthalf' || $attendance_adjustment->mark_type=='earlyout')
                                                                @php $type_label = ''; $apply_date=''; @endphp
                                                                @if($attendance_adjustment->mark_type=='lasthalf')
                                                                    @php
                                                                        $type_label = 'Apply Leave';
                                                                        $apply_date = date('d-m-Y', strtotime($attendance_adjustment->hasAttendance->in_date));
                                                                    @endphp
                                                                @else
                                                                    @php
                                                                        $type_label = 'Apply Discrepency';
                                                                        $apply_date = $attendance_adjustment->hasAttendance->id;
                                                                    @endphp
                                                                @endif
                                                                <a href="#"
                                                                    class="badge bg-label-danger"
                                                                    id="custom-add-btn"
                                                                    tabindex="0" aria-controls="DataTables_Table_0"
                                                                    type="button" data-bs-toggle="modal"
                                                                    data-bs-target="#discrepency-or-leave-modal"
                                                                    data-toggle="tooltip"
                                                                    data-placement="top"
                                                                    title="{{ $type_label }}"
                                                                    data-url="{{ route('user_leaves.store') }}"
                                                                    data-user="{{ $user->slug }}"
                                                                    data-date='{{$apply_date}}'
                                                                    data-type="{{ $reponse['type'] }}"
                                                                    data-leave-types="{{ json_encode($leave_types) }}"
                                                                    >
                                                                    @if($reponse['type']!="earlyout")
                                                                        Leave
                                                                    @else
                                                                        Discrepency
                                                                    @endif
                                                                </a>
                                                            @endif
                                                        @else
                                                            @if($reponse['type']=='lasthalf' || $reponse['type']=='earlyout')
                                                                @php $type_label = ''; $apply_date=''; @endphp
                                                                @if($reponse['type']=='lasthalf')
                                                                    @php
                                                                        $type_label = 'Apply Leave';
                                                                        $apply_date = $i->format("d-m-Y");
                                                                    @endphp
                                                                @else
                                                                    @php
                                                                        $type_label = 'Apply Discrepency';
                                                                        $apply_date = $reponse['attendance_date']->id??'';
                                                                    @endphp
                                                                @endif
                                                                @if($reponse['type']=="earlyout")
                                                                    <a href="#"
                                                                        class="badge bg-label-warning"
                                                                        id="custom-add-btn"
                                                                        tabindex="0" aria-controls="DataTables_Table_0"
                                                                        type="button" data-bs-toggle="modal"
                                                                        data-bs-target="#discrepency-or-leave-modal"
                                                                        data-toggle="tooltip"
                                                                        data-placement="top"
                                                                        title="{{ $type_label }}"
                                                                        data-url="{{ route('user_leaves.store') }}"
                                                                        data-user="{{ $user->slug }}"
                                                                        data-date='{{$apply_date}}'
                                                                        data-type="{{ $reponse['type'] }}"
                                                                        data-leave-types="{{ json_encode($leave_types) }}"
                                                                        >
                                                                        Discrepency
                                                                    </a>
                                                                @elseif($remaining_filable_leaves > 0)
                                                                    <a href="#"
                                                                        class="badge bg-label-danger"
                                                                        id="custom-add-btn"
                                                                        tabindex="0" aria-controls="DataTables_Table_0"
                                                                        type="button" data-bs-toggle="modal"
                                                                        data-bs-target="#discrepency-or-leave-modal"
                                                                        data-toggle="tooltip"
                                                                        data-placement="top"
                                                                        title="{{ $type_label }}"
                                                                        data-url="{{ route('user_leaves.store') }}"
                                                                        data-user="{{ $user->slug }}"
                                                                        data-date='{{$apply_date}}'
                                                                        data-type="{{ $reponse['type'] }}"
                                                                        data-leave-types="{{ json_encode($leave_types) }}"
                                                                        >
                                                                        Leave
                                                                    </a>
                                                                @endif
                                                            @endif
                                                        @endif
                                                    @elseif($reponse['type']=='lasthalf' || $reponse['type']=='earlyout')
                                                        @if($applied->status==1)
                                                            <span class="badge bg-label-success" title="Approved: {{ date('d F Y h:i A', strtotime($applied->updated_at)) }}">Approved</span>
                                                        @elseif($applied->status==2)
                                                            <span class="badge bg-label-danger" title="Rejected: {{ date('d F Y h:i A', strtotime($applied->updated_at)) }}">Rejected</span>
                                                        @else
                                                            <span class="badge bg-label-warning" title="Applied At: {{ date('d F Y h:i A', strtotime($applied->created_at)) }}">Pending</span>
                                                        @endif
                                                    @endif
                                                @endif
                                            @else
                                                {{'-'}}
                                            @endif
                                        </td>
                                        <td>
                                            @if($day!='Sat' && $day!='Sun' && $reponse['type']=='lasthalf' || $reponse['type']=='earlyout' || $reponse['type']=='firsthalf' || $reponse['type']=='lateIn' || $reponse['type']=='regular')
                                                @if(isset($attendance_adjustment) && !empty($attendance_adjustment) && ($i->format("Y-m-d") <= date('Y-m-d')))
                                                    @php $mark_type = $attendance_adjustment->mark_type; @endphp
                                                    @if($mark_type=='firsthalf')
                                                        @php $mark_type = 'Half Day' @endphp
                                                    @endif
                                                    <span class="badge bg-label-danger"><i class="far fa-dot-circle text-danger"></i> Marked as {{ Str::ucfirst($mark_type) }}</span>
                                                @else
                                                    @if($day!='Sat' && $day!='Sun')
                                                        {!! $reponse['label'] !!}
                                                    @else
                                                        {{'-'}}
                                                    @endif
                                                @endif
                                            @else
                                                {{'-'}}
                                            @endif
                                        </td>
                                        <td>@if($day!='Sat' && $day!='Sun'){{$reponse['workingHours']}}@else{{'-'}}@endif</td>

                                        @if(Auth::user()->hasRole('Admin'))
                                            <td>
                                            @if($day!='Sat' && $day!='Sun' && $reponse['type']=='lasthalf' || $reponse['type']=='earlyout' || $reponse['type']=='firsthalf' || $reponse['type']=='lateIn')
                                                @if((isset($applied) && !empty($applied) && $applied->status==0) && ($i->format("Y-m-d") <= date('Y-m-d')))
                                                    <div class="d-flex align-items-center">
                                                        <a href="javascript:;" class="text-body dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                            <i class="ti ti-dots-vertical ti-xs mx-1"></i>
                                                        </a>
                                                        <div class="dropdown-menu dropdown-menu-end m-0">
                                                            <a href="javascript:;" class="dropdown-item attendance-mark-btn" data-attendance-id="{{ $reponse['attendance_id'] }}" data-mark-type="absent" data-user="{{ $user->id }}" data-url='{{ route("mark_attendance.store") }}'>
                                                                Mark As Absent
                                                            </a>
                                                            <a href="javascript:;" class="dropdown-item attendance-mark-btn" data-attendance-id="{{ $reponse['attendance_id'] }}" data-mark-type="fullday" data-user="{{ $user->id }}" data-url='{{ route("mark_attendance.store") }}'>
                                                                Mark As Full Day
                                                            </a>
                                                            <a href="javascript:;" class="dropdown-item attendance-mark-btn" data-attendance-id="{{ $reponse['attendance_id'] }}" data-mark-type="firsthalf" data-user="{{ $user->id }}" data-url='{{ route("mark_attendance.store") }}'>
                                                                Mark As Half Day
                                                            </a>
                                                            <a href="javascript:;" class="dropdown-item attendance-mark-btn" data-attendance-id="{{ $reponse['attendance_id'] }}" data-mark-type="lateIn" data-user="{{ $user->id }}" data-url='{{ route("mark_attendance.store") }}'>
                                                                Mark As Late In
                                                            </a>
                                                        </div>
                                                    </div>
                                                @elseif(empty($applied) && ($i->format("Y-m-d") <= date('Y-m-d')) && $reponse['label'] != '-')
                                                    <div class="d-flex align-items-center">
                                                        <a href="javascript:;" class="text-body dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                            <i class="ti ti-dots-vertical ti-xs mx-1"></i>
                                                        </a>
                                                        <div class="dropdown-menu dropdown-menu-end m-0">
                                                            <a href="javascript:;" class="dropdown-item attendance-mark-btn" data-attendance-id="{{ $reponse['attendance_id'] }}" data-mark-type="absent" data-user="{{ $user->id }}" data-url='{{ route("mark_attendance.store") }}'>
                                                                Mark As Absent
                                                            </a>
                                                            <a href="javascript:;" class="dropdown-item attendance-mark-btn" data-attendance-id="{{ $reponse['attendance_id'] }}" data-mark-type="fullday" data-user="{{ $user->id }}" data-url='{{ route("mark_attendance.store") }}'>
                                                                Mark As Full Day
                                                            </a>
                                                            <a href="javascript:;" class="dropdown-item attendance-mark-btn" data-attendance-id="{{ $reponse['attendance_id'] }}" data-mark-type="firsthalf" data-user="{{ $user->id }}" data-url='{{ route("mark_attendance.store") }}'>
                                                                Mark As Half Day
                                                            </a>
                                                            <a href="javascript:;" class="dropdown-item attendance-mark-btn" data-attendance-id="{{ $reponse['attendance_id'] }}" data-mark-type="lateIn" data-user="{{ $user->id }}" data-url='{{ route("mark_attendance.store") }}'>
                                                                Mark As Late In
                                                            </a>
                                                        </div>
                                                    </div>
                                                @else
                                                -
                                                @endif
                                            @else
                                                {{'-'}}
                                            @endif
                                        </td>
                                        @endif
                                    </tr>
                                @endif
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('admin.dashboards.pop-up-modals')

    <!-- Apply Leave Or Discrepency Modal -->
    <div class="modal fade" id="discrepency-or-leave-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered1 modal-simple modal-add-new-cc">
            <div class="modal-content p-3 p-md-5">
                <div class="modal-body">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center mb-4">
                        <h3 class="mb-2" id="modal-label"></h3>
                    </div>

                    <form id="create-form" class="row g-3" data-method="POST" data-modal-id="discrepency-or-leave-modal">
                        @csrf

                        <input type="hidden" name="user_slug" id="user-slug">
                        <input type="hidden" name="type" id="applied-type">
                        <input type="hidden" name="date" id="applied-date">
                        <div class="mb-3" id="leave_types_div">
                            <label class="form-label" for="leave_type_id">Leave Type</label>
                            <div class="position-relative">
                                <select id="leave_type_id" name="leave_type_id" class="form-control">
                                    <option value="">Select type</option>
                                    @foreach($leave_types as $leave_type)
                                        <option value="{{ $leave_type->id }}">{{ $leave_type->name }}</option>
                                    @endforeach
                                </select>
                                <span id="leave_type_id_error" class="text-danger error"></span>
                            </div>
                        </div>
                         <div class="mb-3">
                            <label class="form-label" for="reason_id">Reason <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <textarea class="form-control" rows="5" name="reason" id="reason_id" placeholder="Enter reason"></textarea>
                                <span id="reason_error" class="text-danger error">{{ $errors->first('reason') }}</span>
                            </div>
                        </div>

                        <div class="col-12 mt-3 action-btn">
                            <div class="demo-inline-spacing sub-btn">
                                <button type="submit" class="btn btn-primary me-sm-3 me-1 applyDiscrepancyLeaveBtn">Submit</button>
                                <button type="reset" class="btn btn-label-secondary btn-reset" data-bs-dismiss="modal" aria-label="Close">
                                    Cancel
                                </button>
                            </div>
                            <div class="demo-inline-spacing loading-btn" style="display: none;">
                                <button class="btn btn-primary waves-effect waves-light" type="button" disabled="">
                                  <span class="spinner-border me-1" role="status" aria-hidden="true"></span>
                                  Loading...
                                </button>
                                <button type="reset" class="btn btn-label-secondary btn-reset" data-bs-dismiss="modal" aria-label="Close">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Apply Leave Or Discrepency Modal -->
@endsection
@push('js')
<script src="{{ asset('public/admin/assets/js/custom-dashboard.js') }}"></script>
<script src="{{ asset('public/admin/assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.js') }}"></script>
<script>
    $(document).on('click', '#custom-add-btn', function(){
        var leave_types = $(this).data('leave-types');
        var user_slug = $(this).attr('data-user');
        var type = $(this).attr('data-type');
        var date = $(this).attr('data-date');

        var targeted_modal = $(this).attr('data-bs-target');

        var url = $(this).attr('data-url');
        var modal_label = $(this).attr('title');

        $('#user-slug').val(user_slug);
        $('#applied-date').val(date);
        $('#applied-type').val(type);

        $(targeted_modal).find('#modal-label').html(modal_label);
        $(targeted_modal).find("#create-form").attr("action", url);

        var html = '';
        if(type=='lateIn' || type=='earlyout'){
            $('#leave_types_div').hide();
        }else if(type=='firsthalf' || type=='lasthalf'){
            $('#leave_types_div').show();
            $.each(leave_types, function(index, val) {
                if(val.name=='Half-Day'){
                    html += '<option value="'+val.id+'" selected>'+val.name+'</option>';
                }
            });
        }else{
            $('#leave_types_div').show();
            $.each(leave_types, function(index, val) {
                if(val.name!='Half-Day'){
                    html += '<option value="'+val.id+'">'+val.name+'</option>';
                }
            });
        }
        $('#leave_type_id').html(html);
    });

    $(function() {
        var currentMonth = $('#Slipbutton').data('current-month');

        var joiningMonthYear = $('#Slipbutton').data('joining-date');
        $('#Slipbutton').datepicker({
            format: 'mm/yyyy',
            startView: 'year',
            minViewMode: 'months',
            startDate:joiningMonthYear,
            endDate: currentMonth,
        }).on('changeMonth', function(e) {
            var employeeSlug = $('#employee-slug option:selected').data('user-slug');
            if(employeeSlug==undefined){
                employeeSlug = $('#current_user_slug').val();
            }
            var selectedMonth = String(e.date.getMonth() + 1).padStart(2, '0');
            var selectedYear = e.date.getFullYear();

            var selectOptionUrl = "{{ URL::to('user/attendance/summary') }}/" + selectedMonth + "/" + selectedYear + "/" + employeeSlug;

            window.location.href = selectOptionUrl;
        });
        const url = new URL(window.location.href);
        const pathname = url.pathname;
        const pathParts = pathname.split('/');
        if(pathParts.length > 6){
        const emp = pathParts.pop();
        const year = pathParts.pop();
        const month = pathParts.pop();

        $('#Slipbutton').datepicker('setDate', new Date(year, month-1));
        } else {

        // Get the current date and time in Pakistan time
            var currentDate = new Date();
            var currentDay = currentDate.getDate();
            var currentHour = currentDate.getUTCHours() + 5; // Add 5 hours for Pakistan time adjustment

            // Check if the current date is on or after the 26th and time is 11:00 AM or later
            if (currentDay >= 26 && currentHour >= 11) {
                // Set the day to the 1st and increment the month by 1 to show the next month
                currentDate.setDate(1);
                currentDate.setMonth(currentDate.getMonth() + 1);
            }

            $('#Slipbutton').datepicker('setDate', currentDate);

            // Update the viewDate when the view changes (e.g., navigating to a different month)
            $(document).on('changeMonth', '.datepicker', function (e) {
                $('#Slipbutton').datepicker('setViewDate', e.date);
            });
        }



    });

    function redirectPage(dropdown) {
      var selectedOption = dropdown.value;

      if (selectedOption !== '') {
        window.location.href = selectedOption;
      }
    }
  </script>
@endpush
