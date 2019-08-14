/**
 * Controls toggling the "enabled" state of the "Country of Origin" in the
 * i-parcel Shipping Method.
 */

toggleCountryOfOrigin = function($altEnabled, $corObject) {
    if ($altEnabled.value == '1') {
        $corObject.enable();
    } else {
        $corObject.disable();
    }
};

document.observe('dom:loaded', function(event) {
    $alternateEnabledObject = $$('#carriers_i-parcel_choose_domestic').first();
    $countryOfOriginObject = $$('#carriers_i-parcel_origin_country_id').first();
    if (typeof($alternateEnabledObject) === 'object') {
        toggleCountryOfOrigin($alternateEnabledObject, $countryOfOriginObject);
        $alternateEnabledObject.observe('change', function(event) {
            toggleCountryOfOrigin($(this), $countryOfOriginObject);
        });
    }
});
