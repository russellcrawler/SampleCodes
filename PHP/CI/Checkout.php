<?php

defined('BASEPATH') OR exit('No direct script access allowed');

use auth\Auth;
use libraries\payments\constants\GatewayType;
use libraries\payments\gateways\GatewayFactory;

/**
 *
 * User related functions
 * @author Teamtweaks
 *
 * @property Gift_card_model $gift_card
 */
class Checkout extends MY_Controller
{
    function __construct()
    {
        parent::__construct();

        $this->load->helper(array('cookie', 'date', 'email'));
        $this->load->library(array('encrypt', 'form_validation'));
        $this->load->model(array('checkout_model', 'order_model', 'cart_model', 'coupon_model' => 'coupon'));
        $this->data['loginCheck'] = $this->checkLogin('U');
        $this->data['countryList'] = $this->checkout_model->get_all_details(COUNTRY_LIST, array());
        define("API_LOGINID", $this->config->item('payment_2'));
        define("API_MERCHANTEMAIL", $this->config->item('payment_0'));
    }
    
    /**
     * Loading Cart Page
     *
     * @param $billing_id
     */
    public function index($billing_id = '')
    {
        if ($this->data['loginCheck'] != '') {
            $this->data['meta_title'] = $this->data['heading'] = 'Billing Address';
            $this->data['CheckoutVal'] = $this->checkout_model->get_all_details(USER_PAYMENT, array('dealCodeNumber' => $this->db->escape_str($this->session->userdata('UserrandomNo'))));
            $this->data['SellerDetails'] = $this->checkout_model->get_all_details(SELLER, array('seller_id' => $this->data['CheckoutVal']->row()->sell_id));
            $user = $this->user_model->get_all_details(USERS, array('id' => $this->db->escape_str($this->checkLogin('U')), 'status' => 'Active'));

            $this->load->model('billing_address_model', 'billing');
            $this->load->helper('form_entity_helper');

            if (empty($billing_id)) {
                $billing_id = $user->row()->billing_id;
            }

            $billing = null;
            if (!empty($billing_id)) {
                $billing = $this->billing->get($billing_id);
            } else {
                $shipping_id = $this->data['CheckoutVal']->row()->shippingid;

                $this->load->model('shipping_address_model', 'shipping');

                if (!empty($shipping_id)) {
                    $billing = $this->shipping->get($shipping_id);
                }
            }

            $this->data['billing'] = $billing;

            $this->data['PublicProfile'] = $user;
            $this->data['UserCheckoutResults'] = $UserCheckoutResults = $this->checkout_model->mani_user_checkout_total($this->data['common_user_id']);

            $UserCheckAmt = explode('|', $UserCheckoutResults);

            $grandTotal = (float)$UserCheckAmt[3];

            if ((bool)$UserCheckAmt[18]) {
                $partialAmount = (float)$UserCheckAmt[19];
                $giftDiscount = (float)$UserCheckAmt[6];

                if ($partialAmount < $giftDiscount) {
                    $giftDiscount = $partialAmount;
                }

                $grandTotal = $grandTotal > $partialAmount ? $partialAmount : $grandTotal;
                $grandTotal -= $giftDiscount;
            }

            if ($grandTotal == 0 || $UserCheckAmt[17] === Product_model::PAYMENT_CASH_OPTION) {
                session('no_billing_address', true);

                redirect('checkout/complete-payment');
            }

            if ($this->session->has_userdata('no_billing_address')) {
                $this->session->unset_userdata('no_billing_address');
            }

            $this->data['countryList'] = $this->checkout_model->get_all_details(COUNTRY_LIST, array());
            $this->data['state'] = $this->user_model->get_all_details('shopsy_states', array())->result_array();

            if (!empty($UserCheckAmt[9])) {
                $this->data['coupon'] = $this->coupon->get($UserCheckAmt[9]);
            }

            $this->load->view('site/checkout/checkout.php', $this->data);
        } else {
            redirect(base_url() . 'login');
        }
    }

