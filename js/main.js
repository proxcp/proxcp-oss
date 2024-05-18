$(document).ready(function() {
	// Custom tab script
    $('.tab-action').click(function() {
        var tabData = $(this).data('tab-cnt');
        // Content
        $('.tab-cnt').removeClass('active');
        $('#' + tabData).css('display','none').toggleClass('active');
        // Actions
        $('.tab-action').removeClass('active');
        $(this).toggleClass('active');
    });

    // Username focus
  	if($('#username').val() == '') {
  		$('#username').focus();
  	}else{
  		$('#password').focus();
  	}

    // Activate all tooltips on page for disabled buttons
    $('.tooltip-wrapper').tooltip();

    // Quick server selection
    $('#manquick').change(function() {
        $('#manquickgo').attr('href', $(this).val());
    });

    // Quick server selection - firewall
    $('#fwquick').change(function() {
        $('#fwquickgo').attr('href', $(this).val());
    });

    $('.template_setup_btn').click(function(e) {
      $(this).addClass("disabled");
      $(this).html('Please wait...');
    });
});
