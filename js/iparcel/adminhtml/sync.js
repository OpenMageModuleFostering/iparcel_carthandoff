/**
 * js lib for i-parcel's ajax sync
 *
 * @category	Iparcel
 * @package		Iparcel_All
 * @author		Bobby Burden <bburden@i-parcel.com>
 */
var iparcelSync = {
	item: null,
	sync: null,
	end: null,

	/**
	 * Initialize sync ajax callback
	 */
	init: function (data){
		iparcelSync.item.count = data.count;
		iparcelSync.sync.eq(1).text(iparcelSync.item.count);
		iparcelSync.end.eq(1).text(iparcelSync.item.count);
		jQuery('#starting').hide();
		jQuery('#sync').show();
		jQuery.get(iparcelSync.item.url,{
			page: ++iparcelSync.item.page,
			step: iparcelSync.item.step,
			type: 'upload'
		},iparcelSync.refresh);
	},

	/**
	 * Refresh sync ajax callback
	 */
	refresh: function(data){
		if (!data.error){
			iparcelSync.item.progress += data.uploaded;
			iparcelSync.item.errors += iparcelSync.item.step-data.uploaded;
			iparcelSync.sync.eq(0).text(iparcelSync.item.progress);
			iparcelSync.end.eq(0).text(iparcelSync.item.progress);
		}else{
			if (iparcelSync.item.page*iparcelSync.item.step > iparcelSync.item.count){
				iparcelSync.item.errors += iparcelSync.item.count - iparcelSync.item.progress;
			}else{
				iparcelSync.item.errors += iparcelSync.item.step;
			}
		}
		if (iparcelSync.item.progress+iparcelSync.item.errors < iparcelSync.item.count){
			jQuery.get(iparcelSync.item.url,{
				page: ++iparcelSync.item.page,
				step: iparcelSync.item.step,
				type: 'upload'
			},iparcelSync.refresh);
		}else{
			iparcelSync.finish();
		}
	},

	/**
	 * Finish sync
	 */
	finish: function(){
		iparcelSync.end.eq(2).text(iparcelSync.item.errors);
		jQuery('#sync').hide();
		jQuery('#end').show();
	},

	/**
	 * Sync object
	 */
	sync: function(url, step){
		this.url = url;
		this.step = step;
		this.progress = 0;
		this.errors = 0;
		this.count = 0;
		this.page = 0;

		iparcelSync.sync = jQuery('#sync span');
		iparcelSync.end = jQuery('#end span');

		this.run = function (){
			jQuery.get(this.url,{type: 'init'}, iparcelSync.init);
		}

		iparcelSync.item = this;

		this.run();
	}
}
