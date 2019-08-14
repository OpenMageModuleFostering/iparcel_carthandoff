var iparcelMage = {
    displayEligibility: function() {
        try{
            $_ipar.fn.iparcel.ux.displayEligibility();
        } catch(exception) {
        }
    },
    ajax: {
        post: function(sku, super_attribute, url) {
            var $jip = jQuery.noConflict();
            var data = super_attribute+'sku='+sku;
            $jip('.' + iparcelPost.iparcelSkuClass).attr('finalsku', 'false');
            $jip.ajax({
                'url': url,
                'data': data,
                type: 'POST',
                async: true,
                success: function(data){
                    if (data){
                        $jip('.' + iparcelPost.iparcelSkuClass).text(data.sku);
                        $jip('.' + iparcelPost.iparcelSkuClass).attr('finalsku', 'true');
    
                        iparcelPost.setStock('true');
    
                        var $options = $jip('.' + iparcelPost.iparcelOptionsClass);
                        $options.empty();
                        $jip.each(data.attributes, function(key, value){
                            var $block = $jip('<div/>');
                            $block.attr('id',key);
                            $block.text(value);
                            $options.append($block);
                        });
    
                        $jip('.iparcelstockquantity').text(data.stock);
                    }
                    iparcelMage.displayEligibility();
                }
            });
        }
    },
    
    parseHtmlEntities: function(str) {
        var textArea = document.createElement('textarea');
        textArea.innerHTML = str;
        return textArea.value;
    }
};
