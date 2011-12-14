<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-favorites.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Database-level access to userfavorites table


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


/*
	Why do we have two types of event streams, shared (in qa_sharedevents) and user-specific (in qa_userevents)?

	An event stream is defined as the set of events which are thrown off ("published") by a particular entity. For
	example, it could include the activity on a particular question, or the activity by a particular user.

	We have an arbitrary many-to-many mapping between event streams and users subscribed to those streams. Over time, a
	particularly popular event stream could accumulate thousands of subscribers. Similarly, over time, a particularly
	hyperactive user could end up subscribing to thousands of event streams.

	If we stored a single copy of each event stream in the database, publishing an event would be very fast. However
	retrieving a hyperactive user's update page would be extremely slow, because it would require retrieving all the
	streams they are subscribed to, and finding the globally most recent (e.g.) 50 events across all those streams.

	So instead we could store a list of news updates for each user. In this case, retrieving a user's update page would
	be very fast. However, recording an event for a popular stream could become extremely slow, since it would have to
	be copied for every user subscribed to the stream.

	The standard solution to these "publish and subscribe" situations is a message-passing architecture. That's what
	Twitter et al use. However that's not a viable option here, because it requires a process to be running in the
	background to manage the queuing and transport of these messages from publishers (event streams) to subscribers
	(users' lists of news updates). While we could have a cron-style process to manage this, I'm avoiding it for as long
	as possible since it complicates setup. It also means there can be delays in updating users' news feeds.
	
	So instead we adopt a hybrid approach. For each event created in an entity's stream, we record a single copy of that
	event in the entity's stream in the qa_sharedevents table. In addition, by default, we place a copy of that event into
	the list of news updates for each user subscribed to the stream, via the qa_userevents table.
	
	However, if there are more than a certain number of subscribers to the stream, we skip this second step, i.e. we
	only record one copy in the qa_sharedevents table. This limits the cost of publishing an event.

	When we generate a user's list of recent updates, we of course retrieve the list of news updates for that user from
	qa_userevents. However we also check to see whether that user is subscribed to any event streams for which updates
	are no longer posted into the user's own list, because the stream has too many subscribers. For each of these
	popular streams, we also retrieve the stream's events from qa_sharedevents. Since users are only likely to be
	subscribed to a small number of popular streams, this limits the cost of retrieving the news updates.

	(Having a shared event stream helps us another way. When a user subscribes to a stream, they can immediately have
	recent events from that stream copied into their list of news updates.)

	Note that this approach isn't aimed at reducing the total cost of keeping all users up-to-date on all events, but
	rather ensuring that no individual operation (posting an event or retrieving a user's list of updates) takes too
	long, since that would turn into a very slow response time for the corresponding HTTP request.
	
	What should we use for the threshold T, so that if a stream has more than T subscribers, its events are only
	recorded in the shared stream? One approach is as follows:
	
	[this ignores stream length and truncation, which are constant factors]

	T = our threshold
	M = the maximum number of streams subscribed to by any user
	P(x) = the probability that a particular stream has more than x subscribers
	C1 = maximum cost of adding an event = maximum number of streams to which event must be added = O(T)
	C2 = maximum cost of retrieving news updates = maximum number of shared streams to be combined = O(M * P(T))

	[we assume that the chance a particular user is subscribed to a particular stream is independent of the user]

	Now if we assume the power law, aka 80/20 rule, we can estimate that P(T) is proportional to 1/T, so that:

	C2 = O(M / T)

	To minimize the maximum of these two complexity maxima, we want to equate them, so that:

	T = M/T => T=sqrt(M)

	So we could keep track of the maximum number of event streams any user is subscribed to, and use its square root.
	Instead of that, we adopt an on-the-fly approach. We start by setting T=10 (see 'max_copy_user_updates' in
	qa-app-options.php) since it's no big deal to write 10 rows to a table. Recall that whenever an event stream gets
	more than T subscribers, we switch those subscribers over to the shared stream. At that point, we check the maximum
	number of (total) shared streams that any of those users are subscribed to. If this is above T, that means that our
	maximum cost of retrieving a list of news updates is starting to go past our maximum cost of recording an event. So
	we rebalance things out by increasing T as appropriate, for use in future cases.
	
	Note that once an event stream has made this switch, to be accessed only via its shared stream, we don't go back.
*/


	function qa_db_favorite_create($userid, $entitytype, $entityid)
