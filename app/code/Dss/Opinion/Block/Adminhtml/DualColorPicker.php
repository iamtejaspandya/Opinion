<?php

declare(strict_types=1);

/**
 * Digit Software Solutions.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 *
 * @category  Dss
 * @package   Dss_Opinion
 * @author    Extension Team
 * @copyright Copyright (c) 2025 Digit Software Solutions. ( https://digitsoftsol.com )
 */

namespace Dss\Opinion\Block\Adminhtml;

use Dss\Core\Block\Adminhtml\ColorPicker as BaseColorPicker;
use Magento\Framework\Data\Form\Element\AbstractElement;

class DualColorPicker extends BaseColorPicker
{
    /**
     * Render two color pickers in one config field
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $value = $element->getData('value');
        [$color1, $color2] = explode(',', $value . ',');

        $inputId1 = $element->getHtmlId() . '_1';
        $inputId2 = $element->getHtmlId() . '_2';
        $inputName = $element->getName();

        $html = '
            <label>Liked: </label>
            <input type="text"
                id="' . $inputId1 . '"
                placeholder="4caf50"
                class="input-text"
                value="' . $color1 . '"
                style="width:100px; margin-right:10px;" />
            <label>Disliked: </label>
            <input type="text"
                id="' . $inputId2 . '"
                placeholder="f44336"
                class="input-text"
                value="' . $color2 . '"
                style="width:100px;" />
            <input type="hidden"
                id="' . $element->getHtmlId() . '"
                name="' . $inputName . '"
                value="' . $value . '" />
        ';

        $html .= '<script>
            require([
                "jquery",
                "jquery/colorpicker/js/colorpicker",
                "domReady!"
            ], function ($) {
                var color1 = $("#' . $inputId1 . '");
                var color2 = $("#' . $inputId2 . '");
                var hidden = $("#' . $element->getHtmlId() . '");

                function updateHidden() {
                    hidden.val(color1.val() + "," + color2.val());
                }

                function initPicker(el) {
                    el.ColorPicker({
                        layout: "hex",
                        onChange: function (hsb, hex, rgb) {
                            el.css("background-color", "#" + hex);
                            el.val(hex);
                            updateHidden();
                        }
                    }).keyup(function () {
                        el.ColorPickerSetColor(el.val());
                        el.css("background-color", "#" + el.val());
                        updateHidden();
                    });

                    el.css("background-color", "#" + el.val());
                }

                initPicker(color1);
                initPicker(color2);
            });
        </script>';

        return $html;
    }
}
