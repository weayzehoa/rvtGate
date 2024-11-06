<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use App\Models\ReceiverBaseSetting as ReceiverBaseSettingDB;
use App\Models\iCarryReceiverBaseSet as ReceiverBaseSetDB;
use DB;

trait ProductAvailableDate
{
    public function getPreDeliveryDate($is_call,$is_out,$max_days,$pay_time){//取得預交日
        foreach($is_call as $k=>$v){
            if($v<=$pay_time){
                array_shift($is_call);
            }
        }
        foreach($is_out as $k=>$v){
            if($v<=$pay_time){
                array_shift($is_out);
            }
        }
        $_date=new \DateTime("{$pay_time}000001");
        //$_date->modify("+1 day");//當天不算，所以+1
        $pay_time_plus_1_day=$_date->format('Ymd');
        $key = array_search($pay_time_plus_1_day,$is_call);
        //pay_time 的訂單先抓N次「可叫」
        $max_days=$max_days-1;//shift N-1次即可
        $imax=$key+$max_days;
        for($i=0;$i<$imax;$i++){
            array_shift($is_call);
        }
        //return $is_call;
        //減掉一天可出
        $key = array_search($is_call[0],$is_out);
        if($key===false || $key===0){
            for($i=1;$i<=30;$i++){//公司不可能超過14天放假
                $_date=new \DateTime($is_call[0]);
                $_date->modify("+{$i} day");
                $_ymd=$_date->format('Ymd');
                $key = array_search($_ymd,$is_out);
                if($key!==false){
                    $answer=$is_out[$key-1];//找到答案了
                    break;
                }
            }
        }else{
            $answer=$is_out[$key-1];//找到答案了
        }
        return $answer;
    }

    function getPreDeliveryDateInSpecialDate($is_call,$is_out,$max_days,$pay_time){//取得預定出貨日
        foreach($is_call as $k=>$v){
            if($v<=$pay_time){
                array_shift($is_call);
            }
        }
        foreach($is_out as $k=>$v){
            if($v<=$pay_time){
                array_shift($is_out);
            }
        }
        $_date=new \DateTime("{$pay_time}000001");
        //$_date->modify("+1 day");//當天不算，所以+1
        $pay_time_plus_1_day=$_date->format('Ymd');
        $key = array_search($pay_time_plus_1_day,$is_call);
        //pay_time 的訂單先抓N次「可叫」
        $max_days=$max_days-1;//shift N-1次即可
        $imax=$key+$max_days;
        for($i=0;$i<$imax;$i++){
            array_shift($is_call);
        }
        //return $is_call;
        //不減掉一天可出
        $key = array_search($is_call[0],$is_out);
        if($key===false || $key===0){
            for($i=1;$i<=30;$i++){//公司不可能超過14天放假
                $_date=new \DateTime($is_call[0]);
                $_date->modify("+{$i} day");
                $_ymd=$_date->format('Ymd');
                $key = array_search($_ymd,$is_out);
                if($key!==false){
                    //$answer=$is_out[$key-1];//找到答案了
                    $answer=$is_out[$key];//找到答案了
                    break;
                }
            }
        }else{
            //$answer=$is_out[$key-1];//找到答案了
            $answer=$is_out[$key];//找到答案了
        }
        return $answer;
    }

    function getPreDeliveryDateInSpecialDateMinusOneDay($colX,$is_out){
        $_date=new \DateTime($colX);
        $is_out = array_merge([''],$is_out); //避開key = 0的問題
        for($i=0;$i<=30;$i++){//公司不可能超過14天放假
            $_date->modify("-1 day");
            $_ymd=$_date->format('Ymd');
            $key = array_search($_ymd,$is_out);
            if(!empty($key)){
                return $answer=$is_out[$key];//找到答案了
            }
        }
    }

