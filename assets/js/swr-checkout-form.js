jQuery(document).ready(function ($) {

    $.validator.addMethod("phoneno", function (phone_number, element) {
        phone_number = phone_number.replace(/\s+/g, "");
        return this.optional(element) || phone_number.length > 9 &&
                phone_number.match(/^((\+[1-9]{1,4}[ \-]*)|(\([0-9]{2,3}\)[ \-]*)|([0-9]{2,4})[ \-]*)*?[0-9]{3,4}?[ \-]*[0-9]{3,4}?$/);
    }, "<br />Please specify a valid Phone Number");

    $.validator.addMethod("numeric", function (value, element) {
        return this.optional(element) || /^\d*[0-9](|.\d*[0-9]|,\d*[0-9])?$/.test(value);
    }, "Enter only Numeric Value");

    $.validator.addMethod("alpha", function (value, element) {
        return this.optional(element) || /^[a-zA-Z ]*$/.test(value);
    }, "Enter only Albhabet Value");

    $.validator.addMethod("email", function (value, element) {
        return this.optional(element) || /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(value);
    }, 'Please enter a valid email address.');

        $("#submit_guest").validate({
            rules: {
                cust_first_name: 'required',
                cust_last_name: 'required',
                cust_email: {
                    required: true,
                    email: true,
                    remote : {
                        url: ajaxurl,
                        type: "post",
                        data: {
                           email_add : function() {
                                return jQuery( "#cust_email" ).val();
                            },
                           'action': 'swr_check_email_adress'
                        }
                    },
                },
                cust_pincode: {
                    required: true,
                    numeric: true
                },
                cust_address: 'required',
                cust_city: {
                    required: true,
                    alpha: true
                },
                cust_state: {
                    required: true,
                    alpha: true
                },
                country_code: 'required',
                mobile: {
                    required: true,
                    phoneno: true
                }
            },
            messages: {
                cust_first_name: 'Please Enter First Name',
                cust_last_name: 'Please Enter Last Name',
                cust_email: {
                    required: 'Please Enter Email',
                    email: 'Please Enter valid Email',
                    remote : "Your email address alredy exist in our database , please login with your email and password",
                },
                cust_pincode: {
                    required: 'Please Enter Pincode',
                    numeric: 'Please Enter valid Pincode',
                },
                cust_address: 'Please Enter Address',
                cust_city: {
                    required: 'Please Enter City',
                    alpha: 'Please Enter valid City',
                },
                cust_state: {
                    required: 'Please Enter State',
                    alpha: 'Please Enter valid State',
                },
                country_code: 'Please Select Country',
                mobile: {
                    required: 'Please Enter Mobile No',
                    phoneno: 'Please Enter valid Mobile No',
                }
            }
        });
    
    
        $("#submit_payment").validate({
            rules: {
                cust_first_name: 'required',
                cust_last_name: 'required',
                cust_email: {
                    required: true,
                    email: true,
                },
                cust_pincode: {
                    required: true,
                    numeric: true
                },
                cust_address: 'required',
                cust_city: {
                    required: true,
                    alpha: true
                },
                cust_state: {
                    required: true,
                    alpha: true
                },
                country_code: 'required',
                mobile: {
                    required: true,
                    phoneno: true
                }
            },
            messages: {
                cust_first_name: 'Please Enter First Name',
                cust_last_name: 'Please Enter Last Name',
                cust_email: {
                    required: 'Please Enter Email',
                    email: 'Please Enter valid Email',
                },
                cust_pincode: {
                    required: 'Please Enter Pincode',
                    numeric: 'Please Enter valid Pincode',
                },
                cust_address: 'Please Enter Address',
                cust_city: {
                    required: 'Please Enter City',
                    alpha: 'Please Enter valid City',
                },
                cust_state: {
                    required: 'Please Enter State',
                    alpha: 'Please Enter valid State',
                },
                country_code: 'Please Select Country',
                mobile: {
                    required: 'Please Enter Mobile No',
                    phoneno: 'Please Enter valid Mobile No',
                }
            }
        });
    
    
    
        $("#loginform").validate({
            rules: {
                log: {
                    required: true,
                    email: true,
                },
                pwd: 'required',
            },
            messages: {
                log: {
                    required: 'Please Enter Email',
                    email: 'Please Enter valid Email',
                },
                pwd: 'Please Enter Password',
            }
        });
        

    jQuery("#sa_submit_address").click(function () {
        $("#add_new_add_popup").validate({
            rules: {
                sa_cust_first_name: 'required',
                sa_cust_last_name: 'required',
                sa_cust_email: {
                    required: true,
                    email: true
                },
                sa_cust_pincode: {
                    required: true,
                    numeric: true
                },
                sa_cust_address: 'required',
                sa_cust_city: {
                    required: true,
                    alpha: true
                },
                sa_cust_state: {
                    required: true,
                    alpha: true
                },
                country_code: 'required',
                sa_mobile: {
                    required: true,
                    phoneno: true
                }
            },
            messages: {
                sa_cust_first_name: 'Please Enter First Name',
                sa_cust_last_name: 'Please Enter Last Name',
                sa_cust_email: {
                    required: 'Please Enter Email',
                    email: 'Please Enter valid Email',
                },
                sa_cust_pincode: {
                    required: 'Please Enter Pincode',
                    numeric: 'Please Enter valid Pincode',
                },
                sa_cust_address: 'Please Enter Address',
                sa_cust_city: {
                    required: 'Please Enter City',
                    alpha: 'Please Enter valid City',
                },
                sa_cust_state: {
                    required: 'Please Enter State',
                    alpha: 'Please Enter valid State',
                },
                country_code: 'Please Select Country',
                sa_mobile: {
                    required: 'Please Enter Mobile No',
                    phoneno: 'Please Enter valid Mobile No',
                }
            }
        });
        
        if (!$('#add_new_add_popup').valid()) {
            return false;
        }
        else{
            var userid = jQuery("#sa_current_uid").val();
            var address_type = jQuery("input[type='radio'][name='sa_address_type']:checked").val();
            var first_name = jQuery("#sa_cust_first_name").val();
            var last_name = jQuery("#sa_cust_last_name").val();
            var email = jQuery("#sa_cust_email").val();
            var pincode = jQuery("#sa_cust_pincode").val();
            var address = jQuery("#sa_cust_address").val();
            var landmark = jQuery("#sa_cust_landmark").val();
            var city = jQuery("#sa_cust_city").val();
            var state = jQuery("#sa_cust_state").val();
            var country = jQuery("#country_code").val();
            var mobile = jQuery("#sa_mobile").val();

            //var link = '<?php echo admin_url("admin-ajax.php"); ?>';

                jQuery.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        'action': 'swr_insert_customer_shipping_address',
                        'cust_user_id': userid,
                        'cust_address_type': address_type,
                        'cust_first_name': first_name,
                        'cust_last_name': last_name,
                        'cust_email': email,
                        'cust_pincode': pincode,
                        'cust_address': address,
                        'cust_landmark': landmark,
                        'cust_city': city,
                        'cust_state': state,
                        'cust_country': country,
                        'cust_mobile': mobile
                    },
                    beforeSend: function () {
                        jQuery('#loading-image').show();
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        console.error(errorThrown);
                        jQuery('#loading-image').hide();
                    },
                    success: function (response) {
                        jQuery('#loading-image').hide();
                        if (response) {
                            var address_html = '<li id="address_' + response + '" class="single-product-item">';
                            address_html += '<strong><p class="bl1">' + first_name + ' ' + last_name + '<a href="#" id=" ' + response + '" class="delete">Delete</a></p></strong>';
                            address_html += '<p>' + address + ' ' + landmark + ' <br>' + pincode + ', ' + city + '<br>' + state + '</p>';
                            address_html += '<p>' + mobile + '</p>';
                            address_html += '<input type="radio" name="address_choice" class="hidden_radio" id="radio_' + response + '" value="' + response + '" checked></li>';
                            jQuery('.product-grid-list').append(address_html);
                            jQuery('#add_new_addr_btn , .js-modal-close').hide();
                        }

                    }
                });
        }
    });


