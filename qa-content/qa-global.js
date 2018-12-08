/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Common Javascript for Q2A pages including posting and AJAX

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

// General page functions

function qa_reveal(elem, type, callback)
{
	if (elem)
		$(elem).slideDown(400, callback);
}

function qa_conceal(elem, type, callback)
{
	if (elem)
		$(elem).slideUp(400);
}

function qa_set_inner_html(elem, type, html)
{
	if (elem)
		elem.innerHTML = html;
}

function qa_set_outer_html(elem, type, html)
{
	if (elem) {
		var e = document.createElement('div');
		e.innerHTML = html;
		elem.parentNode.replaceChild(e.firstChild, elem);
	}
}

function qa_show_waiting_after(elem, inside)
{
	if (elem && !elem.qa_waiting_shown) {
		var w = document.getElementById('qa-waiting-template');

		if (w) {
			var c = w.cloneNode(true);
			c.id = null;

			if (inside)
				elem.insertBefore(c, null);
			else
				elem.parentNode.insertBefore(c, elem.nextSibling);

			elem.qa_waiting_shown = c;
		}
	}
}

function qa_hide_waiting(elem)
{
	var c = elem.qa_waiting_shown;

	if (c) {
		c.parentNode.removeChild(c);
		elem.qa_waiting_shown = null;
	}
}

function qa_vote_click(elem)
{
	var ens = elem.name.split('_');
	var postid = ens[1];
	var vote = parseInt(ens[2]);
	var code = elem.form.elements.code.value;
	var anchor = ens[3];

	qa_ajax_post('vote', {postid: postid, vote: vote, code: code},
		function(lines) {
			if (lines[0] == '1') {
				qa_set_inner_html(document.getElementById('voting_' + postid), 'voting', lines.slice(1).join("\n"));

			} else if (lines[0] == '0') {
				var mess = document.getElementById('errorbox');

				if (!mess) {
					mess = document.createElement('div');
					mess.id = 'errorbox';
					mess.className = 'qa-error';
					mess.innerHTML = lines[1];
					mess.style.display = 'none';
				}

				var postelem = document.getElementById(anchor);
				var e = postelem.parentNode.insertBefore(mess, postelem);
				qa_reveal(e);

			} else
				qa_ajax_error();
		}
	);

	return false;
}

function qa_notice_click(elem)
{
	var ens = elem.name.split('_');
	var code = elem.form.elements.code.value;

	qa_ajax_post('notice', {noticeid: ens[1], code: code},
		function(lines) {
			if (lines[0] == '1')
				qa_conceal(document.getElementById('notice_' + ens[1]), 'notice');
			else if (lines[0] == '0')
				alert(lines[1]);
			else
				qa_ajax_error();
		}
	);

	return false;
}

function qa_favorite_click(elem)
{
	var ens = elem.name.split('_');
	var code = elem.form.elements.code.value;

	qa_ajax_post('favorite', {entitytype: ens[1], entityid: ens[2], favorite: parseInt(ens[3]), code: code},
		function(lines) {
			if (lines[0] == '1')
				qa_set_inner_html(document.getElementById('favoriting'), 'favoriting', lines.slice(1).join("\n"));
			else if (lines[0] == '0') {
				alert(lines[1]);
				qa_hide_waiting(elem);
			} else
				qa_ajax_error();
		}
	);

	qa_show_waiting_after(elem, false);

	return false;
}

function qa_ajax_post(operation, params, callback, apiVersion)
{
	if (typeof(apiVersion) === 'undefined') {
		apiVersion = 0;
	}

	$.extend(params, {qa: 'ajax', qa_operation: operation, qa_root: qa_root, qa_request: qa_request, apiVersion: apiVersion});

	$.post(qa_root, params, function (response) {
			if (apiVersion === 0) {
				var header = 'QA_AJAX_RESPONSE';
				var headerpos = response.indexOf(header);

				if (headerpos >= 0)
					callback(response.substr(headerpos + header.length).replace(/^\s+/, '').split("\n"));
				else
					callback([]);
			} else {
				callback(response);
			}
		},
		apiVersion === 0 ? 'text' : 'json'
	).fail(function (jqXHR) {
		if (jqXHR.readyState > 0)
			qa_ajax_error();
	});
}

