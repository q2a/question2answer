<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-selects.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Builders of selectspec arrays (see qa-db.php) used to specify database SELECTs


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

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}

	require_once QA_INCLUDE_DIR.'qa-db-maxima.php';

	
	function qa_db_select_with_pending() // any number of parameters read via func_get_args()
/*
	Return the results of all the SELECT operations specified by the supplied selectspec parameters, while also
	performing all pending selects that have not yet been executed. If only one parameter is supplied, return its
	result, otherwise return an array of results indexed as per the parameters.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-options.php';
		
		global $qa_db_pending_selectspecs, $qa_db_pending_results;
		
		$selectspecs=func_get_args();
		$singleresult=(count($selectspecs)==1);
		$outresults=array();
		
		foreach ($selectspecs as $key => $selectspec) // can pass null parameters
			if (empty($selectspec)) {
				unset($selectspecs[$key]);
				$outresults[$key]=null;
			}
		
		if (is_array($qa_db_pending_selectspecs))
			foreach ($qa_db_pending_selectspecs as $pendingid => $selectspec)
				if (!isset($qa_db_pending_results[$pendingid]))
					$selectspecs['pending_'.$pendingid]=$selectspec;
				
		$outresults=$outresults+qa_db_multi_select($selectspecs);
		
		if (is_array($qa_db_pending_selectspecs))
			foreach ($qa_db_pending_selectspecs as $pendingid => $selectspec)
				if (!isset($qa_db_pending_results[$pendingid])) {
					$qa_db_pending_results[$pendingid]=$outresults['pending_'.$pendingid];
					unset($outresults['pending_'.$pendingid]);
				}
			
		return $singleresult ? $outresults[0] : $outresults;
	}
	
	
	function qa_db_queue_pending_select($pendingid, $selectspec)
/*
	Queue a $selectspec for running later, with $pendingid (used for retrieval)
*/
	{
		global $qa_db_pending_selectspecs;
		
		$qa_db_pending_selectspecs[$pendingid]=$selectspec;
	}
	
	
	function qa_db_get_pending_result($pendingid, $selectspec=null)
/*
	Get the result of the queued SELECT query identified by $pendingid. Run the query if it hasn't run already. If
	$selectspec is supplied, it doesn't matter if this hasn't been queued before - it will be queued and run now.
*/
	{
		global $qa_db_pending_selectspecs, $qa_db_pending_results;
		
		if (isset($selectspec))
			qa_db_queue_pending_select($pendingid, $selectspec);
		elseif (!isset($qa_db_pending_selectspecs[$pendingid]))
			qa_fatal_error('Pending query was never set up: '.$pendingid);
			
		if (!isset($qa_db_pending_results[$pendingid]))
			qa_db_select_with_pending();
		
		return $qa_db_pending_results[$pendingid];
	}
	
	
	function qa_db_flush_pending_result($pendingid)
/*
	Remove the results of queued SELECT query identified by $pendingid if it has already been run. This means it will
	run again if its results are requested via qa_db_get_pending_result()
*/
	{
		global $qa_db_pending_results;
		
		unset($qa_db_pending_results[$pendingid]);
	}

	
	function qa_db_posts_basic_selectspec($voteuserid=null, $full=false, $user=true)
/*
	Return the common selectspec used to build any selectspecs which retrieve posts from the database.
	If $voteuserid is set, retrieve the vote made by a particular that user on each post.
	If $full is true, get full information on the posts, instead of just information for listing pages.
	If $user is true, get information about the user who wrote the post (or cookie if anonymous).
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$selectspec=array(
			'columns' => array(
				'^posts.postid', '^posts.categoryid', '^posts.type', 'basetype' => 'LEFT(^posts.type, 1)', 'hidden' => "INSTR(^posts.type, '_HIDDEN')>0",
				'^posts.acount', '^posts.selchildid', '^posts.closedbyid', '^posts.upvotes', '^posts.downvotes', '^posts.netvotes', '^posts.views', '^posts.hotness',
				'^posts.flagcount', '^posts.title', '^posts.tags', 'created' => 'UNIX_TIMESTAMP(^posts.created)', '^posts.name',
				'categoryname' => '^categories.title', 'categorybackpath' => "^categories.backpath",
				'categoryids' => "CONCAT_WS(',', ^posts.catidpath1, ^posts.catidpath2, ^posts.catidpath3, ^posts.categoryid)",
			),
			
			'arraykey' => 'postid',
			'source' => '^posts LEFT JOIN ^categories ON ^categories.categoryid=^posts.categoryid',
			'arguments' => array(),
		);
		
		if (isset($voteuserid)) {
			require_once QA_INCLUDE_DIR.'qa-app-updates.php';
			
			$selectspec['columns']['uservote']='^uservotes.vote';
			$selectspec['columns']['userflag']='^uservotes.flag';
			$selectspec['columns']['userfavoriteq']='^userfavorites.entityid<=>^posts.postid';
			$selectspec['source'].=' LEFT JOIN ^uservotes ON ^posts.postid=^uservotes.postid AND ^uservotes.userid=$';
			$selectspec['source'].=' LEFT JOIN ^userfavorites ON ^posts.postid=^userfavorites.entityid AND ^userfavorites.userid=$ AND ^userfavorites.entitytype=$';
			array_push($selectspec['arguments'], $voteuserid, $voteuserid, QA_ENTITY_QUESTION);
		}
		
		if ($full) {
			$selectspec['columns']['content']='^posts.content';
			$selectspec['columns']['notify']='^posts.notify';
			$selectspec['columns']['updated']='UNIX_TIMESTAMP(^posts.updated)';
			$selectspec['columns']['updatetype']='^posts.updatetype';
			$selectspec['columns'][]='^posts.format';
			$selectspec['columns'][]='^posts.lastuserid';
			$selectspec['columns']['lastip']='INET_NTOA(^posts.lastip)';
			$selectspec['columns'][]='^posts.parentid';
			$selectspec['columns']['lastviewip']='INET_NTOA(^posts.lastviewip)';
		}
				
		if ($user) {
			$selectspec['columns'][]='^posts.userid';
			$selectspec['columns'][]='^posts.cookieid';
			$selectspec['columns']['createip']='INET_NTOA(^posts.createip)';
			$selectspec['columns'][]='^userpoints.points';

			if (!QA_FINAL_EXTERNAL_USERS) {
				$selectspec['columns'][]='^users.flags';
				$selectspec['columns'][]='^users.level';
				$selectspec['columns']['email']='^users.email';
				$selectspec['columns']['handle']='^users.handle';
				$selectspec['columns']['avatarblobid']='BINARY ^users.avatarblobid';
				$selectspec['columns'][]='^users.avatarwidth';
				$selectspec['columns'][]='^users.avatarheight';
				$selectspec['source'].=' LEFT JOIN ^users ON ^posts.userid=^users.userid';
				
				if ($full) {
					$selectspec['columns']['lasthandle']='lastusers.handle';
					$selectspec['source'].=' LEFT JOIN ^users AS lastusers ON ^posts.lastuserid=lastusers.userid';
				}
			}
			
			$selectspec['source'].=' LEFT JOIN ^userpoints ON ^posts.userid=^userpoints.userid';
		}
		
		return $selectspec;
	}
	
	
	function qa_db_add_selectspec_opost(&$selectspec, $poststable, $fromupdated=false, $full=false)
/*
	Supplement a selectspec returned by qa_db_posts_basic_selectspec() to get information about another post (answer or
	comment) which is related to the main post (question) retrieved. Pass the name of table which will contain the other
	post in $poststable. Set $fromupdated to true to get information about when this other post was edited, rather than
	created. If $full is true, get full information on this other post.
*/
	{
		$selectspec['arraykey']='opostid';
		
		$selectspec['columns']['obasetype']='LEFT('.$poststable.'.type, 1)';
		$selectspec['columns']['ohidden']="INSTR(".$poststable.".type, '_HIDDEN')>0";
		$selectspec['columns']['opostid']=$poststable.'.postid';
		$selectspec['columns']['ouserid']=$poststable.($fromupdated ? '.lastuserid' : '.userid');
		$selectspec['columns']['ocookieid']=$poststable.'.cookieid';
		$selectspec['columns']['oname']=$poststable.'.name';
		$selectspec['columns']['oip']='INET_NTOA('.$poststable.($fromupdated ? '.lastip' : '.createip').')';
		$selectspec['columns']['otime']='UNIX_TIMESTAMP('.$poststable.($fromupdated ? '.updated' : '.created').')';
		$selectspec['columns']['oflagcount']=$poststable.'.flagcount';
		
		if ($fromupdated)
			$selectspec['columns']['oupdatetype']=$poststable.'.updatetype';
	
		if ($full) {
			$selectspec['columns']['ocontent']=$poststable.'.content';
			$selectspec['columns']['oformat']=$poststable.'.format';
		}
		
		if ($fromupdated || $full)
			$selectspec['columns']['oupdated']='UNIX_TIMESTAMP('.$poststable.'.updated)';
	}

	
	function qa_db_add_selectspec_ousers(&$selectspec, $userstable, $pointstable)
