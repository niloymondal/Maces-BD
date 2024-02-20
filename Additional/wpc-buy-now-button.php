<?php


defined( 'ABSPATH' ) || exit;

! defined( 'WPCBN_VERSION' ) && define( 'WPCBN_VERSION', '1.3.0' );
! defined( 'WPCBN_LITE' ) && define( 'WPCBN_LITE', __FILE__ );
! defined( 'WPCBN_FILE' ) && define( 'WPCBN_FILE', __FILE__ );
! defined( 'WPCBN_URI' ) && define( 'WPCBN_URI', plugin_dir_url( __FILE__ ) );
! defined( 'WPCBN_REVIEWS' ) && define( 'WPCBN_REVIEWS', 'https://wordpress.org/support/plugin/wpc-buy-now-button/reviews/?filter=5' );
! defined( 'WPCBN_CHANGELOG' ) && define( 'WPCBN_CHANGELOG', 'https://wordpress.org/plugins/wpc-buy-now-button/#developers' );
! defined( 'WPCBN_DISCUSSION' ) && define( 'WPCBN_DISCUSSION', 'https://wordpress.org/support/plugin/wpc-buy-now-button' );
! defined( 'WPC_URI' ) && define( 'WPC_URI', WPCBN_URI );

include 'includes/dashboard/wpc-dashboard.php';
include 'includes/kit/wpc-kit.php';
include 'includes/hpos.php';

