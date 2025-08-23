@extends('Layout.usergame')

@section('css')
<style>
.crypto-address-display {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 20px;
    margin-top: 15px;
}

.address-box {
    display: flex;
    gap: 10px;
    margin-top: 8px;
}

.address-box input {
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    font-family: monospace;
    font-size: 12px;
}

.copy-btn {
    white-space: nowrap;
    min-width: 60px;
}

.qr-code-container {
    background: white;
    padding: 10px;
    border-radius: 8px;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 150px;
}

.crypto-instructions .alert {
    background: rgba(52, 144, 220, 0.1);
    border: 1px solid rgba(52, 144, 220, 0.3);
    color: #ffffff;
}

.crypto-instructions .alert-success {
    background: rgba(76, 175, 80, 0.1);
    border: 1px solid rgba(76, 175, 80, 0.3);
    color: #ffffff;
}

.crypto-instructions .alert-warning {
    background: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.3);
    color: #ffffff;
}

.crypto-icons-container img {
    transition: transform 0.2s ease;
}

.crypto-icons-container img:hover {
    transform: scale(1.1);
}

.crypto-currency-icon {
    display: inline-flex;
    align-items: center;
    vertical-align: middle;
}

.crypto-currency-icon img {
    vertical-align: middle;
}
</style>
@endsection