/*
	Supplement a selectspec returned by qa_db_posts_basic_selectspec() to get information about the author of another
	post (answer or comment) which is related to the main post (question) retrieved. Pass the name of table which will
	contain the other user's details in $userstable and the name of the table which will contain the other user's points
	in $pointstable.
*/
	{
		if (!QA_FINAL_EXTERNAL_USERS) {
			$selectspec['columns']['oflags']=$userstable.'.flags';
			$selectspec['columns']['olevel']=$userstable.'.level';
			$selectspec['columns']['oemail']=$userstable.'.email';
			$selectspec['columns']['ohandle']=$userstable.'.handle';
			$selectspec['columns']['oavatarblobid']='BINARY '.$userstable.'.avatarblobid'; // cast to BINARY due to MySQL bug which renders it signed in a union
			$selectspec['columns']['oavatarwidth']=$userstable.'.avatarwidth';
			$selectspec['columns']['oavatarheight']=$userstable.'.avatarheight';
		}
		
		$selectspec['columns']['opoints']=$pointstable.'.points';
	}

	
	function qa_db_slugs_to_backpath($categoryslugs)
/*
	Given $categoryslugs in order of the hierarchiy, return the equivalent value for the backpath column in the categories table
*/
	{
		if (!is_array($categoryslugs)) // accept old-style string arguments for one category deep
			$categoryslugs=array($categoryslugs);
		
		return implode('/', array_reverse($categoryslugs));
	}
	
	
	function qa_db_categoryslugs_sql_args($categoryslugs, &$arguments)
/*
	Return SQL code that represents the constraint of a post being in the category with $categoryslugs, or any of its subcategories
*/
	{
		if (!is_array($categoryslugs)) // accept old-style string arguments for one category deep
			$categoryslugs=strlen($categoryslugs) ? array($categoryslugs) : array();
		
		$levels=count($categoryslugs);
		
		if (($levels>0) && ($levels<=QA_CATEGORY_DEPTH)) {
			$arguments[]=qa_db_slugs_to_backpath($categoryslugs);
			return (($levels==QA_CATEGORY_DEPTH) ? 'categoryid' : ('catidpath'.$levels)).'=(SELECT categoryid FROM ^categories WHERE backpath=$ LIMIT 1) AND ';
		}
		
		return '';
	}
	
	
	function qa_db_qs_selectspec($voteuserid, $sort, $start, $categoryslugs=null, $createip=null, $specialtype=false, $full=false, $count=null)
/*
	Return the selectspec to retrieve questions (of type $specialtype if provided, or 'Q' by default) sorted by $sort,
	restricted to $createip (if not null) and the category for $categoryslugs (if not null), with the corresponding vote
	made by $voteuserid (if not null) and including $full content or not. Return $count (if null, a default is used)
	questions starting from offset $start.
*/
	{
		if (($specialtype=='Q') || ($specialtype=='Q_QUEUED'))
			$type=$specialtype;
		else
			$type=$specialtype ? 'Q_HIDDEN' : 'Q'; // for backwards compatibility
		
		$count=isset($count) ? min($count, QA_DB_RETRIEVE_QS_AS) : QA_DB_RETRIEVE_QS_AS;
		
		switch ($sort) {
			case 'acount':
			case 'flagcount':
			case 'netvotes':
			case 'views':
				$sortsql='ORDER BY ^posts.'.$sort.' DESC, ^posts.created DESC';
				break;
			
			case 'created':
			case 'hotness':
				$sortsql='ORDER BY ^posts.'.$sort.' DESC';
				break;
				
			default:
				qa_fatal_error('qa_db_qs_selectspec() called with illegal sort value');
				break;
		}
		
		$selectspec=qa_db_posts_basic_selectspec($voteuserid, $full);
		
		$selectspec['source'].=" JOIN (SELECT postid FROM ^posts WHERE ".
			qa_db_categoryslugs_sql_args($categoryslugs, $selectspec['arguments']).
			(isset($createip) ? "createip=INET_ATON($) AND " : "").
			"type=$ ".$sortsql." LIMIT #,#) y ON ^posts.postid=y.postid";

		if (isset($createip))
			$selectspec['arguments'][]=$createip;
		
		array_push($selectspec['arguments'], $type, $start, $count);

		$selectspec['sortdesc']=$sort;
		
		return $selectspec;
	}
	
	
	function qa_db_unanswered_qs_selectspec($voteuserid, $by, $start, $categoryslugs=null, $specialtype=false, $full=false, $count=null)
/*
	Return the selectspec to retrieve recent questions (of type $specialtype if provided, or 'Q' by default) which,
	depending on $by, either (a) have no answers, (b) have on selected answers, or (c) have no upvoted answers. The
	questions are restricted to the category for $categoryslugs (if not null), and will have the corresponding vote made
	by $voteuserid (if not null) and will include $full content or not. Return $count (if null, a default is used)
	questions starting from offset $start.
*/
	{
		if (($specialtype=='Q') || ($specialtype=='Q_QUEUED'))
			$type=$specialtype;
		else
			$type=$specialtype ? 'Q_HIDDEN' : 'Q'; // for backwards compatibility

		$count=isset($count) ? min($count, QA_DB_RETRIEVE_QS_AS) : QA_DB_RETRIEVE_QS_AS;
		
		switch ($by) {
			case 'selchildid':
				$bysql='selchildid IS NULL';
				break;
				
			case 'amaxvote':
				$bysql='amaxvote=0';
				break;
				
			default:
				$bysql='acount=0';
				break;
		}
		
		
		$selectspec=qa_db_posts_basic_selectspec($voteuserid, $full);
		
		$selectspec['source'].=" JOIN (SELECT postid FROM ^posts WHERE ".qa_db_categoryslugs_sql_args($categoryslugs, $selectspec['arguments'])."type=$ AND ".$bysql." AND closedbyid IS NULL ORDER BY ^posts.created DESC LIMIT #,#) y ON ^posts.postid=y.postid";
		
		array_push($selectspec['arguments'], $type, $start, $count);

		$selectspec['sortdesc']='created';
		
		return $selectspec;
	}
	
	
	function qa_db_recent_a_qs_selectspec($voteuserid, $start, $categoryslugs=null, $createip=null, $specialtype=false, $fullanswers=false, $count=null)
/*
	Return the selectspec to retrieve the antecedent questions for recent answers (of type $specialtype if provided, or
	'A' by default), restricted to $createip (if not null) and the category for $categoryslugs (if not null), with the
	corresponding vote on those questions made by $voteuserid (if not null). Return $count (if null, a default is used)
	questions starting from offset $start. The selectspec will also retrieve some information about the answers
	themselves (including the content if $fullanswers is true), in columns named with the prefix 'o'.
*/
	{
		if (($specialtype=='A') || ($specialtype=='A_QUEUED'))
			$type=$specialtype;
		else
			$type=$specialtype ? 'A_HIDDEN' : 'A'; // for backwards compatibility

		$count=isset($count) ? min($count, QA_DB_RETRIEVE_QS_AS) : QA_DB_RETRIEVE_QS_AS;
		
		$selectspec=qa_db_posts_basic_selectspec($voteuserid);
		
		qa_db_add_selectspec_opost($selectspec, 'aposts', false, $fullanswers);
		qa_db_add_selectspec_ousers($selectspec, 'ausers', 'auserpoints');

		$selectspec['source'].=" JOIN ^posts AS aposts ON ^posts.postid=aposts.parentid".
			(QA_FINAL_EXTERNAL_USERS ? "" : " LEFT JOIN ^users AS ausers ON aposts.userid=ausers.userid").
			" LEFT JOIN ^userpoints AS auserpoints ON aposts.userid=auserpoints.userid".
			" JOIN (SELECT postid FROM ^posts WHERE ".
			qa_db_categoryslugs_sql_args($categoryslugs, $selectspec['arguments']).
			(isset($createip) ? "createip=INET_ATON($) AND " : "").
			"type=$ ORDER BY ^posts.created DESC LIMIT #,#) y ON aposts.postid=y.postid".
			($specialtype ? '' : " WHERE ^posts.type='Q'");
			
		if (isset($createip))
			$selectspec['arguments'][]=$createip;

		array_push($selectspec['arguments'], $type, $start, $count);

		$selectspec['sortdesc']='otime';
		
		return $selectspec;
	}

	
	function qa_db_recent_c_qs_selectspec($voteuserid, $start, $categoryslugs=null, $createip=null, $specialtype=false, $fullcomments=false, $count=null)