    public function checkout()
    {
        if (!Auth::isAuthenticated()) {
            redirect('login');
        }

        $userId = Auth::getUser()->getId();
        $cartIds = post('cart-ids');
        $shippingId = post('ship-to');

        $this->load->model('classifieds_model');

        $shipOption = post('shipping-type', Classifieds_model::SHIPPING_SHIP_OPTION);

        if ($this->input->method() === 'post') {
            /** @var Cart_model[] $carts */
            $carts = $this->cart_model->getCarts($userId, explode(',', $cartIds));

            if (!Cart_model::isListingsAvailable($carts, $userId)) {
                listing_unavailable($carts[0]->getId());
            }

            $localPickUpOnly = false;

            if ($carts[0]->isPhysicalType() && $carts[0]->getProduct()->isClassified()) {
                $localPickUpOnly = $carts[0]->getProduct()->getClassifiedOptions()->getShipOptions() === Classifieds_model::LOCAL_PICK_UP_SHIP_OPTION
                    || $shipOption === Classifieds_model::LOCAL_PICK_UP_SHIP_OPTION;

                if ($carts[0]->getProduct()->getClassifiedOptions()->getShipOptions() !== Classifieds_model::BOTH_SHIP_OPTION) {
                    $shipOption = $carts[0]->getProduct()->getClassifiedOptions()->getShipOptions();
                }
            }

            if ($carts[0]->isPhysicalType() && !$carts[0]->getProduct()->isClassified()) {
                $localPickUpOnly = $carts[0]->getProduct()->getShipOptions() === Product_model::LOCAL_PICK_UP_SHIP_OPTION
                    || $shipOption === Product_model::LOCAL_PICK_UP_SHIP_OPTION;

                if ($carts[0]->getProduct()->getShipOptions() !== Product_model::BOTH_SHIP_OPTION) {
                    $shipOption = $carts[0]->getProduct()->getShipOptions();
                }
            }

            if ((empty($shippingId) || $shippingId === 'no') && !$localPickUpOnly && $carts[0]->isPhysicalType()) {
                $this->setErrorMessage('Please choose shipping address.');
                redirect('checkout/shopping-cart');
            }

            $paymentMethod = post('payment-method');

            if (empty($paymentMethod) || trim($paymentMethod) === '') {
                $this->setErrorMessage('Please Select the Payment Method.');
                redirect('checkout/shopping-cart');
            }

            if (empty($carts)) {
                $this->setErrorMessage('Shopping Carts not found. Please try again.');
                redirect('checkout/shopping-cart');
            }

            session('checkout:shopping-cart-ids', $cartIds);
            session('checkout:shopping-cart-payment-method', $paymentMethod);
            session('checkout:shipping-option', $shipOption);

            //TODO: Should be rewritten by using only shopping cart models
            $orderNumber = time();

            if ($carts[0]->isServiceType()) {
                $orderNumber = 'se_' . $orderNumber;
            } else {
                $orderNumber = 'or_' . $orderNumber;
            }

            $this->load->model('order_model');

            if (session_has('UserrandomNo')) {
                session_remove('UserrandomNo');
            }

            session('UserrandomNo', $orderNumber);

            $this->order_model->createOrdersByCarts($carts, $this->data['remainGiftBalance'], Order_model::STATUS_PENDING,
                $shipOption, $orderNumber, $paymentMethod);

            redirect(base_url() . "checkout/billing-address");
        }

        redirect('checkout/shopping-cart');
    }
    
