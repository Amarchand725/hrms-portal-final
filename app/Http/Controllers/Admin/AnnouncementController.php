<?php

namespace App\Http\Controllers\Admin;

use DB;
use Auth;
use App\Models\User;
use App\Models\Department;
use App\Models\Announcement;
use Illuminate\Http\Request;
use App\Models\DepartmentUser;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use App\Models\AnnouncementDepartment;
use Yajra\DataTables\Facades\DataTables;
use App\Notifications\AnnouncementNotification;
use App\Notifications\ImportantNotificationWithMail;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('announcements-list');
        $title = 'All Announcements';

        $departments = Department::where('status', 1)->get();
        // $model = Announcement::orderby('id', 'desc')->get();
        
        $model = [];
        Announcement::latest()
            ->chunk(100, function ($announcements) use (&$model) {
                foreach ($announcements as $announcement) {
                    $model[] = $announcement;
                }
        });
        
        if($request->ajax() && $request->loaddata == "yes") {
            return DataTables::of($model)
                ->addIndexColumn()
                ->editColumn('created_at', function ($model) {
                    return Carbon::parse($model->created_at)->format('d, M Y');
                })
                ->editColumn('start_date', function ($model) {
                    return Carbon::parse($model->start_date)->format('d, M Y');
                })
                ->editColumn('end_date', function ($model) {
                    return Carbon::parse($model->end_date)->format('d, M Y');
                })
                ->editColumn('created_by', function ($model) {
                    if(!empty($model->createdBy->first_name)){
                        return '<span class="fw-semibold">'.$model->createdBy->first_name .' '. $model->createdBy->last_name.'</span>';
                    }else{
                        return '-';
                    }
                })
                ->editColumn('title', function ($model) {
                    return '<span class="text-primary fw-semibold">'.$model->title.'</span>';
                })
                ->addColumn('action', function($model){
                    return view('admin.announcements.action', ['model' => $model])->render();
                })
                ->rawColumns(['title', 'created_by', 'action'])
                ->make(true);
        }

        return view('admin.announcements.index', compact('title', 'departments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'title' => 'required|max:255',
            'start_date' => 'required',
        ]);

        DB::beginTransaction();

        try{
            $model = Announcement::create([
                'created_by' => Auth::user()->id,
                'title' => $request->title,
                'description' => $request->description,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);
            
            if($model){
                $all_departments = Department::where('status', 1)->get();
                foreach($all_departments as $department) {
                    $department_ids[] = $department->id;
                }
                
                foreach($department_ids as $department_id){
                    $announcement_department = new AnnouncementDepartment();
                    $announcement_department->announcement_id = $model->id;
                    $announcement_department->department_id = $department_id;
                    $announcement_department->save();

                    $department_users = DepartmentUser::where('user_id', '!=', Auth::user()->id)->where('department_id', $department_id)->where('end_date', NULL)->get();
                    $dep_user_ids = [];
                    foreach($department_users as $department_user){
                        $dep_user = User::where('id', $department_user->user_id)->where('status', 1)->where('is_employee', 1)->first();
                        if(!empty($dep_user)){
                            $dep_user_ids[] = $dep_user;
                        }
                    }
                    
                    $login_user = Auth::user();
                    $notification_data = [
                        'id' => $model->id,
                        'date' => $model->start_date,
                        'type' => 'News Update',
                        'name' => $login_user->first_name.' '.$login_user->last_name,
                        'profile' => $login_user->profile->profile,
                        'title' => $model->title,
                        'reason' => strip_tags($model->description),
                    ];

                    // foreach($dep_user_ids as $dept_user){
                    //     $dept_user->notify(new ImportantNotificationWithMail($notification_data));
                    // }
                }

                DB::commit();
            }

            \LogActivity::addToLog('New Announcement Added');

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function show($announcement_id)
    {
        $model = Announcement::findOrFail($announcement_id);
        return (string) view('admin.announcements.show_content', compact('model'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $this->authorize('announcements-edit');
        $model = Announcement::where('id', $id)->first();
        $departments = Department::where('status', 1)->get();
        return (string) view('admin.announcements.edit_content', compact('model', 'departments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $announcement_id)
    {
        $this->validate($request, [
            'title' => 'required|max:255',
            'start_date' => 'required'
        ]);

        DB::beginTransaction();
        
        $login_user = Auth::user();
        try{
            $model = Announcement::where('id', $announcement_id)->first();
            $model->created_by = $login_user->id;
            $model->title = $request->title;
            $model->description = $request->description;
            $model->start_date = $request->start_date;
            $model->end_date = $request->end_date;
            $model->save();
            
            DB::commit();
            \LogActivity::addToLog('Announcement Updated');
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Announcement $announcement)
    {
        $this->authorize('announcements-delete');
        $model = $announcement->delete();
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
        $model = Announcement::onlyTrashed()->latest()->get();
        $title = 'All Trashed Announcements';

        if($request->ajax()) {
            return DataTables::of($model)
                ->addIndexColumn()
                ->editColumn('created_at', function ($model) {
                    return Carbon::parse($model->created_at)->format('d, M Y');
                })
                ->editColumn('start_date', function ($model) {
                    return Carbon::parse($model->start_date)->format('d, M Y');
                })
                ->editColumn('end_date', function ($model) {
                    return Carbon::parse($model->end_date)->format('d, M Y');
                })
                ->editColumn('created_by', function ($model) {
                    if(!empty($model->createdBy->first_name)){
                        return $model->createdBy->first_name .' '. $model->createdBy->last_name;
                    }else{
                        return '-';
                    }
                })
                ->addColumn('action', function($model){
                    $button = '<a href="'.route('announcements.restore', $model->id).'" class="btn btn-icon btn-label-info waves-effect">'.
                                    '<span>'.
                                        '<i class="ti ti-refresh ti-sm"></i>'.
                                    '</span>'.
                                '</a>';
                    return $button;
                })
                ->make(true);
        }

        return view('admin.announcements.index', compact('title'));
    }
    public function restore($id)
    {
        Announcement::onlyTrashed()->where('id', $id)->restore();
        return redirect()->back()->with('message', 'Record Restored Successfully.');
    }
}
