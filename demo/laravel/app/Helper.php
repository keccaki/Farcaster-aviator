<?php

use App\Models\Gameresult;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Userbit;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

function imageupload($file, $name, $path)
{
    $file_name = "";
    $file_type = "";
    $filePath = "";
    $size = "";

    if ($file) {
        $file_name = $file->getClientOriginalName();
        $file_type = $file->getClientOriginalExtension();
        $fileName = $name . "." . $file_type;
        Storage::disk('public')->put($path . $fileName, File::get($file));
        $filePath = "/" . 'storage/' . $path . $fileName;
    }
    return $file = [
        'fileName' => $file_name,
        'fileType' => $file_type,
        'filePath' => $filePath,
    ];
}
function datealgebra($date, $operator, $value, $format = "Y-m-d")
{
    if ($operator == "-") {
        $date = date_create($date);
        date_sub($date, date_interval_create_from_date_string($value));
        return date_format($date, $format);
    } elseif ($operator == "+") {
        $date = date_create($date);
        date_add($date, date_interval_create_from_date_string($value));
        return date_format($date, $format);
    }
}
function user($parameter,$id=null)
{
    if ($id == null) {
        return session()->get('userlogin')[$parameter];
    }else{
        $data = User::where('id', $id)->first();
        return $data->{$parameter};
    }
    // return session()->get('userlogin')[$parameter];
}
function userdetail($id, $parameter)
{
    $data = User::where('id', $id)->first();
    //return $data->{$parameter};
}
function admin($parameter)
{
    return session()->get('adminlogin')[$parameter];
}
function wallet($userid, $type = "string")
{
    $amount = Wallet::where('userid', $userid)->first();
    if ($amount->amount > 0) {
        if ($type == "num") {
            return $amount->amount;
        } else {
            return number_format($amount->amount);
        }
    } else {
        return 0;
    }
}
function setting($parameter, $default = '0')
{
    $setting = Setting::where('category', $parameter)->first();
    return $setting ? $setting->value : $default;
}

function currentid()
{
    $data = Gameresult::orderBy('id', 'desc')->first();
    if ($data) {
        return $data->id;
    } else {
        return 0;
    }
}
function dformat($date, $format)
{
    $strd = date_create($date);
    // if (date($format) == date_format($strd, $format)) {
    //     return "Today";
    // }
    return date_format($strd, $format);
}
function resultbyid($id)
{
    $data = Gameresult::where('id', $id)->first();
    if ($data && $data->result != 'pending' && $data->result != '') {
        return $data->result;
    }
    return 0;
}
function userbetdetail($id,$parameter)
{
    $data = Userbit::where('id', $id)->first();
    if ($data) {
        return $data->{$parameter};
    }
    return 0;
}
function addwallet($id, $amount, $symbol = "+")
{
    $wallet = wallet::where('userid', $id)->first();
    if ($wallet) {
        if ($symbol == "+") {

            wallet::where('userid', $id)->update([
                "amount" => wallet($id, 'num') + $amount,
            ]);
            return wallet($id, 'num') + $amount;
        } elseif ($symbol == "-") {
            wallet::where('userid', $id)->update([
                "amount" => wallet($id, "num") - $amount,
            ]);
            return wallet($id, "num") - $amount;
        }
        return wallet($id);
    }
}
function appvalidate($input)
{
    if ($input == '' || $input == null || $input == 0) {
        return 'Not found!';
    } else {
        return $input;
    }
}
function lastrecharge($id, $parameter)
{
    $data = Transaction::where('userid', $id)->where('type', 'credit')->where('category', 'recharge')->orderBy('id', 'desc')->first();
    if ($data) {
        return $data->{$parameter};
    }
    return false;
}
function status($code, $type)
{
    if ($type == 'recharge') {
        if ($code == 0) {
            return array('color' => 'warning', 'name' => 'Pending');
        }
        if ($code == 1) {
            return array('color' => 'success', 'name' => 'Approved');
        }
        if ($code == 2) {
            return array('color' => 'danger', 'name' => 'Cancel');
        }
    } elseif ($type == "user") {
        if ($code == 0) {
            return array('color' => 'danger', 'name' => 'Inactive');
        }
        if ($code == 1) {
            return array('color' => 'success', 'name' => 'Active');
        }
        if ($code == 2) {
            return array('color' => 'warning', 'name' => 'Pending');
        }
    }
}
// function bankdetail($userid,$parameter){
//     Bank_detail::where('userid',);
// }
function platform($id)
{
    if ($id == 2) {
        return 'phonepay';
    } elseif ($id == 3) {
        return 'upi';
    } elseif ($id == 1) {
        return 'gpay';
    } elseif ($id == 9) {
        return 'imps';
    } elseif ($id == 6) {
        return 'netbanking';
    } else {
        return 'other';
    }
}

