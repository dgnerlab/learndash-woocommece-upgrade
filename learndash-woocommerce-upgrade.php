<?php
/**
 * Plugin Name: LearnDash LMS - WooCommerce Integration Upgrade
 * Plugin URI: https://github.com/dgnerlab
 * Description: More Powerful - 
 * Version: 1.0
 * Author: dgner
 * Author URI: https://github.com/dgnerlab
 * License: GPL3
 */

add_action('admin_footer', 'woocommerce_product_dropdown_to_learndash');

function woocommerce_product_dropdown_to_learndash() {
    $post_id = get_the_ID();
    $course_slug = str_replace(home_url(), '', get_permalink($post_id));

    $products = wc_get_products(['status' => 'publish', 'limit' => -1]);
    $products_data = [];
    foreach ($products as $product) {
        $products_data[] = [
            'ID' => $product->get_id(),
            'post_title' => $product->get_name(),
            'price' => $product->get_price(),
            'url' => $course_slug . '?add-to-cart=' . $product->get_id()
        ];
    }
    $products_json = json_encode($products_data);

    $linked_products = get_posts([
        'post_type' => 'product',
        'meta_query' => [
            [
                'key' => '_related_course',
                'value' => $post_id,
                'compare' => 'LIKE'
            ]
        ]
    ]);
    $linked_product_id = (count($linked_products) == 1) ? $linked_products[0]->ID : null;

    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const products = <?php echo $products_json; ?>;
            const linkedProductId = <?php echo json_encode($linked_product_id); ?>;
            const buttonURLField = document.getElementById('learndash-course-access-settings_course_price_type_closed_custom_button_url');
            const coursePriceField = document.getElementById('learndash-course-access-settings_course_price_type_closed_price');
            const targetElement = document.getElementById('learndash-course-access-settings_course_price_type_closed_custom_button_url_field');
            
            const dropdownHTML = `
                <div class="sfwd_input sfwd_input_type_select">
                    <span class="sfwd_option_label">
                        <a class="sfwd_help_text_link" style="cursor:pointer;" title="Click for Help!" onclick="toggleVisibility('learndash-product-selection-tip');">
                            <img alt="Help Icon" src="/wp-content/plugins/sfwd-lms/assets/images/question.png">
                            <label class="sfwd_label">WooCommerce</label>
                        </a>
                        <div id="learndash-product-selection-tip" class="sfwd_help_text_div" style="display: none;">
                            <label class="sfwd_help_text">When a WooCommerce product is linked to a course, the price and the 'Add to Cart' link are automatically generated, followed by an 'Integration Success' message. However, if multiple products are linked or none at all, this function doesn't take any action.</label>
                        </div>
                    </span>
                    <span class="sfwd_option_input">
                        <div class="sfwd_option_div">
                            <select id="woocommerce_product_dropdown" style="width:100%;">
                                <option value="">Manual Product Selection</option>
                                ${products.map(product => `<option value="${product.ID}" data-price="${product.price}" data-url="${product.url}">${product.post_title}</option>`).join('')}
                            </select>
                        </div>
                        <button type="button" id="automaticSelection" style="margin-top:10px; width:100%; line-height:30px;">Auto Update</button>
                    </span>
                    <p class="ld-clear"></p>
                </div>
            `;

            targetElement.insertAdjacentHTML('afterend', dropdownHTML);

            const autoUpdateButton = document.getElementById('automaticSelection');
            const productDropdown = document.getElementById('woocommerce_product_dropdown');

            productDropdown.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const selectedProduct = products.find(product => product.ID == selectedOption.value);
                updateFields(selectedProduct);
            });

            autoUpdateButton.addEventListener('click', function() {
                applyLinkedProduct();
            });

            function updateFields(product) {
                if (product) {
                    buttonURLField.value = product.url;
                    coursePriceField.value = product.price;
                    updateButtonStyle(product.ID == linkedProductId);
                } else {
                    buttonURLField.value = '';
                    coursePriceField.value = '';
                    updateButtonStyle(false);
                }
            }

            function updateButtonStyle(isProductLinked) {
                if(isProductLinked) {
                    autoUpdateButton.style.backgroundColor = '#98FB98';
                    autoUpdateButton.innerText = 'Auto Update (Integration Success)';
                } else {
                    autoUpdateButton.style.backgroundColor = '';
                    autoUpdateButton.innerText = 'Auto Update';
                }
            }

            function applyLinkedProduct() {
                const linkedProduct = products.find(product => product.ID == linkedProductId);
                updateFields(linkedProduct);
                productDropdown.value = linkedProduct ? linkedProductId : '';
            }

            if(!buttonURLField.value && !coursePriceField.value) {
                applyLinkedProduct();
            }

            const radioLabel = document.querySelector('label[for="learndash-course-access-settings_course_price_type-closed"]');
            if (radioLabel) {
                radioLabel.innerHTML = radioLabel.innerHTML.replace('Closed', 'Closed (with WooCommerce)');
            }

        });
    </script>
    <?php
}





?>