    /****************************************************************************************************************************************/
    public function UserPaymentProcess_stripe()
    {
        if (!Auth::isAuthenticated()) {
            redirect('login');
        }

        $cartIds = session('checkout:shopping-cart-ids');

        $this->load->model('cart_model');
        /** @var Cart_model[] $carts */
        $carts = $this->cart_model->getCarts(Auth::getUser()->getId(), explode(',', $cartIds));

        if (!Cart_model::isListingsAvailable($carts, Auth::getUser()->getId())) {
            listing_unavailable($carts[0]->getId());
        }

        $UserCheckoutResults = $this->checkout_model->mani_user_checkout_total($this->session->userdata['shopsy_session_user_id'], $carts);
        $UsercheckAmt = @explode('|', $UserCheckoutResults);

        $cart_price = number_format($UsercheckAmt[0], 2, '.', '');
        $ship_price = number_format($UsercheckAmt[1], 2, '.', '');
        $total_price = number_format($UsercheckAmt[3], 2, '.', '');
        $tax_price = number_format($UsercheckAmt[2], 2, '.', '');

        $isClassified = (bool)$UsercheckAmt[16];

        /************GET ADMIN COMMISIION AMOUNT******************/
        $commission = $this->checkout_model->get_commission($isClassified);
        $percent = (($commission / 100) * $total_price);
        $val = $total_price - $percent;
        $selleramount = $val + $ship_price + $tax_price;
        /***************calculate actual seller amount****************/
        $adminfees = ((2.9 / 100) * $percent);
        $vendorfees = ((2.9 / 100) * $selleramount);
        $actualvendoramount = $selleramount - ($adminfees + $vendorfees + 0.30);
        /************GET SELLER PAYPAL EMAIL******************/
        $seller_id = $this->input->post('seller_id');
        $couponId = $this->input->post('coupon_code');

        $gatewayType = GatewayType::STRIPE;
        $seller = $carts[0]->getShop();

        if ($seller !== null) {
            /** @var User_model $user */
            $user = $seller->getUser();

            if ($user->getActivePaymentGateway() !== null) {
                $gatewayType = $user->getActivePaymentGateway()->getGatewayType();
            }
        }

        try {
            $this->data['gateway'] = GatewayFactory::create($gatewayType);
        } catch (Exception $e) {
            $this->setErrorMessage($e->getMessage());

            redirect('checkout/shopping-cart');
        }

        if (!empty($couponId)) {
            /** @var Coupon_model $coupon */
            $coupon = $this->coupon->get($couponId);
        }

        $loginUserId = (int)$this->checkLogin('U');

        $lastFeatureInsertId = $this->session->userdata('UserrandomNo');
        $tax_percentage = $this->session->userdata('tax_percentage');

        if ($tax_percentage == '') {
            $tax_percentage = '0';
        } else {
            $tax_percentage = $tax_percentage;
        }
        if ($tax_price > 0) {
            $tax_price = $tax_price;
        } else {
            $tax_price = 0;
        }

        if ($this->uri->segment(3) != 1) {
            $delete_dataArr = array('dealCodeNumber' => $this->db->escape_str($lastFeatureInsertId), 'status' => 0);
            $this->checkout_model->delete_record('shopsy_user_payment_split', $delete_dataArr);
            $dataArr = array(
                'dealCodeNumber'      => $this->db->escape_str($lastFeatureInsertId),
                'total_amount'        => $this->db->escape_str($total_price),
                'admin_amount'        => $this->db->escape_str($percent),
                'seller_id'           => $this->db->escape_str($seller_id),
                'seller_amount'       => $this->db->escape_str($val),
                'shipping_amount'     => $this->db->escape_str($ship_price),
                'tax'                 => $this->db->escape_str($tax_price),
                'tax_percent'         => $this->db->escape_str($tax_percentage),
                'net_seller_amount'   => $this->db->escape_str($actualvendoramount),
                'seller_total_amount' => $this->db->escape_str($selleramount),
                'status'              => 0,
            );
            $this->checkout_model->simple_insert('shopsy_user_payment_split', $dataArr);
        }
        /***************************/
        /********* update on 14 July by Pervez *******/
        $dropstate = $this->input->post('state');
        $state_new = $this->input->post('state_new');
        $other = $this->input->post('Other');
        if ($other == 1) {
            $state = $dropstate;
        } else {
            $state = $state_new;
        }
        /********* update on 14 July by Pervez *******/
        $condition = array('id' => $loginUserId);
        $full_name = preg_replace('/[^A-Za-z \-\']/', '', $this->input->post('full_name'));
        if ($this->uri->segment(3) != 1) {
            $insID = 0;

            if (!$this->session->has_userdata('no_billing_address')) {
                if ($this->_validateBillingForm()) {
                    //TODO: after optimization use validation rule to save billing address
                }

                $this->load->model('billing_address_model', 'billing');

                $billing = new Billing_address_model();
                $this->handleRequest($this->input->post(), $billing);

                $billing->setUser($loginUserId);

                if ($other != 1) {
                    $billing->setState($state_new);
                }

                $billing->insert();

                $insID = $billing->getId();

                $this->db->update(USERS, array(
                    'billing_id' => $insID,
                ), array('id' => $loginUserId));
            }

            if (!empty($UsercheckAmt[9]) && $UsercheckAmt[9] != 0) {
                /** @var Coupon_model $couponDis */
                $couponDis = $this->coupon->get($UsercheckAmt[9]);

                if (!empty($couponDis)) {
                    $couponType = $couponDis->getCouponType();

                    if ($couponType != Coupon_model::TYPE_FREE_SHIPPING) {
                        $couponDiscount = $couponDis->getAmountOff();
                    } else {
                        $couponDiscount = $couponDis->isDomesticOnly();
                    }
                }
            }

            $paymentMethod = session('checkout:shopping-cart-payment-method');
            $shipOption = session('checkout:shipping-option');

            $partialPayment = $UsercheckAmt[18];
            $grandTotal = (float)$UsercheckAmt[3];
            $giftDiscount = (float)$UsercheckAmt[6];
            $total = Cart_model::getItemsGrandTotal(
                $carts,
                0.,
                $shipOption,
                $paymentMethod
            );

            if ($partialPayment) {
                $partialAmount = $UsercheckAmt[19];

                if ($partialAmount < $giftDiscount) {
                    $giftDiscount = $partialAmount;
                }

                $grandTotal = $grandTotal > $partialAmount ? $partialAmount : $grandTotal;

                $total -= $grandTotal;
                $grandTotal -= $giftDiscount;
            }

            $this->checkout_model->update_details(USER_PAYMENT, array(
                'billingid' => $this->db->escape_str($insID),
                'coupon_id' => !empty($UsercheckAmt[9]) ? $UsercheckAmt[9] : null,
                'coupon_type' => !empty($couponType) ? $couponType : null,
                'coupon_discount' => !empty($couponDiscount) ? $couponDiscount : null,
                'sumtotal' => $grandTotal,
                'indtotal' => $UsercheckAmt[0],
                'tax' => $UsercheckAmt[2],
                'total' => round($total, 2),
                'giftdiscountAmount' => $giftDiscount,
            ), array('dealCodeNumber' => $this->db->escape_str($lastFeatureInsertId)));
        }

        $this->load->model('gift_card_model', 'gift_card');

        $total_price = sprintf("%.2f", $total_price);
        $percent = sprintf("%.2f", $percent);
        $this->data['heading'] = "Complete Payment";
        $this->data['insID'] = $insID;
        $this->session->set_userdata('total_price_final', $total_price);
        $this->session->set_userdata('percent_final', $percent);
        $this->session->set_userdata('gift_discount', $UsercheckAmt[6]);
        $this->session->set_userdata('payment_type', $UsercheckAmt[17]);
        $this->data['seller_id'] = $seller_id;
        $this->data['dealCodeNumber'] = $lastFeatureInsertId;
        $this->data['CheckoutVal'] = $this->checkout_model->get_all_details(USER_PAYMENT, array('dealCodeNumber' => $this->db->escape_str($this->session->userdata('UserrandomNo'))));
        $ship_billing = $this->data['CheckoutVal']->result_array();
        $this->data['shipping_info'] = $this->order_model->get_all_details(SHIPPING_ADDRESS, array('id' => $this->db->escape_str($ship_billing[0]['shippingid'])))->result_array();

        if ($ship_billing[0]['payment_option'] === Product_model::PAYMENT_CASH_OPTION) {
            $this->data['billing_info'] = 'Billing Address n/a. Cash on delivery';
        } elseif ($grandTotal === 0.0) {
            $this->data['billing_info'] = 'Billing Address n/a. Gift Card used';
        } else {
            $this->data['billing_info'] = $this->order_model->get_all_details(BILLING_ADDRESS, array('id' => $this->db->escape_str($ship_billing[0]['billingid'])))->result_array();
        }
        $this->data['UserCheckoutResults'] = $this->checkout_model->mani_user_checkout_total($this->data['common_user_id'], $carts);
        $this->data['coupon'] = !empty($coupon) ? $coupon : null;

        $this->load->model('classifieds_model');

        $this->load->view('site/checkout/complete.php', $this->data);
    }

