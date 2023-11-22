@extends('admin.layouts.app')
@section('title', $title. ' - '. appName())

@section('content')
    <input type="hidden" id="page_url" value="{{ route('employees.index') }}" />
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="card mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card-header">
                            <h4 class="fw-bold mb-0"><span class="text-muted fw-light">Home /</span> {{ $title }}</h4>
                        </div>
                    </div>
                </div>
            </div>
    
            <!-- Users List Table -->
            <div class="card">
                <div class="card-header border-bottom">
                    <div>
                        @if(isset($data['employees']) && !empty($data['employees']))
                            <h5 class="card-title mb-3">Search Filter</h5>
                        @endif
                        <div class="d-flex justify-content-between align-items-center row pb-2 gap-3 gap-md-0">
                                <div class="col-lg-3 col-md-3 user_plan">
                                    @if(isset($data['employees']) && !empty($data['employees']))
                                        <input type="hidden" id="current_user_slug" value="{{ Auth::user()->slug }}" >
                                        <select class="select2 form-select form-select-lg" data-allow-clear="true" id="employee-slug" onchange="redirectPage(this)">
                                            <option value="" selected>Select employee</option>
                                            @foreach ($data['employees'] as $employee)
                                                @if(!empty($employee))
                                                    <option value="{{ URL::to('employees/salary_details/'.$data['month'].'/'.$data['year'].'/'.$employee->slug) }}" data-user-slug="{{ $employee->slug }}" {{ $data['user']->slug==$employee->slug?'selected':'' }}>{{ $employee->first_name }} {{ $employee->last_name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    @else 
                                        <input type="hidden" id="current_user_slug" value="{{ Auth::user()->slug }}" >
                                    @endif
                                </div>
         
                            <div class="col-md-3 pt-2 text-end">
                                <button class="btn btn-primary waves-effect waves-light" data-joining-date="{{ $data['user_joining_date'] }}" data-current-month="{{ date('F') }}" id="Slipbutton">Select Month<i class="ti ti-chevron-down ms-2"></i></button>
                            </div>
                        </div>
    
                    </div>
                    <div id="printable_div">
                        <div class="col-12 mt-3">
                            <div class="user-profile-header mt-4 d-flex flex-column flex-sm-row text-sm-start text-center mb-4">
                                <div class="flex-shrink-0 mt-2 mx-sm-0 mx-auto">
                                    @if(isset($data['user']->profile) && !empty($data['user']->profile->profile))
                                        <img src="{{ asset('public/admin/assets/img/avatars') }}/{{ $data['user']->profile->profile }}" alt="user image" class="d-block h-auto rounded user-profile-img img-fluid" />
                                    @else
                                        <img src="{{ asset('public/admin') }}/default.png" alt="user image" class="d-block h-auto rounded user-profile-img" />
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-md-center align-items-sm-center align-items-center justify-content-md-between justify-content-start ms-4 flex-md-row flex-column gap-4">
                                        <div class="user-profile-info">
                                            <h4 class="mb-1 text-capitalize">{{ $data['user']->first_name }} {{ $data['user']->last_name }}</h4>
    
                                            <ul class="list-unstyled user-profile-info">
                                                <li class="mb-1">
                                                    <span class="fw-semibold me-1">Email:</span>
                                                    <span>
                                                        {{ $data['user']->email }}
                                                    </span>
                                                </li>
                                                <li class="mb-1">
                                                    <span class="fw-semibold me-1">Employment ID:</span>
                                                    <span>
                                                        @if(isset($data['user']->profile) && !empty($data['user']->profile))
                                                            {{ $data['user']->profile->employment_id }}
                                                        @else
                                                        -
                                                        @endif
                                                    </span>
                                                </li>
                                                <li class="mb-1">
                                                    <span class="fw-semibold me-1">Designation:</span>
                                                    <span>
                                                        @if(isset($data['user']->jobHistory->designation->title) && !empty($data['user']->jobHistory->designation->title))
                                                            {{ $data['user']->jobHistory->designation->title }}
                                                        @else
                                                            -
                                                        @endif
                                                    </span>
                                                </li>
                                            </ul>
                                        </div>
                                        @can('generate_pay_slip-create')
                                            <a href="{{ URL::to('employees/generate_salary_slip/'.$data['month'].'/'.$data['year'].'/'.$data['user']->slug) }}" target="_blank" class="btn btn-primary waves-effect waves-light"><i class="ti ti-printer me-1"></i>Generate Salary Slip </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-datatable table-responsive">
                            <div class="dataTables_wrapper dt-bootstrap5 no-footer">
                                <div class="row me-2">
                                    <div class="col-md-2">
                                        <div class="me-3">
                                            <div class="dataTables_length" id="DataTables_Table_0_length"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <h4 class="text-center">Payslip - {{ $data['month_year'] }}</h4>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <table class="table-striped table salary-table">
                                            <tbody>
                                                <tr>
                                                    <th><h6 class="mb-0">Employee No.</h6></th>
                                                    <td class="text-end">
                                                        @if(isset($data['user']->profile) && !empty($data['user']->profile->employment_id))
                                                            {{ $data['user']->profile->employment_id }}
                                                        @else
                                                        -
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th><h6 class="mb-0">Designation</h6></th>
                                                    <td class="text-end">
                                                        @if(isset($data['user']->jobHistory->designation->title) && !empty($data['user']->jobHistory->designation->title))
                                                            {{ $data['user']->jobHistory->designation->title }}
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th><h6 class="mb-0">Total Days</h6></th>
                                                    <td class="text-end">{{ $data['totalDays'] }}</td>
                                                </tr>
                                                <tr>
                                                    <th><h6 class="mb-0">Per Day Salary</h6></th>
                                                    <td class="text-end">{{ number_format($data['per_day_salary']) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-sm-6">
                                        <table class="table-striped table salary-table">
                                            <tbody>
                                                <tr>
                                                    <th><h6 class="mb-0">Employee Name.</h6></th>
                                                    <td class="text-end text-capitalize">{{ $data['user']->first_name }} {{ $data['user']->last_name }}</td>
                                                </tr>
                                                <tr>
                                                    <th><h6 class="mb-0">Appointment Date</h6></th>
                                                    <td class="text-end">
                                                        @if(isset($data['user']->profile) && !empty($data['user']->profile->joining_date))
                                                            {{ date('d M Y', strtotime($data['user']->profile->joining_date)) }}
                                                        @else
                                                        -
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th><h6 class="mb-0">Department</h6></th>
                                                    <td class="text-end">
                                                        @if(isset($data['user']->departmentBridge->department) && !empty($data['user']->departmentBridge->department->name))
                                                            {{ $data['user']->departmentBridge->department->name }}
                                                        @else
                                                        -
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th><h6 class="mb-0">Earning Days</h6></th>
                                                    <td class="text-end">{{ $data['total_earning_days'] }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <table class="table-striped table mt-3 salary-table">
                                            <thead>
                                                <tr class="py-2">
                                                    <th>Title</th>
                                                    <th class="text-center">Actual</th>
                                                    <th class="text-center">Earning</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><h6 class="mb-0">Basic Salary</h5></td>
                                                    <td><p class="mb-0 text-center">{{ number_format($data['salary']) }}</p></td>
                                                    <td><p class="mb-0 text-center">{{ number_format($data['earning_days_amount']) }}</p></td>
                                                </tr>
                                                <tr>
                                                    <td><h6 class="mb-0">House Rent</td>
                                                    <td><p class="mb-0 text-center">0</p></td>
                                                    <td><p class="mb-0 text-center">0</p></td>
                                                </tr>
                                                <tr>
                                                    <td><h6 class="mb-0">Medical</td>
                                                    <td><p class="mb-0 text-center">0</p></td>
                                                    <td><p class="mb-0 text-center">0</p></td>
                                                </tr>
                                                <tr>
                                                    <td><h6 class="mb-0">Cost Of Living Allowance</td>
                                                    <td><p class="mb-0 text-center">0</p></td>
                                                    <td><p class="mb-0 text-center">0</p></td>
                                                </tr>
                                                <tr>
                                                    <td><h6 class="mb-0">Special</td>
                                                    <td><p class="mb-0 text-center">0</p></td>
                                                    <td><p class="mb-0 text-center">0</p></td>
                                                </tr>
                                                <tr>
                                                    <td><h6 class="mb-0">Car Allowance</td>
                                                    <td><p class="mb-0 text-center">{{ number_format($data['car_allowance']) }}</p></td>
                                                    <td><p class="mb-0 text-center">{{ number_format($data['car_allowance']) }}</p></td>
                                                </tr>
                                                <tr>
                                                    <td><h6 class="mb-0">Arrears</td>
                                                    <td><p class="mb-0 text-center">0</p></td>
                                                    <td><p class="mb-0 text-center">0</p></td>
                                                </tr>
                                                <tr>
                                                    <td><h6 class="mb-0">Extra Days Amount</td>
                                                    <td><p class="mb-0 text-center">0</p></td>
                                                    <td><p class="mb-0 text-center">0</p></td>
                                                </tr>
    
                                                <tr>
                                                    <td><h6 class="mb-0">Total Earnings</td>
                                                    <td><p class="mb-0 text-center">{{ $data['total_actual_salary'] }}</p></td>
                                                    <td><p class="mb-0 text-center">{{ $data['total_earning_salary'] }}</p></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-sm-6">
                                        <table class="table-striped table mt-3 salary-table">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th class="text-center">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><h6 class="mb-0">Absent Days Amount</td>
                                                    <td><p class="mb-0 text-center">{{ number_format($data['absent_days_amount']) }}</p></td>
                                                </tr>
                                                <tr>
                                                    <td><h6 class="mb-0">Half Days Amount</td>
                                                    <td><p class="mb-0 text-center">{{ number_format($data['half_days_amount']) }}</p></td>
                                                </tr>
                                                <tr>
                                                    <td><h6 class="mb-0">Late In + Early Out Amount</td>
                                                    <td><p class="mb-0 text-center">{{ number_format($data['late_in_early_out_amount']) }}</p></td>
                                                </tr>
                                                <tr>
                                                    <td><h6 class="mb-0">Income Tax (will be calculated at the time of salary)</td>
                                                    <td><p class="mb-0 text-center">0</p></td>
                                                </tr>
                                                <tr>
                                                    <td><h6 class="mb-0">EOBI</td>
                                                    <td><p class="mb-0 text-center">0</p></td>
                                                </tr>
                                                <tr>
                                                    <td><h6 class="mb-0">Loan Installment</td>
                                                    <td><p class="mb-0 text-center">0</p></td>
                                                </tr>
                                                <tr>
                                                    <td><h6 class="mb-0">Advance Salary</td>
                                                    <td><p class="mb-0 text-center">0</p></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2">&nbsp;</td>
                                                </tr>
    
                                                <tr>
                                                    <td><h6 class="mb-0">NET SALARY</td>
                                                    <td><p class="mb-0 text-center">{{ $data['net_salary'] }}</p></td>
                                                </tr>
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
    @endsection
    @push('js')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="{{ asset('public/admin/assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.js') }}"></script>
        <script>
            $(function() {
                var currentMonth = $('#Slipbutton').data('current-month');
                var joiningMonthYear = $('#Slipbutton').data('joining-date');
                
                $('#Slipbutton').datepicker({
                    format: 'mm/yyyy',
                    startView: 'year',
                    minViewMode: 'months',
                    startDate: joiningMonthYear,
                    endDate: currentMonth,
                }).on('changeMonth', function(e) {
                    var employeeSlug = $('#employee-slug option:selected').data('user-slug');
                    if(employeeSlug==undefined){
                        employeeSlug = $('#current_user_slug').val();
                    }
                    var selectedMonth = String(e.date.getMonth() + 1).padStart(2, '0');
                    var selectedYear = e.date.getFullYear();
                    
                    var selectOptionUrl = "{{ URL::to('employees/salary_details') }}/" + selectedMonth + "/" + selectedYear + "/" + employeeSlug;
                    
                    window.location.href = selectOptionUrl;
                });
                
                const url = new URL(window.location.href);
            const pathname = url.pathname;
            const pathParts = pathname.split('/');
            if(pathParts.length > 5){
                const emp = pathParts.pop();
                const year = pathParts.pop();
                const month = pathParts.pop();
                    
                $('#Slipbutton').datepicker('setDate', new Date(year, month-1));
            } else {
                
            // Get the current date and time in Pakistan time
                // var currentDate = new Date();
                // var currentDay = currentDate.getDate();
                // var currentHour = currentDate.getUTCHours() + 5; // Add 5 hours for Pakistan time adjustment
            
                // // Check if the current date is on or after the 26th and time is 11:00 AM or later
                // if (currentDay >= 26 && currentHour >= 11) {
                //     // Set the day to the 1st and increment the month by 1 to show the next month
                //     currentDate.setDate(1);
                //     currentDate.setMonth(currentDate.getMonth() + 1);
                // }
            
                // $('#Slipbutton').datepicker('setDate', currentDate);
            
                // // Update the viewDate when the view changes (e.g., navigating to a different month)
                // $(document).on('changeMonth', '.datepicker', function (e) {
                //     $('#Slipbutton').datepicker('setViewDate', e.date);
                // });
            }
            });

            function redirectPage(dropdown) {
                var selectedOption = dropdown.value;

                if (selectedOption !== '') {
                    window.location.href = selectedOption;
                }
            }

            $(document).ready(function() {
                $('#printButton').click(function() {
                    var divContents = $('#printable_div').html();
                    var printWindow = window.open('', '', 'width=500,height=500');
                    printWindow.document.open();
                    printWindow.document.write('<html><head><title>Print</title></head><body>' + divContents + '</body></html>');
                    printWindow.document.close();
                    printWindow.print();
                });
            });
        </script>
    @endpush
</div>
