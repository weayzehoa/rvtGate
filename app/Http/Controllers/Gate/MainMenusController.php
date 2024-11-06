<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Mainmenu as MainmenuDB;
use App\Models\Submenu as SubmenuDB;
use App\Http\Requests\Gate\MainmenusRequest;

class MainMenusController extends Controller
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
        $menuCode = 'M26S2';
        $mainMenus = MainmenuDB::where('type',3)->orderBy('type','asc')->orderBy('sort','asc')->get();
        $type1Count = 0;
        $type2Count = 0;
        foreach ($mainMenus as $mainMenu) {
            $mainMenu->type == 1 ? $type1Count++ : '';
            $mainMenu->type == 2 ? $type2Count++ : '';
        }
        return view('gate.menus.mainmenu_index',compact('mainMenus','menuCode','type1Count','type2Count'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $menuCode = 'M26S2';
        return view('gate.menus.mainmenu_show',compact('menuCode'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(MainmenusRequest $request)
    {
        $data = $request->all();
        $data['is_on'] ?? $data['is_on'] = 0;
        $data['open_window'] ?? $data['open_window'] = 0;
        $mainMenu = MainmenuDB::create($data);

        //重新排序
        $mainMenus = MainmenuDB::where('type',$mainMenu->type)->orderBy('sort','ASC')->get();
        $i = 1;
        foreach ($mainMenus as $mainMenu) {
            $id = $mainMenu->id;
            MainmenuDB::where('id', $id)->update(['sort' => $i]);
            $i++;
        }

        return view('gate.menus.mainmenu_show',compact('mainMenu'));
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
        $mainMenu = MainmenuDB::findOrFail($id);
        return view('gate.menus.mainmenu_show',compact('mainMenu','menuCode'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $subMenus = SubmenuDB::where('mainmenu_id',$id)->get();
        return view('gate.menus.submenu_index',compact('subMenus'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(MainmenusRequest $request, $id)
    {
        $data = $request->all();
        $data['is_on'] ?? $data['is_on'] = 0;
        $data['open_window'] ?? $data['open_window'] = 0;
        isset($data['power_action']) ? $data['power_action'] = join(',',$data['power_action']) : $data['power_action'] = null;
        MainmenuDB::findOrFail($id)->update($data);
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
        $mainMenus = MainmenuDB::find($id)->delete();
        $subMenus = SubmenuDB::where('mainmenu_id',$id)->delete();

        //重新排序
        $mainMenus = MainmenuDB::where('type',$mainMenu->type)->orderBy('sort','ASC')->get();
        $i = 1;
        foreach ($mainMenus as $mainMenu) {
            $id = $mainMenu->id;
            MainmenuDB::where('id', $id)->update(['sort' => $i]);
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
        MainmenuDB::findOrFail($request->id)->fill(['is_on' => $is_on])->save();
        return redirect()->back();
    }
    /*
        另開視窗
     */
    public function open(Request $request)
    {
        isset($request->open_window) ? $open_window = $request->open_window : $open_window = 0;
        MainmenuDB::findOrFail($request->id)->fill(['open_window' => $open_window])->save();
        return redirect()->back();
    }
    /*
        向上排序
    */
    public function sortup(Request $request)
    {
        $id = $request->id;
        $mainMenu = MainmenuDB::findOrFail($id);
        $up = ($mainMenu->sort) - 1.5;
        $mainMenu->fill(['sort' => $up]);
        $mainMenu->save();

        $mainMenus = MainmenuDB::where('type',$mainMenu->type)->orderBy('sort','ASC')->get();
        $i = 1;
        foreach ($mainMenus as $mainMenu) {
            $id = $mainMenu->id;
            MainmenuDB::where('id', $id)->update(['sort' => $i]);
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
        $mainMenu = MainmenuDB::findOrFail($id);
        $up = ($mainMenu->sort) + 1.5;
        $mainMenu->fill(['sort' => $up]);
        $mainMenu->save();

        $mainMenus = MainmenuDB::where('type',$mainMenu->type)->orderBy('sort','ASC')->get();
        $i = 1;
        foreach ($mainMenus as $mainMenu) {
            $id = $mainMenu->id;
            MainmenuDB::where('id', $id)->update(['sort' => $i]);
            $i++;
        }
        return redirect()->back();
    }
    /**
     * Show the submenu index.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function submenu($id)
    {
        $menuCode = 'M26S2';
        $subMenus = SubmenuDB::where('mainmenu_id',$id)->orderBy('sort','asc')->get();
        return view('gate.menus.submenu_index',compact('subMenus','menuCode'));
    }
}
