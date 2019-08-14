var $jip = jQuery.noConflict();
var iparcelPost = {
    sku: null,
    url: null,
    sku_list_cache: null,
    stock_list_cache: null,
    textSelectors: '.product-custom-option[type="text"], textarea.product-custom-option',
    clear: function () {
        $jip('.iparcelsku,.iparcelstock').remove();
    },
    single: function(sku, url){
        this.sku = sku;
        if (url.indexOf(':') == 0) {
            url = url.substring(1,url.length);
        }
        this.url = url+'iparcel/ajax/configurable';
        $jip(document).ready(function(){
            var $sku = $jip("<div/>");
            $sku.css("display","none");
            $sku.attr("class","iparcelsku");
            $sku.text(sku);
            $jip('form#product_addtocart_form').append($sku);

            var $options = $jip("<div/>");
            $options.css("display","none");
            $options.attr("class","iparceloptions");
            $jip('form#product_addtocart_form').append($options);

            $jip('.super-attribute-select').change(function(){
                iparcelPost.updateSelect();
            });

            // if there are '.super-attribute-select' elements, find the SKU
            // for the default configuration
            if ($jip('.super-attribute-select.required-entry').length > 0) {
                $jip('.super-attribute-select.required-entry').each(function() {
                    this.validOptions = [];

                    var self = this;
                    $jip(this).children().each(function(){
                        if ($jip(this).val() != '') {
                            self.validOptions.push($jip(this).val());
                        }
                    });

                    if (this.validOptions.length == 1) {
                        // Only one valid option for this required select
                        $jip(this).val(this.validOptions[0]);
                    }
                });
                iparcelPost.updateSelect();
            }
            // Watch for custom option text fields, areas
            $jip(iparcelPost.textSelectors).change(function() {
                iparcelPost.updateTextfields();
            });

            var $iterator = 0;
            $jip(document).bind('DOMNodeInserted', function(e){
                if ($jip(e.target).attr('class') == 'swatch-link'){
                    $jip(e.target).click(iparcelPost.update);
                    if ($iterator++ == 0){
                        var target = e.target;
                        setTimeout(function(){
                            if($iterator == 1){
                                $jip(target).click();
                            }
                        },500);
                    }
                }
            });

            $jip('.swatch-link').click(iparcelPost.update);
        });
    },
    update: function(){
        var $this = $jip(this);
        var id = $this.parent().attr('id').split('option')[1];
        var val = $this.attr('id').split('swatch')[1];
        var super_attribute = '';
        $jip('.super-attribute-select').each(function(){
            var $this = $jip(this);
            if ($this.attr('name') != 'super_attribute['+id+']'){
                super_attribute+=$this.attr('name')+'='+$this.val()+'&';
            }
        });
        iparcelMage.ajax.post(iparcelPost.sku,super_attribute,iparcelPost.url);
    },
    updateSelect: function () {
        iparcelPost.setStock('false');
        var $this = $jip(this);
        var super_attribute = '';
        $jip('.super-attribute-select, .product-custom-option').each(function () {
            var $this = $jip(this);
            super_attribute += $this.attr('name') + '=' + $this.val() + '&';
        });
        iparcelMage.ajax.post(iparcelPost.sku, super_attribute, iparcelPost.url);
    },
    updateTextfields: function() {
        iparcelPost.setStock('false');
        var custom_options = '';
        $jip(iparcelPost.textSelectors).each(function() {
            var $this = $jip(this);
            custom_options += $this.attr('name') + '=' + $this.val() + '&';
        });
        iparcelMage.ajax.post(iparcelPost.sku, custom_options, iparcelPost.url);
    },
    stock: function(qty){
        $jip(document).ready(function(){
            var $stock = $jip("<div/>");
            $stock.css("display","none");
            $stock.attr("class","iparcelstock");
            $stock.text(qty > 0 ? 'true' : 'false');
            $jip('form#product_addtocart_form').append($stock);
        });
    },
    setStock: function(value){
        $jip('.iparcelstock').text(value);
    },
    sku_list: function (sku_list) {
        this.sku_list_cache = sku_list;
        $jip(document).ready(function(){
            $jip.each(sku_list, function(sku,name){
                name = iparcelMage.parseHtmlEntities(name);
                var $sku = $jip("<div/>");
                $sku.css("display","none");
                $sku.attr("class","iparcelsku");
                $sku.text(sku);
                iparcelPost.append($sku,name);
            });
        });
        $jip(document).ajaxSuccess(function(data){
            iparcelPost.sku_list(sku_list);
        });
    },
    stock_list: function(stock_list){
        this.stock_list_cache = stock_list;
        $jip(document).ready(function(){
            $jip.each(stock_list, function(name,qty){
                name = iparcelMage.parseHtmlEntities(name);
                var $stock = $jip("<div/>");
                $stock.css("display","none");
                $stock.attr("class","iparcelstock");
                $stock.text(qty > 0 ? 'true' : 'false');
                iparcelPost.append($stock,name);
            });
        });
    },
    append: function(item,name){
        $jip('.item .product-name a:contains('+name+')').closest('.item').append(item);
    }
};

Ajax.Responders.register({
    onComplete: function(args){
        iparcelPost.sku_list(iparcelPost.sku_list_cache);
        iparcelPost.stock_list(iparcelPost.stock_list_cache);
        if( typeof iparcel.session !== "undefined" ) {
            if( typeof iparcel.session.content !== "undefined" ) {
                if( typeof iparcel.session.content.locale !== "undefined" && iparcel.session.content.locale == "US" ) {
                    $_ipar(iparcel.settings.productListingPriceElement).css("visibility","visible");
                }
                else{
                    iparcel.ux.displayEligibility();
                }
            }
        }
    }
});
