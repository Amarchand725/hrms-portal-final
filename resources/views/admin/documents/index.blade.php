@extends('admin.layouts.app')
@section('title', $title.' - '. appName())

@section('content')
    @if(!isset($temp))
        <input type="hidden" id="page_url" value="{{ route('documents.index') }}">
    @else
        <input type="hidden" id="page_url" value="{{ route('documents.trashed') }}">
    @endif
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="card mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card-header">
                            <h4 class="fw-bold mb-0"><span class="text-muted fw-light">Home /</span> {{ $title }}</h4>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-end align-item-center mt-4">
                            @if(!isset($temp))
                                <div class="dt-buttons flex-wrap">
                                    <a data-toggle="tooltip" data-placement="top" title="All Trashed Records" href="{{ route('documents.trashed') }}" class="btn btn-label-danger me-1">
                                        <span>
                                            <i class="ti ti-trash me-0 me-sm-1 ti-xs"></i>
                                            <span class="d-none d-sm-inline-block">All Trashed Records </span>
                                        </span>
                                    </a>
                                </div>
                                <div class="dt-buttons btn-group flex-wrap">
                                    <button
                                        data-toggle="tooltip"
                                        data-placement="top"
                                        title="Add Attachments"
                                        type="button"
                                        class="btn btn-secondary add-new btn-primary mx-3"
                                        id="add-btn"
                                        data-url="{{ route('documents.store') }}"
                                        tabindex="0" aria-controls="DataTables_Table_0"
                                        type="button" data-bs-toggle="modal"
                                        data-bs-target="#offcanvasAddAnnouncement"
                                        >
                                        <span>
                                            <i class="ti ti-plus me-0 me-sm-1 ti-xs"></i>
                                            <span class="d-none d-sm-inline-block">Add New</span>
                                        </span>
                                    </button>
                                </div>
                            @else
                                <div class="dt-buttons btn-group flex-wrap">
                                    <a data-toggle="tooltip" data-placement="top" title="Show All Records" href="{{ route('documents.index') }}" class="btn btn-success btn-primary mx-3">
                                        <span>
                                            <i class="ti ti-eye me-0 me-sm-1 ti-xs"></i>
                                            <span class="d-none d-sm-inline-block">View All Records</span>
                                        </span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <!-- Users List Table -->
            <div class="card">
                <div class="card-datatable">
                    <div id="DataTables_Table_0_wrapper" class="dataTables_wrapper dt-bootstrap5 no-footer">
                        <div class="container">
                            <table class="dt-row-grouping table dataTable dtr-column border-top table-border data_table table-responsive">
                                <thead>
                                    <tr>
                                        <th scope="col">S.No#</th>
                                        <th scope="col" class="w-20">Employee</th>
                                        <th scope="col">Department</th>
                                        <th scope="col">Date</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Actions</th>
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

    <!-- Add Employment Status Modal -->
    <div class="modal fade" id="offcanvasAddAnnouncement" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-top modal-simple modal-add-new-cc">
            <div class="modal-content p-3 p-md-5">
                <div class="modal-body">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center mb-4">
                        <h3 class="mb-2" id="modal-label"></h3>
                    </div>
                    <form id="create-form" class="row g-3 submitBtnWithFileUpload" data-method="" data-modal-id="offcanvasAddAnnouncement" enctype="multipart/form-data">
                        @csrf

                        <span id="edit-content">
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <label class="form-label" for="employee">Employees <span class="text-danger">*</span></label>

                                    <select id="employee" name="employee" class="form-select select2">
                                        <option value="" selected>Select Status</option>
                                        @if(isset($employees))
                                            @foreach ($employees as $employee)
                                                @php
                                                    $designation = '';
                                                    if(!empty($employee->jobHistory->designation->title)) {
                                                        $designation = '( '. $employee->jobHistory->designation->title. ' )';
                                                    }
                                                @endphp
                                                <option value="{{ $employee->slug }}">{{ $employee->first_name }} {{ $employee->last_name }} {{ $designation }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <div class="fv-plugins-message-container invalid-feedback"></div>
                                    <span id="employee_error" class="text-danger error"></span>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="row mt-3 border-top py-3">
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <h5 class="card-title">Education Documents</h5>
                                        <div class="btn-wrapper">
                                            <button type="button" data-val="2" class="btn btn-label-primary btn-sm add-more-btn"><i class="fa fa-plus"></i></button>
                                        </div>
                                    </div>

                                    <!--document_data-->
                                    <span class="document_data">
                                        <div class="row mt-2">
                                            <div class="col-md-4 mt-3">
                                                <label class="form-label" for="title">Title <span class="text-danger">*</span></label>
                                                <input type="text" id="title" name="titles[]" class="form-control" placeholder="Enter name" />
                                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                                <span id="titles_error" class="text-danger error"></span>
                                            </div>
                                            <div class="col-md-5 mt-3">
                                                <label class="form-label" for="attachments">Attachment <span class="text-danger">*</span></label>
                                                <input type="file" id="attachments" name="attachments[]" data-val="1" class="form-control input-file" />
                                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                                <span id="attachments_error" class="text-danger error"></span>
                                            </div>
                                            <div class="col-md-3 mt-3">
                                                <label class="form-label">Preview</label>
                                                <div class="preview-container-1"></div>
                                            </div>
                                        </div>
                                    </span>
                                    <span id="add-more-data"></span>
                                </div>
                            </div>
                        </span>

                        <div class="col-12 mt-3 action-btn">
                            <div class="demo-inline-spacing sub-btn">
                                <button type="submit" class="btn btn-primary me-sm-3 me-1">Submit</button>
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
    <!--/ Edit Employment Status Modal -->

    <div class="modal fade" id="details-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered1 modal-simple modal-add-new-cc">
            <div class="modal-content p-3 p-md-5">
                <div class="modal-body">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center mb-4">
                        <h3 class="mb-2" id="modal-label"></h3>
                    </div>

                    <div class="col-12">
                        <span id="show-content"></span>
                    </div>

                    <div class="col-12 mt-3 text-end">
                        <button
                            type="reset"
                            class="btn btn-label-primary btn-reset"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('js')
    <script type="text/javascript">
        $(document).on('click', '.add-more-btn', function(){
            var val = parseInt($(this).attr('data-val'));
            var html = '';
            html = '<span class="document_data">'+
                        '<div class="row mt-4 w-full border-top py-2 position-relative">'+
                            '<div class="close-btn-wrapper position-absolute d-flex flex-row-reverse mb-3">'+
                                '<button type="button" class="btn btn-label-primary btn-sm btn-data-close"><i class="fa fa-close icon-close"></i></button>'+
                            '</div>' +
                            '<div class="col-md-4 mt-3">' +
                                '<input type="text" id="title" name="titles[]" class="form-control" placeholder="Enter name" />' +
                                '<div class="fv-plugins-message-container invalid-feedback"></div>' +
                                '<span id="title_error" class="text-danger error"></span>' +
                            '</div>' +
                            '<div class="col-md-5 mt-3">' +
                                '<input type="file" id="attachments" name="attachments[]" data-val="'+val+'" class="form-control input-file" />' +
                                '<div class="fv-plugins-message-container invalid-feedback"></div>' +
                                '<span id="attachments_error" class="text-danger error"></span>' +
                            '</div>' +
                            '<div class="col-md-3 mt-3">' +
                                '<div class="preview-container-'+val+'"></div>' +
                            '</div>' +
                        '</div>'+
                    '</span>';

                    $(this).attr('data-val', val+1);

                    $('#add-more-data').append(html);
        });

        $(document).on('click', '.btn-data-close', function(){
            $(this).parents('.document_data').remove();
        });

        $(document).on('change', ".input-file", function () {
            var val = $(this).attr('data-val');
            var file = this.files[0];
            var reader = new FileReader();
            var inputElement = this; // Capture the 'this' reference

            reader.onload = function (e) {
                // Create an image element
                var img = $('<img style="width:50%">').attr("src", e.target.result);

                // Display the image preview
                $(inputElement).parents('.document_data').find(".preview-container-"+val).html(img);
            };

            // Read the image file as a data URL
            reader.readAsDataURL(file);
        });

        $(document).on('click', '.del-btn', function() {
            var slug = $(this).attr('data-slug');
            var thi = $(this);
            var delete_url = $(this).attr('data-del-url');

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                url: delete_url,
                type: 'POST',
                success: function(response) {
                    if (response) {
                        $('#id-'+slug).remove();
                        toastr.success('You have deleted record successfully.');
                    } else {
                        toastr.error('Sorry something went wrong.');
                    }
                }
            });
        });

        $(document).on('click', '.update-btn', function() {
            var update_url = $(this).attr('data-url');
            var title = $(this).parents('.document_data').find('#title').val();

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                url: update_url,
                type: 'POST',
                data:{title:title},
                success: function(response) {
                    if (response) {
                        toastr.success('You have updated title successfully.');
                        setTimeout(function() {
                            location.reload();
                        }, 1500); // 5000 milliseconds = 5 seconds
                    } else {
                        toastr.error('Sorry something went wrong.');
                    }
                }
            });
        });

        var table = $('.data_table').DataTable();
        if ($.fn.DataTable.isDataTable('.data_table')) {
            table.destroy();
        }
        $(document).ready(function(){
            var page_url = $('#page_url').val();
            var table = $('.data_table').DataTable({
                processing:true,
                serverSide:true,
                ajax: page_url+"?loaddata=yes",
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    {data: 'user_id', name:'user_id'},
                    {data: 'department', name:'department'},
                    {data: 'date', name:'date'},
                    {data: 'status', name:'status'},
                    {data: 'action', name:'action', orderable:false, searchable:false}
                ]
            });
        });
    </script>
@endpush
