<?php

defined('BASEPATH') OR exit('No direct script access allowed');

use auth\Auth;
use libraries\payments\constants\GatewayType;

class UserPaymentGateway extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!Auth::isAuthenticated()) {
            redirect('login');
        }

        $this->load->model(['payment_gateway_model', 'user_payment_gateway_model']);
        $this->load->library('services/paymentGateway/gatewaySettingsService');
    }

    /**
     * GET /payment-gateways
     * Display a list of the user payment gateways
     */
    public function index()
    {
        $this->load->model('payment_gateway_model');

        $activeGateways = $this->payment_gateway_model->where('is_active', true)
            ->getAll();

        $this->data['heading'] = 'Payment Gateways';
        $this->data['gateways'] = $activeGateways;
        $this->data['activeGateway'] = $this->authUser->getActivePaymentGateway();

        $this->load->view('site/user-payment-gateway/list', $this->data);
    }

    /**
     * POST /payment-gateways/make-active
     * Add payment gateway to the user as active
     */
    public function makeActive()
    {
        $gatewayId = (int)$this->input->post('gateway-id');

        $gateway = $this->user_payment_gateway_model->where([
            'id' => $gatewayId,
            'user_id' => $this->authUser->getId(),
        ])
            ->get();

        if ($gateway === null) {
            $this->setErrorMessage('Payment Gateway not found');

            $this->redirectBack('payment-gateways');
        }

        $this->authUser->setActivePaymentGateway($gateway)
            ->save();

        $this->setSuccessMessage('Active payment gateway updated successfully');

        $this->redirectBack('payment-gateways');
    }

    /**
     * GET /payment-gateways/connect/{type}
     * Display connect payment gateway form
     *
     * @param string $type
     */
    public function connect(string $type)
    {
        if (!$this->payment_gateway_model->isActiveType($type)) {
            show_404();
        }

        $this->data['heading'] = 'Connect to ' . GatewayType::TEXTS[$type];
        $this->data['type'] = $type;
        $this->data['settings'] = $this->gatewaySettingsService->getUserSettings($type);
        $this->data['gateway'] = $this->payment_gateway_model->where('gateway_type', $type)->get();

        $this->load->view('site/user-payment-gateway/connect', $this->data);
    }

    /**
     * GET /payment-gateways/connect-oauth/{type}
     * Connect payment gateway by the OAuth2 response
     *
     * @param string $type
     */
    public function connectByOAuth(string $type)
    {
        if (!$this->payment_gateway_model->isActiveType($type)) {
            show_404();
        }

        $this->load->library('services/paymentGateway/userGatewayService');

        try {
            $this->userGatewayService->connect($this->authUser, $type, $this->input->get());

            $heading = 'Payment Gateway Connected';
            $error = '';
            $isConnected = true;
        } catch (Exception $e) {
            $heading = 'Payment Gateway Connection Failed';
            $error = $e->getMessage();
            $isConnected = false;
        }

        $this->data['heading'] = $heading;
        $this->data['error'] = $error;
        $this->data['isConnected'] = $isConnected;

        $this->setRefreshHeader('shop/account-type', 5);

        $this->load->view('site/user-payment-gateway/connect-oauth', $this->data);
    }

    /**
     * POST /payment-gateways/connect/{type}
     * Connect payment gateway by the form
     *
     * @param string $type
     */
    public function connectByForm(string $type)
    {
        if (!$this->payment_gateway_model->isActiveType($type)) {
            show_404();
        }

        $this->load->library('services/paymentGateway/userGatewayService');

        if (!$this->_validateForm($type)) {
            $this->redirectBack('shop/account-type');
        }

        try {
            $this->userGatewayService->connect($this->authUser, $type, $this->input->post());

            $this->setSuccessMessage('Payment Gateway connected successfully');

            redirect('shop/account-type');
        } catch (Exception $e) {
            $this->setErrorMessage($e->getMessage());

            $this->redirectBack('shop/account-type');
        }
    }

    /**
     * @param string $type
     * @return bool
     */
    private function _validateForm(string $type): bool
    {
        $this->load->library('form_validation');

        $settings = $this->gatewaySettingsService->getUserSettings($type);

        $this->form_validation->set_rules('account_id', 'Account ID', ['required']);

        foreach ($settings as $key => $label) {
            $this->form_validation->set_rules($key, $label, ['required']);
        }

        $this->form_validation->set_error_delimiters('', '');

        return $this->form_validation->run();
    }
}