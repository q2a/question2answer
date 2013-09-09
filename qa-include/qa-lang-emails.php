<?php
	
/*
	Question2Answer 1.4 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-lang-emails.php
	Version: 1.4
	Date: 2011-06-17 12:46:02 GMT
	Description: Language phrases for email notifications


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
		'a_commented_body' => "Tu respuesta en ^site_title tiene un nuevo comentario de ^c_handle:\n\n^open^c_content^close\n\nTu respuesta fué:\n\n^open^c_context^close\n\nPuedes responder añadiendo tu comentario:\n\n^url\n\nMuchas gracias,\n\n^site_title",
		'a_commented_subject' => 'Tu respuesta en ^site_title tiene un nuevo comentario',
		'a_followed_body' => "Tu respuesta en ^site_title tiene una nueva pregunta relacionada de ^q_handle:\n\n^open^q_title^close\n\nTu respuesta fué:\n\n^open^a_content^close\n\nHaz clic en el enlace siguiente para responder la nueva pregunta:\n\n^url\n\nMuchas gracias, \n\n^site_title",
		'a_followed_subject' => 'Tu respuesta en ^site_title tiene una pregunta relacionada',
		'a_selected_body' => "¡Enhorabuena! Tu respuesta en ^site_title ha sido seleccionada como la mejor por ^s_handle:\n\n^open^a_content^close\n\nLa pregunta era:\n\n^open^q_title^close\n\nHaz clic en el siguiente enlace para ver tu respuesta:\n\n^url\n\nMuchas gracias, \n\n^site_title",
		'a_selected_subject' => '¡Tu respuesta en ^site_title ha sido seleccionada!',
		'c_commented_body' => "Un nuevo comentario de ^c_handle se ha añadido después de tu comentario en ^site_title:\n\n^open^c_content^close\n\nPuedes verlo aquí::\n\n^open^c_context^close\n\nPuedes contestar añadiendo otro comentario:\n\n^url\n\nMuchas gracias, \n\n^site_title",
		'c_commented_subject' => 'Tu comentario en ^site_title ha sido añadido',
		'confirm_body' => "Por favor, haz clic en el enlace siguiente para confirmar registro en ^site_title.\n\n^url\n\nMuchas gracias, \n^site_title",
		'confirm_subject' => '^site_title - Confirmación de registro',
		'feedback_body' => "Comentarios:\n^message\n\nNombre:\n^name\n\nCorreo electrónico:\n^email\n\nPágina anterior:\n^previous\n\nUsuario:\n^url\n\nDirección IP:\n^ip\n\nNavegador:\n^browser",
		'feedback_subject' => '^ feedback',
		'flagged_body' => "Una publicación de ^p_handle ha recibido ^flags:\n\n^open^p_context^close\n\nPulse debajo para ver la publicación:\n\n^url\n\n\nPulse abajo para revisar todas las publicaciones marcadas:\n\n^a_url\n\n\nGracias,\n\n^site_title",
		'flagged_subject' => "^site_title tiene una publicación marcada",
		'private_message_body' => "Te ha enviado un mensaje privado ^f_handle en ^site_title:\n\n^open^message^close\n\n^moreGracias,\n\n^site_title\n\n\nPara bloquear mensajes privados visite la página de su cuenta:\n^a_url",
		'private_message_info' => "Más información sobre ^f_handle:\n\n^url\n\n",
		'private_message_reply' => "Pulse debajo para contestar a ^f_handle por mensaje privado:\n\n^url\n\n",
		'private_message_subject' => "Message from ^f_handle on ^site_title",
		'new_password_body' => "Tu nueva contraseña para ^site_title es la siguiente.\n\nContraseña: ^password\n\nRecomendamos que cambies esta contraseña inmendiatamente después de ingresar en el sitio web.\n\nMuchas gracias, \n^site_title\n^url",
		'new_password_subject' => '^site_title - Tu nueva contraseña',
		'q_answered_body' => "Tu pregunta en ^site_title ha sido contestada por ^a_handle:\n\n^open^a_content^close\n\nTu pregunta era:\n\n^open^q_title^close\n\nSi te gusta esta respuesta, deberías seleccionarla como la mejor:\n\n^url\n\nMuchas gracias, \n\n^site_title",
		'q_answered_subject' => 'Tu pregunta en ^site_title ha sido contestada',
		'q_commented_body' => "Tu pregunta en ^site_title tiene un nuevo comentario de ^c_handle:\n\n^open^c_content^close\n\nTu pregunta era:\n\n^open^c_context^close\n\nDeberías contestar añadiendo tu comentario:\n\n^url\n\nMuchas gracias, \n\n^site_title",
		'q_commented_subject' => 'Tu pregunta en ^site_title tiene un nuevo comentario',
		'q_posted_body' => "Una nueva pregunta ha sido realizada por ^q_handle:\n\n^open^q_title\n\n^q_content^close\n\nHaz clic en el enlace siguiente para ver la pregunta:\n\n^url\n\nMuchas gracias, \n\n^site_title",
		'q_posted_subject' => '^site_title tiene nuevas preguntas',
		'reset_body' => "Por favor, haz clic el enlace siguiente para cambiar tu contraseña en ^site_title.\n\n^url\n\Como alternativa, introduce el código siguiente en el campo proporcionado.\n\nCode: ^code\n\nSi no has solicitado cambiar tu contraseña, por favor, ignora este mensaje.\n\nMuchas gracias, \n^site_title",
		'reset_subject' => '^site_title - Reactivación de contraseña',
		'welcome_body' => "Muchas gracias por registrarte en ^site_title.\n\n^custom^confirmTus datos de ingreso son los siguientes:\n\nCorreo electrónico: ^email\nContraseña: ^password\n\nPor favor, mantén esta información a mano y en un lugar seguro.\n\nMuchas gracias, \n\n^site_title\n^url",
		'welcome_confirm' => "Por favor, haz clic en el enlace siguiente para confirmar registro.\n\n^url\n\n",
		'welcome_subject' => '¡Bienvenido a ^site_title!',
		'moderate_body' => "Una entrada de ^p_handle requiere tu aprobación:\n\n^open^p_context^close\n\nClick abajo para aprobar o rechazar la entrada:\n\n^url\n\n\nPulsa abajo para revisar todas las publicaciones en cola:\n\n^a_url\n\n\nGracias,\n\n^site_title",
		'moderate_subject' => "Moderación en ^site_title",
		'to_handle_prefix' => "^,\n\n",
		'remoderate_body' => "An edited post by ^p_handle requires your reapproval:\n\n^open^p_context^close\n\nClick below to approve or hide the edited post:\n\n^url\n\n\nClick below to review all queued posts:\n\n^a_url\n\n\nThank you,\n\n^site_title",
		'remoderate_subject' => 'Moderación de ^site_title',
		'u_registered_body' => "Un nuevo usuario se ha registrado como ^u_handle.\n\nHaz click abajo para ver el perfil del usuario:\n\n^url\n\nGracias,\n\n^site_title",
		'u_to_approve_body' => "Un nuevo usuario se ha registrado como ^u_handle.\n\nHaz click abajo para aprobar el usuario:\n\n^url\n\nPulsa abajo para revisar todos los usuarios que están esperando aprobación:\n\n^a_url\n\nGracias,\n\n^site_title",
		'u_registered_subject' => 'Se ha registrado un nuevo usuario en ^site_title',
		'u_approved_body' => "Puedes ver tu nuevo perfirl de usuario aquí:\n\n^url\n\nGracias,\n\n^site_title",
		'u_approved_subject' => 'Tu usuario de ^site_title ha sido aprobado',
		'wall_post_subject' => 'Publica en tu muro de ^site_title',
		'wall_post_body' => "^f_handle ha publicado en tu muro en ^site_title:\n\n^open^post^close\n\nPuedes responder a la publicación aquí:\n\n^url\n\nGracias,\n\n^site_title",

	);
	

/*
	Omit PHP closing tag to help avoid accidental output
*/


