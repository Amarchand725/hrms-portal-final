@extends('admin.layouts.app')
@section('title', $title.' - '. appName())

@push('styles')
@endpush

@section('content')
@if(isset($url) && !empty($url))
    <input type="hidden" id="page_url" value="{{ URL::to('monthly_salary_reports/monthly_report') }}/{{ $month }}/{{ $year }}">
@else
    <input type="hidden" id="page_url" value="{{ route('monthly_salary_reports.index') }}">
@endif

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
         <div class="card">
            <div class="row">
                <div class="col-md-6">
                    <div class="card-header">
                        <h4 class="fw-bold mb-0"><span class="text-muted fw-light">Home /</span> {{ $title }} of {{ $month }}/{{ $year }}</h4>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-end align-item-center mt-4">
                        <div class="dt-buttons flex-wrap">
                            <button type="button" class="btn btn-primary waves-effect waves-light me-3" data-monthly-report-start-month-year="{{ $month_year }}" data-current-month="{{ $selectMonth }}" id="Slipbutton">Select Month<i class="ti ti-chevron-down ms-2"></i></button>
                            <a data-toggle="tooltip" data-placement="top" title="Export Monthly Salary Sheet" href="{{ URL::to('monthly_salary_reports/export_monthly_salary_report/download') }}/{{ $month }}/{{ $year }}" class="btn btn-label-success me-4">
                                <span>
                                    <i class="fa fa-file-excel me-0 me-sm-1 ti-xs"></i>
                                    <span class="d-none d-sm-inline-block">Export as Excel </span>
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users List Table -->
        <div class="card mt-4">
            <div class="card-datatable table-responsive">
                <div id="DataTables_Table_0_wrapper" class="dataTables_wrapper dt-bootstrap5 no-footer">
                    <div class="container">
                        <table class="datatables-users table border-top dataTable no-footer dtr-column data_table table-responsive" id="DataTables_Table_0" aria-describedby="DataTables_Table_0_info" style="width: 1227px;">
                            <thead>
                                <tr>
                                    <th>S.No#</th>
                                    <th>Employee</th>
                                    <th>Actual Salary</th>
                                    <th>Car Allowance</th>
                                    <th>Earning</th>
                                    <th>Approved Days Amount</th>
                                    <th>Deduction</th>
                                    <th>Net Salary</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="body"></tbody>
                        </table>
                    </div>
                </div>
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
            var monthlyReportStartYearMonth = $('#Slipbutton').data('monthly-report-start-month-year');

            $('#Slipbutton').datepicker({
                format: 'mm/yyyy',
                startView: 'year',
                minViewMode: 'months',
                startDate: monthlyReportStartYearMonth,
                endDate: currentMonth,
            }).on('changeMonth', function(e) {
                var selectedMonth = String(e.date.getMonth() + 1).padStart(2, '0');
                var selectedYear = e.date.getFullYear();

                var selectOptionUrl = "{{ URL::to('monthly_salary_reports/monthly_report') }}/" + selectedMonth + "/" + selectedYear;

                window.location.href = selectOptionUrl;
            });
        });

        //datatable
        var table = $('.data_table').DataTable();
        if ($.fn.DataTable.isDataTable('.data_table')) {
            table.destroy();
        }
        $(document).ready(function() {
            var page_url = $('#page_url').val();
            var table = $('.data_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: page_url+"?loaddata=yes",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'employee_id', name: 'employee_id' },
                    { data: 'actual_salary', name: 'actual_salary' },
                    { data: 'car_allowance', name: 'car_allowance' },
                    { data: 'approved_days_amount', name: 'approved_days_amount' },
                    { data: 'earning_salary', name: 'earning_salary' },
                    { data: 'deduction', name: 'deduction' },
                    { data: 'net_salary', name: 'net_salary' },
                    { data: 'generated_date', name: 'generated_date' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ]
            });
        });
    </script>
@endpush
