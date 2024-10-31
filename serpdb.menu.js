jQuery(document).ready(function(){

//bind the button events in the searches tab
	terms_initbinding();

// functions for screen : box 

//the spinner 
	jQuery('#loadingDiv')
            .hide()  // hide it initially
            .ajaxStart(function() {
                jQuery(this).show();
            })
            .ajaxStop(function() {
                jQuery(this).hide();
            })
        ;
                	  
//tab function
	jQuery("a.tab").click(function () {
		
		// switch all tabs off
		jQuery(".active").removeClass("active");
		
		// switch this tab on
		jQuery(this).addClass("active");
		
		// slide all elements with the class 'content' up
		jQuery(".content").slideUp();
		
		// Now figure out what the 'title' attribute value is and find the element with that id.  Then slide that down.
		var content_show = jQuery(this).attr("title");
		jQuery("#"+content_show).slideDown();
	  
	});
	
	//open screen at tab 1 : results                
        jQuery("#tab_1").click();
       
        


// functions for screen : admin : bind click events for update_options 
        jQuery('#serpdb_domain_update').bind('click', function() {
	    
		var site_location = jQuery("#serpdb_myhost").val();
		var serpdb_domain = jQuery("#serpdb_domain").val();
		var ajaxurl = site_location + "/wp-admin/admin-ajax.php";
		
		jQuery.post(
			ajaxurl,
			{
			    action: "serpdb_save_settings",
			    'cookie': encodeURIComponent(document.cookie),
			    subject: "serpdb_domain",
			    domain: serpdb_domain
			},
			function(res)
			{
			     var message_result = eval('(' + res + ')');
			     jQuery("#sys_message").html(message_result.message);
			}
		    );
	        return false;
        });

        jQuery('#update_serpdb_searchdepth').bind('click', function() {
	    
	    var site_location = jQuery("#serpdb_myhost").val();
	    var serpdb_searchdepth = jQuery("#serpdb_searchdepth").val();
            var ajaxurl = site_location + "/wp-admin/admin-ajax.php";
	    
            jQuery.post(
                    ajaxurl,
                    {
                        action: "serpdb_save_settings",
                        'cookie': encodeURIComponent(document.cookie),
                        subject: "serpdb_searchdepth",
                        searchdepth: serpdb_searchdepth
                    },
                    function(res)
                    {
                         var message_result = eval('(' + res + ')');
			 jQuery("#sys_message").html(message_result.message);
                    }
                );
	    return false;
	});
	
       jQuery('#update_serpdb_period').bind('click', function() {
	    
	    var site_location = jQuery("#serpdb_myhost").val();
	    var serpdb_period = jQuery("#serpdb_period").val();
            var ajaxurl = site_location + "/wp-admin/admin-ajax.php";
	    
            jQuery.post(
                    ajaxurl,
                    {
                        action: "serpdb_save_settings",
                        'cookie': encodeURIComponent(document.cookie),
                        subject: "serpdb_period",
                        period: serpdb_period
                    },
                    function(res)
                    {
                         var message_result = eval('(' + res + ')');
			 jQuery("#sys_message").html(message_result.message);
                    }
                );
	    return false;
	});
       
        jQuery('#update_serpdb_permutations').bind('click', function() {
	    
	    var site_location = jQuery("#serpdb_myhost").val();
	    var serpdb_permutations = jQuery("#serpdb_permutations").val();
            var ajaxurl = site_location + "/wp-admin/admin-ajax.php";
	    
            jQuery.post(
                    ajaxurl,
                    {
                        action: "serpdb_save_settings",
                        'cookie': encodeURIComponent(document.cookie),
                        subject: "serpdb_permutations",
                        permutations: serpdb_permutations
                    },
                    function(res)
                    {
                         var message_result = eval('(' + res + ')');
			 jQuery("#sys_message").html(message_result.message);
                    }
                );
	    return false;
	});

        jQuery('#update_serpdb_keepall').bind('click', function() {
	    
	    var site_location = jQuery("#serpdb_myhost").val();
	    var serpdb_keepall = jQuery("#serpdb_keepall").val();
            var ajaxurl = site_location + "/wp-admin/admin-ajax.php";
	    
            jQuery.post(
                    ajaxurl,
                    {
                        action: "serpdb_save_settings",
                        'cookie': encodeURIComponent(document.cookie),
                        subject: "serpdb_keepall",
                        keepall: serpdb_keepall
                    },
                    function(res)
                    {
                         var message_result = eval('(' + res + ')');
			 jQuery("#sys_message").html(message_result.message);
                    }
                );
	    return false;
	});
 
 
 
         jQuery('#update_serpdb_engine_language').bind('click', function() {
	    
	    var site_location = jQuery("#serpdb_myhost").val();
	    var serpdb_engine_language = jQuery("#serpdb_engine_language").val();
            var ajaxurl = site_location + "/wp-admin/admin-ajax.php";
	    
            jQuery.post(
                    ajaxurl,
                    {
                        action: "serpdb_save_settings",
                        'cookie': encodeURIComponent(document.cookie),
                        subject: "serpdb_engine_language",
                        engine_language: serpdb_engine_language
                    },
                    function(res)
                    {
                         var message_result = eval('(' + res + ')');
			 jQuery("#sys_message").html(message_result.message);
                    }
                );
	    return false;
	});	 
	 
	 
// functions for screen : edit searches

	//clear fields
	jQuery('#clear_serpdb_term').bind('click', function() {
	    jQuery("#serpdb_term_freq").val('');
	    jQuery("#serpdb_term_depth").val('');
	    jQuery("#serpdb_term_term").val('');
	    jQuery("#serpdb_term_char").val('');
	    jQuery("#serpdb_term_engine_language").val('');
	    jQuery("#serpdb_term_nextdate").val('');
	    jQuery("#serpdb_term_recordid").html('');
	    return false;	
	});

	//use standard values for term fields
        jQuery('#standard_serpdb_term').bind('click', function() {
	    var site_location = jQuery("#serpdb_myhost").val();
	    var ajaxurl = site_location + "/wp-admin/admin-ajax.php";
	
	    jQuery.post(
                    ajaxurl,
                    {
                        action: "serpdb_save_settings",
                        'cookie': encodeURIComponent(document.cookie),
                        subject: "standard_serpdb_term"
                    },
                    function(res)
                    {
                        var message_result = eval('(' + res + ')');
			jQuery("#sys_message").html(message_result.message);
			jQuery("#serpdb_term_freq").val(message_result.period);
			jQuery("#serpdb_term_depth").val(message_result.depth);
			jQuery("#serpdb_term_char").val(message_result.characteristic);
			jQuery("#serpdb_term_engine_language").val(message_result.engine_language);
                    }
                );
	    return false;	
	});


	//update the term on backend and in forms
	jQuery('#update_serpdb_term').bind('click', function() {
    
	    var serpdb_term_recordid = jQuery("#serpdb_term_recordid").html();
    
	    var site_location = jQuery("#serpdb_myhost").val();
	    var ajaxurl = site_location + "/wp-admin/admin-ajax.php";
    
	    var serpdb_term_freq = jQuery("#serpdb_term_freq").val();
	    var serpdb_term_char = jQuery("#serpdb_term_char").val();
	    var serpdb_term_nextdate = jQuery("#serpdb_term_nextdate").val();
	    var serpdb_term_depth = jQuery("#serpdb_term_depth").val();
	    var serpdb_term_engine_language = jQuery("#serpdb_term_engine_language").val();
	    var serpdb_term_term = jQuery("#serpdb_term_term").val();
    
	    jQuery.post(
		     ajaxurl,
		     {
			 action: "serpdb_save_settings",
			 'cookie': encodeURIComponent(document.cookie),
			 subject: "update_serpdb_term",
			 term_freq: serpdb_term_freq,
			 term_char: serpdb_term_char, 
			 term_term: serpdb_term_term,
			 term_depth: serpdb_term_depth,
			 term_engine_language: serpdb_term_engine_language,
			 term_nextdate: serpdb_term_nextdate,
			 term_recordid: serpdb_term_recordid
		     },
		     function(res)
		     {
			 var message_result = eval('(' + res + ')');
			 jQuery("#sys_message").html(message_result.message);
			 jQuery("#serpdb_term_freq").val('');
			 jQuery("#serpdb_term_depth").val('');
			 jQuery("#serpdb_term_term").val('');
			 jQuery("#serpdb_term_char").val('');
			 jQuery("#serpdb_term_engine_language").val('');
			 jQuery("#serpdb_term_nextdate").val('');
			 jQuery("#serpdb_term_recordid").html('');
			 refreshterms();
			 refreshresults();
		     }
		 );
	    return false;	
	});
    
});


