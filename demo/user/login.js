// $(".header-bottom").hide();
// $(".main-container").css("margin-top", "60px");

function onChangeCallback(ctr){
    var country = $("#countries").val(ctr);
    if (ctr == 'IN') {
        $("#currency option").removeAttr('selected').filter('[value=2]').attr('selected', true);
        $(".styledSelect").text('USD');
    } else {
        $("#currency option").removeAttr('selected').filter('[value=2]').attr('selected', true);
        $(".styledSelect").text('USD');
    }
}

$(document).ready(function () {
    $("#otp_div").hide();
    $("#otp_error").hide();
    $("#registerError").hide();
    $("#confirm_password-error").hide();
    $("#new_password-error").hide();

    const promocode = $("#referral_code").val();
    if (promocode != '' && promocode != undefined) {
        $('#register-modal').modal('show');
        $("#promocode").val(promocode);
        $("#promo_code").val(promocode);
        
    } 
})

$("#login").on('click', function() {
    $("#username").val('');
    $("#password").val('');
    $("#login-error").hide();
    $("#username-error").hide();
    $("#password-error").hide();

})

function login_ajax(logindata, redirect_url) {
    $.ajax({
        url: '/auth/login',
        data: logindata,
        type: "POST",
        dataType: "json",
        success: function(result) {
            $("#loginSubmit").prop('disabled', false);
            if (result.isSuccess) {
                window.location.href = redirect_url;
            } else {
                $("#login-error").text(result.message).show();
               
            }
        }
    });
}

$('#loginForm').validate({
    rules: {
        username: {
            required: true
        },
        password: {
            required: function (element) {
                return $('#username').val() != '' && $('#password').val() == '';
            }
        }
    },
    messages: {
        username: {
            required: "Field must not be empty!",
        },
        password: {
            required: "Field must not be empty!",
        }
    },
    submitHandler: function(form) {
        $("#loginSubmit").prop('disabled', true);
        // login_ajax($(form).serialize(), "/aviator");
        login_ajax($(form).serialize(), "/dashboard");
    }
});

// Magic Link Functionality
let magicLinkCooldown = 0;
let cooldownInterval;

function sendMagicLink(email) {
    $.ajax({
        url: '/auth/magic-link',
        method: 'POST',
        data: {
            email: email,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        dataType: 'json',
        beforeSend: function() {
            $('#magicLinkBtn').prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-1" role="status"></span>SENDING...'
            );
        },
        success: function(response) {
            if (response.isSuccess) {
                // Show success modal
                $('#magic-link-email').text(email);
                $('#login-modal').modal('hide');
                $('#magic-link-modal').modal('show');
                
                // Start cooldown for resend button
                startResendCooldown(60); // 1 minute cooldown
                
                // Reset login form
                $('#loginForm')[0].reset();
                $("#login-error").hide();
                
                // Show success message
                showNotification('success', response.message);
            } else {
                $("#login-error").text(response.message).show();
                showNotification('error', response.message);
            }
        },
        error: function(xhr) {
            let errorMessage = 'Failed to send magic link. Please try again.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            $("#login-error").text(errorMessage).show();
            showNotification('error', errorMessage);
        },
        complete: function() {
            $('#magicLinkBtn').prop('disabled', false).html(
                '<span class="material-symbols-outlined me-1" style="font-size: 16px;">email</span>SEND MAGIC LINK'
            );
        }
    });
}

function startResendCooldown(seconds) {
    magicLinkCooldown = seconds;
    $('#resendMagicLink').prop('disabled', true);
    $('#resend-cooldown').show();
    
    cooldownInterval = setInterval(function() {
        magicLinkCooldown--;
        $('#cooldown-timer').text(magicLinkCooldown);
        
        if (magicLinkCooldown <= 0) {
            clearInterval(cooldownInterval);
            $('#resendMagicLink').prop('disabled', false);
            $('#resend-cooldown').hide();
        }
    }, 1000);
}

function showNotification(type, message) {
    // You can customize this based on your notification system
    // For now, we'll use a simple alert-like system
    console.log(`${type.toUpperCase()}: ${message}`);
}

// Magic Link Button Click Handler
$(document).on('click', '#magicLinkBtn', function() {
    const email = $('#username').val().trim();
    
    if (!email) {
        $("#login-error").text("Please enter your email address first.").show();
        $('#username').focus();
        return;
    }
    
    // Basic email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        $("#login-error").text("Please enter a valid email address.").show();
        $('#username').focus();
        return;
    }
    
    $("#login-error").hide();
    sendMagicLink(email);
});

