document.observe("dom:loaded", function(){
    if (ipar_Session.locale != 'US') {
        setButtonVisibility('ipcarthandoff-button', 'block');
        setButtonVisibility('btn-proceed-checkout', 'none');
        setCheckoutMethodVisibility(false);
    }

    if ($('country')) {
        $('country').observe('change', function(){
            if (this.value == 'US') {
                setButtonVisibility('ipcarthandoff-button', 'none');
                setButtonVisibility('btn-proceed-checkout', 'block');
                setCheckoutMethodVisibility(true);
            } else {
                setButtonVisibility('ipcarthandoff-button', 'block');
                setButtonVisibility('btn-proceed-checkout', 'none');
                setCheckoutMethodVisibility(false);
            }
        });
    }
});

function setButtonVisibility(buttonClass, displayValue)
{
    if (buttonClass == 'btn-proceed-checkout' && cartHandoff_hideCheckout == false) {
        return true;
    }
    $$("." + buttonClass).each(function(div){
        $(div).style.display = displayValue;
    });
}

function setCheckoutMethodVisibility(visibility)
{
    // Give up if hideOtherMethods is disabled
    if (cartHandoff_hideOtherMethods == false) {
        return true;
    }

    $$('.checkout-types li').each(function(li){
        var keep = $(li).select('.btn-proceed-checkout, .ipcarthandoff-button').length;
        if (visibility == false && keep == 0) {
            $(li).style.display = 'none';
        } else {
            $(li).style.display = '';
        }
    });
}