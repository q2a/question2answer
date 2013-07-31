/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-content/qa-admin.js
	Version: See define()s at top of qa-include/qa-base.php
	Description: Javascript for admin pages to handle Ajax-triggered operations


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

var qa_recalc_running=0;

window.onbeforeunload=function(event)
{
	if (qa_recalc_running>0) {
		event=event||window.event;
		var message=qa_warning_recalc;
		event.returnValue=message;
		return message;
	}
};

function qa_recalc_click(state, elem, value, noteid)
{
	if (elem.qa_recalc_running) {
		elem.qa_recalc_stopped=true;
	
	} else {
		elem.qa_recalc_running=true;
		elem.qa_recalc_stopped=false;
		qa_recalc_running++;
		
		document.getElementById(noteid).innerHTML='';
		elem.qa_original_value=elem.value;
		if (value)
			elem.value=value;
		
		qa_recalc_update(elem, state, noteid);
	}
	
	return false;
}

function qa_recalc_update(elem, state, noteid)
{
	if (state)
		qa_ajax_post('recalc', {state:state, code:(elem.form.elements.code_recalc ? elem.form.elements.code_recalc.value : elem.form.elements.code.value)},
			function(lines) {
				if (lines[0]=='1') {
					if (lines[2])
						document.getElementById(noteid).innerHTML=lines[2];
					
					if (elem.qa_recalc_stopped)
						qa_recalc_cleanup(elem);
					else
						qa_recalc_update(elem, lines[1], noteid);
				
				} else if (lines[0]=='0') {
					document.getElementById(noteid).innerHTML=lines[2];
					qa_recalc_cleanup(elem);
				
				} else {
					qa_ajax_error();
					qa_recalc_cleanup(elem);
				}
			}
		);

	else
		qa_recalc_cleanup(elem);
}

function qa_recalc_cleanup(elem)
{
	elem.value=elem.qa_original_value;
	elem.qa_recalc_running=null;
	qa_recalc_running--;
}

function qa_mailing_start(noteid, pauseid)
{
	qa_ajax_post('mailing', {},
		function (lines) {
			if (lines[0]=='1') {
				document.getElementById(noteid).innerHTML=lines[1];
				window.setTimeout(function() { qa_mailing_start(noteid, pauseid); }, 1); // don't recurse
			
			} else if (lines[0]=='0') {
				document.getElementById(noteid).innerHTML=lines[1];
				document.getElementById(pauseid).style.display='none';
				
			} else {
				qa_ajax_error();
			}
		}
	);
}

function qa_admin_click(target)
{
	var p=target.name.split('_');
	
	var params={entityid:p[1], action:p[2]};
	params.code=target.form.elements.code.value;

	qa_ajax_post('click_admin', params,
		function (lines) {
			if (lines[0]=='1')
				qa_conceal(document.getElementById('p'+p[1]), 'admin');
			else if (lines[0]=='0') {
				alert(lines[1]);
				qa_hide_waiting(target);
			} else
				qa_ajax_error();
		}
	);
	
	qa_show_waiting_after(target, false);
	
	return false;
}

function qa_version_check(uri, versionkey, version, urikey, elem)
{
	var params={uri:uri, versionkey:versionkey, version:version, urikey:urikey};
	
	qa_ajax_post('version', params,
		function (lines) {
			if (lines[0]=='1')
				document.getElementById(elem).innerHTML=lines[1];
		}
	);
}