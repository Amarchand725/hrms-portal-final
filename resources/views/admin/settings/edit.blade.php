@extends('admin.layouts.app')
@section('title', $title. ' - '. appName())

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Users List Table -->
        <div class="card">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-3">Company Settings</h5>
            </div>
            <div class="col-xl-10 offset-1">
                <div class="card-body">
                    <form id="company-settings" action="{{ route('settings.update', $model->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        {{ method_field('PATCH') }}

                        <div class="mb-3 row">
                            <label for="name" class="col-md-2 col-form-label">Name <span class="text-danger">*</span></label>
                            <div class="col-md-10">
                                <input class="form-control" type="text" value="{{ $model->name }}" name="name" id="name" placeholder="Enter company name"/>
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                <span id="name_error" class="text-danger error">{{ $errors->first('name') }}</span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="logo" class="col-md-2 col-form-label">White Logo </label>
                            <div class="col-md-10">
                                <input class="form-control" name="logo" type="file" id="logo" />
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                <span id="logo_error" class="text-danger error"></span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="logo" class="col-md-2 col-form-label"></label>
                            <div class="col-md-10">
                                @if(!empty($model->logo))
                                    <div id="logo-preview" class="p-3 w-25 rounded bg-light">
                                        <img class="img-fluid img-thumbnail w-50 mx-auto" src="{{ asset('public/admin/assets/img/logo') }}/{{ $model->logo }}" alt="">
                                    </div>
                                @else
                                    <div id="logo-preview" class="p-3 w-25 rounded bg-light"></div>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="black_logo" class="col-md-2 col-form-label">Black Logo </label>
                            <div class="col-md-10">
                                <input class="form-control" name="black_logo" type="file" id="black_logo" />
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                <span id="black_logo_error" class="text-danger error">{{ $errors->first('black_logo') }}</span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="black_logo" class="col-md-2 col-form-label"></label>
                            <div class="col-md-10">
                                @if(!empty($model->black_logo))
                                    <div id="black_logo-preview">
                                        <img style="width:15%; height:5%" src="{{ asset('public/admin/assets/img/logo') }}/{{ $model->black_logo }}" alt="">
                                    </div>
                                @else
                                    <div id="black_logo-preview"></div>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="favicon" class="col-md-2 col-form-label">Favicon</label>
                            <div class="col-md-10">
                                <input class="form-control" name="favicon" type="file" id="favicon" />
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                <span id="favicon_error" class="text-danger error"></span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="favicon" class="col-md-2 col-form-label"></label>
                            <div class="col-md-10">
                                @if(!empty($model->favicon))
                                    <div id="favicon-preview">
                                        <img style="width:5%; height:5%" src="{{ asset('public/admin/assets/img/favicon') }}/{{ $model->favicon }}" alt="">
                                    </div>
                                @else
                                    <div id="favicon-preview"></div>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="slip_stamp" class="col-md-2 col-form-label">Slip Stamp </label>
                            <div class="col-md-10">
                                <input class="form-control" name="slip_stamp" type="file" id="slip_stamp" />
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                <span id="slip_stamp_error" class="text-danger error">{{ $errors->first('slip_stamp') }}</span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="slip_stamp" class="col-md-2 col-form-label"></label>
                            <div class="col-md-10">
                                @if(!empty($model->slip_stamp))
                                    <div id="slip_stamp-preview">
                                        <img style="width:10%; height:5%" src="{{ asset('public/admin/assets/img/logo') }}/{{ $model->slip_stamp }}" alt="">
                                    </div>
                                @else
                                    <div id="slip_stamp-preview"></div>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="admin_signature" class="col-md-2 col-form-label">Admin Signature </label>
                            <div class="col-md-10">
                                <input class="form-control" name="admin_signature" type="file" id="admin_signature" />
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                <span id="admin_signature_error" class="text-danger error">{{ $errors->first('admin_signature') }}</span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="admin_signature" class="col-md-2 col-form-label"></label>
                            <div class="col-md-10">
                                @if(!empty($model->admin_signature))
                                    <div id="admin_signature-preview">
                                        <img style="width:10%; height:5%" src="{{ asset('public/admin/assets/img/logo') }}/{{ $model->admin_signature }}" alt="">
                                    </div>
                                @else
                                    <div id="admin_signature-preview"></div>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="phone_number" class="col-md-2 col-form-label">Phone Number <span class="text-danger">*</span></label>
                            <div class="col-md-10">
                                <input class="form-control phoneNumber" type="text" name="phone_number" value="{{ $model->phone_number }}" id="phone_number" placeholder="Enter company phone number" />
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                <span id="phone_number_error" class="text-danger error"></span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="email" class="col-md-2 col-form-label">Email <span class="text-danger">*</span></label>
                            <div class="col-md-10">
                                <input class="form-control" type="email" name="email" value="{{ $model->email }}" id="email" placeholder="Enter email" />
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                <span id="email_error" class="text-danger error"></span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="website_url" class="col-md-2 col-form-label">Website URL</label>
                            <div class="col-md-10">
                                <textarea name="website_url" id="website_url" class="form-control" placeholder="Enter website url">{{ $model->website_url }}</textarea>
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                <span id="website_url_error" class="text-danger error"></span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="lanaguage" class="col-md-2 col-form-label">Language</label>
                            <div class="col-md-10">
                                <input class="form-control" type="text" name="language" value="{{ $model->language }}" id="lanaguage" placeholder="Enter language" />
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                <span id="language_error" class="text-danger error"></span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="lanaguage" class="col-md-2 col-form-label">Max Leaves <small>Allow Per Month</small> <span class="text-danger">*</span></label>
                            <div class="col-md-10">
                                <input class="form-control" type="number" name="max_leaves" value="{{ old('max_leaves', $model->max_leaves) }}" id="lanaguage" placeholder="Enter max allow leaves" />
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                <span id="max_leaves_error" class="text-danger error">{{ $errors->first('max_leaves') }}</span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="lanaguage" class="col-md-2 col-form-label">Max Discrepancies <small>Allow Per Month</small> <span class="text-danger">*</span></label>
                            <div class="col-md-10">
                                <input class="form-control" type="number" name="max_discrepancies" value="{{ old('max_discrepancies', $model->max_discrepancies) }}" id="lanaguage" placeholder="Enter max allow discrepancies" />
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                <span id="max_discrepancies_error" class="text-danger error">{{ $errors->first('max_discrepancies') }}</span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="currency_symbol" class="col-md-2 col-form-label">Currency Symbol</label>
                            <div class="col-md-10">
                                <input class="form-control" type="text" name="currency_symbol" value="{{ $model->currency_symbol }}" id="currency_symbol" placeholder="Enter currency symbol"/>
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                <span id="currency_symbol_error" class="text-danger error"></span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="insurance_eligibility" class="col-md-2 col-form-label">Insurance Eligibility <span class="text-danger">*</span></label>
                            <div class="col-md-10">
                                <select class="form-select select2" id="insurance_eligibility" name="insurance_eligibility">
                                    <option value="0" {{ $model->insurance_eligibility==0?'selected':'' }}>Immediately</option>
                                    <option value="1" {{ $model->insurance_eligibility==1?'selected':'' }}>1 Month</option>
                                    <option value="2" {{ $model->insurance_eligibility==2?'selected':'' }}>2 Month</option>
                                    <option value="3" {{ $model->insurance_eligibility==3?'selected':'' }}>3 Month</option>
                                    <option value="4" {{ $model->insurance_eligibility==4?'selected':'' }}>4 Month</option>
                                    <option value="5" {{ $model->insurance_eligibility==5?'selected':'' }}>5 Month</option>
                                    <option value="6" {{ $model->insurance_eligibility==6?'selected':'' }}>6 Month</option>
                                </select>
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                <span id="insurance_eligibility_error" class="text-danger error"></span>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label for="country" class="col-md-2 col-form-label">Country</label>
                            <div class="col-md-10">
                                <select name="country" id="country" class="form-control">
                                    <option value="pakistan" {{ $model->currency_symbol=='pakistan'?'selected':'' }}>Pakistan</option>
                                </select>
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                <span id="country_error" class="text-danger error"></span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="area" class="col-md-2 col-form-label">Area</label>
                            <div class="col-md-10">
                                <input class="form-control" type="text" name="area" value="{{ $model->area }}" id="area" placeholder="Enter area"/>
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                <span id="area_error" class="text-danger error"></span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="city" class="col-md-2 col-form-label">City</label>
                            <div class="col-md-10">
                                <input class="form-control" type="text" name="city" value="{{ $model->city }}" id="city" placeholder="Enter city"/>
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                <span id="city_error" class="text-danger error"></span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="state" class="col-md-2 col-form-label">State</label>
                            <div class="col-md-10">
                                <input class="form-control" type="text" name="state" value="{{ $model->state }}" id="state" placeholder="Enter state"/>
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                <span id="state_error" class="text-danger error"></span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="zip_code" class="col-md-2 col-form-label">Zipcode</label>
                            <div class="col-md-10">
                                <input class="form-control" type="text" name="zip_code" value="{{ $model->zip_code }}" id="zip_code" placeholder="Enter zip code"/>
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                <span id="zip_code_error" class="text-danger error"></span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="address" class="col-md-2 col-form-label">Address <span class="text-danger">*</span></label>
                            <div class="col-md-10">
                                <textarea name="address" id="address" class="form-control" placeholder="Enter address">{{ $model->address }}</textarea>
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                <span id="address_error" class="text-danger error"></span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="facebook_link" class="col-md-2 col-form-label">Facebook Link</label>
                            <div class="col-md-10">
                                <textarea name="facebook_link" id="" class="form-control" placeholder="Enter facebook link here...">{{ $model->facebook_link }}</textarea>
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                <span id="facebook_link_error" class="text-danger error">{{ $errors->first('facebook_link') }}</span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="instagram_link" class="col-md-2 col-form-label">Instagram Link</label>
                            <div class="col-md-10">
                                <textarea name="instagram_link" id="instagram_link" class="form-control" placeholder="Enter instagram link here...">{{ $model->instagram_link }}</textarea>
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                <span id="instagram_link_error" class="text-danger error">{{ $errors->first('instagram_link') }}</span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="linked_in_link" class="col-md-2 col-form-label">LinkedIn Link</label>
                            <div class="col-md-10">
                                <textarea name="linked_in_link" id="linked_in_link" class="form-control" placeholder="Enter instagram link here...">{{ $model->linked_in_link }}</textarea>
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                <span id="linked_inerror" class="text-danger error">{{ $errors->first('linked_in_link') }}</span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="twitter_link" class="col-md-2 col-form-label">Twitter Link</label>
                            <div class="col-md-10">
                                <textarea name="twitter_link" class="form-control" placeholder="Enter twitter link here...">{{ $model->twitter_link }}</textarea>
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                <span id="twitter_error" class="text-danger error">{{ $errors->first('twitter_link') }}</span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="address" class="col-md-2 col-form-label"></label>
                            <div class="col-md-10">
                                <button type="button" class="btn btn-primary me-2 d-none" id="editButton">Edit</button>
                                <button id="updateButton" type="submit" class="btn btn-primary me-2">
                                    Update
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('js')
    <script>
        $(document).ready(function() {
            // When the file input changes
            $('#logo').change(function() {
                var file = this.files[0];
                var reader = new FileReader();

                reader.onload = function(e) {
                // Create an image element
                var img = $('<img style="width:10%; height:5%">').attr('src', e.target.result);

                // Display the image preview
                $('#logo-preview').html(img);
                }

                // Read the image file as a data URL
                reader.readAsDataURL(file);
            });

            $('#black_logo').change(function() {
                var file = this.files[0];
                var reader = new FileReader();

                reader.onload = function(e) {
                // Create an image element
                var img = $('<img style="width:10%; height:5%">').attr('src', e.target.result);

                // Display the image preview
                $('#black_logo-preview').html(img);
                }

                // Read the image file as a data URL
                reader.readAsDataURL(file);
            });

            $('#slip_stamp').change(function() {
                var file = this.files[0];
                var reader = new FileReader();

                reader.onload = function(e) {
                // Create an image element
                var img = $('<img style="width:10%; height:5%">').attr('src', e.target.result);

                // Display the image preview
                $('#slip_stamp-preview').html(img);
                }

                // Read the image file as a data URL
                reader.readAsDataURL(file);
            });

            $('#admin_signature').change(function() {
                var file = this.files[0];
                var reader = new FileReader();

                reader.onload = function(e) {
                    // Create an image element
                    var img = $('<img style="width:10%; height:5%">').attr('src', e.target.result);

                    // Display the image preview
                    $('#admin_signature-preview').html(img);
                }

                // Read the image file as a data URL
                reader.readAsDataURL(file);
            });

            $('#favicon').change(function() {
                var file = this.files[0];
                var reader = new FileReader();

                reader.onload = function(e) {
                // Create an image element
                var img = $('<img style="width:10%; height:5%">').attr('src', e.target.result);

                // Display the image preview
                $('#favicon-preview').html(img);
                }

                // Read the image file as a data URL
                reader.readAsDataURL(file);
            });

            // $('#company-settings input, select, textarea').prop('disabled', true);

            // $('#editButton').click(function() {
            //   $(this).addClass('d-none');
            //   $('#updateButton').removeClass('d-none');
            //   $('#company-settings input, select, textarea').prop('disabled', false);
            // });
        });
    </script>
@endpush
