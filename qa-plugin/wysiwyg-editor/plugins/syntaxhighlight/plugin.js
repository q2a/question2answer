CKEDITOR.plugins.add( 'syntaxhighlight', {
	requires : 'dialog',
	lang : 'en,de,fr', // %REMOVE_LINE_CORE%
	icons : 'syntaxhighlight', // %REMOVE_LINE_CORE%
	init : function( editor ) {
		editor.addCommand( 'syntaxhighlightDialog', new CKEDITOR.dialogCommand( 'syntaxhighlightDialog' ) );
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