/*
	Return the selectspec to retrieve the antecedent questions for recent comments (of type $specialtype if provided, or
	'C' by default), restricted to $createip (if not null) and the category for $categoryslugs (if not null), with the
	corresponding vote on those questions made by $voteuserid (if not null). Return $count (if null, a default is used)
	questions starting from offset $start. The selectspec will also retrieve some information about the comments
	themselves (including the content if $fullcomments is true), in columns named with the prefix 'o'.
*/
	{
		if (($specialtype=='C') || ($specialtype=='C_QUEUED'))
			$type=$specialtype;
		else
			$type=$specialtype ? 'C_HIDDEN' : 'C'; // for backwards compatibility

		$count=isset($count) ? min($count, QA_DB_RETRIEVE_QS_AS) : QA_DB_RETRIEVE_QS_AS;
		
		$selectspec=qa_db_posts_basic_selectspec($voteuserid);
		
		qa_db_add_selectspec_opost($selectspec, 'cposts', false, $fullcomments);
		qa_db_add_selectspec_ousers($selectspec, 'cusers', 'cuserpoints');
		
		$selectspec['source'].=" JOIN ^posts AS parentposts ON".
			" ^posts.postid=(CASE LEFT(parentposts.type, 1) WHEN 'A' THEN parentposts.parentid ELSE parentposts.postid END)".
			" JOIN ^posts AS cposts ON parentposts.postid=cposts.parentid".
			(QA_FINAL_EXTERNAL_USERS ? "" : " LEFT JOIN ^users AS cusers ON cposts.userid=cusers.userid").
			" LEFT JOIN ^userpoints AS cuserpoints ON cposts.userid=cuserpoints.userid".
			" JOIN (SELECT postid FROM ^posts WHERE ".
			qa_db_categoryslugs_sql_args($categoryslugs, $selectspec['arguments']).
			(isset($createip) ? "createip=INET_ATON($) AND " : "").
			"type=$ ORDER BY ^posts.created DESC LIMIT #,#) y ON cposts.postid=y.postid".
			($specialtype ? '' : " WHERE ^posts.type='Q' AND ((parentposts.type='Q') OR (parentposts.type='A'))");

		if (isset($createip))
			$selectspec['arguments'][]=$createip;

		array_push($selectspec['arguments'], $type, $start, $count);

		$selectspec['sortdesc']='otime';
		
		return $selectspec;
	}
	
	
	function qa_db_recent_edit_qs_selectspec($voteuserid, $start, $categoryslugs=null, $lastip=null, $onlyvisible=true, $fulledited=false, $count=null)
/*
	Return the selectspec to retrieve the antecedent questions for recently edited posts, restricted to edits by $lastip
	(if not null), the category for $categoryslugs (if not null) and only visible posts (if $onlyvisible), with the
	corresponding vote on those questions made by $voteuserid (if not null). Return $count (if null, a default is used)
	questions starting from offset $start. The selectspec will also retrieve some information about the edited posts
	themselves (including the content if $fulledited is true), in columns named with the prefix 'o'.
*/
	{
		$count=isset($count) ? min($count, QA_DB_RETRIEVE_QS_AS) : QA_DB_RETRIEVE_QS_AS;
		
		$selectspec=qa_db_posts_basic_selectspec($voteuserid);
		
		qa_db_add_selectspec_opost($selectspec, 'editposts', true, $fulledited);
		qa_db_add_selectspec_ousers($selectspec, 'editusers', 'edituserpoints');
		
		$selectspec['source'].=" JOIN ^posts AS parentposts ON".
			" ^posts.postid=IF(LEFT(parentposts.type, 1)='Q', parentposts.postid, parentposts.parentid)".
			" JOIN ^posts AS editposts ON parentposts.postid=IF(LEFT(editposts.type, 1)='Q', editposts.postid, editposts.parentid)".
			(QA_FINAL_EXTERNAL_USERS ? "" : " LEFT JOIN ^users AS editusers ON editposts.lastuserid=editusers.userid").
			" LEFT JOIN ^userpoints AS edituserpoints ON editposts.lastuserid=edituserpoints.userid".
			" JOIN (SELECT postid FROM ^posts WHERE ".
			qa_db_categoryslugs_sql_args($categoryslugs, $selectspec['arguments']).
			(isset($lastip) ? "lastip=INET_ATON($) AND " : "").
			($onlyvisible ? "type IN ('Q', 'A', 'C')" : "1").
			" ORDER BY ^posts.updated DESC LIMIT #,#) y ON editposts.postid=y.postid".
			($onlyvisible ? " WHERE parentposts.type IN ('Q', 'A', 'C') AND ^posts.type IN ('Q', 'A', 'C')" : "");
			
		if (isset($lastip))
			$selectspec['arguments'][]=$lastip;
			
		array_push($selectspec['arguments'], $start, $count);

		$selectspec['sortdesc']='otime';
		
		return $selectspec;		
	}


	function qa_db_flagged_post_qs_selectspec($voteuserid, $start, $fullflagged=false, $count=null)
/*
	Return the selectspec to retrieve the antecedent questions for the most flagged posts, with the corresponding vote
	on those questions made by $voteuserid (if not null). Return $count (if null, a default is used) questions starting
	from offset $start. The selectspec will also retrieve some information about the flagged posts themselves (including
	the content if $fullflagged is true).
*/
	{
		$count=isset($count) ? min($count, QA_DB_RETRIEVE_QS_AS) : QA_DB_RETRIEVE_QS_AS;
		
		$selectspec=qa_db_posts_basic_selectspec($voteuserid);
		
		qa_db_add_selectspec_opost($selectspec, 'flagposts', false, $fullflagged);
		qa_db_add_selectspec_ousers($selectspec, 'flagusers', 'flaguserpoints');

		$selectspec['source'].=" JOIN ^posts AS parentposts ON".
			" ^posts.postid=IF(LEFT(parentposts.type, 1)='Q', parentposts.postid, parentposts.parentid)".
			" JOIN ^posts AS flagposts ON parentposts.postid=IF(LEFT(flagposts.type, 1)='Q', flagposts.postid, flagposts.parentid)".
			(QA_FINAL_EXTERNAL_USERS ? "" : " LEFT JOIN ^users AS flagusers ON flagposts.userid=flagusers.userid").
			" LEFT JOIN ^userpoints AS flaguserpoints ON flagposts.userid=flaguserpoints.userid".
			" JOIN (SELECT postid FROM ^posts WHERE flagcount>0 AND type IN ('Q', 'A', 'C') ORDER BY ^posts.flagcount DESC, ^posts.created DESC LIMIT #,#) y ON flagposts.postid=y.postid";
			
		array_push($selectspec['arguments'], $start, $count);

		$selectspec['sortdesc']='oflagcount';
		$selectspec['sortdesc_2']='otime';
		
		return $selectspec;		
	}


	function qa_db_posts_selectspec($voteuserid, $postids, $full=false)
/*
	Return the selectspec to retrieve the posts in $postids, with the corresponding vote on those posts made by
	$voteuserid (if not null). Returns full information if $full is true.
*/
	{
		$selectspec=qa_db_posts_basic_selectspec($voteuserid, $full);

		$selectspec['source'].=" WHERE ^posts.postid IN (#)";
		$selectspec['arguments'][]=$postids;

		return $selectspec;
	}
	
	
	function qa_db_posts_basetype_selectspec($postids)
/*
	Return the selectspec to retrieve the basetype for the posts in $postids, as an array mapping postid => basetype
*/
	{
		return array(
			'columns' => array('postid', 'basetype' => 'LEFT(type, 1)'),
			'source' => "^posts WHERE postid IN (#)",
			'arguments' => array($postids),
			'arraykey' => 'postid',
			'arrayvalue' => 'basetype',
		);
	}
	
	
	function qa_db_posts_to_qs_selectspec($voteuserid, $postids, $full=false)
/*
	Return the selectspec to retrieve the basetype for the posts in $postids, as an array mapping postid => basetype
*/
	{
		$selectspec=qa_db_posts_basic_selectspec($voteuserid, $full);
		
		$selectspec['columns']['obasetype']='LEFT(childposts.type, 1)';
		$selectspec['columns']['opostid']='childposts.postid';
		
		$selectspec['source'].=" JOIN ^posts AS parentposts ON".
			" ^posts.postid=IF(LEFT(parentposts.type, 1)='Q', parentposts.postid, parentposts.parentid)".
			" JOIN ^posts AS childposts ON parentposts.postid=IF(LEFT(childposts.type, 1)='Q', childposts.postid, childposts.parentid)".
			" WHERE childposts.postid IN (#)";
			
		$selectspec['arraykey']='opostid';
		$selectspec['arguments'][]=$postids;
		
		return $selectspec;
	}

	
	function qa_db_full_post_selectspec($voteuserid, $postid)
/*
	Return the selectspec to retrieve the full information for $postid, with the corresponding vote made by $voteuserid (if not null)
*/
	{
		$selectspec=qa_db_posts_basic_selectspec($voteuserid, true);

		$selectspec['source'].=" WHERE ^posts.postid=#";
		$selectspec['arguments'][]=$postid;
		$selectspec['single']=true;

		return $selectspec;
	}
	
	
	function qa_db_full_child_posts_selectspec($voteuserid, $parentid)
/*
	Return the selectspec to retrieve the full information for all posts whose parent is $parentid, with the
	corresponding vote made by $voteuserid (if not null)
*/
	{
		$selectspec=qa_db_posts_basic_selectspec($voteuserid, true);
		
		$selectspec['source'].=" WHERE ^posts.parentid=#";
		$selectspec['arguments'][]=$parentid;
		
		return $selectspec;
	}


	function qa_db_full_a_child_posts_selectspec($voteuserid, $questionid)