function addtransaction($userid, $platform, $transactionno, $type, $amount, $category, $remark, $status)
{
    $trn = new Transaction;
    $trn->userid = $userid;
    $trn->platform = $platform;
    $trn->transactionno = $transactionno;
    $trn->type = $type;
    $trn->amount = $amount;
    $trn->category = $category;
    $trn->remark = $remark;
    $trn->status = $status;
    if ($trn->save()) {
        return true;
    }
    return false;
}

/**
 * ============================================
 * CRYPTO PAYMENT HELPER FUNCTIONS
 * ============================================
 * These functions extend the existing payment system
 * to support crypto transactions while maintaining
 * backward compatibility with the game logic.
 */

/**
 * Get crypto wallet for user
 * @param int $userid
 * @return array|null
 */
function cryptowallet($userid)
{
    $wallet = \App\Models\CryptoWallet::where('user_id', $userid)
        ->where('status', 'active')
        ->first();
    
    if (!$wallet) {
        return null;
    }
    
    return [
        'address' => $wallet->wallet_address,
        'created_at' => $wallet->created_at,
        'status' => $wallet->status
    ];
}

/**
 * Get crypto balance for user
 * @param int $userid
 * @param string $currency (SOL, USDT, or 'all')
 * @param string $type (string or num)
 * @return mixed
 */
function cryptobalance($userid, $currency = 'all', $type = 'string')
{
    $wallet = \App\Models\CryptoWallet::where('user_id', $userid)
        ->where('status', 'active')
        ->first();
    
    if (!$wallet) {
        return $currency === 'all' ? ['sol' => 0, 'usdt' => 0, 'total_usd' => 0] : 0;
    }
    
    // Get balance from wallet service
    $service = app(\App\Services\SolanaWalletService::class);
    $balance = $service->getWalletBalance($wallet->wallet_address);
    
    if ($currency === 'all') {
        return [
            'sol' => $type === 'num' ? $balance['sol_balance'] : number_format($balance['sol_balance'], 9),
            'usdt' => $type === 'num' ? $balance['usdt_balance'] : number_format($balance['usdt_balance'], 6),
            'total_usd' => $type === 'num' ? $balance['total_usd_value'] : number_format($balance['total_usd_value'], 2)
        ];
    }
    
    $amount = $currency === 'SOL' ? $balance['sol_balance'] : $balance['usdt_balance'];
    
    if ($type === 'num') {
        return $amount;
    } else {
        $decimals = $currency === 'SOL' ? 9 : 6;
        return number_format($amount, $decimals);
    }
}

/**
 * Add crypto transaction and update game wallet
 * @param int $userid
 * @param string $currency
 * @param float $amount
 * @param string $type (deposit or withdrawal)
 * @param string $txHash
 * @param string $status
 * @return bool
 */
