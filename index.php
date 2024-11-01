<?php if ( ! defined( 'ABSPATH' ) ) exit; 
/*
	Plugin Name: Add to Cart Ajax for Hello Elementor
	Plugin URI: https://wordpress.org/add-to-cart-ajax-for-hello-elementor
	Description: Add to Cart Ajax for WordPress Theme Hello Elementor / Mini Cart.
	Version: 1.1.1
	Author: Fahad Mahmood
	Author URI: http://androidbubble.com/blog/
	Text Domain: atcafhe
	Domain Path: /languages/	
	License: GPL2
	
	This WordPress plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or any later version. This WordPress plugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. You should have received a copy of the GNU General Public License	along with this WordPress plugin. If not, see http://www.gnu.org/licenses/gpl-2.0.html.
*/


	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
	}else{
		 clearstatcache();
	}
	

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
	

	function mcafw_ds_sanitize_data( $input ) {
		if(is_array($input)){		
			$new_input = array();	
			foreach ( $input as $key => $val ) {
				$new_input[ $key ] = (is_array($val)?mcafw_ds_sanitize_data($val):stripslashes(sanitize_text_field( $val )));
			}			
		}else{
			$new_input = stripslashes(sanitize_text_field($input));			
			if(stripos($new_input, '@') && is_email($new_input)){
				$new_input = sanitize_email($new_input);
			}
			if(stripos($new_input, 'http') || wp_http_validate_url($new_input)){
				$new_input = sanitize_url($new_input);
			}			
		}	
		return $new_input;
	}
	function mcafw_ds_header_scripts(){
		//if(!is_user_logged_in()){ return; }
?>
	<style type="text/css">
		
		.woocommerce-mini-cart.cart.woocommerce-cart-form__contents span.quantity{
			display:block;
		}
		
	</style>
    <script type="text/javascript" language="javascript">
		jQuery(document).ready(function($){
			
			if($('body.product-template-default form.cart').length>0){
				
				var ajax_request_sent = false;
				
				$('body.product-template-default div.pc_configurator_form button.configurator-add-to-cart').off('click');
							
				$('body.product-template-default form.cart').on('click', 'button.single_add_to_cart_button:not(.disabled)', function (e){
					e.preventDefault();
					
					var form = $('body.product-template-default form.cart');
					setTimeout(function(){
						mcafw_ds_mini_cart_action(form);
					}, 1000);
					
				});
				$('body.product-template-default div.pc_configurator_form').on('click', 'button.configurator-add-to-cart:not(.disabled)', function (e){					
					e.preventDefault();
					var cart_form = $(this).parent().find('form.cart');							
					setTimeout(function(){
						if(mcafw_object.product_id>0){
							var errors = wp.hooks.applyFilters( 'PC.fe.validate_configuration', PC.fe.errors );
							
							if ( errors.length ) {
								
							}else{
								mcafw_ds_mini_cart_action(cart_form);
							}
						}
					}, 1000);		
					
				});


					
				$('body.product-template-default form.cart').on('submit', function(){
					
					var cart_form = $(this).parent().find('form.cart');
				
					if(cart_form.find('input[name="pc_configurator_data"]').length>0){						
						return false;
					}else{
						return true;
					}
				});
				
				
				
				function mcafw_ds_mini_cart_action(form){	
					
					if(ajax_request_sent){
						return;
					}
					ajax_request_sent = true;
				
					$.blockUI({message:''});
					
					if(form.find('input[name="product_id"]').length==0 && form.find('button[name="add-to-cart"]').length>0){
						form.append('<input type="hidden" name="product_id" value="'+form.find('button[name="add-to-cart"]').val()+'" />');
					}
					
					var data = {
						'action': 'mcafw_ds_mini_cart_action',
						'serialized': form.serialize(),						
						'pc_configurator_data': form.find('input[name="pc_configurator_data"]').val(),				
						'mcafw_ds_ajax_submission':true
					};
			
					
					$.post("<?php echo admin_url( 'admin-ajax.php' ); ?>", data, function (result)
					{
						//result = $.parseJSON(result);
						
						ajax_request_sent = false;	
						
						$.unblockUI();
						
						
						$(document.body).trigger('wc_fragment_refresh');
						
						setTimeout(function(){							
							if($('#elementor-menu-cart__toggle_button').length>0){
								$('#elementor-menu-cart__toggle_button').click();
							}
							$.unblockUI();
						}, 800);
												
						if($('.elementor-widget-woocommerce-menu-cart').length>0){
							$('.elementor-widget-woocommerce-menu-cart').addClass('elementor-menu-cart--shown');
						}
						
						
						
						/*
						var resp = result.resp;
						var updated_item = '';
					
						
						
						$.each(resp, function(i, v){
							
							var varitions = '';
							
							if(v.is_configurator_data=='true'){
								
								variations = '<dl class="variation"><dt class="variation-Configuration">Configuration:</dt><dd class="variation-Configuration">';
			
								$.each(v.variations, function(j, k){
									variations += '<div><strong>'+k.layer_name+'</strong><span class="semicol">:</span> <span class="choice-thumb"><img src="'+k.image+'" alt=""></span> '+k.name+'</div>';
								});
			
								variations += '</dd></dl>';
							
							}
							
							
							updated_item += '<div class="elementor-menu-cart__product woocommerce-cart-form__cart-item cart_item"><div class="elementor-menu-cart__product-image product-thumbnail"><a href="'+v.url+'"><img src="'+v.image+'" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" alt="" width="272" height="300"></a></div><div class="elementor-menu-cart__product-name product-name" data-title="Product"><a href="'+v.url+'">'+v.name+'</a>'+(v.is_configurator_data=='true'?variations:'')+'</div><div class="elementor-menu-cart__product-price product-price" data-title="Price"><span class="quantity"><span class="product-quantity">'+v.quantity+' ×</span> <span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">'+result.symbol+'</span>'+v.price+'</bdi></span></span></div><div class="elementor-menu-cart__product-remove product-remove"><a data-gtm4wp_product_id="'+v.product_id+'" data-gtm4wp_product_name="'+v.name+'" data-gtm4wp_product_price="'+v.price+'" data-gtm4wp_product_cat="" data-gtm4wp_product_url="'+v.url+'" data-gtm4wp_product_variant="'+v.variation_id+'" data-gtm4wp_product_stocklevel="+v.stock+" data-gtm4wp_product_brand="" href="'+v.remove_url+'" class="elementor_remove_from_cart_button" aria-label="" data-product_id="'+v.product_id+'" data-cart_item_key="'+i+'" data-product_sku="'+v.sku+'"></a><a href="'+v.remove_url+'" class="remove_from_cart_button" aria-label="" data-product_id="'+v.product_id+'" data-cart_item_key="'+i+'" data-product_sku="'+v.sku+'"></a> </div></div>';
							
							varitions = '&nbsp;';
							
        					//window.wooptpmDataLayer.cartItemKeys = window.wooptpmDataLayer.cartItemKeys || {};
							//window.wooptpmDataLayer.cartItemKeys["i"] = {"product_id":v.product_id,"variation_id":v.variation_id};
				        });
        
		
        				$('div.elementor-menu-cart__container div.widget_shopping_cart_content').html('<div class="elementor-menu-cart__products woocommerce-mini-cart cart woocommerce-cart-form__contents">'+updated_item+'</div><div class="elementor-menu-cart__subtotal"><strong>Subtotal:</strong> <span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">'+result.symbol+'</span>'+result.cart_total+'</bdi></span></div><div class="elementor-menu-cart__footer-buttons"><a href="'+result.cart_url+'" class="elementor-button elementor-button--view-cart elementor-size-md"><span class="elementor-button-text">View cart</span></a><a href="'+result.checkout_url+'" class="elementor-button elementor-button--checkout elementor-size-md"><span class="elementor-button-text"><?php _e("Checkout", "woocommerce"); ?></span></a>	</div>');
						
						$('span.elementor-button-icon').attr('data-counter', result.total_qty);
						
						*/
						
					});
					
					/*$('body').on('click', '.elementor-menu-cart__close-button', function(){
						$('.elementor-widget-woocommerce-menu-cart').removeClass('elementor-menu-cart--shown');
					});*/
				};
			
			}
			
			

		});
		
				
	</script>    
<?php		
	}
	
	add_action('wp_head', 'mcafw_ds_header_scripts');	
	
	function mcafw_ds_mini_cart_action(){
		
		if(!empty($_POST) && isset($_POST['mcafw_ds_ajax_submission'])){
			
			$ret = array();
			global $woocommerce;
			
			$posted = mcafw_ds_sanitize_data($_POST);
			
			if(array_key_exists('pc_configurator_data', $posted)){
				
				$pc_configurator_data = json_decode( stripcslashes( $posted['pc_configurator_data'] ) );
				parse_str($posted['serialized'], $posted);
				$posted['product_id'] = array_key_exists('add-to-cart', $posted)?$posted['add-to-cart']:0;
				$posted['variation_id'] = isset($posted['variation_id'])?$posted['variation_id']:0;	
				$posted['pc_configurator_data'] = $pc_configurator_data;
				
			}else{
				parse_str($posted['serialized'], $posted);
				$posted['gtm4wp_id'] = isset($posted['gtm4wp_id'])?$posted['gtm4wp_id']:0;
				$posted['product_id'] = isset($posted['product_id'])?$posted['product_id']:0;
				$posted['product_id'] = $posted['product_id']?$posted['product_id']:$posted['gtm4wp_id'];
				$posted['variation_id'] = isset($posted['variation_id'])?$posted['variation_id']:0;	
						
				
				
			}
			$items = $woocommerce->cart->get_cart();
			$woocommerce->cart->add_to_cart($posted['product_id'], $posted['quantity'], $posted['variation_id']);
			
			
			$items = $woocommerce->cart->get_cart();

			$total_qty = 0;
			$cart_total = 0;
			if(!empty($items)){
				
				foreach($items as $item_key=>$item_data){
					
					$is_configurator_data = array_key_exists('configurator_data', $item_data);
					
					$data = $item_data['data'];
					$data_arr = $data->get_data();
					
					$ret[$item_key]['quantity'] = $item_data['quantity'];
										
					$ret[$item_key]['product_id'] = $item_data['product_id'];
					$ret[$item_key]['variation_id'] = $item_data['variation_id'];
					
					$ret[$item_key]['url'] = get_permalink($item_data['product_id']);
					
					$ret[$item_key]['is_configurator_data'] = $is_configurator_data?'true':'false';
					
					if($is_configurator_data){
						
						$ret[$item_key]['url'] .= '?load_config_from_cart='.$item_key.'&amp;open_configurator=1';
						
						
						$configurator_data = $item_data['configurator_data'];
						
						$choices_layers = array();
				
						foreach($configurator_data as $cdata){
							if(array_key_exists('is_choice', $cdata)){
								if($cdata->is_choice){
									
									$choice_images = array_key_exists('images', $cdata->choice)?$cdata->choice['images']:array();
									if(!empty($choice_images)){
										
										foreach($choice_images as $image_key=>$image_options){
											if($image_options['thumbnail']['url']){
												$choices_layers[$cdata->choice['name']][$image_key] = $image_options['thumbnail']['url'];	
											}
										}
									}
								}
							}
						}
						
						$configurator_data_raw = array_key_exists('configurator_data_raw', $item_data)?$item_data['configurator_data_raw']:array();
						if(!empty($configurator_data_raw)){
							foreach($configurator_data_raw as $di => $data_raw){
								if($data_raw->is_choice){
									$ret[$item_key]['variations'][$di] = array('layer_name'=>$data_raw->layer_name,'name'=>$data_raw->name,'image'=>$choices_layers[$data_raw->name][0],'image_index'=>$data_raw->image);
								}
							}
						}
					}
					
					
					$ret[$item_key]['image'] = wp_get_attachment_url( get_post_thumbnail_id(($item_data['variation_id']?$item_data['variation_id']:$item_data['product_id'])), 'thumbnail' );
					
					$product = wc_get_product( $item_data['product_id']);
					
					if($product){
						$ret[$item_key]['price'] = $product->get_price();
					}else{
						$ret[$item_key]['price'] = $data_arr['price'];
					}

					$ret[$item_key]['name'] = $data_arr['name'];
					
					$ret[$item_key]['remove_url'] = wc_get_cart_remove_url( $item_key );
					$ret[$item_key]['sku'] = $data_arr['sku'];
					$ret[$item_key]['stock'] = $data_arr['stock_quantity'];
					
					$total_qty += $item_data['quantity'];
					$cart_total += $item_data['line_total'];
					
				}
			}
			
			echo json_encode(array(
									'resp'=>$ret, 
									'cart_url'=>wc_get_cart_url(), 
									'checkout_url'=>wc_get_checkout_url(), 
									'symbol'=>get_woocommerce_currency_symbol(),
									'total_qty'=> wc_price($total_qty),
									'cart_total'=> wc_price($cart_total)
							));
			exit;
		}
	}
	
	add_action( 'wp_ajax_mcafw_ds_mini_cart_action', 'mcafw_ds_mini_cart_action' );
	add_action( 'wp_ajax_nopriv_mcafw_ds_mini_cart_action', 'mcafw_ds_mini_cart_action' );	
	
	add_action('init', function(){
		if(isset($_GET['mcafw_ds_items_dump'])){
			global $woocommerce;
			$items = $woocommerce->cart->get_cart();
			$choices_layers = array();
			foreach($items as $item){
				$configurator_data = $item['configurator_data'];
				
				foreach($configurator_data as $cdata){
					if(array_key_exists('is_choice', $cdata)){
						if($cdata->is_choice){
							
							$choice_images = array_key_exists('images', $cdata->choice)?$cdata->choice['images']:array();
							if(!empty($choice_images)){
								
								foreach($choice_images as $image_key=>$image_options){
									if($image_options['thumbnail']['url']){
										$choices_layers[$cdata->choice['name']][$image_key] = $image_options['thumbnail']['url'];	
									}
								}
							}
						}
					}
				}
			}
			//pree($choices_layers);
		}
	});
	
	add_action( 'admin_enqueue_scripts', 'register_mcafw_ds_scripts' );
	add_action( 'wp_enqueue_scripts', 'register_mcafw_ds_scripts' );
	
	function register_mcafw_ds_scripts() {
		
		global $post;
		
		wp_enqueue_script(
			'mcafw-scripts',
			plugins_url('js/scripts.js', (__FILE__)),
			array('jquery')
		);	
		
		$translation_array = array(			
			'product_id' =>0,

        );
		
		if(is_object($post) && $post->post_type=='product'){			
			$translation_array['product_id'] = $post->ID;
		}
		
		
		wp_localize_script('mcafw-scripts', 'mcafw_object', $translation_array);
	
	}