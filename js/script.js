jQuery( document ).ready( function($) {
	
	var i = 1;
	var sleepersType = $('#sleepers-type').val();
	
	$('#sleepers-type').change( function(){
		
		var locationUrl = $(location).attr('href');
		
		if( locationUrl.indexOf('&spage') != -1 ) {
			locationUrl = locationUrl.split( '&spage' );
			
			if( locationUrl[0].indexOf('&sleepers-type') != -1 )
				locationUrl = locationUrl[0].split( '&sleepers-type' );
		}
			
		else
			locationUrl = locationUrl.split( '&sleepers-type' );

		var sleepersType = $(this).val();
		
		
		
		window.location.href = locationUrl[0] + '&sleepers-type=' + sleepersType;
		return false;
		
	});
	
	$('.nav-tab').each( function() {
		
		if( i > 1 && $('#sleepers-type').val() != 'unactivated') {
			
			var tabUrl = $(this).attr('href').split( '&sleepers-type' );
			$(this).attr('href', tabUrl[0] + '&sleepers-type=' + sleepersType );
		}
		i += 1;
			
	});
	
	$('.wus-save').click( function() {
		$('#bp-wus-template-form').submit();
	});
	
	if( $('.wus-preview').length )
		$('.wus-preview').attr('href', $('.wus-preview').attr('href').replace('TB_iframe', 'sleepertype='+sleepersType+'&TB_iframe' ) );
		
	$('#bp-wus-send-test').click( function() {
		$('.info-emailing').hide();
		var ok = confirm( 'Are you sure ?');
		
		if( ok ) {
			$('.info-emailing').show();
			return true;
		}
		
		else 
			return false;
	});
	
	$('#bp-wus-send-all').click(function() {
		$('.info-emailing').hide();
		var ok = confirm( bp_wus_vars.confirm );
		
		if( ok ) {
			$('.info-emailing').show();
			return true;
		}
		
		else 
			return false;
	});
	
	// now let's take care of unsubscribed users
	$('.delnot-m-active').click( function() {
		if( $(this).hasClass("loading") )
			return false;
			
		$(this).addClass("loading");
		$(this).parent('td').prepend('<span class="spinner"></span>');
		$('.spinner').css('float', 'left');
		$('.spinner').show();
			
		remove_user_or_signup( 0, $(this).attr('data-activationkey'),  $(this).attr('data-mail'), $(this) );
		
		return false;
	});
	
	$('.delunsubscribe').click( function() {
		if( $(this).hasClass("loading") )
			return false;
			
		$(this).addClass("loading");
		$(this).parent('td').prepend('<span class="spinner"></span>');
		$('.spinner').css('float', 'left');
		$('.spinner').show();
			
		remove_unsubscribe( $(this).attr('data-mail'), $(this) );
		
		return false;
	});
	
	$('.delnot-u-active').click( function() {
		if( $(this).hasClass("loading") )
			return false;
			
		$(this).addClass("loading");
		$(this).parent('td').prepend('<span class="spinner"></span>');
		$('.spinner').css('float', 'left');
		$('.spinner').show();
		
		remove_user_or_signup( $(this).attr('data-userid'), 0, $(this).attr('data-mail'), $(this) );
		
		return false;
	});
	
	function remove_unsubscribe( mail, elem ) {
		
		$.post( ajaxurl, {
			action: 'remove_from_unsubscribe_list',
			'user_email': mail,
			'_wpnonce_ununsubscribe': $("input#_wpnonce_ununsubscribe").val(),
		},
		function(response)
		{
			if( response == 1 ) {
				elem.parent().parent().remove();
			}
			else {
				alert(response);
			}
		});
	}
	
	function remove_user_or_signup( userid, activationkey, email, elem ) {
		$.post( ajaxurl, {
			action: 'delete_user_or_signup',
			'user_id': userid,
			'activation': activationkey,
			'user_email': email,
			'_wpnonce_ununsubscribe': $("input#_wpnonce_ununsubscribe").val(),
		},
		function(response)
		{
			if( response == 1 ) {
				elem.parent().parent().remove();
			}
			else {
				alert(response);
			}
		});
	}
});