// Resend Magic Link Handler
$(document).on('click', '#resendMagicLink', function() {
    if (magicLinkCooldown > 0) {
        return;
    }
    
    const email = $('#magic-link-email').text();
    sendMagicLink(email);
});

// Auto-close magic link modal when user navigates back to login
$('#magic-link-modal').on('hide.bs.modal', function() {
    if (cooldownInterval) {
        clearInterval(cooldownInterval);
    }
});

// Clear errors when modal is shown
$('#login-modal').on('show.bs.modal', function() {
    $("#login-error").hide();
    $('#loginForm')[0].reset();
});

$( "#forgotPassword" ).on('click', function(){
    $(".email_text").text("To recover your password, enter your email or phone number used during registration");
    $(".email_text").css("color","#094b95");
    $("#processSubmit").text('PROCEED');
    $("#user_name_div").show();
    $("#otp_div").hide();
    $("#user_name").val('');
    $("#processSubmit").prop('disabled', false);
    $("#otp_error").hide();
    $("#otp").val('');
    $("#otp").prop('disabled', false);
});

$("#forgotPasswordForm").on('submit', function(e) {
    e.preventDefault(); 
    $("#processSubmit").prop('disabled', true);
    $.ajax({
        url: '/forgot_password_post',
        data: $(this).serialize(),
        type: "POST",
        dataType: "json",
        success: function(result) {
            $("#processSubmit").prop('disabled', false);
            if (result.isSuccess) {
                $(".email_text").text(result.message);
                $(".email_text").css("color","#88c20a");
                $("#user_name").val(result.data.username);
                $("#user_name_div").hide();
                $("#otp_div").show();
                $("#processSubmit").text('SEND CODE AGAIN');
                $("#processSubmit").prop('disabled', true);
                $("#otp_id").val(result.data.id);
                setTimeout(() => {
                    $("#processSubmit").prop('disabled', false);
                }, 10000);
            } else {
            }
        }
    });
})

$("#otp").on('input', function() {
    var otp = $(this).val();
    var otp_id = $("#otp_id").val();
    var username = $("#user_name").val();
    if(otp.length == 4) {
        $(this).prop('disabled', true);
        $.ajax({
            url  : '/verify_otp',
            type : 'post',
            data :  {
                'otp' : otp,
                'otp_id' : otp_id,
                'username' : username,
            },
            success : function(result) {
                $("#new_password").val('');
                $("#confirm_password").val('');
                $("#confirm_password-error").hide();
                $("#new_password-error").hide();
                if(result.isSuccess) {
                    $('#forgot-modal').modal('hide');
                    $('#reset-password-modal').modal('show');
                    $("#reset_username").val(result.data.username);
                    $("#otp_error").hide();
                } else {
                    $("#otp").prop('disabled', false);
                    $("#otp_error").text(result.message);
                    $("#otp_error").show();
                }
            }
        })
    }
})

$('#resetPasswordForm').validate({ 
    rules : {
        password : {
            minlength : 6,
        },
        confirm_password : {
            equalTo: "#new_password"
        }
    },
    messages : {
        password : {
            minlength : "Minimum password length is 6 characters",
        },
        confirm_password : {
            equalTo: "Passwords don't match"
        }
    },
    submitHandler: function(form) {
        $("#saveSubmit").prop('disabled', true);
        $.ajax({
            url: '/reset_password',
            data : $(form).serialize(),
            type: "POST",
            success: function(result) {
                if (result.isSuccess) {
                    $("#saveSubmit").prop('disabled', false);
                    $('#reset-password-modal').modal('hide');
                    let data = {
                        username : result.data.username,
                        password : result.data.password,
                    }
                    // login_ajax(data, '/aviator')
                    login_ajax(data, '/dashboard')
                } else {
    
                }
            }
        });
    }
});

