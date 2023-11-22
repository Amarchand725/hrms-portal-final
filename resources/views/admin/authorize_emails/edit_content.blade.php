<div class="col-12 col-md-12">
    <label class="form-label" for="email_title">Email Title <span class="text-danger">*</span></label>
    <select class="form-control" id="email_title" name="email_title">
        <option value="" selected>Select Email Title</option>
        <option value="promotion" {{ $model->email_title=='promotion'?'selected':'' }}>Promotion</option>
        <option value="new_employee_info" {{ $model->email_title=='new_employee_info'?'selected':'' }}>New Employee Info</option>
        <option value="employee_termination" {{ $model->email_title=='employee_termination'?'selected':'' }}>Employee Temination</option>
        <option value="employee_resignation" {{ $model->email_title=='employee_resignation'?'selected':'' }}>Employee Resignation</option>
    </select>
    <div class="fv-plugins-message-container invalid-feedback"></div>
    <span id="email_title_error" class="text-danger error"></span>
</div>
<div class="col-12 col-md-12 mt-3 select2-primary">
    <label class="form-label" for="to_emails">To Emails <span class="text-danger">*</span></label>
    <select id="to_emails" class="form-control select2" multiple name="to_emails[]">
        <option value="to_employee">To Employee</option>
        @php $temp = true; @endphp 
        @foreach($users as $user)
            @php 
                $to_emails = json_decode($model->to_emails);
                $bool = true;
            
                $department_name = ''; 
            @endphp
            @if(isset($user->departmentBridge->department) && !empty($user->departmentBridge->department->name))
                @php $department_name = '( '. $user->departmentBridge->department->name.' )'; @endphp
            @endif
            @foreach($to_emails as $to_email)
                @if($user->email==$to_email)
                    @php $bool = false @endphp 
                    <option value="{{ $user->email }}" selected>{{ $user->first_name }} {{ $user->last_name }} {{ $department_name }}</option>
                @elseif($to_email=='to_employee' && $temp)
                    @php $bool = false; $temp = false; @endphp 
                    <option value="to_employee" selected>To Employee</option>
                @endif
            @endforeach
            @if($bool)
                <option value="{{ $user->email }}">{{ $user->first_name }} {{ $user->last_name }} {{ $department_name }}</option>
            @endif
        @endforeach
    </select>
    <div class="fv-plugins-message-container invalid-feedback"></div>
    <span id="to_email_error" class="text-danger error"></span>
</div>
<div class="col-12 col-md-12 mt-3 select2-primary">
    <label class="form-label" for="cc_emails">CC Emails</label>
    <select id="cc_emails" class="form-control" multiple name="cc_emails[]">
        <option value="to_employee">To Employee</option>
        
        @php $temp = true; @endphp 
        @foreach($users as $user)
            @php 
                $cc_emails = json_decode($model->cc_emails);
                $bool = true;
                
                $department_name = ''; 
            @endphp 
            @if(isset($user->departmentBridge->department) && !empty($user->departmentBridge->department->name))
                @php $department_name = '( '. $user->departmentBridge->department->name.' )'; @endphp
            @endif
            @foreach($cc_emails as $cc_email)
                @if($user->email==$cc_email)
                    @php $bool = false @endphp 
                    <option value="{{ $user->email }}" selected>{{ $user->first_name }} {{ $user->last_name }} {{ $department_name }}</option>
                @elseif($cc_email=='to_employee' && $temp)
                    @php $bool = false; $temp = false @endphp 
                    <option value="to_employee" selected>To Employee</option>
                @endif
            @endforeach
            @if($bool)
                <option value="{{ $user->email }}">{{ $user->first_name }} {{ $user->last_name }} {{ $department_name }}</option>
            @endif
        @endforeach
     </select>
     <div class="fv-plugins-message-container invalid-feedback"></div>
    <span id="cc_emails_error" class="text-danger error"></span>
</div>
