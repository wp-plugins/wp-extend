jQuery(document).ready(function($) {

	window.wpx_fields = {

		init: function() {

			// elements
			this.$selectFieldsDropdown = $('.wpx-dropdown-selector select');
			this.$imageFieldDeleteBtn = $('.wpx-delete-image-field');
			this.$imageFieldClearBtn = $('.wpx-clear-image-field');
			this.$wpxRewriteCheckbox = $('.wpx-rewrite-switch');
			this.$submitTrigger = $('#submit');

			// this is used to trigger updating the post
			// in the case of the taxonomy or the options page,
			// we need to set it to something else
			if ($('form#post').length) {
				this.$postForm = $('form#post');
				this.screenType = 'post-type';
			} else if ($('form#edittag').length) {
				this.$postForm = $('form#edittag');
				this.screenType = 'taxonomy';
			} else if ($('.wpx-bounds form.validate').length) {
				this.$postForm = $('.wpx-bounds form.validate');
				this.screenType = 'options-page';
			}

			// for media uploading
			this.$mediaLibraryLaunchers = $('.wpx-media, .mce-wpx-media');
			this.$mediaLibraryGalleryLaunchers = $('.wpx-media-gallery');
			this.mediaLibraryWindow;
			this.mediaLibraryGalleryWindow = new Array();
			this.mediaLibraryPostID = wp.media.model.settings.post.id;
			this.$mediaLibraryBtn;
			this.$mediaLibraryGalleryBtn;
			this.$mediaGalleryImage = $(".wpx-gallery-image");
			this.$mediaGalleryImageDelete = $(".wpx-media-gallery-delete");

			// bindings
			this.bindEvents();

		},

		bindEvents: function() {

			// injects hidden fields to process image deletions
			// in the gallery(); field type
			wpx_fields.injectGalleryPrep();

			/**
			 * Delete button in a gallery(); field type
			*/
			wpx_fields.$mediaGalleryImage.live('click', function( event ){
				event.preventDefault();
				$(this).find('img').toggleClass( "selected" );
			});

			/**
			 * Clicking an image in the gallery(); field type
			*/
			wpx_fields.$mediaGalleryImageDelete.live('click', function( event ){
				event.preventDefault();
				wpx_fields.deleteImages(this);
			});

			/**
			 * Opens Media Library for elements with wpx-media-gallery class
			*/
			wpx_fields.$mediaLibraryGalleryLaunchers.live('click', function( event ){
				event.preventDefault();

				// capture the original button clicked
				wpx_fields.$mediaLibraryGalleryBtn = $(this);

				wpx_fields.mediaLibraryGalleryWindow = wpx_fields.mediaLibraryGalleryWindow[wpx_fields.$mediaLibraryGalleryBtn.attr('data-source')];

				// if the media frame already exists, reopen it.
				if ( wpx_fields.mediaLibraryGalleryWindow ) {
					wpx_fields.mediaLibraryGalleryWindow.uploader.uploader.param( 'post_id', wpx_fields.$mediaLibraryGalleryBtn.attr('data-source') );
					wpx_fields.mediaLibraryGalleryWindow.open();
					return;
				} else {
					// Set the wp.media post id so the uploader grabs the ID we want when initialised
					wp.media.model.settings.post.id = wpx_fields.$mediaLibraryGalleryBtn.attr('data-source');
				}

				// we'll show the gallery from this specific post
				wpx_fields.mediaLibraryGalleryWindow = wp.media.frames.mediaLibraryGalleryWindow = wp.media({
					title: 'Manage Gallery',
					frame: 'select',
					library : {
						type : 'image',
						uploadedTo : wpx_fields.$mediaLibraryGalleryBtn.attr('data-source')
					},
					button: {
						text: 'Update Gallery',
					},
					multiple: false
				});

				// when an image is selected, run a callback
				wpx_fields.mediaLibraryGalleryWindow.on( 'select', function() {
					// we set multiple to false so the user can get only one image from the uploader
					attachment = wpx_fields.mediaLibraryGalleryWindow.state().get('selection').first().toJSON();
					// restore the main post ID
					wp.media.model.settings.post.id = wpx_fields.mediaLibraryPostID;
					// save the page
					if (wpx_fields.screenType == 'post-type') {
						wpx_fields.$postForm.submit();
					} else {
						wpx_fields.$submitTrigger.trigger( "click" );
					}
				});

				// open the modal
				wpx_fields.mediaLibraryGalleryWindow.open();
			});

			/**
			 * Opens Media Library for elements with wpx-media class
			*/
			wpx_fields.$mediaLibraryLaunchers.live('click', function( event ){
				event.preventDefault();

				// capture the original button clicked
				wpx_fields.$mediaLibraryBtn = $(this);

				// If the media frame already exists, reopen it.
				if ( wpx_fields.mediaLibraryWindow ) {
					wpx_fields.mediaLibraryWindow.uploader.uploader.param( 'post_id', wpx_fields.$mediaLibraryBtn.attr('data-source') );
					wpx_fields.mediaLibraryWindow.open();
					return;
				} else {
					// Set the wp.media post id so the uploader grabs the ID we want when initialised
					wp.media.model.settings.post.id = wpx_fields.$mediaLibraryBtn.attr('data-source');
				}

				// provide wp.media with settings
				wpx_fields.mediaLibraryWindow = wp.media.frames.mediaLibraryWindow = wp.media({
					title: 'Select / Upload Media',
					frame: 'select',
					button: {
						text: 'Insert into Field',
					},
					multiple: false
				});

				// when an image is selected, run a callback
				wpx_fields.mediaLibraryWindow.on( 'select', function() {
					// we set multiple to false so the user can get only one image from the uploader
					attachment = wpx_fields.mediaLibraryWindow.state().get('selection').first().toJSON();
					// restore the main post ID
					wp.media.model.settings.post.id = wpx_fields.mediaLibraryPostID;
					// pass the image URL back to the field
					wpx_fields.$mediaLibraryBtn.attr('value', attachment.url);
				});

				// open the modal
				wpx_fields.mediaLibraryWindow.open();
			});

			/**
			 * Restore the main ID when the add media button is pressed
			*/
			$('a.add_media').on('click', function() {
				wp.media.model.settings.post.id = wpx_fields.mediaLibraryPostID;
			});

			/**
			 * For selecting options in the wpx_select_field();
			*/
			$(wpx_fields.$selectFieldsDropdown).change(function() {
				wpx_fields.createMetaboxOptions($(this), $(this).val());
			});

			/**
			 * For clearing the attached picture in the image() field
			*/
			$(wpx_fields.$imageFieldClearBtn).click(function(event) {
				event.preventDefault ? event.preventDefault() : event.returnValue = false;
				wpx_fields.clearImageField($(this));
			});

			/**
			 * For clearing the attached picture in the image() field
			*/
			$(wpx_fields.$imageFieldDeleteBtn).click(function(event) {
				event.preventDefault ? event.preventDefault() : event.returnValue = false;
				wpx_fields.deleteImageField($(this));
			});

			/**
			 * for pushing the value of a checkbox
			 * to the hidden field that acts as the on/off switch
			*/
			$(wpx_fields.$wpxRewriteCheckbox).change(function () {
				var hidden_field = $(this).parent().parent().find('.wpx-rewrite-hidden');
				if ($(this).attr("checked")) {
					$(hidden_field).val('1');
				} else {
					$(hidden_field).val('');
				}
			});

		},

		/**
		 * image(); and gallery() field types
		*/
		clearImageField: function(btn) {
			$(btn).parent().parent().parent().parent().find('.wpx-preview-image').remove();
			$(btn).parent().parent().find('.wpx-media, .mce-wpx-media').val('');
			$(btn).parent().parent().removeClass('wpx-has-image');
			$(btn).parent().remove();
		},

		/**
		 * for galleries (wpx-media-gallery)
		*/
		deleteImages: function(deleteBtn) {
			
			// only if something is selected
			if ($( '.wpx-gallery-image img.selected').length == 0) {
				// do nothing
			} else {
				var goAhead = confirm("Are you sure? This cannot be undone.");
				if (goAhead) {
					// get the chosen set (across the whole page)
					var set = $( '.wpx-gallery-image img.selected');
					// create empty array
					var deleteQueueArray = new Array();
					// add selected images to the set
					$(set).each(function( index ) {
						var imageID = $(this).parent().attr('data-id');
						deleteQueueArray.push(imageID);
					});
					// make sure there are no duplicates
					deleteQueueArray = deleteQueueArray.unique();
					// set the hidden field queue
					$('#wpx_prep_gallery_delete_set').val(deleteQueueArray.join(','));
					// then remove this button & submit
					$(deleteBtn).parent().remove();
					if (wpx_fields.screenType == 'post-type') {
						wpx_fields.$postForm.submit();
					} else {
						wpx_fields.$submitTrigger.trigger( "click" );
					}
				} else {
					// do nothing
				}
			}
		},

		/**
		 * for individual images (wpx-media)
		*/
		deleteImageField: function(deleteBtn) {
			// a field to collect this images
			var goAhead = confirm("Are you sure? This cannot be undone.");
			if (goAhead) {
				// get the id of the image to delete
				var attachmentID = $(deleteBtn).attr('data-id');
				// reset the field to this image ID
				$('#wpx_prep_gallery_delete_image').val(attachmentID);
				// clear out the associated field type field & image
				$(deleteBtn).parent().parent().parent().parent().find('.wpx-preview-image').remove();
				$(deleteBtn).parent().parent().find('.wpx-media, .mce-wpx-media').val('');
				$(deleteBtn).parent().parent().removeClass('wpx-has-image');
				// then remove this button
				$(deleteBtn).parent().remove();
				// and submit
				if (wpx_fields.screenType == 'post-type') {
					wpx_fields.$postForm.submit();
				} else {
					wpx_fields.$submitTrigger.trigger( "click" );
				}
			} else {
				// do nothing
			}
		},
 
		/**
		 * wpx_select_fields(); field type
		*/
		createMetaboxOptions: function(whichbox, parts) {
			// add the item to the options
			var piece = parts.split(wpx.universalSeparator);
			var li = '<li><input type="checkbox" checked="checked" name="'+piece[2]+'[]" value="'+piece[0]+'">'+piece[1]+'</li>';
			$(whichbox).parent().parent().find('.wpx-checkbox-multi ul').append(li);
			// remove it from the select
			var option = 'option[value="'+parts+'"]';
			$(whichbox).find(option).remove();
		},

		/**
		 * these hidden fields are used to track which images
		 * should be deleted; we inject a hidden div that will be processed on update
		*/
		injectGalleryPrep: function() {
			if (wpx_fields.$postForm) {
				wpx_fields.$postForm.append('<input type="hidden" name="wpx_prep_gallery_delete_image" id="wpx_prep_gallery_delete_image" value="false">');
				wpx_fields.$postForm.append('<input type="hidden" name="wpx_prep_gallery_delete_set" id="wpx_prep_gallery_delete_set" value="">');
			}
		}


	};

	/**
	 * makes arrays have unique values
	*/
	Array.prototype.unique = function () {
		var arr = this;
		return $.grep(arr, function (v, i) {
			return $.inArray(v, arr) === i;
		});
	}

	wpx_fields.init();

});