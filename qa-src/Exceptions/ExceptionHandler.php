<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

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

namespace Q2A\Exceptions;

use Exception;
use Q2A\Http\PageNotFoundException;

require_once QA_INCLUDE_DIR . 'app/format.php';

class ExceptionHandler
{
	public function __construct()
	{
	}

	public function handle(Exception $exception)
	{
		if ($exception instanceof FatalErrorException) {
			$this->handleFatalErrorException($exception);
		} elseif ($exception instanceof PageNotFoundException) {
			return $this->handlePageNotFoundException($exception);
		} elseif ($exception instanceof ErrorMessageException) {
			return $this->handleErrorMessageException($exception);
		} else {
			return $this->handleUnknownException($exception);
		}
	}

	private function handleFatalErrorException(FatalErrorException $exception)
	{
		qa_fatal_error($exception->getMessage());
	}

	private function handlePageNotFoundException(PageNotFoundException $exception)
	{
		header('HTTP/1.0 404 Not Found');

		$qa_content = $this->handleErrorMessageException($exception);
		$qa_content['suggest_next'] = qa_html_suggest_qs_tags(qa_using_tags());

		return $qa_content;
	}

	private function handleErrorMessageException(ErrorMessageException $exception)
	{
		$qa_content = qa_content_prepare();
		$qa_content['error'] = $exception->getMessage();

		return $qa_content;
	}

	private function handleUnknownException(Exception $exception)
	{
		return $this->handleErrorMessageException(new ErrorMessageException($exception->getMessage()));
	}
}
