# How to contribute

As of version 1.6.3, all development of [Question2Answer][Home] will take place through GitHub. Bug reports and pull requests are encouraged, provided they follow these guidelines.


## Bug reports (issues)

If you find a bug (error) with Question2Answer, please [submit an issue here][Issues]. Be as descriptive as possible: include exactly what you did to make the bug appear, what you expect to happen, and what happened instead. Also include your PHP version and MySQL version. Remember to check for similar issues already reported.

Note that general troubleshooting issues such as installation or how to use a feature should continue to be asked on the [Question2Answer Q&A][QA].


## Pull requests

If you have found the cause of the bug in the Q2A code, you can submit the patch back to the Q2A repository. Create a fork of the repo, make the changes in your fork, then submit a pull request. **All pull requests must be made to the `dev` branch of Q2A.** The `master` branch is the current, stable release version.

If you wish to implement a feature, you should start a discussion on the [Question2Answer Q&A][QA] first. We welcome all ideas but they may not be appropriate for the Q2A core.


## Coding style

From 1.7 onwards a new coding style is being implemented that is more in line with other projects. The core codebase is gradually being refactored, and any new code should use the guidelines below. When making changes it's encouraged to update the style of the surrounding code, e.g. the rest of the function being modified.

**Please keep style-only changes to a separate commit!** For example if you fix a bug, do that first in one commit, then optionally reformat the rest of the function's code and perform a second commit.

### Rules

- PHP code should start with `<?php` (almost always the very first line). The closing tag `?>` should be omitted to avoid accidental output.
- PHP files must use only UTF-8 encoding without BOM.
- Trailing whitespace (tabs or spaces at the end of lines) should be trimmed on save. Any advanced text editor should be able to do this. (For Sublime Text you can add the option `"trim_trailing_white_space_on_save": true` to your preferences. In Notepad++ you can press Alt+Shift+S.)
- Use tabs for indenting. Each file should start at level 0 (i.e. no indentation).
- Functions should use a DocBlock-style comment.
- Operators (`=`, `+` etc) should have a space either side.
- Control structure keywords (`if`, `else`, `foreach` etc) should have a space between them and the opening parenthesis.
- Opening braces for classes and functions should be on the next line.
- Opening braces for control structures should be on the same line.
- Optional braces may be omitted only when the statement spans only one line.

### Examples

Here is an old example. Even though the braces are optional (the foreach contains only one statement), the statement spans several lines so brances should be used here for clarity.

	foreach ($checkarrays as $checkarray)
		if ( isset(${$checkarray}) && is_array(${$checkarray}) )
			foreach (${$checkarray} as $checkkey => $checkvalue)
				if (isset($keyprotect[$checkkey]))
					qa_fatal_error('My superglobals are not for overriding');
				else
					unset($GLOBALS[$checkkey]);

It should be rewritten as:

	foreach ($checkarrays as $checkarray) {
		if (isset(${$checkarray}) && is_array(${$checkarray})) {
			foreach (${$checkarray} as $checkkey => $checkvalue) {
				if (isset($keyprotect[$checkkey]))
					qa_fatal_error('My superglobals are not for overriding');
				else
					unset($GLOBALS[$checkkey]);
			}
		}
	}

Here is a class example showing the placement of braces, operators, and an advanced DocBlock comment.

	class qa_example
	{
		/**
		 * Adds 1 to the supplied number.
		 *
		 * @param int $number The number to increment.
		 *
		 * @return int Returns the new number.
		 */
		public function add_one($number)
		{
			$result = $number + 1;

			return $result;
		}
	}



[Home]: http://www.question2answer.org/
[QA]: http://www.question2answer.org/qa/
[Issues]: https://github.com/q2a/question2answer/issues
