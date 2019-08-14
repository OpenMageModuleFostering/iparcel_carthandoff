<?php
/**
 * Block for checkout button
 *
 * @category    Iparcel
 * @package     Iparcel_CartHandoff
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_CartHandoff_Block_Button extends Mage_Core_Block_Template
{
    /**
     * Return checkout URL for Cart Handoff
     *
     * @return string
     */
    public function getCheckoutUrl()
    {
        return Mage::getUrl('ipcarthandoff/handoff/begin');
    }

    /**
     * Return configured button title
     *
     * @return string
     */
    public function getButtonTitle()
    {
        return Mage::getStoreConfig('payment/ipcarthandoff/title');
    }

    /**
     * Determines if the button should be displayed in this position
     *
     * @return boolean
     */
    public function shouldDisplay()
    {
        if (!Mage::getStoreConfig('payment/ipcarthandoff/display_on_cart')) {
            return false;
        }
        $configValue = Mage::getStoreConfig('payment/ipcarthandoff/cart_placement');
        $optionValues = Mage::getModel('ipcarthandoff/system_config_source_buttonplacement')->toArray();

        if ($optionValues[$configValue] == 'both') {
            return true;
        }

        $nameInLayout = $this->getNameInLayout();
        if (preg_match('/top$/', $nameInLayout)) {
            $position = 'top';
        } else {
            $position = 'bottom';
        }

        if ($position == $optionValues[$configValue]) {
            return true;
        }

        return false;
    }

    /**
     * Adds style to the button's <p> tag. Used to add padding in mini-cart.
     *
     * @return string
     */
    public function getStyle()
    {
        $nameInLayout = $this->getNameInLayout();

        switch ($nameInLayout) {
            case "topcart.extra_actions.ipcarthandoff":
            case "cart_sidebar.extra_actions.ipcarthandoff":
                return "padding: 10px 0 10px 0;";
            default:
                return "";
        }

        return "";
    }
}