$('#registerViaEmailForm').validate({
    rules: {
        email: {
            required: true
        },
        regpassword: {
            required: function (element) {
                return $('#email').val() != '' && $('#regpassword').val() == '';
            }
        }
    },
    messages: {
        email: {
            required: "Field must not be empty!",
        },
        regpassword: {
            required: "Field must not be empty!",
        }
    },
    submitHandler: function(form) {
        $(".registerSubmit").prop('disabled', true);
        $.ajax({
            url: $(form).attr('action'),
            data: $(form).serialize(),
            type: "POST",
            dataType: "json",
            success: function(result) {
                console.log(result);
                $("#email").val('');
                $("#regpassword").val('');
                $(".registerSubmit").prop('disabled', false);
                if(result.isSuccess) {
                    $('#register-modal').modal('hide');
                    const data = {
                        _token: result.data.token,
                        username : result.data.username,
                        password : result.data.password,
                    }
                    login_ajax(data,'/dashboard')
                } else if (result.data.is_email_exist == 1) {
                    $('#forgot-modal').modal('show');
                    $("#user_name").val(result.data.email);
                } else {
                    $("#promo_code_error").show();
                    $("#promo_code_error").text(result.message);
                }
            }
        });
    }
});
$('#amounttransfer').validate({
    rules: {
        userid: {
            required: true
        },
        amount: {
            required: true
        }
    },
    messages: {
        userid: {
            required: "Enter user id!",
        },
        amount: {
            required: "Enter Amount!",
        }
    },
    submitHandler: function(form) {
        $(".registerSubmit").prop('disabled', true);
        $.ajax({
            url: $(form).attr('action'),
            data: $(form).serialize(),
            type: "POST",
            dataType: "json",
            success: function(result) {
                $("#userid").val('');
                $("#amount").val('');
                $(".registerSubmit").prop('disabled', false);
                if(result.isSuccess) {
                    window.location.href='/';
                } else {
                    $("#promo_code_error").show();
                    $("#promo_code_error").html(result.message);
                }
            }
        });
    }
});

$('#registerOneClickForm').validate({
    submitHandler: function(form) {
        $("#registerSubmit").prop('disabled', true);
        $.ajax({
            url: $(form).attr('action'),
            data: $(form).serialize(),
            type: "POST",
            dataType: "json",
            success: function(result) {
                console.log(result);
                if(result.isSuccess) {
                    const data = {
                        username : result.data.user_name,
                        password : result.data.password,
                    }
                    login_ajax(data,`/deposit?username=${result.data.user_name}&password=${result.data.password}`)
                } else {    
                    $("#promocode_error").text(result.message);
                    $("#promocode_error").show();

                }
            }
        });
    }
});

$(".reg_btn").on('click', function() {
    $("#promocode").val('');
    $("#reg_email").val('');
    $("#regpassword").val('');
    $("#promo_code").val('');
    $("#promo_code_error").hide();
    $("#promocode_error").hide();
})

$("#one_click_check").click(function() {
    if(!$(this).is(":checked")) {
        $("#one_click_register").prop('disabled', true)
        $("#one_click_register").css({
            'background-image' : 'linear-gradient(0deg,#9fa8b3,#becad7)',
            'box-shadow'       : 'none',
            'color'            : '#d4d9df',
        })
    } else {
        $("#one_click_register").prop('disabled', false)
        $("#one_click_register").css({
            'background-image' : 'linear-gradient(0deg,#fa5e00 0,#fa7c00)',
            'box-shadow'       : '0 20px 30px rgb(250 65 0 / 40%)',
            'color'            : '#fff',
        })
    }  
}); 

$("#email_policy").click(function() {
    if(!$(this).is(":checked")) {
        $("#register_via_email").prop('disabled', true)
        $("#register_via_email").css({
            'background-image' : 'linear-gradient(0deg,#9fa8b3,#becad7)',
            'box-shadow'       : 'none',
            'color'            : '#d4d9df',
        })
    } else {
        $("#register_via_email").prop('disabled', false)
        $("#register_via_email").css({
            'background-image' : 'linear-gradient(0deg,#fa5e00 0,#fa7c00)',
            'box-shadow'       : '0 20px 30px rgb(250 65 0 / 40%)',
            'color'            : '#fff',
        })
    }  
}); 

/*-------HINAL (START)-------*/

$("#view_password").on('click', function() {
    let type = $("#password").prop('type')
    if (type == 'password') {
        $(this).text('visibility');
        $("#password").prop('type', 'text');
    } else {
        $(this).text('visibility_off');
        $("#password").prop('type', 'password');
    }
})

$("#view_password_register").on('click', function() {
    let type = $("#regpassword").prop('type')
    if (type == 'password') {
        $(this).text('visibility');
        $("#regpassword").prop('type', 'text');
    } else {
        $(this).text('visibility_off');
        $("#regpassword").prop('type', 'password');
    }
})

/*-------HINAL (END)-------*/