@section('content')
    <div class="deposite-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="pay-tabs">
                        <a href="#" class="custom-tabs-link active">DEPOSIT</a>
                        <a href="/withdraw" class="custom-tabs-link">WITHDRAW</a>
                    </div>

                    <input type="hidden" name="username" id="username" value="">
                    <input type="hidden" name="password" id="password" value="">

                    <div class="pay-options">
                        <div class="payment-cols">
                            <div class="grid-view">
                                <div class="grid-list" onclick="paymentGatewayDetails('6')">
                                    <button class="btn payment-btn" data-tab="netbanking">
                                        <img src="images/app-logo/interkassa_net_banking.svg" />
                                        <div class="PaymentCard_limit">Min {{setting('min_recharge')}}</div>
                                    </button>
                                </div>
                                <div class="grid-list" onclick="paymentGatewayDetails('3')">
                                    <button class="btn payment-btn" data-tab="upi">
                                        <img src="images/app-logo/upiMt.svg" />
                                        <div class="PaymentCard_limit">Min {{setting('min_recharge')}}</div>
                                    </button>
                                </div>
                                <div class="grid-list" onclick="selectCryptoPayment()">
                                    <button class="btn payment-btn" data-tab="crypto">
                                        <div class="crypto-icons-container" style="display: flex; align-items: center; justify-content: center; gap: 5px; height: 40px;">
                                            <img src="images/solana-sol-icon.svg" style="height: 24px;" alt="Solana" />
                                            <img src="images/usdt-svgrepo-com.svg" style="height: 24px;" alt="USDT" />
                                        </div>
                                        <div class="PaymentCard_limit">Crypto (SOL/USDT)</div>
                                    </button>
                                </div>
                            </div>
                            <div class="deposite-box" id="netbanking">
                                <div class="d-box">
                                    <div class="limit-txt">LIMITS:<span>{{setting('min_recharge')}}</span></div>
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <div class="login-controls mt-3 rounded-pill h42">
                                                <label for="Username" class="rounded-pill">
                                                    <input type="text" class="form-control text-i10 amount"
                                                        id="net_bank_amount"
                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..*?)\..*/g, '$1').replace(/^0[^.]/, '0');">
                                                    <input type="hidden" id="net_bank_min_amount" value="{{setting('min_recharge')}}">
                                                    <input type="hidden" id="net_bank_max_amount" value="">
                                                    <i class="Input_currency">
                                                        USD
                                                    </i>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <button
                                                class="register-btn rounded-pill d-flex align-items-center w-100 mt-3 orange-shadow"
                                                onclick="deposit('6')">
                                                DEPOSIT
                                            </button>
                                        </div>
                                    </div>
                                    <div class="amount-tooltips">
                                        <button class="btn amount-tooltips-btn">500</button>
                                        <button class="btn amount-tooltips-btn active">1000</button>
                                        <button class="btn amount-tooltips-btn">2500</button>
                                        <button class="btn amount-tooltips-btn">5000</button>
                                    </div>
                                    <label for="net_bank_amount" class="error" id="net_bank_amount-error"></label>
                                </div>
                            </div>
                            <div class="deposite-box" id="Phonepay">
                                <div class="d-box">
                                    <div class="limit-txt">LIMITS:<span>{{setting('min_recharge')}} - </span></div>
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <div class="login-controls mt-3 rounded-pill h42">
                                                <label for="Username" class="rounded-pill">
                                                    <input type="text" class="form-control text-i10 amount"
                                                        id="phonepe_amount"
                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..*?)\..*/g, '$1').replace(/^0[^.]/, '0');">
                                                    <input type="hidden" id="phonepe_min_amount" value="{{setting('min_recharge')}}">
                                                    <input type="hidden" id="phonepe_max_amount" value="">
                                                    <i class="Input_currency">
                                                        USD
                                                    </i>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <button
                                                class="register-btn rounded-pill d-flex align-items-center w-100 mt-3 orange-shadow"
                                                onclick="deposit('2')">
                                                DEPOSIT
                                            </button>
                                        </div>
                                    </div>
                                    <div class="amount-tooltips">
                                        <button class="btn amount-tooltips-btn">500</button>
                                        <button class="btn amount-tooltips-btn active">1000</button>
                                        <button class="btn amount-tooltips-btn">5000</button>
                                        <button class="btn amount-tooltips-btn">10000</button>
                                    </div>
                                    <label for="phonepe_amount" class="error" id="phonepe_amount-error"></label>
                                </div>
                            </div>
                            <div class="deposite-box" id="upi">
                                <div class="d-box">
                                    <div class="limit-txt">LIMITS:<span>{{setting('min_recharge')}}</span></div>
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <div class="login-controls mt-3 rounded-pill h42">
                                                <label for="Username" class="rounded-pill">
                                                    <input type="text" class="form-control text-i10 amount"
                                                        id="upi_amount"
                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..*?)\..*/g, '$1').replace(/^0[^.]/, '0');">
                                                    <input type="hidden" id="upi_min_amount" value="{{setting('min_recharge')}}">
                                                    <input type="hidden" id="upi_max_amount" value="">
                                                    <i class="Input_currency">
                                                        USD
                                                    </i>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <button
                                                class="register-btn rounded-pill d-flex align-items-center w-100 mt-3 orange-shadow"
                                                onclick="deposit('3')">
                                                DEPOSIT
                                            </button>
                                        </div>
                                    </div>
                                    <div class="amount-tooltips">
                                        <button class="btn amount-tooltips-btn">500</button>
                                        <button class="btn amount-tooltips-btn active">1000</button>
                                        <button class="btn amount-tooltips-btn">2500</button>
                                        <button class="btn amount-tooltips-btn">5000</button>
                                    </div>
                                    <label for="upi_amount" class="error" id="upi_amount-error"></label>
                                    <div class="deposite-blc">
                                        <div>BALANCE AFTER DEPOSITING</div>
                                        <div class="dopsite-vlue">$ <span id="upi_amount_txt"></span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>
                    <div class="pay-static-form text-white fw-bold">
                        <div class="form-back d-flex align-items-center">
                            <span class="material-symbols-outlined bold-icon me-1">
                                arrow_back
                            </span>
                            BACK
                        </div>
                        <div class="white-box mt-3 text-center">
                            <img src="images/barcode.png" class="barcode-img" id="barcode"/>
                            <a href="#" class="d-block link-text">How to make deposit?</a>
                            <p class="text-dark">To confirm the deposit, make a transfer to the banking details:</p>
                            <div id="account_number_tag">
                                <div class="d-flex justify-content-between flex-wrap text-dark align-items-center">
                                    <span class="text-muted" id="account_number_title">ACCOUNT NUMBER : </span>
                                    <span class="d-flex align-items-center copy_owner_details" id="copy_acc_no">
                                        <span class="material-symbols-outlined bold-icon text-muted">
                                            content_copy
                                        </span>
                                        <span id="owner_account_number"></span>
                                        <input type="hidden" id="acc_no_hide">
                                    </span>
                                </div>
                            </div>
                            <div id="mobile_number_tag">
                                <div class="d-flex justify-content-between flex-wrap text-dark align-items-center my-2 ">
                                    <span class="text-muted" id="mobile_number_title"></span>
                                    <span class="d-flex align-items-center copy_owner_details" id="copy_mobile_no">
                                        <span class="material-symbols-outlined bold-icon text-muted">
                                            content_copy
                                        </span>
                                        <span id="owner_mobile_no"></span>
                                        <input type="hidden" id="mobile_no_hide">
                                    </span>
                                </div>
                            </div>
                            <div id="name_tag">
                                <div class="d-flex justify-content-between flex-wrap text-dark align-items-center">
                                    <span class="text-muted" id="account_name_title"></span>
                                    <span class="d-flex align-items-center copy_owner_details" id="copy_name">
                                        <span class="material-symbols-outlined bold-icon text-muted">
                                            content_copy
                                        </span>
                                        <span id="owner_name"></span>
                                        <input type="hidden" id="name_hide">
                                    </span>
                                </div>
                            </div>
                            <div id="bank_name_tag">
                                <div class="d-flex justify-content-between flex-wrap text-dark align-items-center my-2">
                                    <span class="text-muted" id="bank_title">BANK NAME:</span>
                                    <span class="d-flex align-items-center copy_owner_details" id="copy_bank_name">
                                        <span class="material-symbols-outlined bold-icon text-muted">
                                            content_copy
                                        </span>
                                        <span id="owner_bank_name"></span>
                                        <input type="hidden" id="bank_name_hide">
                                    </span>
                                </div>
                            </div>
                            <div class="deposite-box" id="crypto">
                                <div class="d-box">
                                    <div class="limit-txt">LIMITS:<span>Min ${{setting('min_recharge')}} USD</span></div>
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <div class="login-controls mt-3 rounded-pill h42">
                                                <label for="crypto_amount" class="rounded-pill">
                                                    <input type="text" class="form-control text-i10 amount"
                                                        id="crypto_amount"
                                                        placeholder="Enter amount in USD"
                                                        oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1').replace(/^0[^.]/, '0');">
                                                    <input type="hidden" id="crypto_min_amount" value="{{setting('min_recharge')}}">
                                                    <input type="hidden" id="crypto_max_amount" value="50000">
                                                    <i class="Input_currency">USD</i>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="login-controls mt-3 rounded-pill h42">
                                                <label for="crypto_currency" class="rounded-pill">
                                                    <select class="form-control text-i10" id="crypto_currency" onchange="updateCryptoIcon()">
                                                        <option value="SOL">Solana (SOL)</option>
                                                        <option value="USDT">Tether (USDT-SPL)</option>
                                                    </select>
                                                    <i class="Input_currency" id="crypto_currency_icon">
                                                        <img src="images/solana-sol-icon.svg" style="height: 16px;" alt="SOL" />
                                                    </i>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <button onclick="generateCryptoAddress()" 
                                                    class="register-btn rounded-pill d-flex align-items-center justify-content-center w-100 mt-3 orange-shadow">
                                                <span class="material-symbols-outlined me-2">qr_code</span>
                                                GENERATE DEPOSIT ADDRESS
                                            </button>
                                        </div>
                                        <div class="col-12" id="crypto-address-section" style="display: none;">
                                            <div class="crypto-address-display">
                                                <div class="row">
                                                    <div class="col-md-8">
                                                        <div class="address-info">
                                                            <label class="text-white small">Your Deposit Address:</label>
                                                            <div class="address-box">
                                                                <input type="text" id="crypto-deposit-address" 
                                                                       class="form-control text-center" readonly>
                                                                <button class="btn btn-sm btn-outline-light copy-btn" 
                                                                        onclick="copyAddress()">
                                                                    Copy
                                                                </button>
                                                            </div>
                                                            <div class="crypto-info mt-2">
                                                                <small class="text-muted">
                                                                    Network: <span class="text-success">
                                                                        <img src="images/solana-sol-icon.svg" style="height: 12px; margin-right: 3px;" alt="Solana" />
                                                                        Solana
                                                                    </span> |
                                                                    Expected: <span id="crypto-expected-amount">0.00</span> <span id="crypto-expected-currency">SOL</span>
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="qr-code-section">
                                                            <div id="crypto-qr-code" class="qr-code-container"></div>
                                                            <small class="text-muted d-block text-center mt-1">
                                                                <span id="qr-currency-indicator">
                                                                    <img src="images/solana-sol-icon.svg" style="height: 12px; margin-right: 3px;" alt="SOL" />
                                                                    Scan to pay
                                                                </span>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="crypto-instructions mt-3">
                                                    <div class="alert alert-success">
                                                        <small>
                                                            <strong>✨ Automatic Processing:</strong><br>
                                                            1. Send exactly the expected amount to the address above<br>
                                                            2. Use the <strong>Solana network</strong> only<br>
                                                            3. <strong>No deposit button needed</strong> - transactions are monitored automatically<br>
                                                            4. Your balance will update within 1-2 minutes after blockchain confirmation
                                                        </small>
                                                    </div>
                                                    <div class="alert alert-warning mt-2">
                                                        <small>
                                                            <strong>⚠️ Important:</strong> Do NOT click any deposit buttons after sending crypto. The system automatically detects your payment.
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="white-box mt-3">
                            <form action="/depositNow" method="post" id="deposit_form">
                                @csrf
                                <input type="hidden" name="amount" id="deposit_amount" value="300">
                                <input type="hidden" name="payment_gateway_type" id="payment_gateway_type">
                                <input type="hidden" name="min_deposit_amount" id="min_deposit_amount">
                                <input type="hidden" name="max_deposit_amount" id="max_deposit_amount">

                                <div class="mb-3 row" id="mobile_div">
                                    <label for="staticEmail" class="col-sm-4 col-5 col-form-label text-muted fw-bold"
                                        id="mobile_title"></label>
                                    <div class="col-sm-8 col-7">
                                        <div class="login-controls">
                                            <label for="mobile_no">
                                                <input type="text" class="form-control text-indent-0" id="mobile_no"
                                                    name="mobile_no">
                                            </label>
                                        </div>
                                    </div>
                                    <label id="mobile_no-error" class="error" for="mobile_no"></label>
                                </div>
                                <div class="mb-3 row" id="name_div">
                                    <label for="staticEmail" class="col-sm-4 col-5 col-form-label text-muted fw-bold"
                                        id="name_title"></label>
                                    <div class="col-sm-8 col-7">
                                        <div class="login-controls">
                                            <label for="name">
                                                <input type="text" class="form-control text-indent-0" id="name"
                                                    name="name">
                                            </label>
                                        </div>
                                    </div>
                                    <label id="name-error" class="error" for="name"></label>
                                </div>
                                <div class="mb-3 row" id="email_div">
                                    <label for="staticEmail" class="col-sm-4 col-5 col-form-label text-muted fw-bold"
                                        id="email_title"></label>
                                    <div class="col-sm-8 col-7">
                                        <div class="login-controls">
                                            <label for="email">
                                                <input type="email" class="form-control text-indent-0" id="email"
                                                    name="email">
                                            </label>
                                        </div>
                                    </div>
                                    <label id="email-error" class="error" for="email"></label>
                                </div>

                                <div class="mb-3 row" id="cwallet_div">
                                    <label for="staticEmail" class="col-sm-4 col-5 col-form-label text-muted fw-bold"
                                        id="cwallet_title"></label>
                                    <div class="col-sm-8 col-7">
                                        <div class="login-controls">
                                            <label for="crypto_wallet_address">
                                                <input type="text" class="form-control text-indent-0"
                                                    id="crypto_wallet_address" name="crypto_wallet_address">
                                            </label>
                                        </div>
                                    </div>
                                    <label id="crypto_wallet_address-error" class="error"
                                        for="crypto_wallet_address"></label>
                                </div>
                                <div class="mb-3 row" id="ctxt_div">
                                    <label for="staticEmail" class="col-sm-4 col-5 col-form-label text-muted fw-bold"
                                        id="ctxt_title"></label>
                                    <div class="col-sm-8 col-7">
                                        <div class="login-controls">
                                            <label for="crypto_transaction_id">
                                                <input type="text" class="form-control text-indent-0"
                                                    id="crypto_transaction_id" name="crypto_transaction_id">
                                            </label>
                                        </div>
                                    </div>
                                    <label id="crypto_transaction_id-error" class="error"
                                        for="crypto_transaction_id"></label>
                                </div>
                                <div class="mb-3 row" id="account_no_div">
                                    <label for="staticEmail" class="col-sm-4 col-5 col-form-label text-muted fw-bold"
                                        id="account_no_title">Account Number</label>
                                    <div class="col-sm-8 col-7">
                                        <div class="login-controls">
                                            <label for="account_no_id">
                                                <input type="text" class="form-control text-indent-0" id="account_no"
                                                    name="account_no">
                                            </label>
                                        </div>
                                    </div>
                                    <label id="account_no-error" class="error" for="account_no"></label>
                                </div>
                                <div class="mb-3 row" id="account_holder_name_div">
                                    <label for="staticEmail" class="col-sm-4 col-5 col-form-label text-muted fw-bold"
                                        id="account_holder_name_title">Account Holder Name</label>
                                    <div class="col-sm-8 col-7">
                                        <div class="login-controls">
                                            <label for="account_holder_name_id">
                                                <input type="text" class="form-control text-indent-0"
                                                    id="account_holder_name" name="account_holder_name">
                                            </label>
                                        </div>
                                    </div>
                                    <label id="account_holder_name-error" class="error"
                                        for="account_holder_name"></label>
                                </div>
                                <div class="mb-3 row" id="ifsc_code_div">
                                    <label for="staticEmail" class="col-sm-4 col-5 col-form-label text-muted fw-bold"
                                        id="ifsc_code_title">IFSC Code</label>
                                    <div class="col-sm-8 col-7">
                                        <div class="login-controls">
                                            <label for="ifsc_code_id">
                                                <input type="text" class="form-control text-indent-0" id="ifsc_code"
                                                    name="ifsc_code">
                                            </label>
                                        </div>
                                    </div>
                                    <label id="ifsc_code-error" class="error" for="ifsc_code"></label>
                                </div>
                                <div class="mb-3 row" id="bank_name_div">
                                    <label for="staticEmail" class="col-sm-4 col-5 col-form-label text-muted fw-bold"
                                        id="bank_name_title">Bank Name</label>
                                    <div class="col-sm-8 col-7">
                                        <div class="login-controls">
                                            <label for="bank_name_id">
                                                <input type="text" class="form-control text-indent-0" id="bank_name "
                                                    name="bank_name">
                                            </label>
                                        </div>
                                    </div>
                                    <label id="bank_name -error" class="error" for="bank_name"></label>
                                </div>
                                <div class="mb-3 row" id="upi_div">
                                    <label for="staticEmail" class="col-sm-4 col-5 col-form-label text-muted fw-bold"
                                        id="upi_title"></label>
                                    <div class="col-sm-8 col-7">
                                        <div class="login-controls">
                                            <label for="upi_id">
                                                <input type="text" class="form-control text-indent-0" id="upi_id"
                                                    name="upi_id">
                                            </label>
                                        </div>
                                    </div>
                                    <label id="upi_id-error" class="error" for="upi_id"></label>
                                </div>
                                <button
                                    class="register-btn rounded-pill d-flex align-items-center w-100 mt-3 orange-shadow">
                                    DEPOSIT
                                </button>
                            </form>

                        </div>
                        <!-- <div class="blues-box mt-3 text-center mb-4">
                        <iframe src='https://player.vimeo.com/video/740300187?h=7da6a3e555' height="300" width="440" frameborder='0' webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
                    </div> -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Include Crypto Modals --}}
    @include('include.crypto-modals')
