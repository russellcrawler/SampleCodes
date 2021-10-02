<?php

defined('BASEPATH') OR exit('No direct script access allowed');

use auth\Auth;
use libraries\services\exceptions\ProductAlreadyInCartException;
use libraries\services\exceptions\ServiceException;

class Cart extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model(array('checkout_model', 'cart_model'));
        $this->data['loginCheck'] = $this->checkLogin('U');
    }

    public function index()
    {
        $user_id = Auth::isAuthenticated() ? Auth::getUser()->getId() : null;
        $this->data['user_id'] = $user_id;
        $this->data['heading'] = 'Shopping Cart';
        $this->data['meta_title'] = $this->data['heading'];

        if ($user_id === null) {
            $user_id = $this->checkLogin('T');
        }

        $carts = $this->cart_model->getCarts($user_id);

        $total = 0;
        $deleteIds = array();
        foreach ($carts as $key => $value) {
            /** @var Cart_model $value */
            $quantity = $value->getQuantity();

            if ($value->isGiftCardType()) {
                $total += $quantity;
                continue;
            }

            if (!$value->isListingAvailable($user_id)) {
                $deleteIds[] = $value->getId();
                unset($carts[$key]);
                continue;
            }

            $total += $quantity;
        }

        if (!empty($deleteIds)) {
            $this->cart_model->where('id', $deleteIds)
                ->delete();
        }

        $cartsSorted = [
            Cart_model::LISTING_TYPE_PHYSICAL => [],
            Cart_model::LISTING_TYPE_DIGITAL => [],
            Cart_model::LISTING_TYPE_SERVICE => [],
            Cart_model::LISTING_TYPE_GIFT_CARD => [],
        ];

        array_walk($carts, static function ($value, $key) use (&$cartsSorted) {
            /** @var Cart_model $value */
            if ($value->isPhysicalType()) {
                $product = $value->getProduct();
                $deposit = $product->isDepositsUse() ? '_use_' . $product->getId() : '';
                if ($product->isClassified()) {
                    $shipOption = $product->getClassifiedOptions()->getShipOptions();
                    $cartKey = $value->getShop()->getId() . "_classified_$shipOption" . '_' . $product->getPaymentOption() . $deposit;
                    $cartsSorted[$value->getListingType()][$cartKey][] = $value;
                } else {
                    $shipOption = $product->getShipOptions();
                    $cartKey = $value->getShop()->getId() . "_$shipOption" . '_' . $product->getPaymentOption() . $deposit;
                    $cartsSorted[$value->getListingType()][$cartKey][] = $value;
                }
            } elseif ($value->isGiftCardType()) {
                $cartsSorted[$value->getListingType()][][] = $value;
            } elseif ($value->isDigitalType()) {
                $cartsSorted[$value->getListingType()][$value->getShop()->getId()][] = $value;
            } else {
                $cartsSorted[$value->getListingType()][] = $value;
            }
        });

        $this->load->model('shipping_address_model', 'shipping');
        $this->load->model('classifieds_model');
        $this->data['carts'] = $cartsSorted;

        /** @var Shipping_address_model[] $userShipping */
        $userShipping = $this->shipping->where([
            'user_id' => $user_id,
            'del_status' => '',
        ])->getAll();

        $shippingAddresses = [];
        $userShippingSort = [];
        foreach ($userShipping as $shipping) {
            $shippingAddresses[$shipping->getId()] = $shipping->getFullName() . ' (' . $shipping->getAddress1() . ')';
            $userShippingSort[$shipping->getId()] = $shipping;
        }

        if (count($userShipping) === 0) {
            $shippingAddresses['no'] = 'No Address Found';
        }

        $this->data['shippingAddresses'] = $shippingAddresses;
        $this->data['userShipping'] = $userShippingSort;
        $this->data['total'] = $total;

        $products = $this->cart_model->relatedPurchases($this->data['common_user_id']);
        $this->data['relatedPurchases'] = $this->product_model->groupProductsWithSales($products);

        $this->load->view('site/cart/cart', $this->data);
    }

    public function updateNote()
    {
        if (Auth::isAuthenticated()) {
            $userId = Auth::getUser()->getId();
        } else {
            $userId = $this->checkLogin('T');
        }

        $cartIds = post('cart-ids');

        if ($this->input->method() !== 'post') {
            echo json_encode([
                'code' => 'error',
                'message' => 'Method not allowed.',
            ]);
            return;
        }

        $this->load->model('cart_model');

        $carts = $this->cart_model->where([
            'id' => explode(',', $cartIds),
            'user_id' => $userId,
        ])->getAll();

        if (empty($carts)) {
            echo json_encode([
                'code' => 'error',
                'message' => 'The cart item not found. Please try again later.',
            ]);
            return;
        }

        if (!$this->_validateNoteForm()) {
            $error = $this->form_validation->error('note');

            echo json_encode([
                'code' => 'error',
                'message' => $error,
            ]);
            return;
        }

        Cart_model::setNoteToItems($carts, post('note'));

        $this->cart_model->updateBatch($carts);

        echo json_encode([
            'code' => 'success',
            'message' => 'Note updated.',
        ]);
    }

    private function _validateNoteForm(): bool
    {
        $this->load->library('form_validation');

        $this->form_validation->set_rules([
            [
                'field' => 'note',
                'label' => 'Note',
                'rules' => ['trim', 'max_length[200]'],
                'errors' => [
                    'max_length' => self::MAX_LENGTH_MESSAGE,
                ],
            ],
        ]);
        $this->form_validation->set_error_delimiters('', '');

        return $this->form_validation->run();
    }

    public function removeFromCart()
    {
        if (Auth::isAuthenticated()) {
            $userId = Auth::getUser()->getId();
        } else {
            $userId = $this->checkLogin('T');
        }

        $cartIds = post('cart-ids');

        if (!empty($cartIds) && $this->input->method() === 'post') {
            $removedItems = $this->cart_model->where(array('id' => explode(',',$cartIds)))->getAll();
            $giftCardsIds = [];

            foreach ($removedItems as $item) {
                if ($item->isGiftCardType()) {
                    $giftCardsIds[] = $item->getProduct()->getId();
                }
            }

            if (!empty($giftCardsIds)) {
                $this->gift_card->where(array(
                    'id' => $giftCardsIds,
                ))->delete();
            }

            $this->cart_model->where(array(
                    'user_id' => $userId,
                    'id' => explode(',', $cartIds),
                ))->delete();

                $this->setSuccessMessage('Items removed from cart.');
        }

        redirect('checkout/shopping-cart');
    }

    public function updateShipping()
    {
        if (Auth::isAuthenticated()) {
            $userId = Auth::getUser()->getId();
        } else {
            $userId = $this->checkLogin('T');
        }

        $cartIds = post('cart-ids');
        $shippingId = post('shipping');

        if ($this->input->method() !== 'post') {
            echo json_encode(array(
                'code' => 'error',
                'message' => 'Method not allowed.',
            ));
            return;
        }

        if (empty($shippingId)) {
            echo json_encode(array(
                'code' => 'error',
                'message' => 'Shipping not found.',
            ));
            return;
        }

        $carts = $this->cart_model->where(array(
            'id' => explode(',', $cartIds),
            'user_id' => $userId,
        ))->getAll();

        foreach ($carts as $cart) {
            $cart->setShippingId($shippingId);
        }

        $this->cart_model->updateBatch($carts);

        echo json_encode(array(
            'code' => 'success',
        ));
    }

    public function updateQuantity()
    {
        if (Auth::isAuthenticated()) {
            $userId = Auth::getUser()->getId();
        } else {
            $userId = $this->checkLogin('T');
        }

        $cartId = post('cart-id');
        $quantity = post('quantity');

        if ($this->input->method() === 'post') {
            $cart = $this->cart_model->where('user_id', $userId)->get($cartId);

            if ($cart === null) {
                $this->setErrorMessage("Can't find cart item. Please try again.");
                redirect('checkout/shopping-cart');
            }

            $maxQuantity = $cart->getMaximumQuantity();

            if ((int)$quantity > $maxQuantity) {
                $this->setErrorMessage('Maximum quantity available is ' . $maxQuantity);
                redirect('checkout/shopping-cart');
            }

            $cart->setQuantity((int)$quantity)
                ->save();
        }

        redirect('checkout/shopping-cart');
    }

    /**
     * @return void
     */
    public function applyCoupon()
    {
        if (!Auth::isAuthenticated()) {
            redirect('login');
        }

        $cartIds = post('cart-ids');
        $couponFieldPostfix = '-' . str_replace(',', '-', $cartIds);
        $couponCode = post('coupon-code' . $couponFieldPostfix);
        $userId = Auth::getUser()->getId();

        if ($this->input->method() === 'post' && $this->_validateCouponForm($couponFieldPostfix)) {
            /** @var Cart_model[] $carts */
            $carts = $this->cart_model->where(array(
                'id' => explode(',', $cartIds),
                'user_id' => $userId,
            ))->getAll();

            if (($shop = $carts[0]->getShop()) === null) {
                $this->setErrorMessage("Can't apply coupon. Try again later.");
                redirect('checkout/shopping-cart');
            }

            $shopId = $shop->getId();

            $this->load->model('coupon_model', 'coupon');

            $coupon = $this->coupon->where(array(
                'shop_id' => $shopId,
                'code' => $couponCode,
                'status' => Coupon_model::STATUS_ACTIVE,
            ))->get();

            Cart_model::setCouponToItems($carts, $coupon);

            $this->cart_model->updateBatch($carts);
            $this->setSuccessMessage('The coupon code was applied successfully.');
        }

        redirect('checkout/shopping-cart');
    }

    /**
     * @param string $postfix
     * @return bool
     */
    private function _validateCouponForm(string $postfix): bool
    {
        $this->load->library('form_validation');

        $this->form_validation->set_rules([
            [
                'field' => 'coupon-code' . $postfix,
                'label' => 'Coupon Code',
                'rules' => 'check_coupon[shop-id,cart-ids]',
            ],
        ]);
        $this->form_validation->set_error_delimiters('', '');

        return $this->form_validation->run();
    }

    public function removeCoupon()
    {
        if (Auth::isAuthenticated()) {
            $userId = Auth::getUser()->getId();
        } else {
            $userId = $this->checkLogin('T');
        }

        $cartIds = post('cart-ids');

        if ($this->input->method() === 'post') {
            $carts = $this->cart_model->where(array(
                'id' => explode(',', $cartIds),
                'user_id' => $userId,
            ))->getAll();

            Cart_model::setCouponToItems($carts, null);

            $this->cart_model->updateBatch($carts);

            $this->setSuccessMessage('The coupon code was removed successfully.');
        }

        redirect('checkout/shopping-cart');
    }

    public function add()
    {
        if (!Auth::isAuthenticated()) {
            redirect('login');
        }

        $productId = post('product');
        $attributes = post('attribute', []);
        $quantity = post('quantity', 1);

        $product = $this->product_model->get($productId);

        if ($product === null) {
            show_404();
        }

        $quantity = $product->isAuctionFormat() ? 1 : $quantity;
        $count = $this->cart_model->where([
            'user_id' => Auth::getUser()->getId(),
            'product_id' => $product->getId(),
        ])->count();

        if ($count > 0 && ($product->isDigitalType() || $product->isAuctionFormat())) {
            redirect('checkout/shopping-cart');
        }

        if ($count > 0 && $product->isServiceType()) {
            $this->cart_model
                ->where([
                    'user_id' => Auth::getUser()->getId(),
                    'product_id' => $product->getId(),
                ])
                ->delete();
        }

        $cart = new Cart_model();
        $cart->setProduct($product)
            ->setUser(Auth::getUser())
            ->setPrice($product->getPrice())
            ->setShop($product->getShop())
            ->setListingType($product->getListingType())
            ->setQuantity($quantity)
            ->setCreatedDate(new DateTime())
            ->save();

        redirect('checkout/shopping-cart');
    }

    //TODO: remove and use Product_model method

    /**
     * @param $product_id
     * @param array $attributes
     * @param int $slots
     * @return int|mixed
     * @deprecated since 2.0.1
     */
    private function getPrice($product_id, $attributes = array(), $slots = 0)
    {
        $attrPrice = 0;

        if ($attributes) {
            $attrPrice = $this->db->select_sum('pricing')
                ->where_in('pid', $attributes)
                ->get(SUBPRODUCT)->row()->pricing;
        }

        $product = $this->db->select('price, additional_price')
            ->get_where(PRODUCT, array('id' => $product_id))->row();

        $prodPrice = $product->price;

        $price = $attrPrice + $prodPrice;

        if ($slots != 0)
        {
            if (!empty($product->additional_price)) {
                $additional_price = json_decode($product->additional_price);

                if ($additional_price === null) {
                    $additional_price = array();
                }

                $per_person = 0;
                for ($i = 0; $i < $slots - 1; $i++) {
                    if (empty($additional_price)) {
                        $per_person += $price;
                        continue;
                    }

                    if (isset($additional_price[$i])) {
                        $per_person += $additional_price[$i];
                    } else {
                        $per_person += $additional_price[count($additional_price) - 1];
                    }
                }

                $price += $per_person;
            } else {
                $price *= $slots;
            }
        }

        return $price;
    }

    /**
     * Add Physical or Digital product to the cart
     * POST /cart/add/product/{productId}
     *
     * @param string $productId
     */
    public function create(string $productId)
    {
        $product = $this->product_model->get($productId);

        if ($product === null) {
            $this->responseJson([
                'message' => 'Product not found',
            ], 404);
        }

        $userId = Auth::isAuthenticated() ? Auth::getUser()->getId() : (int)$this->checkLogin('T');

        $this->load->library('services/cartService', null, 'cartService');

        try {
            $this->cartService->add($product, $userId, $this->input->post());

            $this->responseJson([
                'message' => 'Cart Updated',
                'count' => $this->cart_model->getCartCount($userId),
            ]);
        } catch (ServiceException $e) {
            $this->responseJson([
                'message' => $e->getMessage(),
            ]);
        }
    }

    //TODO: Needs refactoring. Use product and cart models

    /**
     * @deprecated since 2.1.0
     */
    public function usercartadd()
    {
        $this->load->helper('date');

        $product_type = $this->input->post('product_type');
        $quantity = $this->input->post('quantity');
        $product_id = $this->input->post('product_id');
        $attributes = $this->input->post('attributes');
        $slots = $this->input->post('slots');
        settype($quantity, 'integer');
        settype($product_id, 'integer');
        settype($slots, 'integer');

        if ($product_type == "digital") {
            $condition_check = array('sell_id' => $this->db->escape_str($this->input->post('sell_id')), 'product_id' => $this->db->escape_str($product_id), 'user_id' => $this->data['common_user_id']);
            $duplicateMail = $this->cart_model->get_all_details(USER_SHOPPING_CART, $condition_check);
            if ($duplicateMail->num_rows() == 1) {
                echo 'Digital';
                die;
            }
        }
        unset($_POST['product_type']);
        $ok = $this->input->post('sell_id');
        settype($ok, 'integer');
        $this->session->unset_userdata('shopId-' . $ok);
        $this->session->unset_userdata('ShopCountry-' . $ok);
        $excludeArr = array('mqty', 'attributes');
        $dataArrVal = array();
        $mqty = $this->input->post('mqty');
        foreach ($this->input->post() as $key => $val) {
            if (!(in_array($key, $excludeArr))) {
                $dataArrVal[$key] = trim(addslashes($val));
            }
        }
        $datestring = date('Y-m-d H:i:s', now());

        $price = $this->getPrice($product_id, $attributes, $slots);
        $dataArrVal['price'] = $price;

        $indTotal = ($price * $quantity);
        if ($this->input->post('ser_time')) {
            $location = $this->cart_model->get_all_details('shopsy_product', array('id' => $this->db->escape_str($product_id)));
            if ($location->row()->eventState) {
                $eventState = $location->row()->eventState;
            } else {
                $eventState = $location->row()->eventOther;
            }
            $dataArry_data = array(
                'eventLocation' => $location->row()->eventLocation,
                'eventAddress'  => $location->row()->eventAddress,
                'eventCity'     => $location->row()->eventCity,
                'eventState'    => $this->db->escape_str($eventState),
                'eventCountry'  => $location->row()->eventCountry,
                'zipcode'       => $location->row()->zipcode,
                'ser_time'      => $this->db->escape_str($this->input->post('ser_time')),
                'ser_date'      => $this->db->escape_str($this->input->post('ser_date')),
                'slots'         => $this->db->escape_str($slots),
                'created'       => $datestring,
                'user_id'       => $this->data['common_user_id'],
                'indtotal'      => $this->db->escape_str($indTotal),
                'total'         => $this->db->escape_str($indTotal),
                'service_type'  => $this->db->escape_str($this->input->post('service_type')),
                'listing_type'  => Cart_model::LISTING_TYPE_SERVICE,
            );
        } else {
            $dataArry_data = array(
                'created' => $this->db->escape_str($datestring),
                'user_id' => $this->data['common_user_id'],
                'indtotal' => $this->db->escape_str($indTotal),
                'total' => $this->db->escape_str($indTotal),
                'listing_type' => $product_type == Product_model::PRODUCT_TYPE_DIGITAL ? Cart_model::LISTING_TYPE_DIGITAL :
                    ($product_type == Product_model::PRODUCT_TYPE_SERVICE ? Cart_model::LISTING_TYPE_SERVICE : Cart_model::LISTING_TYPE_PHYSICAL),
            );
        }
        $dataArr = array_merge($dataArrVal, $dataArry_data);
        $condition = '';
        if ($this->input->post('ser_time')) {
            $this->data['productVal_ser'] = $this->cart_model->get_all_details(USER_SHOPPING_CART, array(
                'user_id'    => $this->data['common_user_id'],
                'product_id' => $this->db->escape_str($product_id)
            ));
            if ($this->data['productVal_ser']->num_rows > 0) {
                $del_qry = "DELETE FROM " . USER_SHOPPING_CART . " WHERE id = " . $this->data['productVal_ser']->row()->id;
                $taxList = $this->cart_model->ExecuteQuery($del_qry);
            }
            $this->cart_model->simple_insert(USER_SHOPPING_CART, $dataArr);
        } else {
            $this->data['productVal'] = $this->cart_model->get_all_details(USER_SHOPPING_CART, array(
                'service_type'     => "",
                'user_id'          => $this->data['common_user_id'],
                'product_id'       => $this->db->escape_str($product_id),
                'attribute_values' => $this->db->escape_str($this->input->post('attribute_values'))
            ));
            if ($this->data['productVal']->num_rows > 0) {
                $newQty = $this->data['productVal']->row()->quantity + $this->db->escape_str($quantity);
                if ($newQty <= $mqty) {
                    $indTotal = $this->getPrice($product_id, $attributes, $slots) * $newQty;
                    $dataArr = array('quantity' => $newQty, 'indtotal' => $this->db->escape_str($indTotal), 'total' => $this->db->escape_str($indTotal));
                    $condition = array('id' => $this->data['productVal']->row()->id);
                    $this->cart_model->update_details(USER_SHOPPING_CART, $dataArr, $condition);
                } else {
                    echo 'Error|' . $this->data['productVal']->row()->quantity;
                    die;
                }
            } else {
                if ($this->input->post('service_type') == 2) {
                    $this->data['productVal_ser_multi'] = $this->cart_model->get_all_details(USER_SHOPPING_CART, array(
                        'user_id'      => $this->data['common_user_id'],
                        'product_id'   => $this->db->escape_str($product_id),
                        'service_type' => $this->db->escape_str($this->input->post('service_type'))
                    ));
                    if ($this->data['productVal_ser_multi']->num_rows > 0) {
                        $del_qry = "DELETE FROM " . USER_SHOPPING_CART . " WHERE id = " . $this->data['productVal_ser_multi']->row()->id;
                        $taxList = $this->cart_model->ExecuteQuery($del_qry);
                    }
                    $ser_ndate = $this->cart_model->get_all_details('shopsy_service_sub_dates', array(
                        'service_date' => $this->db->escape_str($this->input->post('ser_date')),
                        'product_id'   => $this->db->escape_str($product_id)
                    ));
                    $ser_end_date = $ser_ndate->row()->service_end_date;
                    $ser_end_date_arr = array('ser_end_date' => $ser_end_date);
                    $dataArr = array_merge($dataArr, $ser_end_date_arr);
                    $dataArr['listing_type'] = Cart_model::LISTING_TYPE_SERVICE;
                } elseif ($this->input->post('service_type') == 1) {
                    $dataArr['listing_type'] = Cart_model::LISTING_TYPE_SERVICE;
                }
                $this->cart_model->simple_insert(USER_SHOPPING_CART, $dataArr);
            }
        }

        echo 'Success|' . $this->cart_model->getCartCount($this->data['common_user_id']);
    }

    //TODO: Remove and use removeFromCart
    public function ajaxDelete_complete()
    {
        $product_id = $this->input->post('product_id');
        $id = $this->input->post('id');
        $session_id = $this->input->post('session_id');
        $listing_type = $this->input->post('listing_type');

        if (strpos($session_id, 'se_') !== false) {
            $this->db->delete('shopsy_user_payment', array('product_id' => $product_id, 'id' => $id, 'dealCodeNumber' => $session_id));
            $this->db->delete(USER_SHOPPING_CART, array('product_id' => $product_id, 'user_id' => $this->session->userdata['shopsy_session_user_id']));
        } else {
            $info = $this->checkout_model->get_all_details('shopsy_user_payment', array('id =' => $id))->result_array();
            $this->db->delete(USER_SHOPPING_CART, array(
                'price'                 => $info[0]['price'],
                'product_id'            => $product_id,
                'user_id'               => $this->session->userdata['shopsy_session_user_id'],
                'listing_type'          => $listing_type,
            ));
            $this->db->delete('shopsy_user_payment', array('id' => $id));
        }

        $condition = array('dealCodeNumber' => $session_id);
        $duplicateMail = $this->checkout_model->get_all_details('shopsy_user_payment', $condition);

        if ($duplicateMail->num_rows() > 0) {
            $seller_id = $duplicateMail->row()->sell_id;
            $pricrsplusquqntity = 0;
            $shipping = 0;
            $caltax = 0;
            foreach ($duplicateMail->result_array() as $updateval) {
                $pricrsplusquqntity += number_format($updateval['price'] * $updateval['quantity'], 2, '.', '');
                $shipping += $updateval['shippingcost'];
                if ($updateval['tax'] != 0.00) {
                    $caltax = 1;
                }
            }
            /****************tax calculation *********************************************/
            if ($caltax == 1) {

                //Get Shipping id
                $shipping_id = $this->checkout_model->get_all_details('shopsy_user_payment', array('dealCodeNumber' => $session_id))->result_array();
                $shipVal = $this->checkout_model->get_all_details(SHIPPING_ADDRESS, array('id' => $shipping_id[0]['shippingid']));
                foreach ($shipVal->result() as $Shiprow) {
                    $zipcode = $Shiprow->postal_code;
                    $select_qry = "SELECT state_tax FROM `shopsy_seller_state_tax` WHERE seller_id = '" . $seller_id . "' AND zipcodes = '" . $zipcode . "' AND type = 3";
                    $productList = $this->checkout_model->ExecuteQuery($select_qry)->result();
                    $MainTaxCostTypethree = $productList[0]->state_tax;
                }
                foreach ($shipVal->result() as $Shiprow) {
                    $zipcode = $Shiprow->postal_code;
                    $select_qry = "SELECT state_tax FROM `shopsy_seller_state_tax` WHERE seller_id = '" . $seller_id . "' AND '" . $zipcode . "' BETWEEN `zipcode_range_from` AND `zipcode_range_to` AND type = 2";
                    $productList = $this->checkout_model->ExecuteQuery($select_qry)->result();
                    $MainTaxCostTypetwo = $productList[0]->state_tax;
                }
                foreach ($shipVal->result() as $Shiprow) {
                    $state = $Shiprow->state;
                    $select_qry = "SELECT state_tax FROM `shopsy_seller_state_tax` WHERE seller_id = '" . $seller_id . "' AND state_name LIKE '%" . $state . "%' AND type = 1";
                    $productList = $this->checkout_model->ExecuteQuery($select_qry)->result();
                    $MainTaxCostTypeone = $productList[0]->state_tax;
                }
                if ($MainTaxCostTypethree > 0) {
                    $MainTaxCost = $MainTaxCostTypethree;
                } else if ($MainTaxCostTypetwo > 0) {
                    $MainTaxCost = $MainTaxCostTypetwo;
                } else if ($MainTaxCostTypeone > 0) {
                    $MainTaxCost = $MainTaxCostTypeone;
                } else {
                    $MainTaxCost = '';
                }
                foreach ($duplicateMail->result_array() as $updateval) {
                    $tax_apply = $this->seller_model->get_all_details('shopsy_product', array('id' => $updateval['product_id']))->result_array();
                    if ($tax_apply[0]['tax_apply'] == 1) {
                        $UsercartTaxApplyAmt1 = $updateval['price'] * $updateval['quantity'];
                        $notax1 = number_format(($MainTaxCost / 100 * $UsercartTaxApplyAmt1), 2, '.', '');
                    } else if ($tax_apply[0]['tax_apply'] == 2) {
                        $UsercartTaxApplyAmt2 = ($updateval['price'] * $updateval['quantity']) + ($updateval['shippingcost']);
                        $notax2 = number_format(($MainTaxCost / 100 * $UsercartTaxApplyAmt2), 2, '.', '');
                    } else {
                        $UsercartTaxApplyAmt3 = '';
                        $notax3 = '';
                    }
                }
                $totaltax = $notax1 + $notax2 + $notax3;
            }
            /****************tax calculation *********************************************/
            $valalltotal = $pricrsplusquqntity + $shipping + $totaltax;
            $condition_ne = array('dealCodeNumber' => $session_id);
            $dataArr_ns = array('indtotal' => $pricrsplusquqntity, 'sumtotal' => $valalltotal, 'total' => $valalltotal, 'tax' => $totaltax);
            $this->user_model->update_details('shopsy_user_payment', $dataArr_ns, $condition_ne);
            $UserCheckoutResults = $this->checkout_model->mani_user_checkout_total($this->session->userdata['shopsy_session_user_id']);
            $UsercheckAmt = @explode('|', $UserCheckoutResults);
            $cart_price = number_format($UsercheckAmt[0], 2, '.', '');
            $ship_price = number_format($UsercheckAmt[1], 2, '.', '');
            $tax_price = number_format($UsercheckAmt[2], 2, '.', '');
            $tax_percentage = number_format($UsercheckAmt[8]);
            $total_price = number_format($UsercheckAmt[3], 2, '.', '');
            if (!$tax_percentage) {
                $tax_percentage = 0.00;
            }
            /************GET ADMIN COMMISIION AMOUNT******************/
            $commission = $this->checkout_model->get_commission();
            $percent = (($commission / 100) * $cart_price);
            $val = $cart_price - $percent;
            $selleramount = $val + $ship_price + $tax_price;
            /***************calculate actual seller amount****************/
            $adminfees = ((2.9 / 100) * $percent);
            $vendorfees = ((2.9 / 100) * $selleramount);
            $actualvendoramount = $selleramount - ($adminfees + $vendorfees + 0.30);
            /************GET SELLER PAYPAL EMAIL******************/
            $delete_dataArr = array('dealCodeNumber' => $session_id, 'status' => 0);
            $this->checkout_model->delete_record('shopsy_user_payment_split', $delete_dataArr);
            $dataArr = array(
                'dealCodeNumber'      => $session_id,
                'total_amount'        => $total_price,
                'admin_amount'        => $percent,
                'seller_id'           => $seller_id,
                'seller_amount'       => $val,
                'shipping_amount'     => $ship_price,
                'tax'                 => $tax_price,
                'tax_percent'         => $tax_percentage,
                'net_seller_amount'   => $actualvendoramount,
                'seller_total_amount' => $selleramount,
                'status'              => 0
            );
            $this->checkout_model->simple_insert('shopsy_user_payment_split', $dataArr);
            echo 1;
        } else {
            $delete_dataArr = array('dealCodeNumber' => $session_id, 'status' => 0);
            $this->checkout_model->delete_record('shopsy_user_payment_split', $delete_dataArr);
            echo 2;
        }
    }
}