/*
	Return the selectspec to retrieve the full information for all posts whose parent is an answer which
	has $questionid as its parent, with the corresponding vote made by $voteuserid (if not null)
*/
	{
		$selectspec=qa_db_posts_basic_selectspec($voteuserid, true);
		
		$selectspec['source'].=" JOIN ^posts AS parents ON ^posts.parentid=parents.postid WHERE parents.parentid=# AND LEFT(parents.type, 1)='A'" ;
		$selectspec['arguments'][]=$questionid;
		
		return $selectspec;
	}
	

	function qa_db_post_parent_q_selectspec($postid)
/*
	Return the selectspec to retrieve the question for the parent of $postid (where $postid is of a follow-on question or comment),
	i.e. the parent of $questionid's parent if $questionid's parent is an answer, otherwise $questionid's parent itself.
*/
	{
		$selectspec=qa_db_posts_basic_selectspec();
		
		$selectspec['source'].=" WHERE ^posts.postid=(SELECT IF(LEFT(parent.type, 1)='A', parent.parentid, parent.postid) FROM ^posts AS child LEFT JOIN ^posts AS parent ON parent.postid=child.parentid WHERE child.postid=#)";
		$selectspec['arguments']=array($postid);
		$selectspec['single']=true;
		
		return $selectspec;
	}
	
	
	function qa_db_post_close_post_selectspec($questionid)
/*
	Return the selectspec to retrieve the post (either duplicate question or explanatory note) which has closed $questionid, if any
*/
	{
		$selectspec=qa_db_posts_basic_selectspec(null, true);
		
		$selectspec['source'].=" WHERE ^posts.postid=(SELECT closedbyid FROM ^posts WHERE postid=#)";
		$selectspec['arguments']=array($questionid);
		$selectspec['single']=true;

		return $selectspec;
	}
	
	
	function qa_db_post_meta_selectspec($postid, $title)
/*
	Return the selectspec to retrieve the metadata value for $postid with key $title
*/
	{
		$selectspec=array(
			'columns' => array('title', 'content'),
			'source' => "^postmetas WHERE postid=# AND ".(is_array($title) ? "title IN ($)" : "title=$"),
			'arguments' => array($postid, $title),
			'arrayvalue' => 'content',
		);
		
		if (is_array($title))
			$selectspec['arraykey']='title';
		else
			$selectspec['single']=true;
		
		return $selectspec;
	}
	
	
	function qa_db_related_qs_selectspec($voteuserid, $questionid, $count=null)
/*
	Return the selectspec to retrieve the most closely related questions to $questionid, with the corresponding vote
	made by $voteuserid (if not null). Return $count (if null, a default is used) questions. This works by looking for
	other questions which have title words, tag words or an (exact) category in common.
*/
	{
		$count=isset($count) ? min($count, QA_DB_RETRIEVE_QS_AS) : QA_DB_RETRIEVE_QS_AS;
		
		$selectspec=qa_db_posts_basic_selectspec($voteuserid);
		
		$selectspec['columns'][]='score';
		
		// added LOG(postid)/1000000 here to ensure ordering is deterministic even if several posts have same score
		
		$selectspec['source'].=" JOIN (SELECT postid, SUM(score)+LOG(postid)/1000000 AS score FROM ((SELECT ^titlewords.postid, LOG(#/titlecount) AS score FROM ^titlewords JOIN ^words ON ^titlewords.wordid=^words.wordid JOIN ^titlewords AS source ON ^titlewords.wordid=source.wordid WHERE source.postid=# AND titlecount<#) UNION ALL (SELECT ^posttags.postid, 2*LOG(#/tagcount) AS score FROM ^posttags JOIN ^words ON ^posttags.wordid=^words.wordid JOIN ^posttags AS source ON ^posttags.wordid=source.wordid WHERE source.postid=# AND tagcount<#) UNION ALL (SELECT ^posts.postid, LOG(#/^categories.qcount) FROM ^posts JOIN ^categories ON ^posts.categoryid=^categories.categoryid AND ^posts.type='Q' WHERE ^categories.categoryid=(SELECT categoryid FROM ^posts WHERE postid=#) AND ^categories.qcount<#)) x WHERE postid!=# GROUP BY postid ORDER BY score DESC LIMIT #) y ON ^posts.postid=y.postid";
		
		array_push($selectspec['arguments'], QA_IGNORED_WORDS_FREQ, $questionid, QA_IGNORED_WORDS_FREQ, QA_IGNORED_WORDS_FREQ,
			$questionid, QA_IGNORED_WORDS_FREQ, QA_IGNORED_WORDS_FREQ, $questionid, QA_IGNORED_WORDS_FREQ, $questionid, $count);
			
		$selectspec['sortdesc']='score';
			
		return $selectspec;
	}
	

	function qa_db_search_posts_selectspec($voteuserid, $titlewords, $contentwords, $tagwords, $handlewords, $handle, $start, $full=false, $count=null)
/*
	Return the selectspec to retrieve the top question matches for a search, with the corresponding vote made by
	$voteuserid (if not null) and including $full content or not. Return $count (if null, a default is used) questions
	starting from offset $start. The search is performed for any of $titlewords in the title, $contentwords in the
	content (of the question or an answer or comment for whom that is the antecedent question), $tagwords in tags, for
	question author usernames which match a word in $handlewords or which match $handle as a whole. The results also
	include a 'score' column based on the matching strength and post hotness, and a 'matchparts' column that tells us
	where the score came from (since a question could get weight from a match in the question itself, and/or weight from
	a match in its answers, comments, or comments on answers). The 'matchparts' is a comma-separated list of tuples
	matchtype:matchpostid:matchscore to be used with qa_search_set_max_match().
*/
	{
		$count=isset($count) ? min($count, QA_DB_RETRIEVE_QS_AS) : QA_DB_RETRIEVE_QS_AS;
		
		// add LOG(postid)/1000000 here to ensure ordering is deterministic even if several posts have same score
		// The score also gives a bonus for hot questions, where the bonus scales linearly with hotness. The hottest
		// question gets a bonus equivalent to a matching unique tag, and the least hot question gets zero bonus.

		$selectspec=qa_db_posts_basic_selectspec($voteuserid, $full);
		
		$selectspec['columns'][]='score';
		$selectspec['columns'][]='matchparts';
		$selectspec['source'].=" JOIN (SELECT questionid, SUM(score)+2*(LOG(#)*(^posts.hotness-(SELECT MIN(hotness) FROM ^posts WHERE type='Q'))/((SELECT MAX(hotness) FROM ^posts WHERE type='Q')-(SELECT MIN(hotness) FROM ^posts WHERE type='Q')))+LOG(questionid)/1000000 AS score, GROUP_CONCAT(CONCAT_WS(':', matchposttype, matchpostid, ROUND(score,3))) AS matchparts FROM (";
		$selectspec['sortdesc']='score';
		array_push($selectspec['arguments'], QA_IGNORED_WORDS_FREQ);
		
		$selectparts=0;
		
		if (!empty($titlewords)) {
			// At the indexing stage, duplicate words in title are ignored, so this doesn't count multiple appearances.
			
			$selectspec['source'].=($selectparts++ ? " UNION ALL " : "").
				"(SELECT postid AS questionid, LOG(#/titlecount) AS score, 'Q' AS matchposttype, postid AS matchpostid FROM ^titlewords JOIN ^words ON ^titlewords.wordid=^words.wordid WHERE word IN ($) AND titlecount<#)";

			array_push($selectspec['arguments'], QA_IGNORED_WORDS_FREQ, $titlewords, QA_IGNORED_WORDS_FREQ);
		}
		
		if (!empty($contentwords)) {
			// (1-1/(1+count)) weights words in content based on their frequency: If a word appears once in content
			// it's equivalent to 1/2 an appearance in the title (ignoring the contentcount/titlecount factor).
			// If it appears an infinite number of times, it's equivalent to one appearance in the title.
			// This will discourage keyword stuffing while still giving some weight to multiple appearances.
			// On top of that, answer matches are worth half a question match, and comment/note matches half again.
			
			$selectspec['source'].=($selectparts++ ? " UNION ALL " : "").
				"(SELECT questionid, (1-1/(1+count))*LOG(#/contentcount)*(CASE ^contentwords.type WHEN 'Q' THEN 1.0 WHEN 'A' THEN 0.5 ELSE 0.25 END) AS score, ^contentwords.type AS matchposttype, ^contentwords.postid AS matchpostid FROM ^contentwords JOIN ^words ON ^contentwords.wordid=^words.wordid WHERE word IN ($) AND contentcount<#)";

			array_push($selectspec['arguments'], QA_IGNORED_WORDS_FREQ, $contentwords, QA_IGNORED_WORDS_FREQ);
		}
		
		if (!empty($tagwords)) {
			// Appearances in the tag words count like 2 appearances in the title (ignoring the tagcount/titlecount factor).
			// This is because tags express explicit semantic intent, whereas titles do not necessarily.
			
			$selectspec['source'].=($selectparts++ ? " UNION ALL " : "").
				"(SELECT postid AS questionid, 2*LOG(#/tagwordcount) AS score, 'Q' AS matchposttype, postid AS matchpostid FROM ^tagwords JOIN ^words ON ^tagwords.wordid=^words.wordid WHERE word IN ($) AND tagwordcount<#)";

			array_push($selectspec['arguments'], QA_IGNORED_WORDS_FREQ, $tagwords, QA_IGNORED_WORDS_FREQ);
		}
		
		if (!empty($handlewords)) {
			if (QA_FINAL_EXTERNAL_USERS) {
				require_once QA_INCLUDE_DIR.'qa-app-users.php';
				
				$userids=qa_get_userids_from_public($handlewords);
				
				if (count($userids)) {
					$selectspec['source'].=($selectparts++ ? " UNION ALL " : "").
						"(SELECT postid AS questionid, LOG(#/qposts) AS score, 'Q' AS matchposttype, postid AS matchpostid FROM ^posts JOIN ^userpoints ON ^posts.userid=^userpoints.userid WHERE ^posts.userid IN ($) AND type='Q')";
					
					array_push($selectspec['arguments'], QA_IGNORED_WORDS_FREQ, $userids);
				}

			} else {
				$selectspec['source'].=($selectparts++ ? " UNION ALL " : "").
					"(SELECT postid AS questionid, LOG(#/qposts) AS score, 'Q' AS matchposttype, postid AS matchpostid FROM ^posts JOIN ^users ON ^posts.userid=^users.userid JOIN ^userpoints ON ^userpoints.userid=^users.userid WHERE handle IN ($) AND type='Q')";

				array_push($selectspec['arguments'], QA_IGNORED_WORDS_FREQ, $handlewords);
			}
		}
		
		if (strlen($handle)) { // to allow searching for multi-word usernames (only works if search query contains full username and nothing else)
			if (QA_FINAL_EXTERNAL_USERS) {
				$userids=qa_get_userids_from_public(array($handle));
				
				if (count($userids)) {
					$selectspec['source'].=($selectparts++ ? " UNION ALL " : "").
						"(SELECT postid AS questionid, LOG(#/qposts) AS score, 'Q' AS matchposttype, postid AS matchpostid FROM ^posts JOIN ^userpoints ON ^posts.userid=^userpoints.userid WHERE ^posts.userid=$ AND type='Q')";
					
					array_push($selectspec['arguments'], QA_IGNORED_WORDS_FREQ, reset($userids));
				}

			} else {
				$selectspec['source'].=($selectparts++ ? " UNION ALL " : "").
					"(SELECT postid AS questionid, LOG(#/qposts) AS score, 'Q' AS matchposttype, postid AS matchpostid FROM ^posts JOIN ^users ON ^posts.userid=^users.userid JOIN ^userpoints ON ^userpoints.userid=^users.userid WHERE handle=$ AND type='Q')";

				array_push($selectspec['arguments'], QA_IGNORED_WORDS_FREQ, $handle);
			}
		}
		
		if ($selectparts==0)
			$selectspec['source'].='(SELECT NULL as questionid, 0 AS score, NULL AS matchposttype, NULL AS matchpostid FROM ^posts WHERE postid IS NULL)';

		$selectspec['source'].=") x LEFT JOIN ^posts ON ^posts.postid=questionid GROUP BY questionid ORDER BY score DESC LIMIT #,#) y ON ^posts.postid=y.questionid";
		
		array_push($selectspec['arguments'], $start, $count);
		
		return $selectspec;
	}

	
	function qa_search_set_max_match($question, &$type, &$postid)