    function getMomoBookingShippingDateInTheSameDay($colX,$is_out){
        $_date=new \DateTime($colX);
        $is_out = array_merge([''],$is_out); //避開key = 0的問題
        $_ymd=$_date->format('Ymd');
        $key = array_search($_ymd,$is_out);
        if($key > 0){ //包含當天
            return $answer=$is_out[$key];
        }else{ //第一天可能是假期
            for($i=0;$i<=30;$i++){ //公司不可能超過14天放假
                $_date->modify("-1 day");
                $_ymd=$_date->format('Ymd');
                $key = array_search($_ymd,$is_out);
                if(!empty($key)){
                    return $answer=$is_out[$key];//找到答案了
                }
            }
        }
    }

    function getMomoBookingShippingDateMinusOneDay($colX,$is_out){
        $_date=new \DateTime($colX);
        $is_out = array_merge([''],$is_out); //避開key = 0的問題
        for($i=0;$i<=30;$i++){//公司不可能超過14天放假
            $_date->modify("-1 day");
            $_ymd=$_date->format('Ymd');
            $key = array_search($_ymd,$is_out);
            if(!empty($key)){
                return $answer=$is_out[$key];//找到答案了
            }
        }
    }

    public function checkDatePlusOneDayCanDelivery($date_string){
        $vedd = new \DateTime($date_string);
        for($i=1;$i<=31;$i++){
            $vedd->modify('+1 day');
            if($vedd->format('w')==0 || $vedd->format('w')==6){//六日預定是不出貨的
                $receiverBase = ReceiverBaseSetDB::where('select_time',$vedd->format('Y-m-d'))->where('is_out',1)->count();
                if($receiverBase > 0){
                    return $vedd->format('Y-m-d');
                    break;
                }
            }else{//一到五預定都是可出貨
                $receiverBase = ReceiverBaseSetDB::where('select_time',$vedd->format('Y-m-d'))->where('is_out',0)->count();
                if($receiverBase == 0){
                    return $vedd->format('Y-m-d');
                    break;
                }
            }
        }
    }

    public function checkDatePlusOneDayCanPickup($date_string){
        $vedd = new \DateTime($date_string);
        for($i=1;$i<=31;$i++){
            $vedd->modify('+1 day');
            //一到日預定都是可提貨
            $receiverBase = ReceiverBaseSetDB::where('select_time',$vedd->format('Y-m-d'))->where('is_extract',0)->first();
            if(!empty($receiverBase)){
                if($receiverBase->is_extract == 0){
                    return $vedd->format('Y-m-d');
                    break;
                }
            }
        }
    }

    public function whichDateIsGreater($date_a,$date_b){
        $a=intval(str_replace("-","",$date_a));
        $b=intval(str_replace("-","",$date_b));
        if($a>$b){
            return $a;
        }else{
            return $b;
        }
    }

    public function checkDateMinusOneDayCanDelivery($is_out,$colX){
        $vedd = new \DateTime($colX);
        $key = array_search($colX,$is_out);
        if($key===false || $key===0){
            for($i=1;$i<=30;$i++){//公司不可能超過14天放假
                $_date=new \DateTime($colX);
                $_date->modify("-{$i} day");
                $_ymd=$_date->format('Ymd');
                $key = array_search($_ymd,$is_out);
                if($key!==false){
                    $answer=$is_out[$key-1];//找到答案了
                    break;
                }
            }
        }else{
            $answer=$is_out[$key-1];//找到答案了
        }
        if(!empty($answer)){
            return $answer;
        }
        return $colX;
    }