@endsection
@section('js')
    <script src="{{ url('user/deposit.js') }}"></script>
    
    <!-- QR Code Library -->
    <script src="{{ asset('js/qrcode-simple.js') }}"></script>
    
    <!-- Crypto Deposit System -->
    <script src="{{ asset('user/crypto-deposit.js') }}"></script>
    
    <script>
        // Function to handle crypto payment selection
        function selectCryptoPayment() {
            console.log('🎯 Crypto payment selected');
            try {
                // Hide payment selection panel
                $('.payment-cols').hide();
                console.log('✅ Payment selection panel hidden');

                // Show static deposit wrapper
                $('.pay-static-form').show();
                console.log('✅ Static deposit wrapper shown');

                // Hide manual deposit form (if present)
                $('#deposit_form').hide();
                console.log('✅ Manual deposit form hidden');

                // Show crypto deposit box
                $('#crypto').show();
                console.log('✅ Crypto box shown:', $('#crypto').is(':visible'));

                // Scroll to crypto deposit section
                $('html, body').animate({ scrollTop: $('#crypto').offset().top - 100 }, 500);
                console.log('✅ Scrolled to crypto deposit section');

                // Update active button
                $('.payment-btn').removeClass('active');
                $('.payment-btn[data-tab="crypto"]').addClass('active');
                console.log('✅ Button states updated');

                // Inform user
                if (typeof toastr !== 'undefined') {
                    toastr.success('Crypto payment method selected!');
                }
            } catch (error) {
                console.error('❌ Error in selectCryptoPayment:', error);
                alert('Error selecting crypto payment: ' + error.message);
            }
        }
        
        // Test function to verify crypto functionality
        window.testCrypto = function() {
            console.log('🧪 Testing crypto payment system...');
            selectCryptoPayment();
        };
        
        // Debug function to check system state
        window.debugCrypto = function() {
            console.log('🔍 Crypto System Debug:');
            console.log('- Crypto box exists:', $('#crypto').length > 0);
            console.log('- Crypto box visible:', $('#crypto').is(':visible'));
            console.log('- Generate button exists:', $('button[onclick="generateCryptoAddress()"]').length > 0);
            console.log('- Deposit function exists:', typeof deposit === 'function');
            console.log('- Min amount:', $('#crypto_min_amount').val());
            console.log('- Max amount:', $('#crypto_max_amount').val());
        };
        
        // Function to update crypto currency icon
        function updateCryptoIcon() {
            const currency = $('#crypto_currency').val();
            const iconContainer = $('#crypto_currency_icon');
            
            if (currency === 'SOL') {
                iconContainer.html('<img src="images/solana-sol-icon.svg" style="height: 16px;" alt="SOL" />');
            } else if (currency === 'USDT') {
                iconContainer.html('<img src="images/usdt-svgrepo-com.svg" style="height: 16px;" alt="USDT" />');
            }
        }
        
        $(document).ready(function() {
            console.log('💡 Crypto debugging functions available:');
            console.log('  - testCrypto() - Test crypto payment selection');
            console.log('  - debugCrypto() - Check system state');
            
            // Initialize crypto icon
            updateCryptoIcon();
        });
    </script>
    
    @isset($_GET['msg'])
    @if ($_GET['msg'] == 'Success')
        <script>
            toastr.success("Request send successfully!")
        </script>
    @endif
    @endisset
@endsection
