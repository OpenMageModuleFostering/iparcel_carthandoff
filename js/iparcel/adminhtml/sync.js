/**
 * js lib for i-parcel's ajax sync
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
var iparcelSync = function (initUrl, uploadUrl, step, startButton) {
    this.initUrl = initUrl;
    this.uploadUrl = uploadUrl;
    this.step = step;
    this.startButton = startButton;

    this.progress = 0;
    this.errors = 0;
    this.count = 0;
    this.page = 1;
    this.skus = [];

    this.message = jQuery('#message');
    this.log = jQuery('#log-area');
};

iparcelSync.prototype.run = function () {
    this.message.html('<p>Catalog Sync running...</p>');
    this.addToLog("Starting Catalog Sync...");
    this.addToLog("Generating colleciton of products to sync. This may take a moment...");
    this.startButton.disable();
    var self = this;
    jQuery.get(this.initUrl, function(data) {
        self.setCount(data.count);
        self.count = data.count;
        self.upload();
    });

};

iparcelSync.prototype.upload = function() {
    if (this.page == 0
        || (this.count / this.step) > this.page - 1) {
        var payload = {
            page: this.page,
            step: this.step
        };
        var self = this;
        jQuery.get(this.uploadUrl, payload)
            .done(function(data) {
                // Handle errors from the PHP controller
                if (data.error == 1) {
                    self.addToLog('Unable to complete sync.');
                    self.errors += 1;
                    this.finish();
                } else {
                    self.progress = self.progress + data.uploaded;
                    self.page = ++data.page;
                    self.skus = data.SKUs;
                    self.updateProgress();
                    self.upload();
                }
            });
    } else {
        this.finish();
    }
};

iparcelSync.prototype.finish = function() {
    this.message.html('<p>Catalog Sync Finished.</p>');
    this.addToLog('Finished uploading a total of '
        + this.progress +
        ' compatible SKUs and variations with '
        + this.errors
        + ' errors. \n');

    this.progress = 0;
    this.errors = 0;
    this.count = 0;
    this.page = 0;

    this.message.html('<p>Click "Start" to begin the Catalog Sync</p>');

    this.startButton.enable();
};

iparcelSync.prototype.updateProgress = function() {
    this.addToLog(
        'Uploaded ' + this.progress + ' products of ' + this.count + '... Synced SKUS "' +
        this.skus[0] + '" through "' + this.skus[1] + '"'
    );
};

iparcelSync.prototype.setCount = function(count) {
    this.addToLog('Found a total of ' + count + ' products');
};

iparcelSync.prototype.addToLog = function (text) {
    text = new Date().toISOString() + ": " + text + "\n";
    this.log.append(text);
    this.log.scrollTop(this.log[0].scrollHeight - this.log.height());
};