//refresh & rebind

function terms_initbinding()
{
//bind events to button-images in screen : searches

    jQuery("img.cfg").click(function (e) {

	    var tid = jQuery(this).attr("id");
	    var xid = trim(tid.substring(3, 10));

	    jQuery("#serpdb_term_freq").val(jQuery("#tfreq"+xid).html());
	    jQuery("#serpdb_term_depth").val(jQuery("#tdepth"+xid).html());
	    jQuery("#serpdb_term_term").val(jQuery("#tterm"+xid).html());
	    jQuery("#serpdb_term_char").val(jQuery("#tchar"+xid).html());
	    jQuery("#serpdb_term_nextdate").val(jQuery("#tnextdate"+xid).html());
	    jQuery("#serpdb_term_engine_language").val(jQuery("#tengine_language"+xid).html());
	    jQuery("#serpdb_term_recordid").html(xid);
		    
	    jQuery("#tab_4").click();
	    jQuery("#sys_message").html('edit ' + xid + ' ' + jQuery("#tterm"+xid).html());
    });


   
    jQuery("img.run").click(function (e) {
	    var site_location = jQuery("#serpdb_myhost").val();
	    var ajaxurl = site_location + "/wp-admin/admin-ajax.php";

	    var tid = jQuery(this).attr("id");
	    var xid = trim(tid.substring(3, 10));
            jQuery.post(
                    ajaxurl,
                    {
                        action: "serpdb_save_settings",
                        'cookie': encodeURIComponent(document.cookie),
                        subject: "run_serpdb_term",
                        term_recordid: xid
		    },
                    function(res)
                    {
                         var message_result = eval('(' + res + ')');
			 jQuery("#tnextdate"+xid).html(message_result.nextdate);
			 jQuery("#sys_message").html(message_result.message);
			 refreshresults();
			 }
		);
	    return false;
    });
	


    jQuery("img.del").click(function (e) {
	    var site_location = jQuery("#serpdb_myhost").val();
	    var ajaxurl = site_location + "/wp-admin/admin-ajax.php";

	    var tid = jQuery(this).attr("id");
	    var xid = trim(tid.substring(3, 10));
	    var term = jQuery("#tterm"+xid).html()
	
            jQuery.post(
                    ajaxurl,
                    {
                        action: "serpdb_save_settings",
                        'cookie': encodeURIComponent(document.cookie),
                        subject: "delete_serpdb_term",
                        term_recordid: xid,
			term_term: term
                    },
                    function(res)
                    {
                        var message_result = eval('(' + res + ')');
			jQuery("#sys_message").html(message_result.message);
			 refreshterms();
			 refreshresults();
		    }
                );
            return false;
    });                	     
}

