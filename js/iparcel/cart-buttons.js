document.observe("dom:loaded", function(){
    if (ipar_Session.locale != 'US') {
        setButtonVisibility('ipcarthandoff-button', 'block');
        setButtonVisibility('btn-proceed-checkout', 'none');
    }

    if ($('country')) {
        $('country').observe('change', function(){
            if (this.value == 'US') {
                setButtonVisibility('ipcarthandoff-button', 'none');
                setButtonVisibility('btn-proceed-checkout', 'block');
            } else {
                setButtonVisibility('ipcarthandoff-button', 'block');
                setButtonVisibility('btn-proceed-checkout', 'none');
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
