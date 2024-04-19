<?php

/** Wordpress */

function add_filter(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): bool
{
    return true;
}

function add_action($hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): bool
{
    return true;
}

class WC_Payment_Gateway_CC
{
    protected string $id, $title, $method_title, $method_description;
    protected bool $has_fields, $enabled, $debug;
    protected array $form_fields;

    /**
     * Proxy to parent's get_option and attempt to localize the result using gettext.
     *
     * @param string $key Option key.
     * @param mixed  $empty_value Value to use when option is empty.
     * @return string
     */
    public function get_option($key, $empty_value = null)
    {
    }

    /**
     * Get the return url (thank you page).
     *
     * @param WC_Order|null $order Order object.
     * @return string
     */
    public function get_return_url($order = null)
    {
    }

    /**
     * Process Payment.
     *
     * Process the payment. Override this in your gateway. When implemented, this should.
     * return the success and redirect in an array. e.g:
     *
     *        return array(
     *            'result'   => 'success',
     *            'redirect' => $this->get_return_url( $order )
     *        );
     *
     * @param int $order_id Order ID.
     * @return array
     */
    public function process_payment($order_id)
    {
    }

    /**
     * Initialise settings form fields.
     *
     * Add an array of fields to be displayed on the gateway's settings screen.
     *
     * @since  1.0.0
     */
    public function init_form_fields()
    {
    }

    /**
     * Validate frontend fields.
     *
     * Validate payment fields on the frontend.
     *
     * @return bool
     */
    public function validate_fields()
    {
    }
}

/**
 * Add and store a notice.
 *
 * @since 2.1
 * @version 3.9.0
 * @param string $message     The text to display in the notice.
 * @param string $notice_type Optional. The name of the notice type - either error, success or notice.
 * @param array  $data        Optional notice data.
 */
function wc_add_notice($message, $notice_type = 'success', $data = [])
{
}

/**
 * Outputs a checkout/address form field.
 *
 * @param string $key Key.
 * @param mixed  $args Arguments.
 * @param string $value (default: null).
 * @return string
 */
function woocommerce_form_field($key, $args, $value = null)
{
}

/**
 * Returns the main instance of WC.
 *
 * @since  2.1
 * @return WooCommerce
 */
function WC()
{
}

final class WooCommerce
{
    /**
     * Cart instance.
     *
     * @var WC_Cart
     */
    public $cart = null;
}

/**
 * WC_Cart class.
 */
class WC_Cart
{
    /**
     * Empties the cart and optionally the persistent cart too.
     *
     * @param bool $clear_persistent_cart Should the persistent cart be cleared too. Defaults to true.
     */
    public function empty_cart($clear_persistent_cart = true)
    {
    }
}

/**
 * Main function for returning orders, uses the WC_Order_Factory class.
 *
 * @since  2.2
 *
 * @param mixed $the_order       Post object or post ID of the order.
 *
 * @return bool|WC_Order|WC_Order_Refund
 */
function wc_get_order($the_order = false)
{
}

/**
 * Order Class.
 *
 * These are regular WooCommerce orders, which extend the abstract order class.
 */
class WC_Order
{
    /**
     * Get total cost.
     *
     * @param  string $context View or edit context.
     * @return string
     */
    public function get_total($context = 'view')
    {
    }

    public function add_meta_data(string $key, $value, bool $hidden = false)
    {
    }

    /**
     * When a payment is complete this function is called.
     *
     * Most of the time this should mark an order as 'processing' so that admin can process/post the items.
     * If the cart contains only downloadable items then the order is 'completed' since the admin needs to take no action.
     * Stock levels are reduced at this point.
     * Sales are also recorded for products.
     * Finally, record the date of payment.
     *
     * @param string $transaction_id Optional transaction id to store in post meta.
     * @return bool success
     */
    public function payment_complete($transaction_id = '')
    {
    }

    /**
     * Save data to the database.
     *
     * @since 3.0.0
     * @return int order ID
     */
    public function save()
    {
    }


	/**
	 * Get ID for the rate. This is usually a combination of the method and instance IDs.
	 *
	 * @since 3.2.0
	 * @return string
	 */
	public function get_id()
    {
    }
}
