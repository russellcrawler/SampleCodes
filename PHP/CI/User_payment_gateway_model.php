<?php

defined('BASEPATH') OR exit('No direct script access allowed');

use libraries\payments\exceptions\InvalidGatewayTypeException;
use libraries\payments\gateways\GatewayFactory;
use libraries\services\relationships\RelationshipType;
use traits\GatewaySettingsTrait;

class User_payment_gateway_model extends My_Model
{
    use GatewaySettingsTrait;

    const TABLE_NAME = 'user_payment_gateways';

    /**
     * @var array $relations
     */
    protected $relations = [
        User_model::class => [
            'type' => RelationshipType::BELONGS_TO,
        ],
    ];

    /**
     * @var string|int $user_id
     */
    protected $user_id;

    /**
     * @var string $account_id
     */
    protected $account_id;

    /**
     * @var string|bool $is_live
     */
    protected $is_live = false;

    public function __construct()
    {
        parent::__construct();

        $this->load->library([
            'encryption',
            'services/paymentGateway/gatewaySettingsService',
        ]);
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return (int)$this->user_id;
    }

    /**
     * @param int $userId
     * @return User_payment_gateway_model
     */
    public function setUserId(int $userId): self
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * @return string
     */
    public function getAccountId(): string
    {
        return $this->account_id;
    }

    /**
     * @param string $accountId
     * @return User_payment_gateway_model
     */
    public function setAccountId(string $accountId): self
    {
        $this->account_id = $accountId;

        return $this;
    }

    /**
     * @return bool
     */
    public function isLive(): bool
    {
        return (bool)$this->is_live;
    }

    /**
     * @param bool $isLive
     * @return User_payment_gateway_model
     */
    public function setIsLive(bool $isLive): self
    {
        $this->is_live = $isLive;

        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        $activeGateway = $this->getUser()->getActivePaymentGateway();

        return $activeGateway !== null && $this->getId() === $activeGateway->getId();
    }

    /**
     * @return User_model
     */
    public function getUser(): User_model
    {
        return $this->getRelation(User_model::class);
    }

    /**
     * @return bool
     */
    public function isTransfersEnabled(): bool
    {
        try {
            $gateway = GatewayFactory::create($this->gateway_type);

            return $gateway->isTransfersEnabled($this->account_id);
        } catch (InvalidGatewayTypeException $e) {
            log_message('error', "Error checking transfers enabled status: {$e->getMessage()}");
        }

        return false;
    }

    /**
     * @param int $userId
     * @return array
     */
    public function getConnectedTypesByUser(int $userId): array
    {
        $types = $this->db->select('gateway_type')
            ->where('user_id', $userId)
            ->get(self::TABLE_NAME)
            ->result_array();

        return array_column($types, 'gateway_type');
    }
}