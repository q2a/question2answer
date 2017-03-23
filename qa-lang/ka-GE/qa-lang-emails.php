<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/qa-lang-emails.php
	Description: Language phrases for email notifications
    TRANSLATED INTO GEORGIAN BY GIORGI TSOMAIA

Done:100%
Checked:0%
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

	return array(
		'a_commented_body' => "თქვენს პასუხს ^site_title -ზე აქვს ახალი კომენტარი მომხმარებლისგან ^c_handle:\n\n^open^c_content^close\n\nთქვენი პასუხი იყო:\n\n^open^c_context^close\n\nშეგიძლიათ უპასუხოთ თქვენი კომენტარით:\n\n^url\n\nThank you,\n\n^site_title",
		'a_commented_subject' => 'თქვენს ^site_title პასუხს ახალი კომენტარი აქვს',

		'a_followed_body' => "თქვენს პასუხს ^site_title -ზე აქვს ახალი კავშირში მყოფი შეკითხვა მომხმარებლისგან ^q_handle:\n\n^open^q_title^close\n\nთქვენი პასუხი იყო:\n\n^open^a_content^close\n\nპასუხის გასაცემად დააჭირეთ ქვემოთ:\n\n^url\n\nThank you,\n\n^site_title",
		'a_followed_subject' => 'თქვენს პასუხს ^site_title -ზე აქვს ახალი კავშირში მყოფი შეკითხვა',

		'a_selected_body' => "გილოცავთ! თქვენი პასუხი ^site_title -ზე მოინიშნა ^s_handle -ს მიერ:\n\n^open^a_content^close\n\nშეკითხვა იყო:\n\n^open^q_title^close\n\nთქვენი პასუხის სანახავად დააჭირეთ ქვემოთ:\n\n^url\n\nგმადლობთ,\n\n^site_title",
		'a_selected_subject' => 'თქვენი პასუხი ^site_title -ზე შერჩეულია!',

		'c_commented_body' => "^site_title -ზე თქვენი კომენტარის შემდეგ დაემატა ახალი კომენტარი მომხმარებლისგან ^c_handle:\n\n^open^c_content^close\n\nდისკუსია იყო შემდეგი:\n\n^open^c_context^close\n\nშეგიძლიათ უპასუხოთ ახალი კომენტარით:\n\n^url\n\nგმადლობთ,\n\n^site_title",
		'c_commented_subject' => '^site_title თქვენს კომენტარს ახალი პასუხი აქვს',

		'confirm_body' => "^site_title -ზე თქვენი ელ-ფოსტის დასადასტურებლად გთხოვთ დააჭიროთ ქვემოთ მოცემულ ბმულს.\n\n^url\n\nგმადლობთ,\n^site_title",
		'confirm_subject' => '^site_title - ელ-ფოსტის დადასტურება',

		'feedback_body' => "კომენტარი, მესიჯი:\n^message\n\nსახელი:\n^name\n\nელ-ფოსტა:\n^email\n\nწინა გვერდი:\n^previous\n\nმომხმარებელი:\n^url\n\nIP მისამართი:\n^ip\n\nბრაუზერი:\n^browser",
		'feedback_subject' => '^ უკუკავშირი',

		'flagged_body' => "^p_handle -ს პოსტმა მიიღო ^flags:\n\n^open^p_context^close\n\nპოსტის სანახავად დააჭირეთ ქვემოთ მოცემულ ბმულს:\n\n^url\n\n\nყველა მონიშნული პოსტის სანახავად დააჭირეთ ბმულს:\n\n^a_url\n\n\nგმადლობთ,\n\n^site_title",
		'flagged_subject' => '^site_title ახალი მონიშნული პოსტი',

		'moderate_body' => "^p_handle -ს პოსტი საჭიროებს თქვენს დასტურს:\n\n^open^p_context^close\n\nდასადასტურებლად ან უარსაყოფად დააჭირეთ ბმულს:\n\n^url\n\n\nრიგში მყოფი ყველა პოსტის სანახავად დააჭირეთ ქვემოთ მოცემულ ბმულს:\n\n^a_url\n\n\nგმადლობთ,\n\n^site_title",
		'moderate_subject' => '^site_title მოდერაცია',

		'new_password_body' => "თქვენი ახალი პაროლი ^site_title -ზე.\nპაროლი: ^password\n\nრეკომენდებულია, რომ შეცვალოთ ეს პაროლი შესვლისთანავე.\n\nგმადლობთ,\n^site_title\n^url",
		'new_password_subject' => '^site_title - თქვენი ახალი პაროლი',

		'private_message_body' => "თქვენ მიიღეთ პირადი მესიჯი საიტზე - ^site_title მომხმარებლისგან - ^f_handle:\n\n^open^message^close\n\n^moreგმადლობთ,\n\n^site_title\n\n\nპირადი მესიჯების დასაბლოკად გადადით თქვენს ექაუნთზე:\n^a_url",
		'private_message_info' => "მეტი ინფორმაცია ^f_handle -ს შესახებ:\n\n^url\n\n",
		'private_message_reply' => "დააჭირეთ ქვემოთ მოცემულ ბმულს რათა უპასუხოთ ^f_handle -ს პირად მესიჯს:\n\n^url\n\n",
		'private_message_subject' => 'მესიჯი მომხმარებლისგან - ^f_handle საიტზე - ^site_title',

		'q_answered_body' => "თქვენს შეკითხვას ^site_title -ზე უპასუხა მომხმარებელმა ^a_handle:\n\n^open^a_content^close\n\nთქვენი შეკითხვა იყო:\n\n^open^q_title^close\n\nთუ მოგწონთ ეს პასუხი, შეგიძლიათ მონიშნოთ იგი როგორც საუკეთესო პასუხი:\n\n^url\n\nგმადლობთ,\n\n^site_title",
		'q_answered_subject' => 'თქვენს შეკითხვას ^site_title -ზე უპასუხეს',

		'q_commented_body' => "თქვენს შეკითხვას ^site_title -ზე აქვს ახალი კომენტარი მომხმარებლისგან ^c_handle:\n\n^open^c_content^close\n\nთქვენი შეკითხვა იყო:\n\n^open^c_context^close\n\nშეგიძლიათ უპასუხოთ კომენტარით:\n\n^url\n\nგმადლობთ,\n\n^site_title",
		'q_commented_subject' => 'თქვენს შეკითხვას ^site_title -ზე ახალი კომენტარი აქვს',

		'q_posted_body' => "დასმულია ახალი შეკითხვა მომხმარებლისგან ^q_handle:\n\n^open^q_title\n\n^q_content^close\n\nშეკითხვის სანახავად დააჭირეთ ქვემოთ მოცემულ ბმულს:\n\n^url\n\nგმადლობთ,\n\n^site_title",
		'q_posted_subject' => '^site_title ახალი შეკითხვა',

		'remoderate_body' => "მომხმარებლის ^p_handle მიერ რედაქტირებული პოსტი საჭიროებს თქვენს ხელახალ დასტურს:\n\n^open^p_context^close\n\nრედაქტირებულის პოსტის დასადასტურებლად ან დასამალად დააჭირეთ ქვემოთ მოცემულ ბმულს:\n\n^url\n\n\nრიგში მდგომი ყველა პოსტის სანახავად დააჭირეთ ქვემოთ მოცემულ ბმულს:\n\n^a_url\n\n\nThank you,\n\n^site_title",
		'remoderate_subject' => '^site_title მოდერაცია',

		'reset_body' => "^site_title -ზე პაროლის აღსადგენად დააჭირეთ ქვემოთ მოცემულ ბმულს.\n\n^url\n\nან შეიყვანეთ ქვემოთ მოცემული კოდი შესაბამის ველში.\n\nკოდი: ^code\n\nთუ თქვენ არ მოგითხოვიათ პაროლის აღდგენა მაშინ ყურადღებას ნუ მიაქცევთ ამ მესიჯს.\n\nგმადლობთ,\n^site_title",
		'reset_subject' => '^site_title - დავიწყებული პაროლის აღდგენა',

		'to_handle_prefix' => "^,\n\n",

		'u_registered_body' => "დარეგისტრირდა ახალი მომხმარებელი ^u_handle.\n\nმისი პროფილის სანახავად დააჭირეთ ქვემოთ მოცემულ ბმულს:\n\n^url\n\nგმადლობთ,\n\n^site_title",
		'u_registered_subject' => '^site_title დარეგისტრირდა ახალი მომხმარებელი',
		'u_to_approve_body' => "დარეგისტრირდა ახალი მომხმარებელი როგორც ^u_handle.\n\nდადასტურებისთვის დააჭირეთ ქვემოთ მოცემულ ბმულს:\n\n^url\n\nდააჭირეთ ქვემოთ რათა ნახოთ დასტურის მოლოდინში მყოფი ყველა მომხმარებელი:\n\n^a_url\n\nგმადლობთ,\n\n^site_title",

		'u_approved_body' => "თქვენ შეგიძლიათ ნახოთ თქვენი ახალი პროფილი ამ ბმულზე:\n\n^url\n\nგმადლობთ,\n\n^site_title",
		'u_approved_subject' => 'თქვენი მომხმარებელი ^site_title -ზე დადასტურებულია',

		'wall_post_body' => "^f_handle დაპოსტა თქვენს კედელზე ^site_title -ზე:\n\n^open^post^close\n\nშეგიძლიათ უპასუხოთ აქედან:\n\n^url\n\nგმადლობთ,\n\n^site_title",
		'wall_post_subject' => 'დაპოსტეთ თქვენს კედელზე ^site_title -ზე',

		'welcome_body' => "გმადლობთ ^site_title -ზე რეგისტრაციისათვის.\n\n^custom^confirm თქვენი მონაცემებია:\n\nUsername: ^handle\nEmail: ^email\n\nგთხოვთ, შეინახეთ ეს ინფორმაცია უსაფრთხო ადგილას.\n\nგმადლობთ,\n\n^site_title\n^url",
		'welcome_confirm' => "ელ-ფოსტის დასადასტურებლად გთხოვთ დააჭიროთ ქვემოთ მოცემულ ბმულს.\n\n^url\n\n",
		'welcome_subject' => 'კეთილი იყოს თქვენი მობრძანება ჩვენს საიტზე - ^site_title!',
	);


/*
	Omit PHP closing tag to help avoid accidental output
*/