    //TODO: Update validation rules and move it into config validation groups
    //and validate all address form
    private function _validateBillingForm()
    {
        $this->load->library('form_validation');

        $this->form_validation->set_rules(array(
            array(
                'field' => 'full_name',
                'label' => 'Full Name',
                'rules' => array('trim', 'required'),
                'errors' => array(
                    'required' => self::REQUIRED_MESSAGE,
                ),
            ),
            array(
                'field' => 'address1',
                'label' => 'Street Address',
                'rules' => array('trim', 'required'),
                'errors' => array(
                    'required' => self::REQUIRED_MESSAGE,
                ),
            ),
            array(
                'field' => 'city',
                'label' => 'City',
                'rules' => array('trim', 'required'),
                'errors' => array(
                    'required' => self::REQUIRED_MESSAGE,
                ),
            ),
            array(
                'field' => 'state',
                'label' => 'State',
                'rules' => array('trim', 'callback__check_state[Other,1]'),
                'errors' => array(
                    '_check_state' => self::REQUIRED_MESSAGE,
                ),
            ),
            array(
                'field' => 'state_new',
                'label' => 'State',
                'rules' => array('trim', 'callback__check_state[Other,2]'),
                'errors' => array(
                    '_check_state' => self::REQUIRED_MESSAGE,
                ),
            ),
            array(
                'field' => 'country',
                'label' => 'Country',
                'rules' => array('trim', 'required'),
                'errors' => array(
                    'required' => self::REQUIRED_MESSAGE,
                ),
            ),
            array(
                'field' => 'postal_code',
                'label' => 'Zip / Postal Code',
                'rules' => array('trim', 'required'),
                'errors' => array(
                    'required' => self::REQUIRED_MESSAGE,
                ),
            ),
            array(
                'field' => 'phone',
                'label' => 'Phone',
                'rules' => array('trim', 'required'),
                'errors' => array(
                    'required' => self::REQUIRED_MESSAGE,
                ),
            ),
        ));
        $this->form_validation->set_error_delimiters('', '');

        return $this->form_validation->run();
    }

