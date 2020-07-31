/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-content/qa-admin.js
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

var qa_recalc_running = 0;

window.onbeforeunload = function(event)
{
	if (qa_recalc_running > 0) {
		event = event || window.event;
		var message = qa_warning_recalc;
		event.returnValue = message;
		return message;
	}
};

function qa_recalc_click(state, elem, value, noteid)
{
	if (elem.qa_recalc_running) {
		elem.qa_recalc_stopped = true;

	} else {
		elem.qa_recalc_running = true;
		elem.qa_recalc_stopped = false;
		qa_recalc_running++;

		document.getElementById(noteid).innerHTML = '';
		elem.qa_original_value = elem.value;
		if (value)
			elem.value = value;

		qa_recalc_update(elem, state, noteid);
	}

	return false;
}

function qa_recalc_update(elem, state, noteid)
{
	if (state) {
		var recalcCode = elem.form.elements.code_recalc ? elem.form.elements.code_recalc.value : elem.form.elements.code.value;

		qa_ajax_post(
			'recalc',
			{state: state, code: recalcCode},
			function (response) {
				if (response.message) {
					document.getElementById(noteid).innerHTML = response.message;
				}

				if (elem.qa_recalc_stopped) {
					qa_recalc_cleanup(elem);
				} else {
					qa_recalc_update(elem, response.state, noteid);
				}
			}, 1
		);
	} else {
		qa_recalc_cleanup(elem);
	}
}

function qa_recalc_cleanup(elem)
{
	elem.value = elem.qa_original_value;
	elem.qa_recalc_running = null;
	qa_recalc_running--;
}

function qa_mailing_start(noteid, pauseid)
{
	qa_ajax_post(
		'mailing',
		{},
		function (response) {
			document.getElementById(noteid).innerHTML = response.message;
			if (response.continue) {
				window.setTimeout(function () {
					qa_mailing_start(noteid, pauseid);
				}, 1); // don't recurse
			} else {
				document.getElementById(pauseid).style.display = 'none';
			}
		}, 1
	);
}

function qa_update_dom(response)
{
	if (!response.hasOwnProperty('domUpdates')) {
		return;
	}

	for (var i = 0; i < response.domUpdates.length; i++) {
		var domUpdate = response.domUpdates[i];
		switch (domUpdate.action) {
			case 'conceal':
				qa_conceal(document.querySelector(domUpdate.selector));
				break;
			case 'reveal':
				qa_reveal(document.querySelector(domUpdate.selector));
				break;
			default: // replace
				$(domUpdate.selector).html(domUpdate.html);
		}
	}
}

function qa_admin_click(target)
{
	var p = target.name.split('_');

	var params = {entityid: p[1], action: p[2]};
	params.code = target.form.elements.code.value;

	qa_ajax_post('click_admin', params,
		function (response) {
			qa_update_dom(response);

			if (response.result === 'error' && response.error.severity === 'fatal') {
				alert(response.error.message);
			}

			qa_hide_waiting(target);
		}, 1
	);

	qa_show_waiting_after(target, false);

	return false;
}

function qa_version_check(uri, version, elem, isCore)
{
	qa_ajax_post(
		'version',
		{uri: uri, version: version, isCore: isCore},
		function (response) {
			if (response.result === 'error') {
				alert(response.error.message);

				return;
			}

			document.getElementById(elem).innerHTML = response.html;
		}, 1
	);
}

function qa_version_check_array(versionChecks)
{
	for (var i = 0; i < versionChecks.length; i++) {
		qa_version_check(versionChecks[i].uri, versionChecks[i].version, versionChecks[i].elem, false)
	}
}

function qa_get_enabled_plugins_hashes()
{
	var hashes = [];
	$('[id^=plugin_enabled]:checked').each(
		function(idx, elem) {
			hashes.push(elem.id.replace("plugin_enabled_", ""));
		}
	);

	$('[name=enabled_plugins_hashes]').val(hashes.join(';'));
}
