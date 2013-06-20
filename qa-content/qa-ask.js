/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-content/qa-ask.js
	Version: See define()s at top of qa-include/qa-base.php
	Description: Javascript for ask page and question editing, including tag auto-completion


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

function qa_title_change(value)
{
	qa_ajax_post('asktitle', {title:value}, function(lines) {
		if (lines[0]=='1') {
			if (lines[1].length) {
				qa_tags_examples=lines[1];
				qa_tag_hints(true);
			}
			
			if (lines.length>2) {
				var simelem=document.getElementById('similar');
				if (simelem)
					simelem.innerHTML=lines.slice(2).join('\n');
			}
			
		} else if (lines[0]=='0')
			alert(lines[1]);
		else
			qa_ajax_error();
	});
	
	qa_show_waiting_after(document.getElementById('similar'), true);
}

function qa_html_unescape(html)
{
	return html.replace(/&amp;/g, '&').replace(/&quot;/g, '"').replace(/&lt;/g, '<').replace(/&gt;/g, '>');
}

function qa_html_escape(text)
{
	return text.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function qa_tag_click(link)
{
	var elem=document.getElementById('tags');
	var parts=qa_tag_typed_parts(elem);
	
	// removes any HTML tags and ampersand
	var tag=qa_html_unescape(link.innerHTML.replace(/<[^>]*>/g, ''));
	
	var separator=qa_tag_onlycomma ? ', ' : ' ';
	
	// replace if matches typed, otherwise append
	var newvalue=(parts.typed && (tag.toLowerCase().indexOf(parts.typed.toLowerCase())>=0))
		? (parts.before+separator+tag+separator+parts.after+separator) : (elem.value+separator+tag+separator);
	
	// sanitize and set value
	if (qa_tag_onlycomma)
		elem.value=newvalue.replace(/[\s,]*,[\s,]*/g, ', ').replace(/^[\s,]+/g, '');
	else
		elem.value=newvalue.replace(/[\s,]+/g, ' ').replace(/^[\s,]+/g, '');

	elem.focus();
	qa_tag_hints();
		
	return false;
}

function qa_tag_hints(skipcomplete)
{
	var elem=document.getElementById('tags');
	var html='';
	var completed=false;
			
	// first try to auto-complete
	if (qa_tags_complete && !skipcomplete) {
		var parts=qa_tag_typed_parts(elem);
	
		if (parts.typed) {
			html=qa_tags_to_html((qa_html_unescape(qa_tags_examples+','+qa_tags_complete)).split(','), parts.typed.toLowerCase());
			completed=html ? true : false;
		}
	}
	
	// otherwise show examples
	if (qa_tags_examples && !completed)
		html=qa_tags_to_html((qa_html_unescape(qa_tags_examples)).split(','), null);
	
	// set title visiblity and hint list
	document.getElementById('tag_examples_title').style.display=(html && !completed) ? '' : 'none';
	document.getElementById('tag_complete_title').style.display=(html && completed) ? '' : 'none';
	document.getElementById('tag_hints').innerHTML=html;
}

function qa_tags_to_html(tags, matchlc)
{
	var html='';
	var added=0;
	var tagseen={};
	
	for (var i=0; i<tags.length; i++) {
		var tag=tags[i];
		var taglc=tag.toLowerCase();
		
		if (!tagseen[taglc]) {
			tagseen[taglc]=true;
			
			if ( (!matchlc) || (taglc.indexOf(matchlc)>=0) ) { // match if necessary
				if (matchlc) { // if matching, show appropriate part in bold
					var matchstart=taglc.indexOf(matchlc);
					var matchend=matchstart+matchlc.length;
					inner='<span style="font-weight:normal;">'+qa_html_escape(tag.substring(0, matchstart))+'<b>'+
						qa_html_escape(tag.substring(matchstart, matchend))+'</b>'+qa_html_escape(tag.substring(matchend))+'</span>';
				} else // otherwise show as-is
					inner=qa_html_escape(tag);
					
				html+=qa_tag_template.replace(/\^/g, inner.replace('$', '$$$$'))+' '; // replace ^ in template, escape $s
				
				if (++added>=qa_tags_max)
					break;
			}
		}
	}
	
	return html;
}

function qa_caret_from_end(elem)
{
	if (document.selection) { // for IE
		elem.focus();
		var sel=document.selection.createRange();
		sel.moveStart('character', -elem.value.length);
		
		return elem.value.length-sel.text.length;

	} else if (typeof(elem.selectionEnd)!='undefined') // other browsers
		return elem.value.length-elem.selectionEnd;

	else // by default return safest value
		return 0;
}

function qa_tag_typed_parts(elem)
{
	var caret=elem.value.length-qa_caret_from_end(elem);
	var active=elem.value.substring(0, caret);
	var passive=elem.value.substring(active.length);
	
	// if the caret is in the middle of a word, move the end of word from passive to active
	if (
		active.match(qa_tag_onlycomma ? /[^\s,][^,]*$/ : /[^\s,]$/) &&
		(adjoinmatch=passive.match(qa_tag_onlycomma ? /^[^,]*[^\s,][^,]*/ : /^[^\s,]+/))
	) {
		active+=adjoinmatch[0];
		passive=elem.value.substring(active.length);
	}
	
	// find what has been typed so far
	var typedmatch=active.match(qa_tag_onlycomma ? /[^\s,]+[^,]*$/ : /[^\s,]+$/) || [''];
	
	return {before:active.substring(0, active.length-typedmatch[0].length), after:passive, typed:typedmatch[0]};
}

function qa_category_select(idprefix, startpath)
{
	var startval=startpath ? startpath.split("/") : [];
	var setdescnow=true;
	
	for (var l=0; l<=qa_cat_maxdepth; l++) {
		var elem=document.getElementById(idprefix+'_'+l);
		
		if (elem) {
			if (l) {
				if (l<startval.length && startval[l].length) {
					var val=startval[l];
					
					for (var j=0; j<elem.options.length; j++)
						if (elem.options[j].value==val)
							elem.selectedIndex=j;
				} else
					var val=elem.options[elem.selectedIndex].value;
			} else
				val='';
			
			if (elem.qa_last_sel!==val) {
				elem.qa_last_sel=val;
				
				var subelem=document.getElementById(idprefix+'_'+l+'_sub');
				if (subelem)
					subelem.parentNode.removeChild(subelem);
				
				if (val.length || (l==0)) {
					subelem=elem.parentNode.insertBefore(document.createElement('span'), elem.nextSibling);
					subelem.id=idprefix+'_'+l+'_sub';
					qa_show_waiting_after(subelem, true);
					
					qa_ajax_post('category', {categoryid:val},
						(function(elem, l) {
							return function(lines) {
								var subelem=document.getElementById(idprefix+'_'+l+'_sub');
								if (subelem)
									subelem.parentNode.removeChild(subelem);
								
								if (lines[0]=='1') {
									elem.qa_cat_desc=lines[1];
									
									var addedoption=false;
									
									if (lines.length>2) {
										var subelem=elem.parentNode.insertBefore(document.createElement('span'), elem.nextSibling);
										subelem.id=idprefix+'_'+l+'_sub';
										subelem.innerHTML=' ';
										
										var newelem=elem.cloneNode(false);
										
										newelem.name=newelem.id=idprefix+'_'+(l+1);
										newelem.options.length=0;
										
										if (l ? qa_cat_allownosub : qa_cat_allownone)
											newelem.options[0]=new Option(l ? '' : elem.options[0].text, '', true, true);
										
										for (var i=2; i<lines.length; i++) {
											var parts=lines[i].split('/');
											
											if (String(qa_cat_exclude).length && (String(qa_cat_exclude)==parts[0]))
												continue;
												
											newelem.options[newelem.options.length]=new Option(parts.slice(1).join('/'), parts[0]);
											addedoption=true;
										}
										
										if (addedoption) {
											subelem.appendChild(newelem);
											qa_category_select(idprefix, startpath);

										}
										
										if (l==0)
											elem.style.display='none';
									}
									
									if (!addedoption)
										set_category_description(idprefix);
								
								} else if (lines[0]=='0')
									alert(lines[1]);
								else
									qa_ajax_error();
							}
						})(elem, l)
					);
					
					setdescnow=false;
				}
				
				break;
			}
		}
	}
	
	if (setdescnow)
		set_category_description(idprefix);
}

function set_category_description(idprefix)
{
	var n=document.getElementById(idprefix+'_note');
	
	if (n) {
		desc='';
		
		for (var l=1; l<=qa_cat_maxdepth; l++) {
			var elem=document.getElementById(idprefix+'_'+l);
			
			if (elem && elem.options[elem.selectedIndex].value.length)
				desc=elem.qa_cat_desc;
		}
		
		n.innerHTML=desc;
	}
}