/*
	Processes the matchparts column in $question which was returned from a search performed via qa_db_search_posts_selectspec()
	Returns the id of the strongest matching answer or comment, or null if the question itself was the strongest match
*/
	{
		$type='Q';
		$postid=$question['postid'];
		$bestscore=null;
		
		$matchparts=explode(',', $question['matchparts']);
		foreach ($matchparts as $matchpart)
			if (sscanf($matchpart, '%1s:%f:%f', $matchposttype, $matchpostid, $matchscore)==3)
				if ( (!isset($bestscore)) || ($matchscore>$bestscore) ) {
					$bestscore=$matchscore;
					$type=$matchposttype;
					$postid=$matchpostid;
				}

		return null;
	}
	

	function qa_db_full_category_selectspec($slugsorid, $isid)
/*
	Return a selectspec to retrieve the full information on the category whose id is $slugsorid (if $isid is true),
	otherwise whose backpath matches $slugsorid
*/
	{
		if ($isid)
			$identifiersql='categoryid=#';
		else {
			$identifiersql='backpath=$';
			$slugsorid=qa_db_slugs_to_backpath($slugsorid);
		}
		
		return array(
			'columns' => array('categoryid', 'parentid', 'title', 'tags', 'qcount', 'content', 'backpath'),
			'source' => '^categories WHERE '.$identifiersql,
			'arguments' => array($slugsorid),
			'single' => 'true',
		);
	}

	
	function qa_db_category_nav_selectspec($slugsorid, $isid, $ispostid=false, $full=false)
/*
	Return the selectspec to retrieve ($full or not) info on the categories which "surround" the central category specified
	by $slugsorid, $isid and $ispostid. The "surrounding" categories include all categories (even unrelated) at the
	top level, any ancestors (at any level) of the category, the category's siblings and sub-categories (to one level).
	The central category is specified as follows. If $isid AND $ispostid then $slugsorid is the ID of a post with the category.
	Otherwise if $isid then $slugsorid is the category's own id. Otherwise $slugsorid is the full backpath of the category. 
*/
	{
		if ($isid) {
			if ($ispostid)
				$identifiersql='categoryid=(SELECT categoryid FROM ^posts WHERE postid=#)';
			else
				$identifiersql='categoryid=#';

		} else {
			$identifiersql='backpath=$';
			$slugsorid=qa_db_slugs_to_backpath($slugsorid);
		}
		
		$parentselects=array( // requires QA_CATEGORY_DEPTH=4
			'SELECT NULL AS parentkey', // top level
			'SELECT grandparent.parentid FROM ^categories JOIN ^categories AS parent ON ^categories.parentid=parent.categoryid JOIN ^categories AS grandparent ON parent.parentid=grandparent.categoryid WHERE ^categories.'.$identifiersql, // 2 gens up
			'SELECT parent.parentid FROM ^categories JOIN ^categories AS parent ON ^categories.parentid=parent.categoryid WHERE ^categories.'.$identifiersql,
				// 1 gen up
			'SELECT parentid FROM ^categories WHERE '.$identifiersql, // same gen
			'SELECT categoryid FROM ^categories WHERE '.$identifiersql, // gen below
		);
		
		$selectspec=array(
			'columns' => array('^categories.categoryid', '^categories.parentid', 'title' => '^categories.title', 'tags' => '^categories.tags', '^categories.qcount', '^categories.position'),
			'source' => '^categories JOIN ('.implode(' UNION ', $parentselects).') y ON ^categories.parentid<=>parentkey'.($full ? ' LEFT JOIN ^categories AS child ON child.parentid=^categories.categoryid GROUP BY ^categories.categoryid' : '').' ORDER BY ^categories.position',
			'arguments' => array($slugsorid, $slugsorid, $slugsorid, $slugsorid),
			'arraykey' => 'categoryid',
			'sortasc' => 'position',
		);
		
		if ($full) {
			$selectspec['columns']['childcount']='COUNT(child.categoryid)';
			$selectspec['columns']['content']='^categories.content';
			$selectspec['columns']['backpath']='^categories.backpath';
		}
		
		return $selectspec;
	}
	
	
	function qa_db_category_sub_selectspec($categoryid)
/*
	Return the selectspec to retrieve information on all subcategories of $categoryid (used for Ajax navigation of hierarchy)
*/
	{
		return array(
			'columns' => array('categoryid', 'title', 'tags', 'qcount', 'position'),
			'source' => '^categories WHERE parentid<=># ORDER BY position',
			'arguments' => array($categoryid),
			'arraykey' => 'categoryid',
			'sortasc' => 'position',
		);
	}
	
	
	function qa_db_slugs_to_category_id_selectspec($slugs)
/*
	Return the selectspec to retrieve a single category as specified by its $slugs (in order of hierarchy)
*/
	{
		return array(
			'columns' => array('categoryid'),
			'source' => '^categories WHERE backpath=$',
			'arguments' => array(qa_db_slugs_to_backpath($slugs)),
			'arrayvalue' => 'categoryid',
			'single' => true,
		);
	}
	
	
	function qa_db_pages_selectspec($onlynavin=null, $onlypageids=null)
/*
	Return the selectspec to retrieve the list of custom pages or links, ordered for display
*/
	{
		$selectspec=array(
			'columns' => array('pageid', 'title', 'flags', 'permit' => 'permit+0', 'nav', 'tags', 'position', 'heading'),
				// +0 required to work around MySQL bug where by permit value is mis-read as signed, e.g. -106 instead of 150
			'arraykey' => 'pageid',
			'sortasc' => 'position',
		);
		
		if (isset($onlypageids)) {
			$selectspec['source']='^pages WHERE pageid IN (#)';
			$selectspec['arguments']=array($onlypageids);
		
		} elseif (isset($onlynavin)) {
			$selectspec['source']='^pages WHERE nav IN ($) ORDER BY position';
			$selectspec['arguments']=array($onlynavin);

		} else
			$selectspec['source']='^pages ORDER BY position';
		
		return $selectspec;
	}
	
	
	function qa_db_widgets_selectspec()
