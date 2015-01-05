<?php
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	Description: This file is part of the Q2A Spanish Translation
	Author: Gabriel Zanetti http://github.com/pupi1985

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
		'a_commented_body' => "Tu respuesta en ^site_title tiene un nuevo comentario de ^c_handle:\n\n^open^c_content^close\n\nTu respuesta fue:\n\n^open^c_context^close\n\nPuedes responder añadiendo tu comentario:\n\n^url\n\nGracias,\n\n^site_title",
		'a_commented_subject' => 'Tu respuesta en ^site_title tiene un nuevo comentario',
		'a_followed_body' => "Tu respuesta en ^site_title tiene una nueva pregunta relacionada de ^q_handle:\n\n^open^q_title^close\n\nTu respuesta fue:\n\n^open^a_content^close\n\nHaz clic en el siguiente enlace para responder la nueva pregunta:\n\n^url\n\nGracias, \n\n^site_title",
		'a_followed_subject' => 'Tu respuesta en ^site_title tiene una pregunta relacionada',
		'a_selected_body' => "¡Felicitaciones! Tu respuesta en ^site_title ha sido seleccionada como la mejor por ^s_handle:\n\n^open^a_content^close\n\nLa pregunta era:\n\n^open^q_title^close\n\nHaz clic en el siguiente enlace para ver tu respuesta:\n\n^url\n\nGracias, \n\n^site_title",
		'a_selected_subject' => '¡Tu respuesta en ^site_title ha sido seleccionada!',
		'c_commented_body' => "Un nuevo comentario de ^c_handle se ha añadido después de tu comentario en ^site_title:\n\n^open^c_content^close\n\nPuedes verlo aquí:\n\n^open^c_context^close\n\nPuedes contestar añadiendo otro comentario:\n\n^url\n\nGracias, \n\n^site_title",
		'c_commented_subject' => 'Tu comentario en ^site_title ha sido añadido',
		'confirm_body' => "Por favor, haz clic en el enlace siguiente para confirmar tu dirección de email en ^site_title.\n\n^url\n\nGracias, \n^site_title",
		'confirm_subject' => '^site_title - Confirmación de dirección de email',
		'feedback_body' => "Comentarios:\n^message\n\nNombre:\n^name\n\nDirección de email:\n^email\n\nPágina anterior:\n^previous\n\nUsuario:\n^url\n\nDirección IP:\n^ip\n\nNavegador:\n^browser",
		'feedback_subject' => '^ feedback',
		'flagged_body' => "Una publicación de ^p_handle ha recibido ^flags:\n\n^open^p_context^close\n\nHaga clic en el siguiente enlace para ver la publicación:\n\n^url\n\n\nHaga clic en el siguiente enlace para revisar todas las publicaciones marcadas:\n\n^a_url\n\n\nGracias,\n\n^site_title",
		'flagged_subject' => '^site_title tiene una publicación marcada',
		'moderate_body' => "Una entrada de ^p_handle requiere tu aprobación:\n\n^open^p_context^close\n\nHaz clic en el siguiente enlace para aprobar o rechazar la entrada:\n\n^url\n\n\nHaz clic en el siguiente enlace para revisar todas las entradas encoladas:\n\n^a_url\n\n\nGracias,\n\n^site_title",
		'moderate_subject' => 'Moderación de ^site_title',
		'new_password_body' => "Tu nueva contraseña para ^site_title es la siguiente.\n\nContraseña: ^password\n\nTe recomendamos que cambies esta contraseña inmendiatamente después de ingresar a el sitio web.\n\nGracias, \n^site_title\n^url",
		'new_password_subject' => '^site_title - Tu nueva contraseña',
		'private_message_body' => "Te ha enviado un mensaje privado ^f_handle en ^site_title:\n\n^open^message^close\n\n^moreGracias,\n\n^site_title\n\n\nPara bloquear mensajes privados visita la página de tu cuenta:\n^a_url",
		'private_message_info' => "Más información sobre ^f_handle:\n\n^url\n\n",
		'private_message_reply' => "Haga clic en el siguiente enlace para contestar a ^f_handle por mensaje privado:\n\n^url\n\n",
		'private_message_subject' => 'Mensaje de ^f_handle en ^site_title',
		'q_answered_body' => "Tu pregunta en ^site_title ha sido contestada por ^a_handle:\n\n^open^a_content^close\n\nTu pregunta era:\n\n^open^q_title^close\n\nSi te gusta esta respuesta, deberías seleccionarla como la mejor:\n\n^url\n\nGracias, \n\n^site_title",
		'q_answered_subject' => 'Tu pregunta en ^site_title ha sido contestada',
		'q_commented_body' => "Tu pregunta en ^site_title tiene un nuevo comentario de ^c_handle:\n\n^open^c_content^close\n\nTu pregunta era:\n\n^open^c_context^close\n\nDeberías contestar añadiendo tu comentario:\n\n^url\n\nGracias, \n\n^site_title",
		'q_commented_subject' => 'Tu pregunta en ^site_title tiene un nuevo comentario',
		'q_posted_body' => "Una nueva pregunta ha sido realizada por ^q_handle:\n\n^open^q_title\n\n^q_content^close\n\nHaz clic en el siguiente enlace para ver la pregunta:\n\n^url\n\nGracias, \n\n^site_title",
		'q_posted_subject' => '^site_title tiene nuevas preguntas',
		'remoderate_body' => "Una entrada editada por ^p_handle requiere tu reaprobacion:\n\n^open^p_context^close\n\nHaz clic en el siguiente enlace para aprobar u ocultar la entrada editada:\n\n^url\n\n\nHaz clic en el siguiente enlace para revisar todas las entradas encoladas:\n\n^a_url\n\n\nGracias,\n\n^site_title",
		'remoderate_subject' => 'Moderación de ^site_title',
		'reset_body' => "Por favor, haz clic en el enlace siguiente para cambiar tu contraseña en ^site_title.\n\n^url\n\nComo alternativa, introduce el código siguiente en el campo proporcionado.\n\nCode: ^code\n\nSi no has solicitado cambiar tu contraseña, por favor, ignora este mensaje.\n\nGracias, \n^site_title",
		'reset_subject' => '^site_title - Reactivación de contraseña',
		'to_handle_prefix' => "^,\n\n",
		'u_approved_body' => "Puedes ver tu nuevo perfil de usuario haciendo clic en el siguiente enlace:\n\n^url\n\nGracias,\n\n^site_title",
		'u_approved_subject' => 'Tu usuario de ^site_title ha sido aprobado',
		'u_registered_body' => "Un nuevo usuario se ha registrado como ^u_handle.\n\nHaz clic en el siguiente enlace para ver el perfil del usuario:\n\n^url\n\nGracias,\n\n^site_title",
		'u_registered_subject' => 'Se ha registrado un nuevo usuario en ^site_title',
		'u_to_approve_body' => "Un nuevo usuario se ha registrado como ^u_handle.\n\nHaz clic en el siguiente enlace para aprobar el usuario:\n\n^url\n\nHaz clic en el siguiente enlace para revisar todos los usuarios que están esperando aprobación:\n\n^a_url\n\nGracias,\n\n^site_title",
		'wall_post_body' => "^f_handle ha publicado en tu muro de ^site_title:\n\n^open^post^close\n\nPuedes responder a la publicación haciendo clic en el siguiente enlace:\n\n^url\n\nGracias,\n\n^site_title",
		'wall_post_subject' => 'Publicación en tu muro de ^site_title',
		'welcome_body' => "Muchas gracias por registrarte en ^site_title.\n\n^custom^confirmTus datos de ingreso son los siguientes:\n\nUsuario: ^handle\nEmail: ^email\n\nPor favor, mantén esta información a mano y en un lugar seguro.\n\nGracias, \n\n^site_title\n^url",
		'welcome_confirm' => "Por favor, haz clic en el enlace siguiente para confirmar tu dirección de email.\n\n^url\n\n",
		'welcome_subject' => '¡Bienvenido a ^site_title!',
	);
