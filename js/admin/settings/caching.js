$(document).ready(function($) {
	$('#clear_cache').live('click', function(event) {
		$('#cache_message').html('Clearing Cache...');
		$.post(ajaxurl, {action: 'user_empty_cache'}, function(data, textStatus, xhr) {
			if (data && data['result']) {
				$('#cache_message').html(data['message'])
			};
			setTimeout(function () {
				$('#cache_message').html('');
				$('#num_cached_items').html('0');
			}, 1500);
		},'json');
	});



	var cache_datatable = $('#list_of_caches_list').dataTable( {
        "bFilter": false,
        "bProcessing": true,
        // "bServerSide": true,
        "sServerMethod": "POST",
        "sAjaxSource": ajaxurl, 
        "iDisplayLength" : 5,
        "aoColumns" : [
            { sWidth: '75px' },    //name
            { sWidth: '220px' },    //type
            { sWidth: '75px' },     //neighborhood
            { sWidth: '80%' },    //edit
            { sWidth: '60px' }    //remove
        ], 
        "fnServerParams": function ( aoData ) {
            aoData.push( { "name": "action", "value" : "get_cache_items"} );
        }
    });
});