/*
	Return the selectspec to retrieve the list of widgets, ordered for display
*/
	{
		return array(
			'columns' => array('widgetid', 'place', 'position', 'tags', 'title'),
			'source' => '^widgets ORDER BY position',
			'sortasc' => 'position',
		);
	}
	
	
	function qa_db_page_full_selectspec($slugorpageid, $ispageid)
/*
	Return the selectspec to retrieve the full information about a custom page
*/
	{
		return array(
			'columns' => array('pageid', 'title', 'flags', 'permit', 'nav', 'tags', 'position', 'heading', 'content'),
			'source' => '^pages WHERE '.($ispageid ? 'pageid' : 'tags').'=$',
			'arguments' => array($slugorpageid),
			'single' => true,
		);
	}
	
	
	function qa_db_tag_recent_qs_selectspec($voteuserid, $tag, $start, $full=false, $count=null)
/*
	Return the selectspec to retrieve the most recent questions with $tag, with the corresponding vote on those
	questions made by $voteuserid (if not null) and including $full content or not. Return $count (if null, a default is
	used) questions starting from $start.
*/
	{
		$count=isset($count) ? min($count, QA_DB_RETRIEVE_QS_AS) : QA_DB_RETRIEVE_QS_AS;
		
		require_once QA_INCLUDE_DIR.'qa-util-string.php';
		
		$selectspec=qa_db_posts_basic_selectspec($voteuserid, $full);
		
		// use two tests here - one which can use the index, and the other which narrows it down exactly - then limit to 1 just in case
		$selectspec['source'].=" JOIN (SELECT postid FROM ^posttags WHERE wordid=(SELECT wordid FROM ^words WHERE word=$ AND word=$ COLLATE utf8_bin LIMIT 1) ORDER BY postcreated DESC LIMIT #,#) y ON ^posts.postid=y.postid";
		array_push($selectspec['arguments'], $tag, qa_strtolower($tag), $start, $count);
		$selectspec['sortdesc']='created';
		
		return $selectspec;
	}

	
	function qa_db_tag_word_selectspec($tag)
/*
	Return the selectspec to retrieve the number of questions tagged with $tag (single value)
*/
	{
		return array(
			'columns' => array('wordid', 'word', 'tagcount'),
			'source' => '^words WHERE word=$',
			'arguments' => array($tag),
			'single' => true,
		);
	}

	
	function qa_db_user_recent_qs_selectspec($voteuserid, $identifier, $count=null, $start=0)
/*
	Return the selectspec to retrieve recent questions by the user identified by $identifier, where $identifier is a
	handle if we're using internal user management, or a userid if we're using external users. Also include the
	corresponding vote on those questions made by $voteuserid (if not null). Return $count (if null, a default is used)
	questions.
*/
	{
		$count=isset($count) ? min($count, QA_DB_RETRIEVE_QS_AS) : QA_DB_RETRIEVE_QS_AS;
		
		$selectspec=qa_db_posts_basic_selectspec($voteuserid);
		
		$selectspec['source'].=" WHERE ^posts.userid=".(QA_FINAL_EXTERNAL_USERS ? "$" : "(SELECT userid FROM ^users WHERE handle=$ LIMIT 1)")." AND type='Q' ORDER BY ^posts.created DESC LIMIT #,#";
		array_push($selectspec['arguments'], $identifier, $start, $count);
		$selectspec['sortdesc']='created';
		
		return $selectspec;
	}

	
	function qa_db_user_recent_a_qs_selectspec($voteuserid, $identifier, $count=null, $start=0)
/*
	Return the selectspec to retrieve the antecedent questions for recent answers by the user identified by $identifier
	(see qa_db_user_recent_qs_selectspec() comment), with the corresponding vote on those questions made by $voteuserid
	(if not null). Return $count (if null, a default is used) questions. The selectspec will also retrieve some
	information about the answers themselves, in columns named with the prefix 'o'.
*/
	{
		$count=isset($count) ? min($count, QA_DB_RETRIEVE_QS_AS) : QA_DB_RETRIEVE_QS_AS;
		
		$selectspec=qa_db_posts_basic_selectspec($voteuserid);
		
		qa_db_add_selectspec_opost($selectspec, 'aposts');
		
		$selectspec['columns']['oupvotes']='aposts.upvotes';
		$selectspec['columns']['odownvotes']='aposts.downvotes';
		$selectspec['columns']['onetvotes']='aposts.netvotes';
		
		$selectspec['source'].=" JOIN ^posts AS aposts ON ^posts.postid=aposts.parentid".
			" JOIN (SELECT postid FROM ^posts WHERE ".
			" userid=".(QA_FINAL_EXTERNAL_USERS ? "$" : "(SELECT userid FROM ^users WHERE handle=$ LIMIT 1)").
			" AND type='A' ORDER BY created DESC LIMIT #,#) y ON aposts.postid=y.postid WHERE ^posts.type='Q'";
			
		array_push($selectspec['arguments'], $identifier, $start, $count);
		$selectspec['sortdesc']='otime';
		
		return $selectspec;
	}

		
	function qa_db_user_recent_c_qs_selectspec($voteuserid, $identifier, $count=null)
/*
	Return the selectspec to retrieve the antecedent questions for recent comments by the user identified by $identifier
	(see qa_db_user_recent_qs_selectspec() comment), with the corresponding vote on those questions made by $voteuserid
	(if not null). Return $count (if null, a default is used) questions. The selectspec will also retrieve some
	information about the comments themselves, in columns named with the prefix 'o'.
*/
	{
		$count=isset($count) ? min($count, QA_DB_RETRIEVE_QS_AS) : QA_DB_RETRIEVE_QS_AS;
		
		$selectspec=qa_db_posts_basic_selectspec($voteuserid);
		
		qa_db_add_selectspec_opost($selectspec, 'cposts');
		
		$selectspec['source'].=" JOIN ^posts AS parentposts ON".
			" ^posts.postid=(CASE parentposts.type WHEN 'A' THEN parentposts.parentid ELSE parentposts.postid END)".
			" JOIN ^posts AS cposts ON parentposts.postid=cposts.parentid".
			" JOIN (SELECT postid FROM ^posts WHERE ".
			" userid=".(QA_FINAL_EXTERNAL_USERS ? "$" : "(SELECT userid FROM ^users WHERE handle=$ LIMIT 1)").
			" AND type='C' ORDER BY created DESC LIMIT #) y ON cposts.postid=y.postid WHERE ^posts.type='Q' AND parentposts.type IN ('Q', 'A')";
			
		array_push($selectspec['arguments'], $identifier, $count);
		$selectspec['sortdesc']='otime';
		
		return $selectspec;
	}
	
	
	function qa_db_user_recent_edit_qs_selectspec($voteuserid, $identifier, $count=null)
/*
	Return the selectspec to retrieve the antecedent questions for recently edited posts by the user identified by
	$identifier (see qa_db_user_recent_qs_selectspec() comment), with the corresponding vote on those questions made by
	$voteuserid (if not null). Return $count (if null, a default is used) questions. The selectspec will also retrieve
	some information about the edited posts themselves, in columns named with the prefix 'o'.
*/
	{
		$count=isset($count) ? min($count, QA_DB_RETRIEVE_QS_AS) : QA_DB_RETRIEVE_QS_AS;
		
		$selectspec=qa_db_posts_basic_selectspec($voteuserid);
		
		qa_db_add_selectspec_opost($selectspec, 'editposts', true);
		
		$selectspec['source'].=" JOIN ^posts AS parentposts ON".	
			" ^posts.postid=IF(LEFT(parentposts.type, 1)='Q', parentposts.postid, parentposts.parentid)".
			" JOIN ^posts AS editposts ON parentposts.postid=IF(LEFT(editposts.type, 1)='Q', editposts.postid, editposts.parentid)".
			" JOIN (SELECT postid FROM ^posts WHERE ".
			" lastuserid=".(QA_FINAL_EXTERNAL_USERS ? "$" : "(SELECT userid FROM ^users WHERE handle=$ LIMIT 1)").
			" AND type IN ('Q', 'A', 'C') ORDER BY updated DESC LIMIT #) y ON editposts.postid=y.postid ".
			" WHERE parentposts.type IN ('Q', 'A', 'C') AND ^posts.type IN ('Q', 'A', 'C')";
		
		array_push($selectspec['arguments'], $identifier, $count);
		$selectspec['sortdesc']='otime';
		
		return $selectspec;
	}
	
	
	function qa_db_popular_tags_selectspec($start, $count=null)
/*
	Return the selectspec to retrieve the most popular tags. Return $count (if null, a default is used) tags, starting
	from offset $start. The selectspec will produce a sorted array with tags in the key, and counts in the values.
*/
	{
		$count=isset($count) ? $count : QA_DB_RETRIEVE_TAGS;
		
		return array(
			'columns' => array('word', 'tagcount'),
			'source' => '^words JOIN (SELECT wordid FROM ^words WHERE tagcount>0 ORDER BY tagcount DESC LIMIT #,#) y ON ^words.wordid=y.wordid',
			'arguments' => array($start, $count),
			'arraykey' => 'word',
			'arrayvalue' => 'tagcount',
			'sortdesc' => 'tagcount',
		);
	}


	function qa_db_userfields_selectspec()