    public function findReceiverBase($max)
    {
        $data = [];
        if(!empty($max)){
            //找出is_call與is_out
            $_date=new \DateTime($max->max_pay_time);
            $_date->modify("+{$max->max_days} day");
            $the_max_pick_date=$_date->format('Y-m-d');
            if(!empty($max->max_receiver_key_time)){
                $_num_max_receiver_key_time=str_replace("-","",substr($max->max_receiver_key_time,0,10));
                $num_the_max_pick_date=str_replace("-","",substr($the_max_pick_date,0,10));
                if($_num_max_receiver_key_time>$num_the_max_pick_date){
                    $_date=new \DateTime($max->receiver_key_time);
                    $the_max_pick_date=$_date->format('Y-m-d');
                }
            }

            $_date=new \DateTime($max->min_pay_time);
            $_date->modify("+1 day");//當天不算，所以+1
            $the_min_pick_date=$_date->format('Y-m-d');

            $_date=new \DateTime($the_max_pick_date);
            $_date->modify("+90 day");
            $the_max_pick_date=$_date->format('Y-m-d');

            $firstDate  = new \DateTime($the_min_pick_date);
            $secondDate = new \DateTime($the_max_pick_date);

            $period = new \DatePeriod(
                new \DateTime($the_min_pick_date),
                new \DateInterval('P1D'),
                new \DateTime($the_max_pick_date)
            );
            foreach ($period as $date) $dd[] = $date->format('Y-m-d'); //找出區間所有日期
            for($d=0;$d<count($dd);$d++){
                $receiverBase = ReceiverBaseSetDB::where('select_time',$dd[$d])
                    ->select([
                        'receiver_base_set.*',
                        DB::raw("DATE_FORMAT(select_time,'%w') as week"),
                    ])->first();
                if(!empty($receiverBase)){
                    if($receiverBase->is_out == 1){
                        $is_out[] = str_replace('-','',$dd[$d]);
                    }
                    if($receiverBase->is_call == 1){
                        $is_call[] = str_replace('-','',$dd[$d]);
                    }
                    if($receiverBase->is_logistics == 1){
                        $is_logistics[] = str_replace('-','',$dd[$d]);
                    }
                    if($receiverBase->is_extract == 1){
                        $is_extract[] = str_replace('-','',$dd[$d]);
                    }
                }else{
                    if(date('w',strtotime($dd[$d])) == 6 || date('w',strtotime($dd[$d])) == 0){//星期六、日
                        $is_extract[] = str_replace('-','',$dd[$d]);
                    }else{ //星期一至星期五
                        $is_out[] = str_replace('-','',$dd[$d]);
                        $is_call[] = str_replace('-','',$dd[$d]);
                        $is_logistics[] = str_replace('-','',$dd[$d]);
                        $is_extract[] = str_replace('-','',$dd[$d]);
                    }
                }
            }
            $data['is_out'] = $is_out;
            $data['is_call'] = $is_call;
            $data['is_logistics'] = $is_logistics;
            $data['is_extract'] = $is_extract;
        }
        return $data;
    }

