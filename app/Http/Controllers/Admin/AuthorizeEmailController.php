<?php

namespace App\Http\Controllers\Admin;

use DB;
use Str;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\AuthorizeEmail;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;

class AuthorizeEmailController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('authorize_emails-list');
        $title = 'All Authorized Emails';

        $users = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['department manager', 'admin']);
        })->get();

        $model = AuthorizeEmail::orderby('id', 'desc')->get();
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
                ->addColumn('email_title', function ($model) {
                    return '<span class="text-primary fw-semibold">'.Str::title($model->email_title).'</span>';
                })
                ->editColumn('to_emails', function ($model) {
                    return '<span class="fw-semibold">'.view('admin.authorize_emails.to_emails', ['model' => $model])->render().'</span>';
                })
                ->editColumn('cc_emails', function ($model) {
                    return '<span class="fw-semibold">'.view('admin.authorize_emails.cc_emails', ['model' => $model])->render().'</span>';
                })
                ->addColumn('action', function($model){
                    return view('admin.authorize_emails.action', ['model' => $model])->render();
                })
                ->rawColumns(['to_emails', 'cc_emails', 'status', 'email_title', 'action'])
                ->make(true);
        }

        return view('admin.authorize_emails.index', compact('title', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'email_title' => 'required|max:255',
            'to_emails' => 'required',
        ]);

        DB::beginTransaction();

        try{
            $cc_emails = NULL;
            if(!empty($request->cc_emails)){
                $cc_emails = json_encode($request->cc_emails);
            }
            $model = AuthorizeEmail::create([
                'email_title' => $request->email_title,
                'to_emails' => json_encode($request->to_emails),
                'cc_emails' => $cc_emails,
            ]);

            DB::commit();

            \LogActivity::addToLog('Authorize User Emails Added');

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function show($announcement_id)
    {
        $model = Announcement::findOrFail($announcement_id);
        return (string) view('admin.authorize_emails.show_content', compact('model'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $this->authorize('authorize_emails-edit');
        $model = AuthorizeEmail::where('id', $id)->first();
        $users = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['department manager', 'admin']);
        })->get();
        return (string) view('admin.authorize_emails.edit_content', compact('model', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $authorize_id)
    {
        $this->validate($request, [
            'email_title' => 'required|max:255',
            'to_emails' => 'required',
        ]);

        DB::beginTransaction();

        try{
            $cc_emails = NULL;
            if(!empty($request->cc_emails)){
                $cc_emails = json_encode($request->cc_emails);
            }
            
            $authorize = AuthorizeEmail::where('id', $authorize_id)->first();
            $authorize->email_title = $request->email_title;
            $authorize->to_emails = json_encode($request->to_emails);
            $authorize->cc_emails = $cc_emails;
            $authorize->save();

            DB::commit();

            \LogActivity::addToLog('Authorize Email Updated');

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AuthorizeEmail $authorize_email)
    {
        $this->authorize('authorize_emails-delete');
        $model = $authorize_email->delete();
        if($model){
            $onlySoftDeleted = AuthorizeEmail::onlyTrashed()->count();
            return response()->json([
                'status' => true,
                'trash_records' => $onlySoftDeleted
            ]);
        }else{
            return false;
        }
    }

    public function trashed(Request $request)
    {
        $title = 'All Trashed Authorized Emails';
        $model = AuthorizeEmail::orderby('id', 'desc')->onlyTrashed()->get();
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
                ->addColumn('email_title', function ($model) {
                    if($model->email_title=='new_employee_info'){
                        return 'New Employee Information';
                    }elseif($model->email_title=='employee_termination'){
                        return 'Employee Termination';
                    }
                })
                ->editColumn('cc_emails', function ($model) {
                    return view('admin.authorize_emails.cc_emails', ['model' => $model])->render();
                })
                ->addColumn('action', function($model){
                    $button = '<a href="'.route('authorize_emails.restore', $model->id).'" class="btn btn-icon btn-label-info waves-effect">'.
                                    '<span>'.
                                        '<i class="ti ti-refresh ti-sm"></i>'.
                                    '</span>'.
                                '</a>';
                    return $button;
                })
                ->rawColumns(['to_emails', 'cc_emails', 'action'])
                ->make(true);
        }

        return view('admin.authorize_emails.index', compact('title'));
    }
    public function restore($id)
    {
        AuthorizeEmail::onlyTrashed()->where('id', $id)->restore();
        return redirect()->back()->with('message', 'Record Restored Successfully.');
    }
}
