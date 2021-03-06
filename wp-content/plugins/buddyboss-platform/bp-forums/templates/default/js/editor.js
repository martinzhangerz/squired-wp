jQuery( document ).ready(
	function() {

		if ( typeof window.MediumEditor !== 'undefined' ) {

			  var toolbarOptions = {
					buttons: ['bold', 'italic', 'unorderedlist','orderedlist', 'quote', 'anchor' ]
			};
			if ( jQuery( '#bbp_editor_forum_content' ).length ) {
				window.forums_medium_forum_editor = new window.MediumEditor(
					'#bbp_editor_forum_content',
					{
						placeholder: {
							text: window.bbpEditorJsStrs.description,
							hideOnClick: true
						},
						toolbar: toolbarOptions
					  }
				);

				window.forums_medium_forum_editor.subscribe(
					'editableInput',
					function () {
						jQuery( '#bbp_forum_content' ).val( window.forums_medium_forum_editor.getContent() );
					}
				);
			}
			if ( jQuery( '#bbp_editor_reply_content' ).length ) {
				window.forums_medium_reply_editor = new window.MediumEditor(
					'#bbp_editor_reply_content',
					{
						placeholder: {
							text: window.bbpEditorJsStrs.type_reply,
							hideOnClick: true
						},
						toolbar: toolbarOptions
					}
				);

				window.forums_medium_reply_editor.subscribe(
					'editableInput',
					function () {
						  jQuery( '#bbp_reply_content' ).val( window.forums_medium_reply_editor.getContent() );
					}
				);
			}
			if ( jQuery( '#bbp_editor_topic_content' ).length ) {
				window.forums_medium_topic_editor = new window.MediumEditor(
					'#bbp_editor_topic_content',
					{
						placeholder: {
							text: window.bbpEditorJsStrs.type_topic,
							hideOnClick: true
						},
						toolbar: toolbarOptions
					  }
				);

				window.forums_medium_topic_editor.subscribe(
					'editableInput',
					function () {
						jQuery( '#bbp_topic_content' ).val( window.forums_medium_topic_editor.getContent() );
					}
				);
			}
		}

			/* Use backticks instead of <code> for the Code button in the editor */
		if ( typeof( edButtons ) !== 'undefined' ) {
			/*globals edButtons:false */
			edButtons[110] = new QTags.TagButton( 'code', 'code', '`', '`', 'c' );
			/*globals QTags:false */
			QTags._buttonsInit();
		}

			/* Tab from topic title */
			jQuery( '#bbp_topic_title' ).bind(
				'keydown.editor-focus',
				function(e) {
					if ( e.which !== 9 ) {
						return;
					}

					if ( ! e.ctrlKey && ! e.altKey && ! e.shiftKey ) {
						if ( typeof( tinymce ) !== 'undefined' ) {
							/*globals tinymce:false */
							if ( ! tinymce.activeEditor.isHidden() ) {
								var editor = tinymce.activeEditor.editorContainer;
								jQuery( '#' + editor + ' td.mceToolbar > a' ).focus();
							} else {
								jQuery( 'textarea.bbp-the-content' ).focus();
							}
						} else {
							jQuery( 'textarea.bbp-the-content' ).focus();
						}

						e.preventDefault();
					}
				}
			);

			/* Shift + tab from topic tags */
			jQuery( '#bbp_topic_tags' ).bind(
				'keydown.editor-focus',
				function(e) {
					if ( e.which !== 9 ) {
						  return;
					}

					if ( e.shiftKey && ! e.ctrlKey && ! e.altKey ) {
						if ( typeof( tinymce ) !== 'undefined' ) {
							if ( ! tinymce.activeEditor.isHidden() ) {
								 var editor = tinymce.activeEditor.editorContainer;
								 jQuery( '#' + editor + ' td.mceToolbar > a' ).focus();
							} else {
								jQuery( 'textarea.bbp-the-content' ).focus();
							}
						} else {
							jQuery( 'textarea.bbp-the-content' ).focus();
						}

						e.preventDefault();
					}
				}
			);
	}
);