    public function findReceiverBaseArrayMode($max)
    {
        $data = [];
        if(!empty($max)){
            //找出is_call與is_out
            $_date=new \DateTime($max['max_pay_time']);
            $_date->modify("+{$max['max_days']} day");
            $the_max_pick_date=$_date->format('Y-m-d');
            if(!empty($max['max_receiver_key_time'])){
                $_num_max_receiver_key_time=str_replace("-","",substr($max['max_receiver_key_time'],0,10));
                $num_the_max_pick_date=str_replace("-","",substr($the_max_pick_date,0,10));
                if($_num_max_receiver_key_time>$num_the_max_pick_date){
                    $_date=new \DateTime($max['receiver_key_time']);
                    $the_max_pick_date=$_date->format('Y-m-d');
                }
            }

            $_date=new \DateTime($max['min_pay_time']);
            $_date->modify("+1 day");//當天不算，所以+1
            $the_min_pick_date=$_date->format('Y-m-d');

            $_date=new \DateTime($the_max_pick_date);
            $_date->modify("+90 day");
            $the_max_pick_date=$_date->format('Y-m-d');

            $firstDate  = new \DateTime($the_min_pick_date);
            $secondDate = new \DateTime($the_max_pick_date);

            $period = new \DatePeriod(
                new \DateTime($the_min_pick_date),
                new \DateInterval('P1D'),
                new \DateTime($the_max_pick_date)
            );
            foreach ($period as $date) $dd[] = $date->format('Y-m-d'); //找出區間所有日期
            for($d=0;$d<count($dd);$d++){
                $receiverBase = ReceiverBaseSetDB::where('select_time',$dd[$d])
                    ->select([
                        'receiver_base_set.*',
                        DB::raw("DATE_FORMAT(select_time,'%w') as week"),
                    ])->first();
                if(!empty($receiverBase)){
                    if($receiverBase->is_out == 1){
                        $is_out[] = str_replace('-','',$dd[$d]);
                    }
                    if($receiverBase->is_call == 1){
                        $is_call[] = str_replace('-','',$dd[$d]);
                    }
                    if($receiverBase->is_logistics == 1){
                        $is_logistics[] = str_replace('-','',$dd[$d]);
                    }
                    if($receiverBase->is_extract == 1){
                        $is_extract[] = str_replace('-','',$dd[$d]);
                    }
                }else{
                    if(date('w',strtotime($dd[$d])) == 6 || date('w',strtotime($dd[$d])) == 0){//星期六、日
                        $is_extract[] = str_replace('-','',$dd[$d]);
                    }else{ //星期一至星期五
                        $is_out[] = str_replace('-','',$dd[$d]);
                        $is_call[] = str_replace('-','',$dd[$d]);
                        $is_logistics[] = str_replace('-','',$dd[$d]);
                        $is_extract[] = str_replace('-','',$dd[$d]);
                    }
                }
            }
            $data['is_out'] = $is_out;
            $data['is_call'] = $is_call;
            $data['is_logistics'] = $is_logistics;
            $data['is_extract'] = $is_extract;
        }
        return $data;
    }

    public function findMomoIsOut($colX)
    {
        $data = [];
        if(!empty($colX)){
            //找出is_call與is_out
            $_date=new \DateTime($colX);
            $_date->modify("1 day"); //包括當天可出
            for($i=0;$i<30;$i++){
                $date = $_date->modify("-1 day");
                $dd[] = $date->format('Y-m-d');
            }
            for($d=0;$d<count($dd);$d++){
                $receiverBase = ReceiverBaseSetDB::where('select_time',$dd[$d])
                    ->select([
                        'receiver_base_set.*',
                        DB::raw("DATE_FORMAT(select_time,'%w') as week"),
                    ])->first();
                if(!empty($receiverBase)){
                    if($receiverBase->is_out == 1){
                        $is_out[] = str_replace('-','',$dd[$d]);
                    }
                }else{
                    if(date('w',strtotime($dd[$d])) == 6 || date('w',strtotime($dd[$d])) == 0){//星期六、日

                    }else{ //星期一至星期五
                        $is_out[] = str_replace('-','',$dd[$d]);
                    }
                }
            }
            $data['is_out'] = $is_out;
        }
        return $data;
    }