jQuery(document).on("click", '.delete', function (event) {
        var del_id = jQuery(this).attr("id");
        if (confirm("Are you sure you want to delete this address?")) {
            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'swr_delete_ajax_request_response',
                    add_id: del_id,
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error("error occured");
                },
                success: function (data) {
                    jQuery('#address_' + del_id).remove();
                    window.location.reload(true);
                }
            });
        }
        return false;
    });
        
        $(document).on("click", '.exitsting_add', function(event) {
			$(".exitsting_add").removeClass('checked');
                        $(".exitsting_add").removeClass('active');
			$(this).addClass('active');
			$(this).find(".hidden_radio").prop('checked', true); 
        });


$("#personal_information").validate({
            rules: {
                profile_first_name: 'required',
                profile_last_name: 'required',
                profile_email: {
                    required: true,
                    email: true
                },
                old_password: {
                    remote : {
                        url: ajaxurl,
                        type: "post",
                        data: {
                           email_add : function() {
                                return jQuery( "#old_password" ).val();
                            },
                           'action': 'swr_check_old_password'
                        }
                    },
                },
                confirm_new_password: {
                    equalTo: "#new_password"
                }
            },
            messages: {
                profile_first_name: 'Please Enter First Name',
                profile_last_name: 'Please Enter Last Name',
                profile_email: {
                    required: 'Please Enter Email',
                    email: 'Please Enter valid Email',
                },
                old_password: {
                    remote : "Your password are not match with our database, please enter correct password",
                },
                confirm_new_password: {
                    equalTo : "New password do not match",
                },
            }
        });
    
});