/*
	Return the selectspec to retrieve the list of user profile fields, ordered for display
*/
	{
		return array(
			'columns' => array('fieldid', 'title', 'content', 'flags', 'permit', 'position'),
			'source' => '^userfields',
			'arraykey' => 'title',
			'sortasc' => 'position',
		);
	}

	
	function qa_db_user_account_selectspec($useridhandle, $isuserid)
/*
	Return the selecspec to retrieve a single array with details of the account of the user identified by
	$useridhandle, which should be a userid if $isuserid is true, otherwise $useridhandle should be a handle.
*/
	{
		return array(
			'columns' => array(
				'^users.userid', 'passsalt', 'passcheck' => 'HEX(passcheck)', 'email', 'level', 'emailcode', 'handle',
				'created' => 'UNIX_TIMESTAMP(created)', 'sessioncode', 'sessionsource', 'flags', 'loggedin' => 'UNIX_TIMESTAMP(loggedin)',
				'loginip' => 'INET_NTOA(loginip)', 'written' => 'UNIX_TIMESTAMP(written)', 'writeip' => 'INET_NTOA(writeip)',
				'avatarblobid' => 'BINARY avatarblobid', // cast to BINARY due to MySQL bug which renders it signed in a union
				'avatarwidth', 'avatarheight', 'points', 'wallposts',
			),
			
			'source' => '^users LEFT JOIN ^userpoints ON ^userpoints.userid=^users.userid WHERE ^users.'.($isuserid ? 'userid' : 'handle').'=$',
			'arguments' => array($useridhandle),
			'single' => true,
		);
	}

	
	function qa_db_user_profile_selectspec($useridhandle, $isuserid)
/*
	Return the selectspec to retrieve all user profile information of the user identified by
	$useridhandle (see qa_db_user_account_selectspec() comment), as an array of [field] => [value]
*/
	{
		return array(
			'columns' => array('title', 'content'),
			'source' => '^userprofile WHERE userid='.($isuserid ? '$' : '(SELECT userid FROM ^users WHERE handle=$ LIMIT 1)'),
			'arguments' => array($useridhandle),
			'arraykey' => 'title',
			'arrayvalue' => 'content',
		);
	}
	
	
	function qa_db_user_notices_selectspec($userid)
/*
	Return the selectspec to retrieve all notices for the user $userid
*/
	{
		return array(
			'columns' => array('noticeid', 'content', 'format', 'tags', 'created' => 'UNIX_TIMESTAMP(created)'),
			'source' => '^usernotices WHERE userid=$ ORDER BY created',
			'arguments' => array($userid),
			'sortasc' => 'created',
		);
	}

	
	function qa_db_user_points_selectspec($identifier, $isuserid=QA_FINAL_EXTERNAL_USERS)
/*
	Return the selectspec to retrieve all columns from the userpoints table for the user identified by $identifier
	(see qa_db_user_recent_qs_selectspec() comment), as a single array
*/
	{
		return array(
			'columns' => array('points', 'qposts', 'aposts', 'cposts', 'aselects', 'aselecteds', 'qupvotes', 'qdownvotes', 'aupvotes', 'adownvotes', 'qvoteds', 'avoteds', 'upvoteds', 'downvoteds', 'bonus'),
			'source' => '^userpoints WHERE userid='.($isuserid ? '$' : '(SELECT userid FROM ^users WHERE handle=$ LIMIT 1)'),
			'arguments' => array($identifier),
			'single' => true,
		);
	}

	
	function qa_db_user_rank_selectspec($identifier, $isuserid=QA_FINAL_EXTERNAL_USERS)
/*
	Return the selectspec to calculate the rank in points of the user identified by $identifier
	(see qa_db_user_recent_qs_selectspec() comment), as a single value
*/
	{
		return array(
			'columns' => array('rank' => '1+COUNT(*)'),
			'source' => '^userpoints WHERE points>COALESCE((SELECT points FROM ^userpoints WHERE userid='.($isuserid ? '$' : '(SELECT userid FROM ^users WHERE handle=$ LIMIT 1)').'), 0)',
			'arguments' => array($identifier),
			'arrayvalue' => 'rank',
			'single' => true,
		);
	}

	
	function qa_db_top_users_selectspec($start, $count=null)
/*
	Return the selectspec to get the top scoring users, with handles if we're using internal user management. Return
	$count (if null, a default is used) users starting from the offset $start.
*/
	{
		$count=isset($count) ? min($count, QA_DB_RETRIEVE_USERS) : QA_DB_RETRIEVE_USERS;
		
		if (QA_FINAL_EXTERNAL_USERS)
			return array(
				'columns' => array('userid', 'points'),
				'source' => '^userpoints ORDER BY points DESC LIMIT #,#',
				'arguments' => array($start, $count),
				'arraykey' => 'userid',
				'sortdesc' => 'points',
			);
		
		else
			return array(
				'columns' => array('^users.userid', 'handle', 'points', 'flags', '^users.email', 'avatarblobid' => 'BINARY avatarblobid', 'avatarwidth', 'avatarheight'),
				'source' => '^users JOIN (SELECT userid FROM ^userpoints ORDER BY points DESC LIMIT #,#) y ON ^users.userid=y.userid JOIN ^userpoints ON ^users.userid=^userpoints.userid',
				'arguments' => array($start, $count),
				'arraykey' => 'userid',
				'sortdesc' => 'points',
			);
	}

	
	function qa_db_users_from_level_selectspec($level)
/*
	Return the selectspec to get information about users at a certain privilege level or higher
*/
	{
		return array(
			'columns' => array('^users.userid', 'handle', 'level'),
			'source' => '^users WHERE level>=# ORDER BY level DESC',
			'arguments' => array($level),
			'sortdesc' => 'level',
		);
	}

	
	function qa_db_users_with_flag_selectspec($flag)
/*
	Return the selectspec to get information about users with the $flag bit set (unindexed query)
*/
	{
		return array(
			'columns' => array('^users.userid', 'handle', 'flags', 'level'),
			'source' => '^users WHERE (flags & #)',
			'arguments' => array($flag),
		);
	}
	
	
	function qa_db_recent_messages_selectspec($fromidentifier, $fromisuserid, $toidentifier, $toisuserid, $count=null, $start=0)
/*
	If $fromidentifier is not null, return the selectspec to get recent private messages which have been sent from
	the user identified by $fromidentifier+$fromisuserid to the user identified by $toidentifier+$toisuserid (see
	qa_db_user_recent_qs_selectspec() comment). If $fromidentifier is null, then get recent wall posts
	for the user identified by $toidentifier+$toisuserid. Return $count (if null, a default is used) messages.
*/
	{
		$count=isset($count) ? min($count, QA_DB_RETRIEVE_MESSAGES) : QA_DB_RETRIEVE_MESSAGES;
		
		return array(
			'columns' => array(
				'messageid', 'fromuserid', 'touserid', 'content', 'format', 'created' => 'UNIX_TIMESTAMP(^messages.created)',
				'fromflags' => '^users.flags', 'fromlevel' => '^users.level', 'fromemail' => '^users.email', 'fromhandle' => '^users.handle',
				'fromavatarblobid' => 'BINARY ^users.avatarblobid', // cast to BINARY due to MySQL bug which renders it signed in a union
				'fromavatarwidth' => '^users.avatarwidth', 'fromavatarheight' => '^users.avatarheight',
			),

			'source' => '^messages LEFT JOIN ^users ON fromuserid=^users.userid WHERE '.(isset($fromidentifier)
				? ('fromuserid='.($fromisuserid ? "$" : "(SELECT userid FROM ^users WHERE handle=$ LIMIT 1)")." AND type='PRIVATE'")
				: "type='PUBLIC'"
			).' AND touserid='.($toisuserid ? "$" : "(SELECT userid FROM ^users WHERE handle=$ LIMIT 1)").' ORDER BY ^messages.created DESC LIMIT #,#',

			'arguments' => isset($fromidentifier) ? array($fromidentifier, $toidentifier, $start, $count) : array($toidentifier, $start, $count),
			'arraykey' => 'messageid',
			'sortdesc' => 'created',
		);
	}
	
	
	function qa_db_is_favorite_selectspec($userid, $entitytype, $identifier)