function refreshterms() {
//updates terms screen by calling TermsTable
    var site_location = jQuery("#serpdb_myhost").val();
    var ajaxurl = site_location + "/wp-admin/admin-ajax.php";
    jQuery.post(
	    ajaxurl,
	    {
		action: "serpdb_save_settings",
		'cookie': encodeURIComponent(document.cookie),
		subject: "refresh_serpdb_term"
	    },
	    function(res)
	    {
		var message_result = eval('(' + res + ')');
		jQuery('#serpdb_Searches').html(message_result.thehtml);
		//rebinds the jquery functionality
		terms_initbinding();   
	    }
    );
}	

function refreshresults() {
//updates sesults screen by calling ResultsTable 

    var site_location = jQuery("#serpdb_myhost").val();
    var ajaxurl = site_location + "/wp-admin/admin-ajax.php";
    jQuery.post(
	    ajaxurl,
	    {
		action: "serpdb_save_settings",
		'cookie': encodeURIComponent(document.cookie),
		subject: "refresh_serpdb_result"
	    },
	    function(res)
	    {
		var message_result = eval('(' + res + ')');
		jQuery("#sys_message").html(message_result.message);
		jQuery('#serpdb_Results').html(message_result.thehtml);
		//rebinds the jquery functionality
		terms_initbinding();   
	    }
    );
}



function trim(value) {
  value = value.replace(/^\s+/,'');
  value = value.replace(/\s+$/,'');
  return value;
}