/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-content/qa-user.js
	Version: See define()s at top of qa-include/qa-base.php
	Description: Javascript to handle user page actions


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

function qa_submit_wall_post(elem, morelink)
{
	var params={};
	
	params.message=document.forms.wallpost.message.value;
	params.handle=document.forms.wallpost.handle.value;
	params.start=document.forms.wallpost.start.value;
	params.code=document.forms.wallpost.code.value;
	params.morelink=morelink ? 1 : 0;
	
	qa_ajax_post('wallpost', params,
		function(lines) {
			
			if (lines[0]=='1') {
				var l=document.getElementById('wallmessages');
				l.innerHTML=lines.slice(2).join("\n");
				
				var c=document.getElementById(lines[1]); // id of new message
				if (c) {
					c.style.display='none';
					qa_reveal(c, 'wallpost');
				}

				document.forms.wallpost.message.value='';
				qa_hide_waiting(elem);
				
			} else if (lines[0]=='0') {
				document.forms.wallpost.qa_click.value=elem.name;
				document.forms.wallpost.submit();
				
			} else {
				qa_ajax_error();
			}
		}
	);
	
	qa_show_waiting_after(elem, false);
	
	return false;
}


function qa_wall_post_click(messageid, target)
{
	var params={};
	
	params.messageid=messageid;
	params.handle=document.forms.wallpost.handle.value;
	params.code=document.forms.wallpost.code.value;

	params[target.name]=target.value;

	qa_ajax_post('click_wall', params,
		function (lines) {
			if (lines[0]=='1') {
				var l=document.getElementById('m'+messageid);
				var h=lines.slice(1).join("\n");
				
				if (h.length)
					qa_set_outer_html(l, 'wallpost', h)
				else
					qa_conceal(l, 'wallpost');
			
			} else {
				document.forms.wallpost.qa_click.value=target.name;
				document.forms.wallpost.submit();
			}
		}
	);
	
	qa_show_waiting_after(target, false);
	
	return false;
}