function addcryptotransaction($userid, $currency, $amount, $type, $txHash, $status = 'confirmed')
{
    try {
        $wallet = \App\Models\CryptoWallet::where('user_id', $userid)
            ->where('status', 'active')
            ->first();
        
        if (!$wallet) {
            return false;
        }
        
        // Get current exchange rate
        $exchangeRate = \App\Models\CryptoExchangeRate::getRate("{$currency}/USD");
        if (!$exchangeRate) {
            $exchangeRate = $currency === 'SOL' ? 100.0 : 1.0; // Fallback rates
        }
        
        $amountUsd = $amount * $exchangeRate;
        
        // Create crypto transaction
        $cryptoTx = \App\Models\CryptoTransaction::create([
            'user_id' => $userid,
            'transaction_hash' => $txHash,
            'wallet_address' => $wallet->wallet_address,
            'transaction_type' => $type,
            'currency' => $currency,
            'amount' => $amount,
            'amount_usd' => $amountUsd,
            'status' => $status,
            'confirmations' => 32,
            'processed_at' => now()
        ]);
        
        // Update game wallet balance
        if ($status === 'confirmed') {
            $gameAmount = $type === 'deposit' ? $amountUsd : -$amountUsd;
            addwallet($userid, $gameAmount);
            
            // Create traditional transaction for compatibility
            addtransaction(
                $userid,
                'crypto',
                $txHash,
                $type === 'deposit' ? 'credit' : 'debit',
                $amountUsd,
                $type === 'deposit' ? 'crypto_deposit' : 'crypto_withdrawal',
                "Crypto {$type} - {$amount} {$currency}",
                '1'
            );
        }
        
        return true;
        
    } catch (Exception $e) {
        \Log::error('Crypto transaction failed', [
            'user_id' => $userid,
            'currency' => $currency,
            'amount' => $amount,
            'type' => $type,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * Check if crypto payments are enabled
 * @return bool
 */
function cryptoenabled()
{
    return setting('crypto_enabled') === 'true';
}

/**
 * Get crypto transaction history for user
 * @param int $userid
 * @param string $type (deposit, withdrawal, or 'all')
 * @param int $limit
 * @return array
 */
function cryptotransactions($userid, $type = 'all', $limit = 10)
{
    $query = \App\Models\CryptoTransaction::where('user_id', $userid)
        ->orderBy('created_at', 'desc')
        ->limit($limit);
    
    if ($type !== 'all') {
        $query->where('transaction_type', $type);
    }
    
    return $query->get()->toArray();
}

/**
 * Get crypto deposit address for user
 * @param int $userid
 * @return string|null
 */
function cryptodepositaddress($userid)
{
    $wallet = cryptowallet($userid);
    return $wallet ? $wallet['address'] : null;
}

/**
 * Check if user has pending crypto withdrawals
 * @param int $userid
 * @return bool
 */
function haspendingcryptowithdrawals($userid)
{
    return \App\Models\CryptoTransaction::where('user_id', $userid)
        ->where('transaction_type', 'withdrawal')
        ->where('status', 'pending')
        ->exists();
}

/**
 * Get crypto withdrawal limits for user
 * @param int $userid
 * @param string $currency
 * @return array
 */
function cryptowithdrawallimits($userid, $currency)
{
    $dailyLimit = $currency === 'SOL' ? 
        (float) setting('max_withdrawal_sol') : 
        (float) setting('max_withdrawal_usdt');
    
    $todayWithdrawals = \App\Models\CryptoTransaction::where('user_id', $userid)
        ->where('transaction_type', 'withdrawal')
        ->where('currency', $currency)
        ->where('status', 'confirmed')
        ->whereDate('created_at', today())
        ->sum('amount');
    
    return [
        'daily_limit' => $dailyLimit,
        'used_today' => $todayWithdrawals,
        'remaining' => max(0, $dailyLimit - $todayWithdrawals)
    ];
}

/**
 * Format crypto amount with proper decimals
 * @param float $amount
 * @param string $currency
 * @return string
 */
function formatcrypto($amount, $currency)
{
    $decimals = $currency === 'SOL' ? 9 : 6;
    return number_format($amount, $decimals) . ' ' . $currency;
}

/**
 * Convert crypto amount to USD
 * @param float $amount
 * @param string $currency
 * @return float
 */
function cryptotousd($amount, $currency)
{
    return \App\Models\CryptoExchangeRate::convert($amount, $currency, 'USD');
}

/**
 * Convert USD to crypto amount
 * @param float $usdAmount
 * @param string $currency
 * @return float
 */
function usdtocrypto($usdAmount, $currency)
{
    return \App\Models\CryptoExchangeRate::convert($usdAmount, 'USD', $currency);
}

/**
 * Get crypto security score for user
 * @param int $userid
 * @return int
 */
function cryptosecurityscore($userid)
{
    $recentLogs = \App\Models\CryptoSecurityLog::where('user_id', $userid)
        ->where('created_at', '>=', now()->subDays(7))
        ->get();
    
    if ($recentLogs->isEmpty()) {
        return 0;
    }
    
    $avgRisk = $recentLogs->avg('risk_score');
    return (int) $avgRisk;
}

/**
 * Enhanced wallet function that includes crypto balances
 * This maintains backward compatibility while adding crypto support
 * @param int $userid
 * @param string $type
 * @return mixed
 */
function walletenhanced($userid, $type = "string")
{
    // Get traditional wallet balance
    $traditionalBalance = wallet($userid, 'num');
    
    // Get crypto balances if enabled
    if (cryptoenabled()) {
        $cryptoBalances = cryptobalance($userid, 'all', 'num');
        $totalCryptoUsd = $cryptoBalances['total_usd'];
        
        // Total balance includes traditional + crypto
        $totalBalance = $traditionalBalance + $totalCryptoUsd;
        
        if ($type === "num") {
            return $totalBalance;
        } else {
            return number_format($totalBalance, 2);
        }
    }
    
    // Fallback to traditional wallet
    return wallet($userid, $type);
}