if ( ! function_exists( 'wpcbn_init' ) ) {
	add_action( 'plugins_loaded', 'wpcbn_init', 11 );

	function wpcbn_init() {
		// load text-domain
		load_plugin_textdomain( 'wpc-buy-now-button', false, basename( __DIR__ ) . '/languages/' );

		if ( ! function_exists( 'WC' ) || ! version_compare( WC()->version, '3.0', '>=' ) ) {
			add_action( 'admin_notices', 'wpcbn_notice_wc' );

			return null;
		}

		if ( ! class_exists( 'WPCleverWpcbn' ) && class_exists( 'WC_Product' ) ) {
			class WPCleverWpcbn {
				protected static $param;
				protected static $settings = [];
				protected static $instance = null;

				public static function instance() {
					if ( is_null( self::$instance ) ) {
						self::$instance = new self();
					}

					return self::$instance;
				}

				function __construct() {
					self::$settings = (array) get_option( 'wpcbn_settings', [] );
					self::$param    = self::get_setting( 'parameter', 'buy-now' );

					add_action( 'init', [ $this, 'init' ] );
					add_filter( 'body_class', [ $this, 'body_class' ] );
					add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
					add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
					add_action( 'admin_init', [ $this, 'register_settings' ] );
					add_action( 'admin_menu', [ $this, 'admin_menu' ] );
					add_filter( 'plugin_action_links', [ $this, 'action_links' ], 10, 2 );
					add_filter( 'plugin_row_meta', [ $this, 'row_meta' ], 10, 2 );

					// add button for archive
					$position_archive = apply_filters( 'wpcbn_button_position_archive', self::get_setting( 'button_position_archive', 'after_add_to_cart' ) );

					switch ( $position_archive ) {
						case 'after_title':
							add_action( 'woocommerce_shop_loop_item_title', [ $this, 'button_archive' ], 11 );
							break;
						case 'after_rating':
							add_action( 'woocommerce_after_shop_loop_item_title', [ $this, 'button_archive' ], 6 );
							break;
						case 'after_price':
							add_action( 'woocommerce_after_shop_loop_item_title', [ $this, 'button_archive' ], 11 );
							break;
						case 'before_add_to_cart':
							add_action( 'woocommerce_after_shop_loop_item', [ $this, 'button_archive' ], 9 );
							break;
						case 'after_add_to_cart':
							add_action( 'woocommerce_after_shop_loop_item', [ $this, 'button_archive' ], 11 );
							break;
					}

					// add button for single
					$position_single = apply_filters( 'wpcbn_button_position_single', self::get_setting( 'button_position_single', 'after_add_to_cart' ) );

					switch ( $position_single ) {
						case 'before_add_to_cart':
							add_action( 'woocommerce_after_add_to_cart_quantity', [ $this, 'button_single' ] );
							break;
						case 'after_add_to_cart':
							add_action( 'woocommerce_after_add_to_cart_button', [ $this, 'button_single' ] );
							break;
					}

					// add to cart
					add_action( 'template_redirect', [ $this, 'template_redirect' ] );
					add_filter( 'woocommerce_add_to_cart_redirect', [ $this, 'add_to_cart_redirect' ], 9999 );
				}

				public static function get_settings() {
					return apply_filters( 'wpcbn_get_settings', self::$settings );
				}

				public static function get_setting( $name, $default = false ) {
					if ( ! empty( self::$settings ) && isset( self::$settings[ $name ] ) ) {
						$setting = self::$settings[ $name ];
					} else {
						$setting = get_option( 'wpcbn_' . $name, $default );
					}

					return apply_filters( 'wpcbn_get_setting', $setting, $name, $default );
				}

				function init() {
					// parameter
					self::$param = apply_filters( 'wpcbn_parameter', ( ! empty( self::$param ) ? sanitize_title( self::$param ) : 'buy-now' ) );

					// shortcode
					add_shortcode( 'wpcbn_btn_archive', [ $this, 'archive_shortcode' ] );
					add_shortcode( 'wpcbn_btn_single', [ $this, 'single_shortcode' ] );
				}

				function body_class( $classes ) {
					if ( self::get_setting( 'hide_atc', 'no' ) === 'yes' ) {
						$classes[] = 'wpcbn-hide-atc';
					}

					return $classes;
				}

				function enqueue_scripts() {
					wp_enqueue_style( 'wpcbn-frontend', WPCBN_URI . 'assets/css/frontend.css', [], WPCBN_VERSION );
					wp_enqueue_script( 'wpcbn-frontend', WPCBN_URI . 'assets/js/frontend.js', [ 'jquery' ], WPCBN_VERSION, true );
				}

				function admin_enqueue_scripts() {
					wp_enqueue_script( 'wpcbn-backend', WPCBN_URI . 'assets/js/backend.js', [ 'jquery' ], WPCBN_VERSION, true );
				}

				function archive_shortcode( $atts ) {
					$output = '';

					$atts = shortcode_atts( [
						'id' => null
					], $atts, 'wpcbn_btn_archive' );

					$btn_text = self::get_setting( 'button_text', '' );

					if ( empty( $btn_text ) ) {
						$btn_text = esc_html__( 'Buy now', 'wpc-buy-now-button' );
					}

					$btn_text  = apply_filters( 'wpcbn_btn_archive_text', $btn_text, $atts );
					$btn_class = apply_filters( 'wpcbn_btn_archive_class', 'wpcbn-btn wpcbn-btn-archive button product_type_simple add_to_cart_button', $atts );
					$btn_href  = apply_filters( 'wpcbn_redirect', self::get_setting( 'redirect', 'checkout' ) ) === 'cart' ? wc_get_cart_url() : wc_get_checkout_url();

					if ( ! $atts['id'] ) {
						global $product;

						if ( $product && $product->is_type( 'simple' ) && $product->is_in_stock() && $product->is_purchasable() ) {
							$output .= sprintf( '<a href="%s?' . self::$param . '=%s" data-quantity="1" class="%s" data-product_id="%s" rel="nofollow">%s</a>', $btn_href, $product->get_ID(), $btn_class, $product->get_ID(), $btn_text );
						}
					} else {
						$output .= sprintf( '<a href="%s?' . self::$param . '=%s" data-quantity="1" class="%s" data-product_id="%s" rel="nofollow">%s</a>', $btn_href, absint( $atts['id'] ), $btn_class, absint( $atts['id'] ), $btn_text );
					}

					return apply_filters( 'wpcbn_btn_archive', $output, $atts );
				}

				function single_shortcode( $atts ) {
					$output = '';

					$atts = shortcode_atts( [
						'id' => null
					], $atts, 'wpcbn_btn_single' );

					$btn_text = self::get_setting( 'button_text', '' );

					if ( empty( $btn_text ) ) {
						$btn_text = esc_html__( 'Buy now', 'wpc-buy-now-button' );
					}

					$btn_text  = apply_filters( 'wpcbn_btn_single_text', $btn_text, $atts );
					$btn_class = apply_filters( 'wpcbn_btn_single_class', 'wpcbn-btn wpcbn-btn-single single_add_to_cart_button button alt', $atts );

					if ( ! $atts['id'] ) {
						global $product;

						if ( $product ) {
							$output .= sprintf( '<button type="submit" name="' . esc_attr( self::$param ) . '" value="%d" class="%s" data-product_id="%s">%s</button>', $product->get_ID(), $btn_class, $product->get_ID(), $btn_text );
						}
					} else {
						$output .= sprintf( '<button type="submit" name="' . esc_attr( self::$param ) . '" value="%d" class="%s" data-product_id="%s">%s</button>', absint( $atts['id'] ), $btn_class, absint( $atts['id'] ), $btn_text );
					}

					return apply_filters( 'wpcbn_btn_single', $output, $atts );
				}

				function action_links( $links, $file ) {
					static $plugin;

					if ( ! isset( $plugin ) ) {
						$plugin = plugin_basename( __FILE__ );
					}

					if ( $plugin === $file ) {
						$settings = '<a href="' . admin_url( 'admin.php?page=wpclever-wpcbn&tab=settings' ) . '">' . esc_html__( 'Settings', 'wpc-buy-now-button' ) . '</a>';
						array_unshift( $links, $settings );
					}

					return (array) $links;
				}

				function row_meta( $links, $file ) {
					static $plugin;

					if ( ! isset( $plugin ) ) {
						$plugin = plugin_basename( __FILE__ );
					}

					if ( $plugin === $file ) {
						$row_meta = [
							'support' => '<a href="' . esc_url( WPCBN_DISCUSSION ) . '" target="_blank">' . esc_html__( 'Community support', 'wpc-buy-now-button' ) . '</a>',
						];

						return array_merge( $links, $row_meta );
					}

					return (array) $links;
				}

				function register_settings() {
					// settings
					register_setting( 'wpcbn_settings', 'wpcbn_settings' );
				}

				function admin_menu() {
					add_submenu_page( 'wpclever', esc_html__( 'WPC Buy Now Button', 'wpc-buy-now-button' ), esc_html__( 'Buy Now Button', 'wpc-buy-now-button' ), 'manage_options', 'wpclever-wpcbn', [
						$this,
						'admin_menu_content'
					] );
				}

				function admin_menu_content() {
					add_thickbox();
					$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'settings';
					?>
                    <div class="wpclever_settings_page wrap">
                        <h1 class="wpclever_settings_page_title"><?php echo esc_html__( 'WPC Buy Now Button', 'wpc-buy-now-button' ) . ' ' . WPCBN_VERSION; ?></h1>
                        <div class="wpclever_settings_page_desc about-text">
                            <p>
								<?php printf( esc_html__( 'Thank you for using our plugin! If you are satisfied, please reward it a full five-star %s rating.', 'wpc-buy-now-button' ), '<span style="color:#ffb900">&#9733;&#9733;&#9733;&#9733;&#9733;</span>' ); ?>
                                <br/>
                                <a href="<?php echo esc_url( WPCBN_REVIEWS ); ?>" target="_blank"><?php esc_html_e( 'Reviews', 'wpc-buy-now-button' ); ?></a> |
                                <a href="<?php echo esc_url( WPCBN_CHANGELOG ); ?>" target="_blank"><?php esc_html_e( 'Changelog', 'wpc-buy-now-button' ); ?></a> |
                                <a href="<?php echo esc_url( WPCBN_DISCUSSION ); ?>" target="_blank"><?php esc_html_e( 'Discussion', 'wpc-buy-now-button' ); ?></a>
                            </p>
                        </div>
						<?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) { ?>
                            <div class="notice notice-success is-dismissible">
                                <p><?php esc_html_e( 'Settings updated.', 'wpc-buy-now-button' ); ?></p>
                            </div>
						<?php } ?>
                        <div class="wpclever_settings_page_nav">
                            <h2 class="nav-tab-wrapper">
                                <a href="<?php echo admin_url( 'admin.php?page=wpclever-wpcbn&tab=settings' ); ?>" class="<?php echo esc_attr( $active_tab === 'settings' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
									<?php esc_html_e( 'Settings', 'wpc-buy-now-button' ); ?>
                                </a>
                                <a href="<?php echo admin_url( 'admin.php?page=wpclever-kit' ); ?>" class="nav-tab">
									<?php esc_html_e( 'Essential Kit', 'wpc-buy-now-button' ); ?>
                                </a>
                            </h2>
                        </div>
                        <div class="wpclever_settings_page_content">
							<?php if ( $active_tab === 'settings' ) { ?>
                                <form method="post" action="options.php">
                                    <table class="form-table">
                                        <tr class="heading">
                                            <th><?php esc_html_e( 'General', 'wpc-buy-now-button' ); ?></th>
                                            <td><?php esc_html_e( 'General settings.', 'wpc-buy-now-button' ); ?></td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Button text', 'wpc-buy-now-button' ); ?></th>
                                            <td>
                                                <input type="text" name="wpcbn_settings[button_text]" value="<?php echo self::get_setting( 'button_text', '' ); ?>" placeholder="<?php esc_html_e( 'Buy now', 'wpc-buy-now-button' ); ?>"/>
                                                <span class="description"><?php esc_html_e( 'Leave blank to use the default text or its equivalent translation in multiple languages.', 'wpc-buy-now-button' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Button position on archive', 'wpc-buy-now-button' ); ?></th>
                                            <td>
												<?php $position_archive = apply_filters( 'wpcbn_button_position_archive', 'default' ); ?>
                                                <select name="wpcbn_settings[button_position_archive]" <?php echo esc_attr( $position_archive !== 'default' ? 'disabled' : '' ); ?>>
													<?php if ( $position_archive === 'default' ) {
														$position_archive = self::get_setting( 'button_position_archive', 'after_add_to_cart' );
													} ?>
                                                    <option value="after_title" <?php selected( $position_archive, 'after_title' ); ?>><?php esc_html_e( 'After title', 'wpc-buy-now-button' ); ?></option>
                                                    <option value="after_rating" <?php selected( $position_archive, 'after_rating' ); ?>><?php esc_html_e( 'After rating', 'wpc-buy-now-button' ); ?></option>
                                                    <option value="after_price" <?php selected( $position_archive, 'after_price' ); ?>><?php esc_html_e( 'After price', 'wpc-buy-now-button' ); ?></option>
                                                    <option value="before_add_to_cart" <?php selected( $position_archive, 'before_add_to_cart' ); ?>><?php esc_html_e( 'Before add to cart button', 'wpc-buy-now-button' ); ?></option>
                                                    <option value="after_add_to_cart" <?php selected( $position_archive, 'after_add_to_cart' ); ?>><?php esc_html_e( 'After add to cart button', 'wpc-buy-now-button' ); ?></option>
                                                    <option value="0" <?php selected( $position_archive, '0' ); ?>><?php esc_html_e( 'None (hide it)', 'wpc-buy-now-button' ); ?></option>
                                                </select>
                                                <span class="description"><?php printf( esc_html__( 'You also can use the shortcode %s', 'wpc-buy-now-button' ), '<code>[wpcbn_btn_archive]</code>' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Button position on single', 'wpc-buy-now-button' ); ?></th>
                                            <td>
												<?php $position_single = apply_filters( 'wpcbn_button_position_single', 'default' ); ?>
                                                <select name="wpcbn_settings[button_position_single]" <?php echo esc_attr( $position_single !== 'default' ? 'disabled' : '' ); ?>>
													<?php if ( $position_single === 'default' ) {
														$position_single = self::get_setting( 'button_position_single', 'after_add_to_cart' );
													} ?>
                                                    <option value="before_add_to_cart" <?php selected( $position_single, 'before_add_to_cart' ); ?>><?php esc_html_e( 'Before add to cart button', 'wpc-buy-now-button' ); ?></option>
                                                    <option value="after_add_to_cart" <?php selected( $position_single, 'after_add_to_cart' ); ?>><?php esc_html_e( 'After add to cart button', 'wpc-buy-now-button' ); ?></option>
                                                    <option value="0" <?php selected( $position_single, '0' ); ?>><?php esc_html_e( 'None (hide it)', 'wpc-buy-now-button' ); ?></option>
                                                </select>
                                                <span class="description"><?php printf( esc_html__( 'You also can use the shortcode %s', 'wpc-buy-now-button' ), '<code>[wpcbn_btn_single]</code>' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Parameter', 'wpc-buy-now-button' ); ?></th>
                                            <td>
                                                <input type="text" name="wpcbn_settings[parameter]" placeholder="buy-now" value="<?php echo self::get_setting( 'parameter' ); ?>"/>
                                                <span class="description"><?php printf( esc_html__( 'Parameter for the buy now button or link. Default %s', 'wpc-buy-now-button' ), '<code>buy-now</code>' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Hide add-to-cart button', 'wpc-buy-now-button' ); ?></th>
                                            <td>
                                                <select name="wpcbn_settings[hide_atc]">
													<?php $hide_atc = self::get_setting( 'hide_atc', 'no' ); ?>
                                                    <option value="yes" <?php selected( $hide_atc, 'yes' ); ?>><?php esc_html_e( 'Yes', 'wpc-buy-now-button' ); ?></option>
                                                    <option value="no" <?php selected( $hide_atc, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-buy-now-button' ); ?></option>
                                                </select>
                                                <span class="description"><?php esc_html_e( 'Hide the default add-to-cart button.', 'wpc-buy-now-button' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Reset cart', 'wpc-buy-now-button' ); ?></th>
                                            <td>
                                                <select name="wpcbn_settings[reset_cart]">
													<?php $reset_cart = self::get_setting( 'reset_cart', 'no' ); ?>
                                                    <option value="yes" <?php selected( $reset_cart, 'yes' ); ?>><?php esc_html_e( 'Yes', 'wpc-buy-now-button' ); ?></option>
                                                    <option value="no" <?php selected( $reset_cart, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-buy-now-button' ); ?></option>
                                                </select>
                                                <span class="description"><?php esc_html_e( 'Reset the cart before doing buy now.', 'wpc-buy-now-button' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Redirect to', 'wpc-buy-now-button' ); ?></th>
                                            <td>
                                                <select name="wpcbn_settings[redirect]" class="wpcbn_redirect">
													<?php $redirect = self::get_setting( 'redirect', 'checkout' ); ?>
                                                    <option value="checkout" <?php selected( $redirect, 'checkout' ); ?>><?php esc_html_e( 'Checkout page', 'wpc-buy-now-button' ); ?></option>
                                                    <option value="cart" <?php selected( $redirect, 'cart' ); ?>><?php esc_html_e( 'Cart page', 'wpc-buy-now-button' ); ?></option>
                                                    <option value="custom" <?php selected( $redirect, 'custom' ); ?>><?php esc_html_e( 'Custom page', 'wpc-buy-now-button' ); ?></option>
                                                </select>
                                                <input name="wpcbn_settings[redirect_custom]" type="url" class="regular-text wpcbn_redirect_custom" value="<?php echo esc_url( self::get_setting( 'redirect_custom' ) ); ?>"/>
                                            </td>
                                        </tr>
                                        <tr class="heading">
                                            <th colspan="2"><?php esc_html_e( 'Suggestion', 'wpc-buy-now-button' ); ?></th>
                                        </tr>
                                        <tr>
                                            <td colspan="2">
                                                To display custom engaging real-time messages on any wished positions, please install
                                                <a href="https://wordpress.org/plugins/wpc-smart-messages/" target="_blank">WPC Smart Messages</a> plugin. It's free!
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2">
                                                Wanna save your precious time working on variations? Try our brand-new free plugin
                                                <a href="https://wordpress.org/plugins/wpc-variation-bulk-editor/" target="_blank">WPC Variation Bulk Editor</a> and
                                                <a href="https://wordpress.org/plugins/wpc-variation-duplicator/" target="_blank">WPC Variation Duplicator</a>.
                                            </td>
                                        </tr>
                                        <tr class="submit">
                                            <th colspan="2">
												<?php settings_fields( 'wpcbn_settings' ); ?><?php submit_button(); ?>
                                            </th>
                                        </tr>
                                    </table>
                                </form>
							<?php } ?>
                        </div>
                    </div>
					<?php
				}

				function button_archive() {
					echo do_shortcode( '[wpcbn_btn_archive]' );
				}

				function button_single() {
					echo do_shortcode( '[wpcbn_btn_single]' );
				}

				function template_redirect() {
					if ( ! isset( $_REQUEST[ self::$param ] ) ) {
						return false;
					}

					$product_id   = absint( $_REQUEST[ self::$param ] ?: 0 );
					$quantity     = floatval( isset( $_REQUEST['quantity'] ) ? $_REQUEST['quantity'] : 1 );
					$variation_id = absint( isset( $_REQUEST['variation_id'] ) ? $_REQUEST['variation_id'] : 0 );
					$variation    = [];

					foreach ( $_REQUEST as $name => $value ) {
						if ( substr( $name, 0, 10 ) === 'attribute_' ) {
							$variation[ $name ] = $value;
						}
					}

					if ( $product_id ) {
						if ( self::get_setting( 'reset_cart', 'no' ) === 'yes' ) {
							WC()->cart->empty_cart();
						}

						if ( $variation_id ) {
							if ( self::get_setting( 'reset_cart', 'no' ) === 'yes' ) {
								WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation );
							}
						} else {
							WC()->cart->add_to_cart( $product_id, $quantity );
						}

						switch ( apply_filters( 'wpcbn_redirect', self::get_setting( 'redirect', 'checkout' ) ) ) {
							case 'checkout':
								$redirect = wc_get_checkout_url();
								break;
							case 'cart':
								$redirect = wc_get_cart_url();
								break;
							default:
								$redirect = self::get_setting( 'redirect_custom', '/' );
						}

						$redirect = esc_url( apply_filters( 'wpcbn_redirect_url', $redirect ) );

						if ( empty( $redirect ) ) {
							$redirect = '/';
						}

						wp_safe_redirect( $redirect );
						exit;
					}

					return null;
				}

				function add_to_cart_redirect( $url ) {
					if ( ! empty( $_REQUEST[ self::$param ] ) ) {
						switch ( apply_filters( 'wpcbn_redirect', self::get_setting( 'redirect', 'checkout' ) ) ) {
							case 'checkout':
								$redirect = wc_get_checkout_url();
								break;
							case 'cart':
								$redirect = wc_get_cart_url();
								break;
							default:
								$redirect = self::get_setting( 'redirect_custom', '/' );
						}

						$redirect = esc_url( apply_filters( 'wpcbn_redirect_url', $redirect ) );

						if ( empty( $redirect ) ) {
							$redirect = '/';
						}

						return $redirect;
					}

					return $url;
				}
			}

			return WPCleverWpcbn::instance();
		}

		return null;
	}
}

if ( ! function_exists( 'wpcbn_notice_wc' ) ) {
	function wpcbn_notice_wc() {
		?>
        <div class="error">
            <p><strong>WPC Buy Now Button</strong> requires WooCommerce version 3.0 or greater.</p>
        </div>
		<?php
	}
}
