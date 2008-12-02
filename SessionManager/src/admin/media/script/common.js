function page_content(url,div) {
	new Ajax.Updater(
		$(div),
		url
	);
}

function field_increase(id, number) {
  var p = parseInt($(id).value);
  p+= number;

  if (! (p>0 & p<1000))
    return false;

  $(id).value = p;
}

function field_check_integer(field) {
  var p = parseInt($(field).value);
  if ((! isNaN(p)) && p>0 && p<1000)
    return true;

  $(field).value = $(field).defaultValue;
}

function markAllRows( container_id ) {
    var rows = document.getElementById(container_id).getElementsByTagName('tr');
    var unique_id;
    var checkbox;

    for ( var i = 0; i < rows.length; i++ ) {

        checkbox = rows[i].getElementsByTagName( 'input' )[0];

        if ( checkbox && checkbox.type == 'checkbox' ) {
            unique_id = checkbox.name + checkbox.value;
            if ( checkbox.disabled == false ) {
                checkbox.checked = true;
            }
        }
    }

    return true;
}

function unMarkAllRows( container_id ) {
    var rows = document.getElementById(container_id).getElementsByTagName('tr');
    var unique_id;
    var checkbox;

    for ( var i = 0; i < rows.length; i++ ) {

        checkbox = rows[i].getElementsByTagName( 'input' )[0];

        if ( checkbox && checkbox.type == 'checkbox' ) {
            unique_id = checkbox.name + checkbox.value;
            checkbox.checked = false;
        }
    }

    return true;
}

function popupOpen2(ulteoForm) {
	var my_width = screen.width;
	var my_height = screen.height;
	var new_width = 0;
	var new_height = 0;
	var pos_top = 0;
	var pos_left = 0;

	new_width = my_width;
	new_height = my_height;

	var rand = Math.round(Math.random()*100);

	var w = window.open('about:blank', 'Ulteo'+rand, 'toolbar=no,status=no,top='+pos_top+',left='+pos_left+',width='+new_width+',height='+new_height+',scrollbars=no,resizable=no,resizeable=no,fullscreen=no');
	$('joinsession').target = 'Ulteo'+rand;

	return false;
}