function qa_ajax_error()
{
	alert('Unexpected response from server - please try again or switch off Javascript.');
}

function qa_display_rule_show(target, show, first)
{
	var e = document.getElementById(target);
	if (e) {
		if (first || e.nodeName == 'SPAN')
			e.style.display = (show ? '' : 'none');
		else if (show)
			$(e).fadeIn();
		else
			$(e).fadeOut();
	}
}


// Question page actions

var qa_element_revealed = null;

function qa_toggle_element(elem)
{
	var e = elem ? document.getElementById(elem) : null;

	if (e && e.qa_disabled)
		e = null;

	if (e && (qa_element_revealed == e)) {
		qa_conceal(qa_element_revealed, 'form');
		qa_element_revealed = null;

	} else {
		if (qa_element_revealed)
			qa_conceal(qa_element_revealed, 'form');

		if (e) {
			if (e.qa_load && !e.qa_loaded) {
				e.qa_load();
				e.qa_loaded = true;
			}

			if (e.qa_show)
				e.qa_show();

			qa_reveal(e, 'form', function() {
				var t = $(e).offset().top;
				var h = $(e).height() + 16;
				var wt = $(window).scrollTop();
				var wh = $(window).height();

				if ((t < wt) || (t > (wt + wh)))
					qa_scroll_page_to(t);
				else if ((t + h) > (wt + wh))
					qa_scroll_page_to(t + h - wh);

				if (e.qa_focus)
					e.qa_focus();
			});
		}

		qa_element_revealed = e;
	}

	return !(e || !elem); // failed to find item
}

function qa_submit_answer(questionid, elem)
{
	var params = qa_form_params('a_form');

	params.a_questionid = questionid;

	qa_ajax_post('answer', params,
		function(lines) {
			if (lines[0] == '1') {
				if (lines[1] < 1) {
					var b = document.getElementById('q_doanswer');
					if (b)
						b.style.display = 'none';
				}

				var t = document.getElementById('a_list_title');
				qa_set_inner_html(t, 'a_list_title', lines[2]);
				qa_reveal(t, 'a_list_title');

				var e = document.createElement('div');
				e.innerHTML = lines.slice(3).join("\n");

				var c = e.firstChild;
				c.style.display = 'none';

				var l = document.getElementById('a_list');
				l.insertBefore(c, l.firstChild);

				var a = document.getElementById('anew');
				a.qa_disabled = true;

				qa_reveal(c, 'answer');
				qa_conceal(a, 'form');

			} else if (lines[0] == '0') {
				document.forms['a_form'].submit();

			} else {
				qa_ajax_error();
			}
		}
	);

	qa_show_waiting_after(elem, false);

	return false;
}

function qa_submit_comment(questionid, parentid, elem)
{
	var params = qa_form_params('c_form_' + parentid);

	params.c_questionid = questionid;
	params.c_parentid = parentid;

	qa_ajax_post('comment', params,
		function(lines) {

			if (lines[0] == '1') {
				var l = document.getElementById('c' + parentid + '_list');
				l.innerHTML = lines.slice(2).join("\n");
				l.style.display = '';

				var a = document.getElementById('c' + parentid);
				a.qa_disabled = true;

				var c = document.getElementById(lines[1]); // id of comment
				if (c) {
					c.style.display = 'none';
					qa_reveal(c, 'comment');
				}

				qa_conceal(a, 'form');

			} else if (lines[0] == '0') {
				document.forms['c_form_' + parentid].submit();

			} else {
				qa_ajax_error();
			}

		}
	);

	qa_show_waiting_after(elem, false);

	return false;
}

function qa_answer_click(answerid, questionid, target)
{
	var params = {};

	params.answerid = answerid;
	params.questionid = questionid;
	params.code = target.form.elements.code.value;
	params[target.name] = target.value;

	qa_ajax_post('click_a', params,
		function(lines) {
			if (lines[0] == '1') {
				qa_set_inner_html(document.getElementById('a_list_title'), 'a_list_title', lines[1]);

				var l = document.getElementById('a' + answerid);
				var h = lines.slice(2).join("\n");

				if (h.length)
					qa_set_outer_html(l, 'answer', h);
				else
					qa_conceal(l, 'answer');

			} else {
				target.form.elements.qa_click.value = target.name;
				target.form.submit();
			}
		}
	);

	qa_show_waiting_after(target, false);

	return false;
}