    //新版用
    public function productAvailableDate($inputStockDays, $today = null, $type = null)
    {
        //由於有商品備貨日超過34天, 故 endDate 用庫存日+30天作為迴圈避免找錯日期
        $today == null ? $today = date('Y-m-d') : '';
        $today == date('Y-m-d') ? $endDate = Carbon::now()->addDays($inputStockDays + 30) : $endDate = Carbon::create(substr($today,0,4), substr($today,5,2), substr($today,8,2), 0)->addDays($inputStockDays + 30);
        $today == date('Y-m-d') ? $startHour = intval(date("G",strtotime(Carbon::now()))) : $startHour = 12;
        //訂單日期小於2020-01-01的訂單無法算出出貨與提貨日, 直接返回空值
        if($today < '2020-01-01'){
            return null;
        }
        //ReceiverBaseSetting 資料表只到2032年12月31日, 若此function還有在用的話, 需再增加資料, 否則 endDate 會停留在 2032-12-31
        $tmps = ReceiverBaseSettingDB::where([['select_date','>=',$today],['select_date','<=',$endDate]])->orderBy('select_date','asc')->get();
        $tmps = $tmps->groupBy('select_date');
        foreach($tmps as $d => $tmp){
            $everyDays[] = $d;
            foreach($tmp as $t){
                $dates[$d][$t->type] = $t->is_ok;
            }
        }
        $atLeastDays = 0;
        if($inputStockDays == 1){
            if($startHour < 11){ //商品備貨日1天內的商品，中午12點前下單且下單日為【出貨日】計算：提貨
                foreach($dates as $date => $val){
                    if($date == $today){
                        $availableDate = $date;
                        !empty($type) && $type == 'shipping' ? $availableShippingDate = $date : '';
                        break;
                    }
                }
            }else{ //商品備貨日1天內的商品，中午10點後下單 或 中午10點前下單但下單日不是【出貨日】計算：出貨、提貨
                foreach($everyDays as $k=>$d){
                    if($k>$atLeastDays){
                        if($dates[$d]['out']==1){
                            $atLeastDays=$k;
                            !empty($type) && $type == 'shipping' ? $availableShippingDate = $d : '';
                            break;
                        }
                    }
                }
                foreach($everyDays as $k=>$d){
                    if($k>$atLeastDays){
                        if($dates[$d]['pickup']==1){
                            $atLeastDays=$k;
                            $availableDate = $d;
                            break;
                        }
                    }
                }
            }
        }elseif($inputStockDays == 2){ //備貨日為2天
            foreach($everyDays as $k=>$d){
                if($k>$atLeastDays){
                    if($dates[$d]['out']==1){
                        $atLeastDays=$k;
                        !empty($type) && $type == 'shipping' ? $availableShippingDate = $d : '';
                        break;
                    }
                }
            }
            foreach($everyDays as $k=>$d){
                if($k>$atLeastDays){
                    if($dates[$d]['pickup']==1){
                        $atLeastDays=$k;
                        $availableDate = $d;
                        break;
                    }
                }
            }
        }elseif($inputStockDays == 3){
            foreach($everyDays as $k=>$d){
                if($k>$atLeastDays){
                    if($dates[$d]['call']==1){
                        $atLeastDays=$k;
                        break;
                    }
                }
            }
            foreach($everyDays as $k=>$d){
                if($k>$atLeastDays){
                    if($dates[$d]['out']==1){
                        $atLeastDays=$k;
                        !empty($type) && $type == 'shipping' ? $availableShippingDate = $d : '';
                        break;
                    }
                }
            }
            foreach($everyDays as $k=>$d){
                if($k>$atLeastDays){
                    if($dates[$d]['pickup']==1){
                        $atLeastDays=$k;
                        $availableDate = $d;
                        break;
                    }
                }
            }
        }else{ //備貨日為$n天
            for($n = 4; $n <= $inputStockDays ; $n++){
                $atLeastDays = 0; //跑迴圈要歸零,不然會被累加
                foreach($everyDays as $k=>$d){
                    if($k>$atLeastDays){
                        if($dates[$d]['call']==1){
                            $atLeastDays=$k;
                            break;
                        }
                    }
                }
                $needLogisticsDay=$n-3;
                $checkLogisticsDay=0;
                foreach($everyDays as $k=>$d){
                    if($k>$atLeastDays){
                        if($dates[$d]['logistics']==1){
                            $checkLogisticsDay+=1;
                            $atLeastDays=$k;
                            if($needLogisticsDay==$checkLogisticsDay){
                                break;
                            }
                        }
                    }
                }
                foreach($everyDays as $k=>$d){
                    if($k>$atLeastDays){
                        if($dates[$d]['out']==1){
                            $atLeastDays=$k;
                            !empty($type) && $type == 'shipping' ? $availableShippingDate = $d : '';
                            break;
                        }
                    }
                }
                foreach($everyDays as $k=>$d){
                    if($k>$atLeastDays){
                        if($dates[$d]['pickup']==1){
                            $atLeastDays=$k;
                            $availableDate = $d;
                            break;
                        }
                    }
                }
            }
        }
        if(!empty($type) && $type == 'shipping'){
            return $availableShippingDate;
        }
        return $availableDate;
    }
}
