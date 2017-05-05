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
	

})(jQuery);