CKEDITOR.plugins.add( 'syntaxhighlight', {
	requires : 'dialog',
	lang : 'en,de,fr', // %REMOVE_LINE_CORE%
	icons : 'syntaxhighlight', // %REMOVE_LINE_CORE%
	init : function( editor ) {
		editor.addCommand( 'syntaxhighlightDialog', new CKEDITOR.dialogCommand( 'syntaxhighlightDialog', {
			allowedContent: 'pre[title](*)',
			requiredContent: 'pre[title](*)'
		} ) );
		editor.ui.addButton && editor.ui.addButton( 'Syntaxhighlight',
		{
			label : editor.lang.syntaxhighlight.title,
			command : 'syntaxhighlightDialog',
			toolbar : 'insert,98'
		} );

		if ( editor.contextMenu ) {
			editor.addMenuGroup( 'syntaxhighlightGroup' );
			editor.addMenuItem( 'syntaxhighlightItem', {
				label: editor.lang.syntaxhighlight.contextTitle,
				icon: this.path + 'icons/syntaxhighlight.png',
				command: 'syntaxhighlightDialog',
				group: 'syntaxhighlightGroup'
			});
			editor.contextMenu.addListener( function( element ) {
				if ( element.getAscendant( 'pre', true ) ) {
					return { syntaxhighlightItem: CKEDITOR.TRISTATE_OFF };
				}
			});
		}

		CKEDITOR.dialog.add( 'syntaxhighlightDialog', this.path + 'dialogs/syntaxhighlight.js' );
	}
});

/**
 * Whether the "Hide gutter & line numbers" checkbox is checked by default in the
 * Syntaxhighlight dialog.
 *
 *		config.syntaxhighlight_hideGutter = false;
 *
 * @cfg
 * @member CKEDITOR.config
 */
CKEDITOR.config.syntaxhighlight_hideGutter = false;

/**
 * Whether the "Hide code controls at the top of the code block" checkbox is checked by default in the
 * Syntaxhighlight dialog.
 *
 *		config.syntaxhighlight_hideControls = false;
 *
 * @cfg
 * @member CKEDITOR.config
 */
CKEDITOR.config.syntaxhighlight_hideControls = false;

/**
 * Whether the "Collapse the code block by default" checkbox is checked by default in the
 * Syntaxhighlight dialog.
 *
 *		config.syntaxhighlight_collapse = false;
 *
 * @cfg
 * @member CKEDITOR.config
 */
CKEDITOR.config.syntaxhighlight_collapse = false;

/**
 * "Default code title" text-field default value in the
 * Syntaxhighlight dialog.
 *
 *		config.syntaxhighlight_codeTitle = '';
 *
 * @cfg
 * @member CKEDITOR.config
 */
CKEDITOR.config.syntaxhighlight_codeTitle = '';

/**
 * Whether the "Show row columns in the first line" checkbox is checked by default in the
 * Syntaxhighlight dialog.
 *
 *		config.syntaxhighlight_showColumns = false;
 *
 * @cfg
 * @member CKEDITOR.config
 */
CKEDITOR.config.syntaxhighlight_showColumns = false;

/**
 * Whether the "Switch off line wrapping" checkbox is checked by default in the
 * Syntaxhighlight dialog.
 *
 *		config.syntaxhighlight_noWrap = false;
 *
 * @cfg
 * @member CKEDITOR.config
 */
CKEDITOR.config.syntaxhighlight_noWrap = false;

/**
 * "Default line count" text-field default value in the
 * Syntaxhighlight dialog.
 *
 *		config.syntaxhighlight_firstLine = 0;
 *
 * @cfg
 * @member CKEDITOR.config
 */
CKEDITOR.config.syntaxhighlight_firstLine = 0;

/**
 * "Enter a comma seperated lines of lines you want to highlight" text-field default value in the
 * Syntaxhighlight dialog.
 *
 *		config.syntaxhighlight_highlight = null;
 *
 * @cfg
 * @member CKEDITOR.config
 */
CKEDITOR.config.syntaxhighlight_highlight = null;

/**
 * "Select language" select default selection in the
 * Syntaxhighlight dialog.
 *
 *		config.syntaxhighlight_lang = null;
 *
 * @cfg
 * @member CKEDITOR.config
 */
CKEDITOR.config.syntaxhighlight_lang = null;

/**
 * Default content of the "Code" textarea in the
 * Syntaxhighlight dialog.
 *
 *		config.syntaxhighlight_code = '';
 *
 * @cfg
 * @member CKEDITOR.config
 */
CKEDITOR.config.syntaxhighlight_code = '';
