@extends('admin.layouts.app')
@section('title', $title .' - '. appName())
@php use App\Http\Controllers\AttendanceController; @endphp

@section('content')
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="card mb-4">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card-header">
                            <h4 class="fw-bold mb-0"><span class="text-muted fw-light">Home /</span> {{ $title }} of <b>{{ $fullMonthName }}</b></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header d-flex justify-content-between border-bottom">
                    <div>
                        <span class="card-title mb-0">
                            <div class="d-flex align-items-center">
                                @if(isset($user->profile) && !empty($user->profile->profile))
                                    <img src="{{ asset('public/admin/assets/img/avatars') }}/{{ $user->profile->profile }}" style="width:40px !important; height:40px !important" alt class="h-auto" />
                                @else
                                    <img src="{{ asset('public/admin') }}/default.png" style="width:40px !important; height:40px !important" alt class="h-auto rounded-circle" />
                                @endif
                                <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                    <div class="mx-3">
                                        <div class="d-flex align-items-center">
                                            <h6 class="mb-0 me-1">{{ Str::ucfirst($user->first_name) }} {{ Str::ucfirst($user->last_name) }}</h6>
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
                        </span>
                    </div>
                </div>
                <div class="card-header d-flex justify-content-between align-items-center row">
                    <div class="row align-items-end">

                        <div class="col-md-8">
                            <label>Employees Filter </label>
                            @if(isset($data['employees']) && !empty($data['employees']))
                                <select class="select2 form-select" id="employees_ids" name="employees[]" multiple>
                                    @foreach ($data['employees'] as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                         <div class="col-md-2">
                            <label class="d-block"></label>
                            <button class="btn btn-primary waves-effect waves-light w-100" data-current-month="{{ date('F') }}" id="Slipbutton">Select Month<i class="ti ti-chevron-down ms-2"></i></button>
                        </div>
                        <input type="hidden" id="getMonth" value="{{ $month }}" />
                        <input type="hidden" id="getYear" value="{{ $year }}" />
                        <div class="col-md-2">
                            <label class="d-block"></label>
                            <button type="button" disabled id="process" class="btn btn-primary d-none w-100" style="display:none">Processing...</button>
                            <button type="button" id="filter-btn" class="btn btn-primary monthly-attendance-filter-report-btn d-block w-100" data-show-url="{{ route('employee.attendance.monthly.report.filter') }}"><i class="fa fa-search me-2"></i> Filter </button>
                        </div>
                    </div>
                </div>
                <div class="card-header border-bottom">
                    <span id="show-filter-attendance-content">
                        <div class="row">
                        <div class="col-12 ">
                            <table class="attendance-table">
                                <tbody>
                                @php $bool = ''; $counter = 0; @endphp
                                @foreach($data['users'] as $key=>$f_user)
                                    @php
                                        $counter++;

                                        $total_days = 0;
                                        $regulars = 0;
                                        $late_ins = 0;
                                        $early_outs = 0;
                                        $half_days = 0;
                                        $absents = 0;

                                        $bool = true;
                                        $shift = '';
                                        if(!empty($f_user->userWorkingShift)){
                                            $shift = $f_user->userWorkingShift->workShift;
                                        }else{
                                            if(isset($f_user->departmentBridge->department->departmentWorkShift->workShift) && !empty($f_user->departmentBridge->department->departmentWorkShift->workShift->id)){
                                                $shift = $f_user->departmentBridge->department->departmentWorkShift->workShift;
                                            }
                                        }

                                        $begin = new DateTime($data['from_date']);
                                        $end   = new DateTime($data['to_date']);
                                    @endphp
                                    @if(!empty($shift))
                                        @php
                                            $statistics = getAttandanceCount($f_user->id, $data['from_date'], $data['to_date'], $data['behavior'], $shift);

                                            $total_days = $statistics['totalDays'];
                                            $regulars = $regulars+$statistics['workDays'];
                                            if($data['behavior']=='all'){
                                                $late_ins = $late_ins+$statistics['lateIn'];
                                                $early_outs = $early_outs+$statistics['earlyOut'];
                                                $half_days = $half_days+$statistics['halfDay'];
                                                $absents = $absents+$statistics['absent'];
                                            }elseif($data['behavior']=='lateIn'){
                                                $late_ins = $late_ins+$statistics['lateIn'];
                                                $early_outs = 0;
                                                $half_days = 0;
                                                $absents = 0;
                                            }elseif($data['behavior']=='regular'){
                                                $late_ins = $late_ins+$statistics['lateIn'];
                                                $early_outs = $early_outs+$statistics['earlyOut'];
                                                $half_days = $half_days+$statistics['halfDay'];
                                            }elseif($data['behavior']=='earlyout'){
                                                $early_outs = $early_outs+$statistics['earlyOut'];
                                                $late_ins = 0;
                                                $half_days = 0;
                                            }elseif($data['behavior']=='absent'){
                                                $absents = $absents+$statistics['absent'];
                                                $late_ins = 0;
                                                $early_outs = 0;
                                                $half_days = 0;
                                            }
                                        @endphp
                                        @if($bool)
                                            <tr class="user_profile_seperator">
                                                <td>{{ $data['users']->firstItem()+$key }}#</td>
                                                <td colspan="5">
                                                    <div class="d-flex justify-content-start align-items-start user-name ps-3">
                                                        <div class="avatar-wrapper">
                                                            <div class="avatar avatar-sm me-2 mt-1">
                                                                @if(!empty($f_user->profile->profile))
                                                                    <img src="{{ asset('public/admin/assets/img/avatars') }}/{{ $f_user->profile->profile }}" alt="Avatar" class="rounded-circle">
                                                                @else
                                                                    <img src="{{ asset('public/admin/default.png') }}" alt="Avatar" class="rounded-circle">
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="d-flex flex-column">
                                                            <a href="{{ route('employees.show', $f_user->slug) }}" class="text-body text-truncate text-start">
                                                                <span class="fw-semibold">{{ $f_user->first_name??'' }} {{ $f_user->last_name??'' }}</span>
                                                            </a>
                                                            <small class="text-muted">{{ $f_user->email??'-' }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            @php $bool = false; @endphp
                                        @endif
                                        <tr>
                                            <td colspan="6">
                                                <div class="col-12 mb-3">
                                                    <div class="card mb-4">
                                                        <div class="card-widget-separator-wrapper">
                                                            <div class="card-body card-widget-separator">
                                                                <div class="row gy-4 gy-sm-1">
                                                                    <div class="col-sm-2 col-lg-2">
                                                                        <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-3 pb-sm-0">
                                                                            <div>
                                                                              <h4 class="mb-2">{{$total_days}}</h4>
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
                                                                                <h4 class="mb-2">{{$regulars}}</h4>
                                                                                <p class="mb-0 fw-medium">Regular</p>
                                                                            </div>
                                                                            <span class="avatar p-2 me-lg-4">
                                                                              <span class="avatar-initial bg-label-secondary rounded"><i class="ti-md ti ti-square-check text-primary"></i></span>
                                                                            </span>
                                                                        </div>
                                                                        <hr class="d-none d-sm-block d-lg-none me-4">
                                                                    </div>
                                                                    <div class="col-sm-2 col-lg-2">
                                                                        <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-3 pb-sm-0">
                                                                            <div>
                                                                              <h4 class="mb-2">{{$late_ins}}</h4>
                                                                              <p class="mb-0 fw-medium">Late In</p>
                                                                            </div>
                                                                            <span class="avatar p-2 me-lg-4">
                                                                              <span class="avatar-initial bg-label-secondary rounded"><i class="ti-md ti ti-arrow-bar-to-down text-primary"></i></span>
                                                                            </span>
                                                                        </div>
                                                                        <hr class="d-none d-sm-block d-lg-none me-4">
                                                                    </div>
                                                                    <div class="col-sm-2 col-lg-2">
                                                                        <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-3 pb-sm-0">
                                                                            <div>
                                                                              <h4 class="mb-2">{{$early_outs}}</h4>
                                                                              <p class="mb-0 fw-medium">Early Out</p>
                                                                            </div>
                                                                            <span class="avatar p-2 me-lg-4">
                                                                              <span class="avatar-initial bg-label-secondary rounded"><i class="ti-md ti ti-arrow-bar-to-up text-primary"></i></span>
                                                                            </span>
                                                                        </div>
                                                                        <hr class="d-none d-sm-block d-lg-none me-4">
                                                                    </div>
                                                                    <div class="col-sm-2 col-lg-2">
                                                                        <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-3 pb-sm-0">
                                                                            <div>
                                                                              <h4 class="mb-2">{{$half_days}}</h4>
                                                                              <p class="mb-0 fw-medium">Half Day</p>
                                                                            </div>
                                                                            <span class="avatar p-2 me-lg-4">
                                                                              <span class="avatar-initial bg-label-secondary rounded"><i class="ti-md ti ti-square-half text-primary"></i></span>
                                                                            </span>
                                                                        </div>
                                                                        <hr class="d-none d-sm-block d-lg-none me-4">
                                                                    </div>
                                                                    <div class="col-sm-2 col-lg-2">
                                                                        <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-3 pb-sm-0">
                                                                            <div>
                                                                              <h4 class="mb-2">{{$absents}}</h4>
                                                                              <p class="mb-0 fw-medium">Absents</p>
                                                                            </div>
                                                                            <span class="avatar p-2 me-lg-4">
                                                                              <span class="avatar-initial bg-label-secondary rounded"><i class="ti-md ti ti-clock-off text-primary"></i></span>
                                                                            </span>
                                                                        </div>
                                                                        <hr class="d-none d-sm-block d-lg-none me-4">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                                <tr>
                                    <td colspan="6">
    									Displying {{$data['users']->firstItem()}} to {{$data['users']->lastItem()}} of {{$data['users']->total()}} records
                                        <div class="d-flex justify-content-center">
                                            {!! $data['users']->links('pagination::bootstrap-4') !!}
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    </span>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('js')
    <script src="{{ asset('public/admin/assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.js') }}"></script>
    <script>
        $(function() {
            var currentMonth = $('#Slipbutton').data('current-month');



            $('#Slipbutton').datepicker({
                format: 'mm/yyyy',
                startView: 'year',
                minViewMode: 'months',
                endDate: currentMonth,
            }).on('changeMonth', function(e) {
                var selectedMonth = String(e.date.getMonth() + 1).padStart(2, '0');
                var selectedYear = e.date.getFullYear();
                // alert(selectedMonth+' ---- '+selectedYear);
                var selectOptionUrl = "{{ URL::to('employee/monthly/attendance/report') }}/" + selectedMonth + "/" + selectedYear;
                // alert(selectOptionUrl);
                // $('#Slipbutton').datepicker('setDate', new Date(selectedYear, selectedMonth, 1));
                window.location.href = selectOptionUrl;

            });
                const url = new URL(window.location.href);
                const pathname = url.pathname;
                const pathParts = pathname.split('/');
                const year = pathParts.pop();
                const month = pathParts.pop();

                $('#Slipbutton').datepicker('setDate', new Date(year, month-1));
        });

        function redirectPage(dropdown) {
            var selectedOption = dropdown.value;

            if (selectedOption !== '') {
                window.location.href = selectedOption;
            }
        }

        $(document).ready(function(){
            var input_employees = $('#employees_ids').val();
            var filterButton = $('#filter-btn');

            if(input_employees == ''){
                filterButton.prop('disabled', true);
            } else {
                filterButton.prop('disabled', false);
            }
        });

        // Attach an event listener for the input change event
        $('#employees_ids').on('change', function(){
            var filterButton = $('#filter-btn');

            if ($(this).val() != '') {
                // If a date range is selected, enable the filter button
                filterButton.prop('disabled', false);
            } else {
                // If the input is empty, disable the filter button
                filterButton.prop('disabled', true);
            }
        });
    </script>
@endpush
