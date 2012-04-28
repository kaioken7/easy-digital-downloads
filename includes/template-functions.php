<?php

// automatically appends the purchase link to download content, if enabled
function edd_append_purchase_link($content) {
	global $post;
	if($post->post_type == 'download' && is_singular() && is_main_query()) {
		if(!get_post_meta($post->ID, '_edd_hide_purchase_link', true)) {			
			$button_text = get_post_meta($post->ID, '_edd_purchase_text', true) ? get_post_meta($post->ID, '_edd_purchase_text', true) : __('Purchase', 'edd');
			$style = get_post_meta($post->ID, '_edd_purchase_style', true) ? get_post_meta($post->ID, '_edd_purchase_style', true) : 'button';
			$color = get_post_meta($post->ID, '_edd_purchase_color', true);
			$content .= edd_get_purchase_link($post->ID, $button_text, $style, $color);
		}
	}
	return $content;
}
add_filter('the_content', 'edd_append_purchase_link');

function edd_get_purchase_link($download_id = null, $link_text = 'Purchase', $style = 'button', $color = 'blue') {
	global $edd_options, $post, $user_ID;

	$page = get_permalink($post->ID); // current page
	$link = add_query_arg('download_id', $download_id, add_query_arg('edd_action', 'add_to_cart', $page));
	$variable_pricing = get_post_meta($download_id, '_variable_pricing', true);
	
	$purchase_form = '<form id="edd_purchase_' . $download_id . '" action="" method="POST">';
		
		if($variable_pricing) {
			$prices = get_post_meta($download_id, 'edd_variable_prices', true);
			$purchase_form .= '<div class="edd_price_options">';
				if($prices) {
					foreach($prices as $key => $price) {
						$checked = '';
						if($key == 0) {
							$checked = 'checked="checked"';
						}
						$purchase_form .= '<input type="radio" ' . $checked . ' name="edd_options[price_id]" id="edd_price_option_' . $key . '" value="' . $key . '"/>&nbsp;';
						$purchase_form .= '<label for="edd_price_option_' . $key . '">' . $price['name'] . ' - ' . edd_currency_filter($price['amount']) . '</label><br/>';
					}
				}
			$purchase_form .= '</div><!--end .edd_price_options-->';
		}
		
		$purchase_form .= '<div class="edd_purchase_submit_wrapper">';
		
			if(edd_has_user_purchased($user_ID, $download_id)) {
				do_action('edd_has_purchased_item_message', $user_ID, $download_id);
			}
		
			if($style == 'button') {
				
				/*
				* this is the old purchase link, before it was changed to a form. Left here for reference, if needed
				$link = '<a href="' . $link . '" class="edd-add-to-cart edd_button edd_' . $color . '" data-action="edd_add_to_cart" data-download-id="' . $download_id . '">';
					$link .= '<span class="edd_button_outer"><span class="edd_button_inner">';
						$link .= '<span class="edd_button_text"><span>' . $link_text . '</span><span style="display:none">' . __('Checkout', 'edd') . '</span></span>';
					$link .= '</span></span>';
				$link .= '</a>';
				*/
				
				$purchase_button = '<span class="edd_button edd_' . $color . '"><span class="edd_button_outer"><span class="edd_button_inner"><input type="submit" class="edd_button_text edd-submit" id="edd-purchase-button" name="edd_purchase_download" value="' . $link_text . '"/></span></span></span>';
				
				$purchase_form .= $purchase_button;
				
			} else {
				//$purchase_form .= '<a href="' . $link . '" class="edd-add-to-cart" data-action="edd_add_to_cart" data-download-id="' . $download_id . '"><span class="edd_link_text"><span>' . $link_text . '</span><span style="display:none">' . __('Checkout', 'edd') . '</span></span></a>';
				
				$purchase_text = '<input type="submit" class="edd_submit_plain" id="edd-purchase-text" name="edd_purchase_download" value="' . $link_text . '"/>';
				
				$purchase_form .= $purchase_text;
			}
			if(edd_is_ajax_enabled()) {
				$purchase_form .= '<img src="' . EDD_PLUGIN_URL . 'includes/images/loading.gif" class="edd-cart-ajax" style="display: none;"/>';
				$purchase_form .= '&nbsp;<span style="display:none;" class="edd-cart-added-alert">' . __('added to your cart', 'edd') . '</span>';
			}
	
		$purchase_form .= '</div><!--end .edd_purchase_submit_wrapper-->';	
		$purchase_form .= '<input type="hidden" name="download_id" value="' . $download_id . '">';
		$purchase_form .= '<input type="hidden" name="edd_action" value="add_to_cart">';
	$purchase_form .= '</form><!--end #edd_purchase_' . $download_id . '-->';
		
	return apply_filters('edd_purchase_download_form', $purchase_form, $download_id, $link_text, $style, $color);
}

function edd_remove_item_url($cart_key, $post, $ajax = false) {
	global $post;
	$current_page = $ajax ? home_url() : get_permalink($post->ID);
	$remove_url = add_query_arg('cart_item', $cart_key, add_query_arg('edd_action', 'remove', $current_page));
	return apply_filters('edd_remove_item_url', $remove_url);
}

function edd_filter_success_page_content($content) {
	
	global $edd_options;
	
	if(isset($edd_options['success_page']) && isset($_GET['payment-confirmation']) && is_page($edd_options['success_page'])) {
		
		if(has_filter('edd_payment_confirm_' . $_GET['payment-confirmation'])) {
			$content = apply_filters('edd_payment_confirm_' . $_GET['payment-confirmation'], $content);
		}
	}
	return $content;
}
add_filter('the_content', 'edd_filter_success_page_content');

function edd_get_button_colors() {
	return apply_filters('edd_button_colors', array('gray', 'pink', 'blue', 'green', 'teal', 'black', 'dark gray', 'orange', 'purple', 'slate'));
}

function edd_show_has_purchased_item_message($user_id, $download_id) {
	echo __('You have already purchased this item,but you may purchase it again.', 'edd');
}
add_action('edd_has_purchased_item_message', 'edd_show_has_purchased_item_message', 10, 2);