function qa_comment_click(commentid, questionid, parentid, target)
{
	var params = {};

	params.commentid = commentid;
	params.questionid = questionid;
	params.parentid = parentid;
	params.code = target.form.elements.code.value;
	params[target.name] = target.value;

	qa_ajax_post('click_c', params,
		function(lines) {
			if (lines[0] == '1') {
				var l = document.getElementById('c' + commentid);
				var h = lines.slice(1).join("\n");

				if (h.length)
					qa_set_outer_html(l, 'comment', h);
				else
					qa_conceal(l, 'comment');

			} else {
				target.form.elements.qa_click.value = target.name;
				target.form.submit();
			}
		}
	);

	qa_show_waiting_after(target, false);

	return false;
}

function qa_show_comments(questionid, parentid, elem)
{
	var params = {};

	params.c_questionid = questionid;
	params.c_parentid = parentid;

	qa_ajax_post('show_cs', params,
		function(lines) {
			if (lines[0] == '1') {
				var l = document.getElementById('c' + parentid + '_list');
				l.innerHTML = lines.slice(1).join("\n");
				l.style.display = 'none';
				qa_reveal(l, 'comments');

			} else {
				qa_ajax_error();
			}
		}
	);

	qa_show_waiting_after(elem, true);

	return false;
}

function qa_form_params(formname)
{
	var es = document.forms[formname].elements;
	var params = {};

	for (var i = 0; i < es.length; i++) {
		var e = es[i];
		var t = (e.type || '').toLowerCase();

		if (((t != 'checkbox') && (t != 'radio')) || e.checked)
			params[e.name] = e.value;
	}

	return params;
}

function qa_scroll_page_to(scroll)
{
	$('html,body').animate({scrollTop: scroll}, 400);
}


// Ask form

