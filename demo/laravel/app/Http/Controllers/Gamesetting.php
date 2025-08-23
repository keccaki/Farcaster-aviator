<?php
                    
//         define('DB_SERVER', 'localhost');
// define('DB_USERNAME', 'seekosoft_adbanaouser');
// define('DB_PASSWORD', 'seekosoft_adbanaouser@11');
// define('DB_NAME', 'seekosoft_adbanao');
// // Try connecting to the Database
// $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// //Check the connection
// if($conn == false){
//     dir('Error: Cannot connect');
// }
// $sql3 = "SELECT value FROM emredperiod WHERE category=game_between_time_end and id='14'";
// $result3 =$conn->query($sql3);
// $row3 = mysqli_fetch_assoc($result3);
// @$period=$row3['value'];


namespace App\Http\Controllers;

use App\Models\Gameresult;
use App\Models\Setting;
use App\Models\Userbit;
use Illuminate\Http\Request;
use Carbon\Carbon;

class Gamesetting extends Controller
{
    
    public function crash_plane()
    {
        return 1;
    }
    public function game_existence(Request $r)
    {
        $event = $r->event;
        if ($event == "check") {
            $new = Setting::where('category', 'game_status')->where('value', '0')->first();
            
            if ($new || (session()->has('gamegenerate') && session()->get('gamegenerate') == 1)) {
                return array('data'=>true);
            }else{
                return array('data'=>false);
            }
            return array('data'=>false);
        }
    }
    public function new_game_generated(Request $r)
    {
        $new = Setting::where('category', 'game_status')->update(['value' => '0']);
        $r->session()->put('gamegenerate','1');
        return response()->json(array("id" => currentid()));
    }
    
    public function increamentor(Request $r)
    {
        $gamestatusdata = Setting::where('category', 'game_status')->first();
        $res = 0;
        if($gamestatusdata){
                
        // Check REAL user bets only (not fake bots)
        $totalbet = Userbit::where('gameid',currentid())->count();
        $totalamount = Userbit::where('gameid',currentid())->sum('amount');
        
        if ($totalbet == 0) {
            // No real bets: Use realistic early crash multipliers
            $randomMultipliers = [1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 2.0, 2.2, 2.5, 3.0];
            $res = $randomMultipliers[array_rand($randomMultipliers)];
        } else {
            // Real bets exist: Use sophisticated House Edge algorithm
            $houseEdgeMultipliers = $this->calculateHouseEdgeMultiplier($totalamount, $totalbet);
            $res = $houseEdgeMultipliers;
        }
        
        $status = true;
        $result = $res;
        $response = array('status'=>$status,'result'=>$result);
        return response()->json($response);
        }
    }

    /**
     * Calculate multiplier based on house edge and bet patterns
     */
    private function calculateHouseEdgeMultiplier($totalAmount, $totalBets) 
    {
        // Realistic crash game algorithm with house edge protection
        $baseMultipliers = [
            1.00, 1.01, 1.02, 1.03, 1.04, 1.05, 1.06, 1.07, 1.08, 1.09,
            1.10, 1.11, 1.12, 1.13, 1.14, 1.15, 1.16, 1.17, 1.18, 1.19,
            1.20, 1.22, 1.25, 1.30, 1.35, 1.40, 1.45, 1.50, 1.55, 1.60,
            1.65, 1.70, 1.75, 1.80, 1.85, 1.90, 1.95, 2.00, 2.10, 2.20,
            2.30, 2.40, 2.50, 2.75, 3.00, 3.50, 4.00, 5.00, 7.00, 10.00
        ];

        // Weight distribution based on realistic crash game probabilities
        $weights = [
            // 1.00-1.09: 25% chance (house edge protection)
            10, 10, 8, 8, 6, 6, 4, 4, 3, 3,
            // 1.10-1.19: 20% chance 
            5, 5, 4, 4, 3, 3, 2, 2, 2, 2,
            // 1.20-1.60: 30% chance (sweet spot)
            3, 4, 5, 6, 5, 4, 3, 3, 2, 2,
            2, 2, 2, 2, 2, 2, 2, 2, 2, 2,
            // 1.65-2.50: 15% chance
            1, 1, 1, 1, 1, 1, 1, 2, 1, 1,
            // 2.75-10.00: 10% chance (rare big wins)
            1, 1, 1, 1, 1, 1, 1, 1, 1, 1
        ];

        // Adjust weights based on total bet amount (house protection)
        if ($totalAmount > 10000) {
            // High betting: Reduce high multiplier chances
            for ($i = 30; $i < count($weights); $i++) {
                $weights[$i] = max(1, $weights[$i] - 1);
            }
            // Increase low multiplier chances
            for ($i = 0; $i < 20; $i++) {
                $weights[$i] += 2;
            }
        } elseif ($totalAmount < 500) {
            // Low betting: Slightly better chances for players
            for ($i = 20; $i < 40; $i++) {
                $weights[$i] += 1;
            }
        }

        // Create weighted array
        $weightedMultipliers = [];
        for ($i = 0; $i < count($baseMultipliers); $i++) {
            for ($j = 0; $j < $weights[$i]; $j++) {
                $weightedMultipliers[] = $baseMultipliers[$i];
            }
        }

        // Random selection from weighted array
        return $weightedMultipliers[array_rand($weightedMultipliers)];
    }

    
    public function game_over(Request $r)
    {
        $r->session()->forget('result');
        $result = Gameresult::where('id', currentid())->update([
            "result" => number_format($r->last_time, 2),
        ]);
        $alluserbit = Userbit::where('gameid', currentid())->where('status', 0)->get();
        foreach ($alluserbit as $key) {
			if(floatval($r->last_time) <= 1.20){
			$result = 0;
		    }else{
			$result = $r->last_time;
			}
            $finalamount = floatval($key->amount) * floatval($result);
            Userbit::where('id', $key->id)->update(["status"=> 1]);
            // addwallet($key->userid,$finalamount);
        }
        $new = Setting::where('category', 'game_status')->update(['value' => '0']);
        $r->session()->put('gamegenerate','0');
        $result = new Gameresult;
        $result->result = "pending";
        $result->save();
        return wallet(user('id'));
    }

