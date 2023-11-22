<?php

namespace App\Http\Controllers\Admin;

use DB;
use Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Http\Request;
use App\Models\WorkShiftDetail;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;

class WorkShiftController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('work_shifts-list');
        $title = 'All Work Shifts';
        
        $model = WorkShift::orderby('id', 'desc')->get();
        if($request->ajax() && $request->loaddata == "yes") {
            return DataTables::of($model)
                ->addIndexColumn()
                ->editColumn('status', function ($model) {
                    $label = '';

                    switch ($model->status) {
                        case 1:
                            $label = '<span class="badge bg-label-success" text-capitalized="">Active</span>';
                            break;
                        case 0:
                            $label = '<span class="badge bg-label-danger" text-capitalized="">De-active</span>';
                            break;
                    }

                    return $label;
                })
                ->editColumn('start_date', function ($model) {
                    return Carbon::parse($model->start_date)->format('d, M Y');
                })
                ->editColumn('start_time', function ($model) {
                    return '<span class="fw-semibold">'.Carbon::parse($model->start_time)->format('h:i A').'</span>';
                })
                ->editColumn('end_time', function ($model) {
                    return '<span class="fw-semibold">'.Carbon::parse($model->end_time)->format('h:i A').'</span>';
                })
                ->editColumn('type', function ($model) {
                    if($model->type == 'scheduled') {
                        $label = '<span class="badge bg-label-info text-capitalized">'.Str::ucfirst($model->type).'</span>';
                    }else{
                        $label = '<span class="badge bg-label-success text-capitalized" >'.Str::ucfirst($model->type).'</span>';
                    }

                    return $label;
                })
                ->editColumn('name', function ($model) {
                    return '<span class="text-primary fw-semibold">'.$model->name.'</span>';
                })
                ->addColumn('action', function($model){
                    return view('admin.work_shifts.action', ['model' => $model])->render();
                })
                ->rawColumns(['type', 'status', 'name', 'start_time', 'end_time', 'action'])
                ->make(true);
        }

        return view('admin.work_shifts.index', compact('title'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => ['required', 'unique:work_shifts', 'max:255'],
            'start_date' => ['required'],
            'type' => ['required'],
            'start_time' => ['required'],
            'end_time' => ['required'],
            'description' => ['max:500'],
        ]);

        // $work_shift = $request->except(['start_time', 'end_time', 'weekend_days']);

        DB::beginTransaction();

        try{
            WorkShift::create($request->all());
            // if($model){
            //     $daysOfWeek = [];

            //     for ($day = 0; $day < 7; $day++) {
            //         // Create a Carbon instance for the current day of the week
            //         $date = Carbon::now()->startOfWeek()->addDays($day);

            //         //Day key
            //         $short_day = Str::lower($date->format('D'));
            //         // Add the day name to the array
            //         $daysOfWeek[$short_day] = $date->format('l');
            //     }

            //     foreach($daysOfWeek as $short_day_key=>$dayOfWeek){
            //         $bool = false;
            //         foreach($request->weekend_days as $weekend_day){
            //             if($short_day_key==$weekend_day){
            //                 $bool = true;
            //             }
            //         }
            //         $work_shift_detail = new WorkShiftDetail();
            //         $work_shift_detail->working_shift_id = $model->id;
            //         $work_shift_detail->weekday_key = $short_day_key;
            //         $work_shift_detail->weekday = $dayOfWeek;
            //         $work_shift_detail->is_weekend = $bool;
            //         if(!$bool){
            //             $work_shift_detail->start_time = $request->start_time;
            //             $work_shift_detail->end_time = $request->end_time;
            //         }

            //         $work_shift_detail->save();
            //     }

            //     DB::commit();
            // }

            DB::commit();

            \LogActivity::addToLog('New Work shift Added');

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $this->authorize('work_shifts-edit');
        $model = WorkShift::where('id', $id)->first();
        return (string) view('admin.work_shifts.edit_content', compact('model'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WorkShift $workShift)
    {
        $this->validate($request, [
            'name' => 'required|max:255|unique:work_shifts,id,'.$workShift->id,
            'start_date' => ['required'],
            'type' => ['required'],
            'start_time' => ['required'],
            'end_time' => ['required'],
            'description' => ['max:500'],
        ]);

        // $work_shift = $request->except(['start_time', 'end_time', 'weekend_days']);

        DB::beginTransaction();

        try{
            $model = $workShift->update($request->all());
            // if($model){
            //     $daysOfWeek = [];
            //     if(isset($request->weekend_days) && !empty($request->weekend_days)) {
            //         WorkShiftDetail::where('working_shift_id', $workShift->id)->delete();

            //         for ($day = 0; $day < 7; $day++) {
            //             // Create a Carbon instance for the current day of the week
            //             $date = Carbon::now()->startOfWeek()->addDays($day);

            //             //Day key
            //             $short_day = Str::lower($date->format('D'));
            //             // Add the day name to the array
            //             $daysOfWeek[$short_day] = $date->format('l');
            //         }

            //         foreach($daysOfWeek as $short_day_key=>$dayOfWeek) {
            //             $bool = false;
            //             foreach($request->weekend_days as $weekend_day) {
            //                 if($short_day_key==$weekend_day) {
            //                     $bool = true;
            //                 }
            //             }
            //             $work_shift_detail = new WorkShiftDetail();
            //             $work_shift_detail->working_shift_id = $workShift->id;
            //             $work_shift_detail->weekday_key = $short_day_key;
            //             $work_shift_detail->weekday = $dayOfWeek;
            //             $work_shift_detail->is_weekend = $bool;
            //             if(!$bool) {
            //                 $work_shift_detail->start_time = $request->start_time;
            //                 $work_shift_detail->end_time = $request->end_time;
            //             }

            //             $work_shift_detail->save();
            //         }
            //     }

            //     DB::commit();
            // }

            DB::commit();

            \LogActivity::addToLog('Work shift Updated');

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WorkShift $workShift)
    {
        $this->authorize('work_shifts-delete');
        $model = $workShift->delete();
        if($model){
            return response()->json([
                'status' => true,
            ]);
        }else{
            return false;
        }
    }
    public function trashed(Request $request)
    {
        $title = 'All Trashed Shifts';
        $temp = 'All Trashed Shifts';

        $model = WorkShift::orderby('id', 'desc')->onlyTrashed()->get();
        if($request->ajax()) {
            return DataTables::of($model)
                ->addIndexColumn()
                ->editColumn('status', function ($model) {
                    $label = '';

                    switch ($model->status) {
                        case 1:
                            $label = '<span class="badge bg-label-success" text-capitalized="">Active</span>';
                            break;
                        case 0:
                            $label = '<span class="badge bg-label-danger" text-capitalized="">De-active</span>';
                            break;
                    }

                    return strip_tags($label);
                })
                ->editColumn('start_date', function ($model) {
                    return Carbon::parse($model->start_date)->format('d, M Y');
                })
                ->editColumn('start_time', function ($model) {
                    return Carbon::parse($model->start_time)->format('h:i A');
                })
                ->editColumn('end_time', function ($model) {
                    return Carbon::parse($model->end_time)->format('h:i A');
                })
                ->editColumn('type', function ($model) {
                    if($model->type == 'scheduled') {
                        $label = '<span class="badge bg-label-info text-capitalized">'.Str::ucfirst($model->type).'</span>';
                    }else{
                        $label = '<span class="badge bg-label-success text-capitalized" >'.Str::ucfirst($model->type).'</span>';
                    }

                    return strip_tags($label);
                })
                ->addColumn('action', function($model){
                    $button = '<a href="'.route('work_shifts.restore', $model->id).'" class="btn btn-icon btn-label-info waves-effect">'.
                                    '<span>'.
                                        '<i class="ti ti-refresh ti-sm"></i>'.
                                    '</span>'.
                                '</a>';
                    return $button;
                })
                ->make(true);
        }

        return view('admin.work_shifts.index', compact('title', 'temp'));
    }
    public function restore($id)
    {
        WorkShift::onlyTrashed()->where('id', $id)->restore();
        return redirect()->back()->with('message', 'Record Restored Successfully.');
    }

    public function getWorkShifts(Request $request){
        $user = User::where('id', $request->user_id)->first();
        $user->userWorkingShift;
        $shifts = WorkShift::where('status', 1)->get();
        return (string) view('admin.work_shifts.work_shifts', compact('shifts', 'user'));
    }
}