    public function _check_state($value, $field)
    {
        $field = explode(',', $field);
        $fieldValue = $field[1];
        $field = $field[0];

        $postValue = $this->input->post($field);

        if ($postValue == $fieldValue) {
            return !empty($value);
        }

        return TRUE;
    }

    //TODO: Update by using Stripe Library
    /****************** Insert the seller checkout to user********************/
    public function RenewPaypal()
    {
        require_once(APPPATH . 'libraries/stripe/init.php');
        if ($_POST) {

            $condition_new = array('id' => 4);
            $info = $this->seller_model->get_all_details(PAYMENT_GATEWAY, $condition_new);
            $gatewaySettings = unserialize($info->row()->settings);
            if (!is_array($gatewaySettings)) {
                $gatewaySettings = array();
            }
            if ($gatewaySettings['mode'] == 'live') {
                $stripe_key = $gatewaySettings['Live_Secret_Key'];
            } else {
                $stripe_key = $gatewaySettings['Test_Secret_Key'];
            }
            \Stripe\Stripe::setApiKey($stripe_key);
            // Get the credit card details submitted by the form
            $loginUserId = $this->checkLogin('U');
            $transactionId = $this->session->userdata('transactionId' . $loginUserId);
            $payInfo = $this->checkout_model->get_all_details(SELLER_PAYMENT, array('txn_id' => $transactionId));
            $totalAmount = $payInfo->row()->amount;
            $prdList = $payInfo->row()->note;
            $prdArr = @explode(',', $prdList);
            $token = htmlspecialchars($_POST['stripeToken']);
            $total_price = $totalAmount * 100;
            // Create the charge on Stripe's servers - this will charge the user's card
            try {
                $charge = \Stripe\Charge::create(array(
                    "amount"      => $total_price, // amount in cents, again
                    "currency"    => "usd",
                    "source"      => $token,
                    "description" => "Listing Fees"
                ));
                $condition2 = array('txn_id' => $transactionId);
                $dataArr2 = array('charge_id' => $charge['id']);
                $this->order_model->update_details('shopsy_seller_payment', $dataArr2, $condition2);
                redirect(base_url() . 'order/renewalsuccess/' . $loginUserId . '/' . $transactionId);
            } catch (\Stripe\Error\Card $e) {
                $loginUserId = $this->checkLogin('U');
                $transactionId = $this->session->userdata('transactionId' . $loginUserId);
                $payInfo = $this->checkout_model->get_all_details(SELLER_PAYMENT, array('txn_id' => $transactionId));
                $prdList = $payInfo->row()->note;
                $prdVals = $prdList;
                $this->data['productList'] = $prdVals;
                $condition1 = " where p.id in ( " . $prdVals . " ) group by p.id order by p.id desc";
                $this->data['select_product'] = $this->product_model->view_product_details($condition1)->result();
                $loggeduserID = $this->data['loginCheck'];
                $this->data['shopDetail'] = $this->product_model->get_all_details(PRODUCT, array('user_id' => $loggeduserID, 'pay_status' => 'Paid', 'status' => 'Expires'));
                $this->data['userDetails'] = $this->seller_model->get_all_details(USER, array('id' => $loggeduserID))->row();
                $this->data['CardsDetails'] = $this->product_model->get_all_details(CREDITCARDS, array('user_id' => $loggeduserID))->row();
                $this->data['sellingPayment'] = $this->product_model->get_all_details(ADMIN_SETTINGS, array('id' => 1));
                $this->db->select('status,listing_expiry');
                $this->db->from(SELLER);
                $this->db->where('seller_id = ' . $this->data['loginCheck']);
                $this->data['SellerValShop'] = $this->db->get();
                $this->data['products_in_pay'] = count($deleteProducts);
                $total_amt = $this->data['products_in_pay'] * $this->data['sellingPayment']->row()->product_cost;
                $this->data['total_amt'] = $total_amt;
                $this->data['meta_title'] = $this->data['heading'] = 'Pay Listing Fees';
                $this->session->set_userdata('invalid', 'Your card was declined.');
                if ($payInfo->row()->pay_type == "Itemized Fee") {
                    $this->load->view('site/shop/renew_listings', $this->data);
                } else {
                    $this->load->view('site/shop/subscribe_listings', $this->data);
                }
            }
        }
    }
}

/* End of file checkout.php */
/* Location: ./application/controllers/site/checkout.php */
