@php
    $statistics = getAttandanceCount($model->id, $year."-".((int)$month-1)."-26", $year."-".(int)$month."-25",'all', $shift->id);
    
    $total_regular_todays = [];
    $total_late_in_todays = [];
    $total_half_day_todays = [];
    $total_absent_todays = [];
    $total_team_todays = '';
    foreach($employees as $employee_member){
        $current_date = date("Y-m-d");
        if(date("H")>=8){
            $next_date = date("Y-m-d", strtotime($current_date.'+1 day'));
        }else{
            $current_date = date("Y-m-d", strtotime($current_date.'-1 day'));
            $next_date = date("Y-m-d", strtotime($current_date.'+1 day'));
        }
        
        $attendance_single_record  = getAttandanceSingleRecord($employee_member->id, $current_date, $next_date,'all', $shift->id);    
        
        if($attendance_single_record['type']=='lateIn' || $attendance_single_record['type']=='earlyout'){
            $attendance_date = '';
            if(!empty($attendance_single_record['attendance_date']->in_date)){
                $attendance_date = date('d F Y', strtotime($attendance_single_record['attendance_date']->in_date));
            }
            $total_late_in_todays[] = [
                'employee' => $attendance_single_record['user']->first_name,
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
                'employee' => $attendance_single_record['user']->first_name,
                'punchIn' => $attendance_single_record['punchIn'],
                'punchOut' => $punch_out,
                'date' => $attendance_date,
                'type' => $attendance_single_record['type'],
            ];
        }else if($attendance_single_record['type']=='firsthalf'){
            $total_half_day_todays[] = [
                'employee' => $attendance_single_record['user']->first_name,
                'punchIn' => $attendance_single_record['punchIn'],
                'punchOut' => $attendance_single_record['punchOut'],
                'date' => date('d F Y', strtotime($attendance_single_record['attendance_date']->in_date)),
                'type' => $attendance_single_record['type'],
            ];
        }else if($attendance_single_record['type']=='absent'){
            $total_absent_todays[] = [
                'employee' => $attendance_single_record['user']->first_name,
                'type' => $attendance_single_record['type'],
                'date' => date('d F Y', strtotime($attendance_single_record['attendance_date'])),
            ];
        }else if($attendance_single_record['type']=='regular'){
            $total_regular_todays[] = [
                'employee' => $attendance_single_record['user']->first_name,
                'punchIn' => $attendance_single_record['punchIn'],
                'punchOut' => $attendance_single_record['punchOut'],
                'date' => date('d F Y', strtotime($attendance_single_record['attendance_date']->in_date)),
                'type' => $attendance_single_record['type'],
            ];
        }
    }
    $total_team_todays = array_merge($total_late_in_todays, $total_half_day_todays, $total_absent_todays, $total_regular_todays);
    
@endphp