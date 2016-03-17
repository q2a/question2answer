# How to contribute

As of version 1.6.3, all development of [Question2Answer][Home] will take place through GitHub. Bug reports and pull requests are encouraged, provided they follow these guidelines.


## Bug reports (issues)

If you find a bug (error) with Question2Answer, please [submit an issue here][Issues]. Be as descriptive as possible: include exactly what you did to make the bug appear, what you expect to happen, and what happened instead. Also include your PHP version and MySQL version. Remember to check for similar issues already reported.

If you think you've found a security issue, you can responsibly disclose it to us using the [contact form here](http://www.question2answer.org/feedback.php).

Note that general troubleshooting issues such as installation or how to use a feature should continue to be asked on the [Question2Answer Q&A][QA].


## Pull requests

If you have found the cause of the bug in the Q2A code, you can submit the patch back to the Q2A repository. Create a fork of the repo, make the changes in your fork, then submit a pull request. Bug fix pull requessts must be made to the `dev` branch. PRs for new features must be made to the next version branch, for example `1.8`.

If you wish to implement a feature, you should start a discussion on the [Question2Answer Q&A][QA] first. We welcome all ideas but they may not be appropriate for the Q2A core. Consider whether your idea could be developed as a plugin.


## Coding style

From 1.7 onwards a new coding style is being implemented that is more in line with other projects. The core codebase is gradually being refactored, and any new code should use the guidelines below. When making changes it's encouraged to update the style of the surrounding code, e.g. the rest of the function being modified.

However, **please keep style-only changes to a separate commit!** For example if you fix a bug, do that first in one commit, then optionally reformat the rest of the function's code and perform a second commit.

### Guidelines

- PHP code should start with `<?php` (almost always the very first line). The closing tag `?>` should be omitted to avoid accidental output.
- PHP files should use UTF-8 encoding without BOM (this is usually default in most text editors).
- Trailing whitespace (tabs or spaces at the end of lines) should be trimmed on save. Any advanced text editor should be able to do this. (For Sublime Text you can add the option `"trim_trailing_white_space_on_save": true` to your preferences. In Notepad++ you can press Alt+Shift+S.)
- Use tabs for indenting. Each file should start at level 0 (i.e. no indentation).
- Functions should use a DocBlock-style comment.
- Operators (`=`, `+` etc) should have a space either side.
- Control structure keywords (`if`, `else`, `foreach` etc) should have a space between them and the opening parenthesis.
- Opening braces for classes and functions should be on the next line.
- Opening braces for control structures should be on the same line. All control structures should use braces.

### Examples

Here is an example of the old style. Even though the braces are technically optional (the foreach contains only one statement), they should be used here for clarity.

	foreach ($thingarray as $thing)
		if (isset($thing['id']))
			if (strpos($thing['id'], 'Hello')===0)
				$newthing='Goodbye';
			elseif ($thing['id']=='World')
				$newerthing='Galaxy';
		else
			return null;

It should be rewritten as:

	foreach ($thingarray as $thing) {
		if (isset($thing['id'])) {
			if (strpos($thing['id'], 'Hello') === 0) {
				$newthing = 'Goodbye';
			} elseif ($thing['id'] == 'World') {
				$newerthing = 'Galaxy';
			}
		} else {
			return null;
		}
	}

Here is a class example showing the placement of braces, operators, and a DocBlock comment.

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

### New autoloaded classes

From version 1.7 some classes are autoloaded, so it's possible to use them without adding a `require_once` first. These loosely follow [PSR-0][PSR0] using faux namespaces. This is being done slowly and carefully to maintain backwards compatibility, and does not apply to plugins, themes, nor most of the core for that matter.

Classes are stored in the `qa-include/Q2A` folder, and then in subfolders depending on their categorization.

Class names should be of the form `Q2A_<Namespace>_<Class>`, e.g. `Q2A_Util_Debug`. There may be multiple "namespaces", e.g. `Q2A_Db_User_Messages`.

Classes are mapped to PHP files with the underscores converted to directory separators. The `Q2A_Util_Debug` class is in the file `qa-include/Q2A/Util/Debug.php`. A class named `Q2A_Db_User_Messages` would be in a file `qa-include/Q2A/Db/User/Messages.php`.



[Home]: http://www.question2answer.org/
[QA]: http://www.question2answer.org/qa/
[Issues]: https://github.com/q2a/question2answer/issues
[PSR0]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md