    public function betNow(Request $r)
    {
        $status = false;
        $message = "Something went wrong!";
        $returnbets = array();
        for($i=0; $i < count($r->all_bets); $i++){
		$result = new Userbit;
        $result->userid = user('id');
        $result->amount = $r->all_bets[$i]['bet_amount'];
        $result->type = $r->all_bets[$i]['bet_type'];
        $result->gameid = currentid();
        $result->section_no = $r->all_bets[$i]['section_no'];
        if ($r->all_bets[$i]['bet_amount'] < wallet(user('id'), 'num')) {
            if ($result->save()) {
                $status = true;
                array_push($returnbets, [
                    "bet_id" => $result->id,
                ]);
				/*array_push($returnbets, [
                    "bet_id" => currentid(),
                ]);*/
                $exact_wallet_balance = addwallet(user('id'), floatval($r->all_bets[$i]['bet_amount']), "-");
                $data = array(
                    "wallet_balance" => wallet(user('id')),
                    "return_bets" => $returnbets
                );
                $message = "";
            }
        } else {
            $status = false;
            $data = array();
            $message = "Insufficient fund!!";
        }
		}
        $response = array("isSuccess" => $status, "data" => $data, "message" => $message);
        return response()->json($response);
    }
    public function currentlybet()
    {
        $allbets = Userbit::where("gameid", currentid())->join('users','users.id','=','userbits.userid')->get();
        $currentGameBet = $allbets;
        for ($i=0; $i < rand(400,900); $i++) { 
            $currentGameBet[]=array(
                "userid" => rand(10000,50000),
                "amount" => rand(999,9999),
				"image"  => "/images/avtar/av-".rand(1,72).".png"
            );
        }
        $currentGame = array("id"=>currentid());
        $currentGameBetCount = count($currentGameBet);
        $response = array("currentGame" => $currentGame, "currentGameBet" => $currentGameBet, "currentGameBetCount" => $currentGameBetCount);
        return response()->json($response);
    }
    public function my_bets_history(){
        $userid = user('id');
        $userbets = Userbit::where("userid", $userid)->where('status',1)->where('created_at', '>=', Carbon::today()->toDateString())->orderBy('id','desc')->get();
        return response()->json($userbets);
    }
	public function cashout(Request $r){
		$game_id = $r->game_id;
		$bet_id = $r->bet_id;
		$win_multiplier = $r->win_multiplier;
		$cash_out_amount = 0;
		$status = false;
        $message = "";
        $data = array();
		$result = resultbyid($game_id) == 0 ? $win_multiplier : resultbyid($game_id);
		if(floatval($result) <= 1.20){
			$result = 0;
		}
		$cash_out_amount = floatval(userbetdetail($bet_id,'amount'))*floatval($result);
		addwallet(user('id'),$cash_out_amount); 
		$data = array(
                    "wallet_balance" => wallet(user('id'),"num"),
                    "cash_out_amount" => $cash_out_amount
                );
        Userbit::where('id', $bet_id)->update(["status"=> 1,"cashout_multiplier"=>$win_multiplier]);
        $status = true;
		$response = array("isSuccess" => $status, "data" => $data, "message" => $message);
        return response()->json($response);
	}
	
	public function cronjob(){
	    //0 = Game end & statrting soon
	    //1 = Game start & and is in proccess
	    $gamestatusdata = Setting::where('category', 'game_status')->first();
	    $game_status = 0;
	    if($gamestatusdata){
	        $game_status = $gamestatusdata->value;
	    }
	    if($game_status == 1){
	    $last_start_time = Setting::where('category', 'game_start_time')->first()->value;
	    $last_till_time = Setting::where('category', 'game_between_time')->first()->value;
	    $bothdifference = datealgebra($last_start_time, '+', ($last_till_time/1000).' seconds', $format = "Y-m-d h:i:s");
	    if(strtotime(date('Y-m-d h:i:s')) >= strtotime($bothdifference)){
	        $gamestatusdata = Setting::where('category', 'game_status')->update([
	             "value"  => 0
	             ]);
	    }
	    }elseif($game_status == 0){
	         $gamestatusdata = Setting::where('category', 'game_status')->update(["value"  => 1]);
	         $gamestatusdata = Setting::where('category', 'game_start_time')->update(["value"  => date('Y-m-d h:i:s')]);
	         $gamestatusdata = Setting::where('category', 'game_between_time')->update(["value"  => 5000]);
	    }else{}
	}
}