function qa_title_change(value)
{
	qa_ajax_post('asktitle', {title: value}, function(lines) {
		if (lines[0] == '1') {
			if (lines[1].length) {
				qa_tags_examples = lines[1];
				qa_tag_hints(true);
			}

			if (lines.length > 2) {
				var simelem = document.getElementById('similar');
				if (simelem)
					simelem.innerHTML = lines.slice(2).join('\n');
			}

		} else if (lines[0] == '0')
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
	var elem = document.getElementById('tags');
	var parts = qa_tag_typed_parts(elem);

	// removes any HTML tags and ampersand
	var tag = qa_html_unescape(link.innerHTML.replace(/<[^>]*>/g, ''));

	var separator = qa_tag_onlycomma ? ', ' : ' ';

	// replace if matches typed, otherwise append
	var newvalue = (parts.typed && (tag.toLowerCase().indexOf(parts.typed.toLowerCase()) >= 0))
		? (parts.before + separator + tag + separator + parts.after + separator) : (elem.value + separator + tag + separator);

	// sanitize and set value
	if (qa_tag_onlycomma)
		elem.value = newvalue.replace(/[\s,]*,[\s,]*/g, ', ').replace(/^[\s,]+/g, '');
	else
		elem.value = newvalue.replace(/[\s,]+/g, ' ').replace(/^[\s,]+/g, '');

	elem.focus();
	qa_tag_hints();

	return false;
}

function qa_tag_hints(skipcomplete)
{
	var elem = document.getElementById('tags');
	var html = '';
	var completed = false;

	// first try to auto-complete
	if (qa_tags_complete && !skipcomplete) {
		var parts = qa_tag_typed_parts(elem);

		if (parts.typed) {
			html = qa_tags_to_html((qa_html_unescape(qa_tags_examples + ',' + qa_tags_complete)).split(','), parts.typed.toLowerCase());
			completed = html ? true : false;
		}
	}

	// otherwise show examples
	if (qa_tags_examples && !completed)
		html = qa_tags_to_html((qa_html_unescape(qa_tags_examples)).split(','), null);

	// set title visiblity and hint list
	document.getElementById('tag_examples_title').style.display = (html && !completed) ? '' : 'none';
	document.getElementById('tag_complete_title').style.display = (html && completed) ? '' : 'none';
	document.getElementById('tag_hints').innerHTML = html;
}

function qa_tags_to_html(tags, matchlc)
{
	var html = '';
	var added = 0;
	var tagseen = {};

	for (var i = 0; i < tags.length; i++) {
		var tag = tags[i];
		var taglc = tag.toLowerCase();

		if (!tagseen[taglc]) {
			tagseen[taglc] = true;

			if ((!matchlc) || (taglc.indexOf(matchlc) >= 0)) { // match if necessary
				if (matchlc) { // if matching, show appropriate part in bold
					var matchstart = taglc.indexOf(matchlc);
					var matchend = matchstart + matchlc.length;
					inner = '<span style="font-weight:normal;">' + qa_html_escape(tag.substring(0, matchstart)) + '<b>' +
						qa_html_escape(tag.substring(matchstart, matchend)) + '</b>' + qa_html_escape(tag.substring(matchend)) + '</span>';
				} else // otherwise show as-is
					inner = qa_html_escape(tag);

				html += qa_tag_template.replace(/\^/g, inner.replace('$', '$$$$')) + ' '; // replace ^ in template, escape $s

				if (++added >= qa_tags_max)
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
		var sel = document.selection.createRange();
		sel.moveStart('character', -elem.value.length);

		return elem.value.length - sel.text.length;

	} else if (typeof (elem.selectionEnd) != 'undefined') // other browsers
		return elem.value.length - elem.selectionEnd;

	else // by default return safest value
		return 0;
}

function qa_tag_typed_parts(elem)
{
	var caret = elem.value.length - qa_caret_from_end(elem);
	var active = elem.value.substring(0, caret);
	var passive = elem.value.substring(active.length);

	// if the caret is in the middle of a word, move the end of word from passive to active
	if (
		active.match(qa_tag_onlycomma ? /[^\s,][^,]*$/ : /[^\s,]$/) &&
		(adjoinmatch = passive.match(qa_tag_onlycomma ? /^[^,]*[^\s,][^,]*/ : /^[^\s,]+/))
		) {
		active += adjoinmatch[0];
		passive = elem.value.substring(active.length);
	}

	// find what has been typed so far
	var typedmatch = active.match(qa_tag_onlycomma ? /[^\s,]+[^,]*$/ : /[^\s,]+$/) || [''];

	return {before: active.substring(0, active.length - typedmatch[0].length), after: passive, typed: typedmatch[0]};
}

function qa_category_select(idprefix, startpath)
{
	var startval = startpath ? startpath.split("/") : [];
	var setdescnow = true;

	for (var l = 0; l <= qa_cat_maxdepth; l++) {
		var elem = document.getElementById(idprefix + '_' + l);

		if (elem) {
			if (l) {
				if (l < startval.length && startval[l].length) {
					var val = startval[l];

					for (var j = 0; j < elem.options.length; j++)
						if (elem.options[j].value == val)
							elem.selectedIndex = j;
				} else
					var val = elem.options[elem.selectedIndex].value;
			} else
				val = '';

			if (elem.qa_last_sel !== val) {
				elem.qa_last_sel = val;

				var subelem = document.getElementById(idprefix + '_' + l + '_sub');
				if (subelem)
					subelem.parentNode.removeChild(subelem);

				if (val.length || (l == 0)) {
					subelem = elem.parentNode.insertBefore(document.createElement('span'), elem.nextSibling);
					subelem.id = idprefix + '_' + l + '_sub';
					qa_show_waiting_after(subelem, true);

					qa_ajax_post('category', {categoryid: val},
						(function(elem, l) {
							return function(lines) {
								var subelem = document.getElementById(idprefix + '_' + l + '_sub');
								if (subelem)
									subelem.parentNode.removeChild(subelem);

								if (lines[0] == '1') {
									elem.qa_cat_desc = lines[1];

									var addedoption = false;

									if (lines.length > 2) {
										subelem = elem.parentNode.insertBefore(document.createElement('span'), elem.nextSibling);
										subelem.id = idprefix + '_' + l + '_sub';
										subelem.innerHTML = ' ';

										var newelem = elem.cloneNode(false);

										newelem.name = newelem.id = idprefix + '_' + (l + 1);
										newelem.options.length = 0;

										if (l ? qa_cat_allownosub : qa_cat_allownone)
											newelem.options[0] = new Option(l ? '' : elem.options[0].text, '', true, true);

										for (var i = 2; i < lines.length; i++) {
											var parts = lines[i].split('/');

											if (String(qa_cat_exclude).length && (String(qa_cat_exclude) == parts[0]))
												continue;

											newelem.options[newelem.options.length] = new Option(parts.slice(1).join('/'), parts[0]);
											addedoption = true;
										}

										if (addedoption) {
											subelem.appendChild(newelem);
											qa_category_select(idprefix, startpath);

										}

										if (l == 0)
											elem.style.display = 'none';
									}

									if (!addedoption)
										set_category_description(idprefix);

								} else if (lines[0] == '0')
									alert(lines[1]);
								else
									qa_ajax_error();
							}
						})(elem, l)
					);

					setdescnow = false;
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
	var n = document.getElementById(idprefix + '_note');

	if (n) {
		desc = '';

		for (var l = 1; l <= qa_cat_maxdepth; l++) {
			var elem = document.getElementById(idprefix + '_' + l);

			if (elem && elem.options[elem.selectedIndex].value.length)
				desc = elem.qa_cat_desc;
		}

		n.innerHTML = desc;
	}
}


// User functions

function qa_submit_wall_post(elem, morelink)
{
	var params = {};

	params.message = document.forms.wallpost.message.value;
	params.handle = document.forms.wallpost.handle.value;
	params.start = document.forms.wallpost.start.value;
	params.code = document.forms.wallpost.code.value;
	params.morelink = morelink ? 1 : 0;

	qa_ajax_post('wallpost', params,
		function(lines) {
			if (lines[0] == '1') {
				var l = document.getElementById('wallmessages');
				l.innerHTML = lines.slice(2).join("\n");

				var c = document.getElementById(lines[1]); // id of new message
				if (c) {
					c.style.display = 'none';
					qa_reveal(c, 'wallpost');
				}

				document.forms.wallpost.message.value = '';
				qa_hide_waiting(elem);

			} else if (lines[0] == '0') {
				document.forms.wallpost.qa_click.value = elem.name;
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
	var params = {};

	params.messageid = messageid;
	params.handle = document.forms.wallpost.handle.value;
	params.start = document.forms.wallpost.start.value;
	params.code = document.forms.wallpost.code.value;

	params[target.name] = target.value;

	qa_ajax_post('click_wall', params,
		function(lines) {
			if (lines[0] == '1') {
				var l = document.getElementById('m' + messageid);
				var h = lines.slice(1).join("\n");

				if (h.length)
					qa_set_outer_html(l, 'wallpost', h);
				else
					qa_conceal(l, 'wallpost');

			} else {
				document.forms.wallpost.qa_click.value = target.name;
				document.forms.wallpost.submit();
			}
		}
	);

	qa_show_waiting_after(target, false);

	return false;
}

function qa_pm_click(messageid, target, box)
{
	var params = {};

	params.messageid = messageid;
	params.box = box;
	params.handle = document.forms.pmessage.handle.value;
	params.start = document.forms.pmessage.start.value;
	params.code = document.forms.pmessage.code.value;

	params[target.name] = target.value;

	qa_ajax_post('click_pm', params,
		function(lines) {
			if (lines[0] == '1') {
				var l = document.getElementById('m' + messageid);
				var h = lines.slice(1).join("\n");

				if (h.length)
					qa_set_outer_html(l, 'pmessage', h);
				else
					qa_conceal(l, 'pmessage');

			} else {
				document.forms.pmessage.qa_click.value = target.name;
				document.forms.pmessage.submit();
			}
		}
	);

	qa_show_waiting_after(target, false);

	return false;
}
