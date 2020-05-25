jQuery(function($) {
	function bbp_reply_ajax_call( action, nonce, form_data ) {
		var $data = {
			action : action,
			nonce  : nonce
		};

		$.each(form_data, function(i, field){
			if ( field.name === 'action' ) {
				$data.bbp_reply_form_action = field.value;
			} else {
				$data[field.name] = field.value;
			}
		});

		$.post( window.bbpReplyAjaxJS.bbp_ajaxurl, $data, function ( response ) {
			if ( response.success ) {

				$( '.bbp-reply-form form' ).removeClass('submitting');

				var reply_list_item = '';

				if ( 'edit' === response.reply_type ) {
					reply_list_item = '<li class="highlight">' + response.content + '</li>';
					// in-place editing doesn't work yet, but could (and should) eventually
					$('#post-' + response.reply_id).parent('li').replaceWith(reply_list_item);
				} else {
					if ( window.bbpReplyAjaxJS.threaded_reply && response.reply_parent && response.reply_parent !== response.reply_id ) {
						// threaded comment
						var $parent = null;
						var reply_list_item_depth = '1';
						if ( $('#post-' + response.reply_parent).parent('li').data('depth') == window.bbpReplyAjaxJS.threaded_reply_depth ) {
							var depth = parseInt(window.bbpReplyAjaxJS.threaded_reply_depth)-1;
							$parent = $('#post-' + response.reply_parent).closest('li.depth-'+depth);
							reply_list_item_depth = window.bbpReplyAjaxJS.threaded_reply_depth;
						} else {
							$parent = $('#post-' + response.reply_parent).parent('li');
							reply_list_item_depth = parseInt($parent.data('depth'))+1;
						}
						var list_type = 'ul';
						if ( $('.bb-single-reply-list').is('ol') ) {
							list_type = 'ol';
						}
						if ( ! $parent.find('>'+list_type+'.bbp-threaded-replies').length ) {
							$parent.append('<' + list_type + ' class="bbp-threaded-replies"></' + list_type + '>');
						}
						reply_list_item = '<li class="highlight depth-'+reply_list_item_depth+'" data-depth="'+reply_list_item_depth+'">' + response.content + '</li>';
						$parent.find('>'+list_type+'.bbp-threaded-replies').append(reply_list_item);
					} else {
						reply_list_item = '<li class="highlight depth-1" data-depth="1">' + response.content + '</li>';
						$('.bb-single-reply-list').append(reply_list_item);
					}
				}

				// Get all the tags without page reload.
				if ( response.tags !== '' ){
					var tagsDivSelector   = $( 'body .item-tags' );
					var tagsDivUlSelector = $( 'body .item-tags ul' );
					if ( tagsDivSelector.css( 'display' ) === 'none' ) {
						tagsDivSelector.append( response.tags );
						tagsDivSelector.show();
					} else {
						tagsDivUlSelector.remove();
						tagsDivSelector.append( response.tags );
					}
				}

				if ( reply_list_item != '' ) {
					$('body').animate({
						scrollTop: $(reply_list_item).offset().top
					}, 500);
					setTimeout(function () {
						$(reply_list_item).removeClass('highlight');
					}, 2000);
				}

				if ( typeof bp !== 'undefined' &&
					typeof bp.Nouveau !== 'undefined' &&
					typeof bp.Nouveau.Media !== 'undefined' &&
					typeof bp.Nouveau.Media.dropzone_media !== 'undefined' &&
					bp.Nouveau.Media.dropzone_media.length
				) {
					for( var i = 0; i < bp.Nouveau.Media.dropzone_media.length; i++ ) {
						bp.Nouveau.Media.dropzone_media[i].saved = true;
					}
				}

				reset_reply_form();

				var scrubberposts = $('.scrubberpost');
				for( var k in scrubberposts ) {
					if ( $(scrubberposts[k]).hasClass('post-'+response.reply_id) ) {
						window.BuddyBossThemeBbpScrubber.goToPost(parseInt(k,10)+1,'');
						break;
					}
				}
			} else {
				console.log(response);
				if ( !response.content ) {
					response.content = window.bbpReplyAjaxJS.generic_ajax_error;
				}
				console.log( response.content );
			}
			$( '.bbp-reply-form form' ).removeClass('submitting');
		} );
	}

	function reset_reply_form() {
		if ( typeof window.forums_medium_reply_editor !== 'undefined' ) {
			window.forums_medium_reply_editor.resetContent();
		}
		jQuery('#bbp_editor_reply_content').removeClass('error');
		$('#bbp-close-btn').trigger('click');
		$('#bbp_reply_content').val('');
		if ( typeof bp !== 'undefined' &&
			typeof bp.Nouveau !== 'undefined' &&
			typeof bp.Nouveau.Media !== 'undefined'
		) {
			bp.Nouveau.Media.resetForumsGifComponent();
			bp.Nouveau.Media.resetForumsMediaComponent();
		}
	}

	if ( ! $('body').hasClass('reply-edit') ) {
		$('.bbp-reply-form form').on('submit', function (e) {
			e.preventDefault();

			if ($(this).hasClass('submitting')) {
				return false;
			}

			$(this).addClass('submitting');

			var valid = true;
			var media_valid = true;

			var editor = false;
			if (typeof window.forums_medium_reply_editor !== 'undefined') {
				editor = window.forums_medium_reply_editor;
			}

			if (
				(
					$('.bbp-reply-form form').find('#bbp_media').length > 0
					&& $('.bbp-reply-form form').find('#bbp_media_gif').length > 0
					&& $('.bbp-reply-form form').find('#bbp_media').val() == ''
					&& $('.bbp-reply-form form').find('#bbp_media_gif').val() == ''
				)
				|| (
					$('.bbp-reply-form form').find('#bbp_media').length > 0
					&& $('.bbp-reply-form form').find('#bbp_media_gif').length <= 0
					&& $('.bbp-reply-form form').find('#bbp_media').val() == ''
				)
				|| (
					$('.bbp-reply-form form').find('#bbp_media_gif').length > 0
					&& $('.bbp-reply-form form').find('#bbp_media').length <= 0
					&& $('.bbp-reply-form form').find('#bbp_media_gif').val() == ''
				)
			) {
				media_valid = false;
			}

			if (
				( editor && $.trim( editor.getContent().replace('<p><br></p>', '') ) === '' )
				&& media_valid == false
			) {
				jQuery('#bbp_editor_reply_content').addClass('error');
				valid = false;
			} else if (
				( !editor && $.trim( $('.bbp-reply-form form').find('#bbp_reply_content').val() ) === '' )
				&& media_valid == false
			) {
				$('.bbp-reply-form form').find('#bbp_reply_content').addClass('error');
				valid = false;
			} else {
				if (editor) {
					jQuery('#bbp_editor_reply_content').removeClass('error');
				}
				$('.bbp-reply-form form').find('#bbp_reply_content').removeClass('error');
			}

			if (valid) {
				bbp_reply_ajax_call('reply', window.bbpReplyAjaxJS.reply_nonce, $(this).serializeArray());
			} else {
				$(this).removeClass('submitting');
			}

		});
	}
});
