function changeNames(row_, index_) {
	var re = /(.+\[)([0-9+])(\].+)$/;
	var rt = false;

	var elements = row_.getElementsByTagName('input');
	for (var i = 0, n = elements.length; i < n; i++) {
		if (rt == false) {
			var buf = elements[i].name.replace(re, '$2');
			rt = '$1'+(parseInt(buf)+1)+'$3';
		}

		elements[i].name = elements[i].name.replace(re, rt);
	}

	var elements2 = row_.getElementsByTagName('select');
	for (var i = 0, n = elements2.length; i < n; i++) {
		elements2[i].name = elements2[i].name.replace(re, rt);
	}
}

function switchButton(node_) {
  var buf = node_.getElementsByTagName('tr');

  for (var i = 0; i < buf.length; i++) {
    var buf2 = buf[i].getElementsByTagName('input');
    for (var j = 0; j < buf2.length; j++) {
      if (buf2[j].type != 'button')
        continue;

        if (buf2[j].value == '+')
          buf2[j].style.display = 'none';
        else if (buf2[j].value == '-')
          buf2[j].style.display = '';
    }
  }
}

function add_field(clone_tr_) {
  var table = clone_tr_.parentNode;

  var buf = clone_tr_.cloneNode(true);
  switchButton(table);
  var buf2 = buf.getElementsByTagName('input');
  for(var j = 0; j < buf2.length; j++) {
    if (buf2[j].type == 'text')
      buf2[j].value = '';
  }
  table.appendChild(buf);

  changeNames(buf, table.rows.length-1);
}

function del_field(clone_tr_) {
  clone_tr_.parentNode.removeChild(clone_tr_);
}
