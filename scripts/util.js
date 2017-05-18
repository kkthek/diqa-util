(function($) {

	window.DIQAUTIL = window.DIQAUTIL || {};
	DIQAUTIL.Util = DIQAUTIL.Util || {};

	DIQAUTIL.Util.AjaxIndicator = function() {

		var that = {};
		/**
		 * Ajax indicator for the whole page
		 */
		that.setGlobalLoading = function(state) {
			
			if ($('.globalSpinner').length == 0) {
				$('body').append($('<div class="globalSpinner" style="display: none;"></div>'))
			}
			var wgScriptPath = mw.config.get('wgScriptPath');
			css = {
				'background-image' : 'url(' + wgScriptPath
						+ '/extensions/Util/skins/ajax-preview-loader.gif)',
				'background-repeat' : 'no-repeat',
				'background-position' : 'center'
			};

			if (state) {
				$('.globalSpinner').css(css).show();
			} else {
				$('.globalSpinner').css(css).hide();
			}
		};

		/**
		 * Returns current state of the Ajax indicator 
		 */
		that.getGlobalLoading = function() {
			if ($('.globalSpinner').length == 0) {
				return false;
			}
			return $('.globalSpinner').is(':visible') ;
		};

		return that;
	};
	
	DIQAUTIL.Util.openInFancyBox = function () {
		
	        $('a.imageOverlay').each(function(){
		        $(this).click( function(){
					jQuery.fancybox({
						'href' : $(this).attr('href'),
						'width' : '100%',
						'height' : '100%',	
						'border-width' : '0px',
						'autoScale' : true,
						'autoDimensions' : true,
						'transitionIn' : 'none',
						'transitionOut' : 'none',
						'type' : 'iframe',
						'overlayColor' : '#222',
						'overlayOpacity' : '1.0',
						'hideOnContentClick' : true,
						'scrolling' : 'auto'
					});
					return false;
		        });
	        });
	};

	
	$(function(){
		DIQAUTIL.Util.openInFancyBox();
	});	

})(jQuery);

