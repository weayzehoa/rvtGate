<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Models\Mainmenu as MainmenuDB;
use App\Models\Submenu as SubmenuDB;
use App\Http\Requests\Gate\SubmenusRequest;

class SubMenusController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // 先經過 middleware 檢查
        $this->middleware('auth:gate');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $data['is_on'] ?? $data['is_on'] = 0;
        $data['open_window'] ?? $data['open_window'] = 0;
        $subMenu = SubmenuDB::create($data);

        //重新排序
        $subMenus = SubmenuDB::where(['mainmenu_id' => $request->mainmenu_id])->orderBy('sort','ASC')->get();
        $i = 1;
        foreach ($subMenus as $subMenu) {
            $id = $subMenu->id;
            SubmenuDB::where(['id' => $id , 'mainmenu_id' => $request->mainmenu_id])->update(['sort' => $i]);
            $i++;
        }
        return redirect()->route('gate.submenus.show',$subMenu->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $menuCode = 'M26S2';
        $subMenu = SubmenuDB::findOrFail($id);
        $mainMenu = $subMenu->mainmenu;
        return view('gate.menus.submenu_show',compact('mainMenu','subMenu','menuCode'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $menuCode = 'M26S2';
        $mainMenu = MainmenuDB::findOrFail($id);
        return view('gate.menus.submenu_show',compact('mainMenu','menuCode'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = $request->all();
        $data['is_on'] ?? $data['is_on'] = 0;
        $data['open_window'] ?? $data['open_window'] = 0;
        $data['power_action'] = join(',',$data['power_action']);
        SubmenuDB::findOrFail($id)->update($data);
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $subMenu = SubmenuDB::findOrFail($id);
        $subMenu->delete();
        //重新排序
        $subMenus = SubmenuDB::where('mainmenu_id', $subMenu->mainmenu_id)->orderBy('sort','ASC')->get();
        $i = 1;
        foreach ($subMenus as $subMenu) {
            $id = $subMenu->id;
            $mainmenu_id = $subMenu->mainmenu_id;
            SubmenuDB::where(['id' => $id, 'mainmenu_id' => $mainmenu_id])->update(['sort' => $i]);
            $i++;
        }
        return redirect()->back();
    }
    /*
        啟用或禁用該主選單
     */
    public function active(Request $request)
    {
        isset($request->is_on) ? $is_on = $request->is_on : $is_on = 0;
        SubmenuDB::findOrFail($request->id)->fill(['is_on' => $is_on])->save();
        return redirect()->back();
    }
    /*
        另開視窗
     */
    public function open(Request $request)
    {
        isset($request->open_window) ? $open_window = $request->open_window : $open_window = 0;
        SubmenuDB::findOrFail($request->id)->fill(['open_window' => $open_window])->save();
        return redirect()->back();
    }
    /*
        向上排序
    */
    public function sortup(Request $request)
    {
        $id = $request->id;
        $subMenu = SubmenuDB::findOrFail($id);
        $up = ($subMenu->sort) - 1.5;
        $subMenu->fill(['sort' => $up]);
        $subMenu->save();
        $mainmenu_id = $subMenu->mainmenu->id;
        $subMenus = SubmenuDB::where('mainmenu_id', $mainmenu_id)->orderBy('sort','ASC')->get();
        $i = 1;
        foreach ($subMenus as $subMenu) {
            $id = $subMenu->id;
            SubmenuDB::where(['id' => $id, 'mainmenu_id' => $mainmenu_id])->update(['sort' => $i]);
            $i++;
        }
        return redirect()->back();
    }
    /*
        向下排序
    */
    public function sortdown(Request $request)
    {
        $id = $request->id;
        $subMenu = SubmenuDB::findOrFail($id);
        $up = ($subMenu->sort) + 1.5;
        $subMenu->fill(['sort' => $up]);
        $subMenu->save();
        $mainmenu_id = $subMenu->mainmenu->id;
        $subMenus = SubmenuDB::where('mainmenu_id', $mainmenu_id)->orderBy('sort','ASC')->get();
        $i = 1;
        foreach ($subMenus as $subMenu) {
            $id = $subMenu->id;
            SubmenuDB::where(['id' => $id, 'mainmenu_id' => $mainmenu_id])->update(['sort' => $i]);
            $i++;
        }
        return redirect()->back();
    }
}
