var $jip = jQuery.noConflict();
var iparcelPost = {
    sku: null,
    url: null,
    sku_list_cache: null,
    stock_list_cache: null,

    // Class names
    iparcelSkuClass: 'iparcelsku',
    iparcelStockClass: 'iparcelstock',
    iparcelOptionsClass: 'iparcelOptions',
    swatchLinkClass: 'swatch-link',

    // Selectors
    productAddToCartFormSelector: 'form#product_addtocart_form',
    textSelector: '.product-custom-option[type="text"], textarea.product-custom-option',
    superAttributeSelector: '.super-attribute-select',
    requiredSuperAttributeSelector: '.super-attribute-select.required-entry',
    productCustomOptionSelector: '.super-attribute-select, .product-custom-option',
    productNameAnchorSelector: '.item .product-name a',
    itemSelector: '.item',

    updateProperties: function(sourceObject) {
	for (var propertyName in iparcelPost) {
	    if (propertyName.substr(-5) === 'Class'
		|| propertyName.substr(-8) === 'Selector')
	    {
		// Check for the propertyName to be set in the sourceObject
		if (sourceObject.hasOwnProperty(propertyName)
		    && sourceObject[propertyName] !== null)
		{
		    iparcelPost[propertyName] = sourceObject[propertyName];
		}
	    }
	}
    },

    clear: function() {
        $jip('.' + iparcelPost.iparcelSkuClass + ', .' + iparcelPost.iparcelStockClass).remove();
    },

    single: function(sku, url) {
        iparcelPost.sku = sku;

        if (url.indexOf(':') == 0) {
            url = url.substring(1,url.length);
        }

        iparcelPost.url = url+'iparcel/ajax/configurable';

        $jip(document).ready(function(){
            var $sku = $jip("<div/>");
            $sku.css("display", "none");
            $sku.attr("class", iparcelPost.iparcelSkuClass);
            $sku.text(sku);
            $jip(iparcelPost.productAddToCartFormSelector).append($sku);

            var $options = $jip("<div/>");
            $options.css("display", "none");
            $options.attr("class", iparcelPost.iparcelOptionsClass);
            $jip(iparcelPost.productAddToCartFormSelector).append($options);

            $jip(iparcelPost.superAttributeSelector).change(function(){
                iparcelPost.updateSelect();
            });

            // if there are '.super-attribute-select' elements, find the SKU
            // for the default configuration
            if ($jip(iparcelPost.requiredSuperAttributeSelector).length > 0) {
                $jip(iparcelPost.requiredSuperAttributeSelector).each(function() {
                    iparcelPost.validOptions = [];
                    var self = iparcelPost;

                    $jip(iparcelPost).children().each(function(){
                        if ($jip(iparcelPost).val() != '') {
                            self.validOptions.push($jip(iparcelPost).val());
                        }
                    });

                    if (iparcelPost.validOptions.length == 1) {
                        // Only one valid option for iparcelPost required select
                        $jip(iparcelPost).val(iparcelPost.validOptions[0]);
                    }
                });

                iparcelPost.updateSelect();
            }

            // Watch for custom option text fields, areas
            $jip(iparcelPost.textSelector).change(function() {
                iparcelPost.updateTextfields();
            });

            var $iterator = 0;

	    // Handle swatch clicks
            $jip(document).bind('DOMNodeInserted', function(e){
                if ($jip(e.target).attr('class') == iparcelPost.swatchLinkClass){
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

            $jip('.' + iparcelPost.swatchLinkClass).click(iparcelPost.update);
        });
    },

    update: function(){
        var $this = $jip(this);
        var id = $this.parent().attr('id').split('option')[1];
        var val = $this.attr('id').split('swatch')[1];
        var super_attribute = '';

        $jip(iparcelPost.superAttributeSelector).each(function(){
            var $this = $jip(this);
            if ($this.attr('name') != 'super_attribute['+id+']'){
                super_attribute += $this.attr('name') + '=' + $this.val() + '&';
            }
        });

        iparcelMage.ajax.post(iparcelPost.sku, super_attribute, iparcelPost.url);
    },

    updateSelect: function () {
        iparcelPost.setStock('false');
        var $this = $jip(this);
        var super_attribute = '';

        $jip(iparcelPost.productCustomOptionSelector).each(function () {
            var $this = $jip(this);
            super_attribute += $this.attr('name') + '=' + $this.val() + '&';
        });

        iparcelMage.ajax.post(iparcelPost.sku, super_attribute, iparcelPost.url);
    },

    updateTextfields: function() {
        iparcelPost.setStock('false');
        var custom_options = '';

        $jip(iparcelPost.textSelector).each(function() {
            var $this = $jip(this);
            custom_options += $this.attr('name') + '=' + $this.val() + '&';
        });

        iparcelMage.ajax.post(iparcelPost.sku, custom_options, iparcelPost.url);
    },

    stock: function(qty){
        $jip(document).ready(function() {
            var $stock = $jip("<div/>");
            $stock.css("display", "none");
            $stock.attr("class", iparcelPost.iparcelStockClass);
            $stock.text(qty > 0 ? 'true' : 'false');
            $jip(iparcelPost.productAddToCartFormSelector).append($stock);
        });
    },

    setStock: function(value){
        $jip('.' + iparcelPost.iparcelStockClass).text(value);
    },

    sku_list: function (sku_list) {
        iparcelPost.sku_list_cache = sku_list;

        $jip(document).ready(function() {
            $jip.each(sku_list, function(sku,name){
                name = iparcelMage.parseHtmlEntities(name);
                var $sku = $jip("<div/>");
                $sku.css("display", "none");
                $sku.attr("class", iparcelPost.iparcelSkuClass);
                $sku.text(sku);
                iparcelPost.append($sku,name);
            });
        });

        $jip(document).ajaxSuccess(function(data) {
            iparcelPost.sku_list(sku_list);
        });
    },

    stock_list: function(stock_list){
        this.stock_list_cache = stock_list;

        $jip(document).ready(function() {
            $jip.each(stock_list, function(name,qty){
                name = iparcelMage.parseHtmlEntities(name);
                var $stock = $jip("<div/>");
                $stock.css("display", "none");
                $stock.attr("class", iparcelPost.iparcelStockClass);
                $stock.text(qty > 0 ? 'true' : 'false');
                iparcelPost.append($stock,name);
            });
        });
    },

    append: function(item,name){
        var $item = $jip(iparcelPost.productNameAnchorSelector).filter(function () {
            return $jip(this).text() == name;
        });

        $item.closest(iparcelPost.itemSelector).append(item);
    }
};

Ajax.Responders.register({
    onComplete: function(args){
        iparcelPost.sku_list(iparcelPost.sku_list_cache);
        iparcelPost.stock_list(iparcelPost.stock_list_cache);
p
        if (typeof iparcel.session !== "undefined") {
            if (typeof iparcel.session.content !== "undefined") {
                if (typeof iparcel.session.content.locale !== "undefined" && iparcel.session.content.locale == "US") {
                    $_ipar(iparcel.settings.productListingPriceElement).css("visibility","visible");
                } else {
                    iparcel.ux.displayEligibility();
                }
            }
        }
    }
});