/*
	Add the entity $entitytype with $entityid to the favorites list of $userid. Handles switching streams across from
	per-user to per-entity based on how many other users have favorited the entity (see long explanation above). If
	appropriate, it also adds recent events from that entity to the user's event stream.
*/
	{
		$threshold=qa_opt('max_copy_user_updates'); // if this many users subscribe to it, create a shared stream

	//	Add in the favorite for this user, unshared events at first (will be switched later if appropriate)
	
		qa_db_query_sub(
			'INSERT IGNORE INTO ^userfavorites (userid, entitytype, entityid, nouserevents) VALUES ($, $, #, 0)',
			$userid, $entitytype, $entityid
		);
		
	//	See whether this entity already has another favoriter who uses its shared event stream
	
		$useshared=qa_db_read_one_value(qa_db_query_sub(
			'SELECT COUNT(*) FROM ^userfavorites WHERE entitytype=$ AND entityid=# AND nouserevents>0 LIMIT 1',
			$entitytype, $entityid
		));
			
	//	If not, check whether it's time to switch it over to a shared stream
		
		if (!$useshared) {
			$favoriters=qa_db_read_one_value(qa_db_query_sub(
				'SELECT COUNT(*) FROM ^userfavorites WHERE entitytype=$ AND entityid=# LIMIT #',
				$entitytype, $entityid, $threshold
			));
			
			$useshared=($favoriters >= $threshold);
		}
	
	//	If we're going to use the shared stream...
		
		if ($useshared) {

		//	... for all the people for whom we're switching this to a shared stream, find the highest number of other shared streams they have
		
			$maxshared=qa_db_read_one_value(qa_db_query_sub(
				'SELECT MAX(c) FROM (SELECT COUNT(*) AS c FROM ^userfavorites AS shared JOIN ^userfavorites AS unshared '.
				'WHERE shared.userid=unshared.userid AND shared.nouserevents>0 AND unshared.entitytype=$ AND unshared.entityid=# AND unshared.nouserevents=0 GROUP BY shared.userid) y',
				$entitytype, $entityid
			));
		
		//	... if this number is greater than our current 'max_copy_user_updates' threshold, increase that threshold (see long comment above)
		
			if (($maxshared+1)>$threshold)
				qa_opt('max_copy_user_updates', $maxshared+1);
		
		//	... now switch all unshared favoriters (including this new one) over to be shared
	
			qa_db_query_sub(
				'UPDATE ^userfavorites SET nouserevents=1 WHERE entitytype=$ AND entityid=# AND nouserevents=0',
				$entitytype, $entityid
			);
			
	//	Otherwise if we're going to record this in user-specific streams ...

		} else {
			require_once QA_INCLUDE_DIR.'qa-db-events.php';
			
		//	... copy across recent events from the shared stream

			qa_db_query_sub(
				'INSERT INTO ^userevents (userid, entitytype, entityid, questionid, lastpostid, updatetype, lastuserid, updated) '.
				'SELECT #, entitytype, entityid, questionid, lastpostid, updatetype, lastuserid, updated FROM '.
				'^sharedevents WHERE entitytype=$ AND entityid=#',
				$userid, $entitytype, $entityid
			);
			
		//	... and truncate the user's stream as appropriate
		
			qa_db_user_events_truncate($userid);
		}
	}
	
	
	function qa_db_favorite_delete($userid, $entitytype, $entityid)
/*
	Delete the entity $entitytype with $entityid from the favorites list of $userid, removing any corresponding events
	from the user's stream.
*/
	{
		qa_db_query_sub(
			'DELETE FROM ^userfavorites WHERE userid=$ AND entitytype=$ AND entityid=#',
			$userid, $entitytype, $entityid
		);
		
		qa_db_query_sub(
			'DELETE FROM ^userevents WHERE userid=$ AND entitytype=$ AND entityid=#',
			$userid, $entitytype, $entityid
		);
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/