/*
	Return the selectspec to retrieve whether or not $userid has favorited entity $entitytype identifier by $identifier.
	The $identifier should be a handle, word, backpath or postid for users, tags, categories and questions respectively.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-updates.php';
		
		$selectspec=array(
			'columns' => array('flags' => 'COUNT(*)'),
			'source' => '^userfavorites WHERE userid=$ AND entitytype=$',
			'arrayvalue' => 'flags',
			'single' => true,
		);
		
		switch ($entitytype) {
			case QA_ENTITY_USER:
				$selectspec['source'].=' AND entityid=(SELECT userid FROM ^users WHERE handle=$ LIMIT 1)';
				break;

			case QA_ENTITY_TAG:
				$selectspec['source'].=' AND entityid=(SELECT wordid FROM ^words WHERE word=$ LIMIT 1)';
				break;

			case QA_ENTITY_CATEGORY:
				$selectspec['source'].=' AND entityid=(SELECT categoryid FROM ^categories WHERE backpath=$ LIMIT 1)';
				$identifier=qa_db_slugs_to_backpath($identifier);
				break;
			
			default:
				$selectspec['source'].=' AND entityid=$';
				break;
		}
		
		$selectspec['arguments']=array($userid, $entitytype, $identifier);

		return $selectspec;
	}
	
	
	function qa_db_user_favorite_qs_selectspec($userid)
/*
	Return the selectspec to retrieve an array of $userid's favorited questions, with the usual information.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-updates.php';
		
		$selectspec=qa_db_posts_basic_selectspec($userid);
		
		$selectspec['source'].=" JOIN ^userfavorites AS selectfave ON ^posts.postid=selectfave.entityid WHERE selectfave.userid=$ AND selectfave.entitytype=$ AND ^posts.type='Q'";
		array_push($selectspec['arguments'], $userid, QA_ENTITY_QUESTION);
		$selectspec['sortdesc']='created';
		
		return $selectspec;
	}
	
	
	function qa_db_user_favorite_users_selectspec($userid)
/*
	Return the selectspec to retrieve an array of $userid's favorited users, with information about those users' accounts.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-updates.php';
		
		return array(
			'columns' => array('^users.userid', 'handle', 'points', 'flags', '^users.email', 'avatarblobid' => 'BINARY avatarblobid', 'avatarwidth', 'avatarheight'),
			'source' => "^users JOIN ^userpoints ON ^users.userid=^userpoints.userid JOIN ^userfavorites ON ^users.userid=^userfavorites.entityid WHERE ^userfavorites.userid=$ AND ^userfavorites.entitytype=$",
			'arguments' => array($userid, QA_ENTITY_USER),
			'sortasc' => 'handle',
		);
	}
	
	
	function qa_db_user_favorite_tags_selectspec($userid)
/*
	Return the selectspec to retrieve an array of $userid's favorited tags, with information about those tags.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-updates.php';
		
		return array(
			'columns' => array('word', 'tagcount'),
			'source' => "^words JOIN ^userfavorites ON ^words.wordid=^userfavorites.entityid WHERE ^userfavorites.userid=$ AND ^userfavorites.entitytype=$",
			'arguments' => array($userid, QA_ENTITY_TAG),
			'sortdesc' => 'tagcount',
		);
	}
	
	
	function qa_db_user_favorite_categories_selectspec($userid)
/*
	Return the selectspec to retrieve an array of $userid's favorited categories, with information about those categories.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-updates.php';
		
		return array(
			'columns' => array('categoryid', 'title', 'tags', 'qcount', 'backpath', 'content'),
			'source' => "^categories JOIN ^userfavorites ON ^categories.categoryid=^userfavorites.entityid WHERE ^userfavorites.userid=$ AND ^userfavorites.entitytype=$",
			'arguments' => array($userid, QA_ENTITY_CATEGORY),
			'sortasc' => 'title',
		);
	}
	
	
	function qa_db_user_favorite_non_qs_selectspec($userid)
/*
	Return the selectspec to retrieve information about all a user's favorited items except the questions. Depending on
	the type of item, the array for each item will contain a userid, category backpath or tag word.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-updates.php';
		
		return array(
			'columns' => array('type' => 'entitytype', 'userid' => 'IF (entitytype=$, entityid, NULL)', 'categorybackpath' => '^categories.backpath', 'tags' => '^words.word'),
			'source' => '^userfavorites LEFT JOIN ^words ON entitytype=$ AND wordid=entityid LEFT JOIN ^categories ON entitytype=$ AND categoryid=entityid WHERE userid=$ AND entitytype!=$',
			'arguments' => array(QA_ENTITY_USER, QA_ENTITY_TAG, QA_ENTITY_CATEGORY, $userid, QA_ENTITY_QUESTION),
		);
	}	
	

	function qa_db_user_updates_selectspec($userid, $forfavorites=true, $forcontent=true)
/*
	Return the selectspec to retrieve the list of recent updates for $userid. Set $forfavorites to whether this should
	include updates on the user's favorites and $forcontent to whether it should include responses to user's content.
	This combines events from both the user's stream and the the shared stream for any entities which the user has
	favorited and which no longer post to user streams (see long comment in qa-db-favorites.php).
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-updates.php';
		
		$selectspec=qa_db_posts_basic_selectspec($userid);
		
		$nonesql=qa_db_argument_to_mysql(QA_ENTITY_NONE, true);
		
		$selectspec['columns']['obasetype']='LEFT(updateposts.type, 1)';
		$selectspec['columns']['oupdatetype']='fullevents.updatetype';
		$selectspec['columns']['ohidden']="INSTR(updateposts.type, '_HIDDEN')>0";
		$selectspec['columns']['opostid']='fullevents.lastpostid';
		$selectspec['columns']['ouserid']='fullevents.lastuserid';
		$selectspec['columns']['otime']='UNIX_TIMESTAMP(fullevents.updated)';
		$selectspec['columns']['opersonal']='fullevents.entitytype='.$nonesql;
		$selectspec['columns']['oparentid']='updateposts.parentid';

		qa_db_add_selectspec_ousers($selectspec, 'eventusers', 'eventuserpoints');
			
		if ($forfavorites) { // life is hard
			$selectspec['source'].=' JOIN '.
				"(SELECT entitytype, questionid, lastpostid, updatetype, lastuserid, updated FROM ^userevents WHERE userid=$".
				($forcontent ? '' : " AND entitytype!=".$nonesql).
				" UNION SELECT ^sharedevents.entitytype, questionid, lastpostid, updatetype, lastuserid, updated FROM ^sharedevents JOIN ^userfavorites ON ^sharedevents.entitytype=^userfavorites.entitytype AND ^sharedevents.entityid=^userfavorites.entityid AND ^userfavorites.nouserevents=1 WHERE userid=$) fullevents ON ^posts.postid=fullevents.questionid";

			array_push($selectspec['arguments'], $userid, $userid);
		
		} else { // life is easy
			$selectspec['source'].=" JOIN ^userevents AS fullevents ON ^posts.postid=fullevents.questionid AND fullevents.userid=$ AND fullevents.entitytype=".$nonesql;
			$selectspec['arguments'][]=$userid;
		}
		
		$selectspec['source'].=
			" JOIN ^posts AS updateposts ON updateposts.postid=fullevents.lastpostid".
			" AND (updateposts.type IN ('Q', 'A', 'C') OR fullevents.entitytype=".$nonesql.")".
			" AND (^posts.selchildid=fullevents.lastpostid OR NOT fullevents.updatetype<=>$) AND ^posts.type IN ('Q', 'Q_HIDDEN')".
			(QA_FINAL_EXTERNAL_USERS ? '' : ' LEFT JOIN ^users AS eventusers ON fullevents.lastuserid=eventusers.userid').
			' LEFT JOIN ^userpoints AS eventuserpoints ON fullevents.lastuserid=eventuserpoints.userid';
		$selectspec['arguments'][]=QA_UPDATE_SELECTED;

		unset($selectspec['arraykey']); // allow same question to be retrieved multiple times

		$selectspec['sortdesc']='otime';
			
		return $selectspec;
	}
	
	
	function qa_db_user_limits_selectspec($userid)
/*
	Return the selectspec to retrieve all of the per-hour activity limits for user $userid
*/
	{
		return array(
			'columns' => array('action', 'period', 'count'),
			'source' => '^userlimits WHERE userid=$',
			'arguments' => array($userid),
			'arraykey' => 'action',
		);
	}
	
	
	function qa_db_ip_limits_selectspec($ip)
/*
	Return the selectspec to retrieve all of the per-hour activity limits for ip address $ip
*/
	{
		return array(
			'columns' => array('action', 'period', 'count'),
			'source' => '^iplimits WHERE ip=COALESCE(INET_ATON($), 0)',
			'arguments' => array($ip),
			'arraykey' => 'action',
		);
	}
	
	
	function qa_db_user_levels_selectspec($identifier, $isuserid=QA_FINAL_EXTERNAL_USERS, $full=false)
/*
	Return the selectspec to retrieve all of the context specific (currently per-categpry) levels for the user identified by
	$identifier, which is treated as a userid if $isuserid is true, otherwise as a handle. Set $full to true to obtain extra
	information about these contexts (currently, categories).
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-updates.php';

		$selectspec=array(
			'columns' => array('entityid', 'entitytype', 'level'),
			'source' => '^userlevels'.($full ? ' LEFT JOIN ^categories ON ^userlevels.entitytype=$ AND ^userlevels.entityid=^categories.categoryid' : '').' WHERE userid='.($isuserid ? '$' : '(SELECT userid FROM ^users WHERE handle=$ LIMIT 1)'),
			'arguments' => array($identifier),
		);
		
		if ($full) {
			array_push($selectspec['columns'], 'title', 'backpath');
			array_unshift($selectspec['arguments'], QA_ENTITY_CATEGORY);
		}
		
		return $selectspec;
	}
	
	
/*
	Omit PHP closing tag to help avoid accidental output
*/