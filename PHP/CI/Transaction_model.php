<?php

defined('BASEPATH') OR exit('No direct script access allowed');

use libraries\payments\constants\TransactionType;
use libraries\services\relationships\RelationshipType;
use traits\AmountTrait;
use traits\CreatedTrait;
use traits\GatewayTypeTrait;

class Transaction_model extends My_Model
{
    use CreatedTrait;
    use AmountTrait;
    use GatewayTypeTrait;

    const TABLE_NAME = 'transactions';

    /**
     * @var array $relations
     */
    protected $relations = [
        Refund_model::class => [
            'type' => RelationshipType::HAS_MANY,
            'foreign_key' => 'transaction_id',
            'local_key' => 'transaction_id',
        ],
    ];

    /**
     * @var string $primaryKey
     */
    protected $primaryKey = 'transaction_id';

    /**
     * @var string $transaction_id
     */
    protected $transaction_id;

    /**
     * @var string $type
     */
    protected $type;

    /**
     * @var string|null $account_id
     */
    protected $account_id;

    /**
     * @var string $order_number
     */
    protected $order_number;

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Transaction_model
     */
    public function setType(string $type): self
    {
        if (TransactionType::isValid($type)) {
            $this->type = $type;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isTransfer(): bool
    {
        return $this->type === TransactionType::TRANSFER;
    }

    /**
     * @return bool
     */
    public function isCharge(): bool
    {
        return $this->type === TransactionType::CHARGE;
    }

    /**
     * @return bool
     */
    public function isApplicationFee(): bool
    {
        return $this->type === TransactionType::APPLICATION_FEE;
    }

    /**
     * @return null|string
     */
    public function getAccountId()
    {
        return $this->account_id;
    }

    /**
     * @param string $accountId
     * @return Transaction_model
     */
    public function setAccountId(string $accountId): self
    {
        $this->account_id = $accountId;

        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionId(): string
    {
        return $this->transaction_id;
    }

    /**
     * @param string $transactionId
     * @return Transaction_model
     */
    public function setTransactionId(string $transactionId): self
    {
        $this->transaction_id = $transactionId;

        return $this;
    }

    /**
     * @return string
     */
    public function getOrderNumber(): string
    {
        return $this->order_number;
    }

    /**
     * @param string $orderNumber
     * @return Transaction_model
     */
    public function setOrderNumber(string $orderNumber): self
    {
        $this->order_number = $orderNumber;

        return $this;
    }

    /**
     * @return Refund_model[]
     */
    public function getRefunds(): array
    {
        return $this->getRelation(Refund_model::class);
    }

    /**
     * @return bool
     */
    public function isRefunded(): bool
    {
        return $this->getRefundedAmount() >= $this->getAmount();
    }

    /**
     * @return int
     */
    public function getAvailableRefundAmount(): int
    {
        return max(0, $this->getAmount() - $this->getRefundedAmount());
    }

    /**
     * @return int
     */
    public function getRefundedAmount(): int
    {
        if ($this->isRelationLoaded(Refund_model::class)) {
            return array_reduce($this->getRefunds(), static function (int $carry, Refund_model $refund) {
                return $carry + $refund->getAmount();
            }, 0);
        }

        return (int)$this->db->select_sum('amount', 'refunded')
            ->where('transaction_id', $this->transaction_id)
            ->get(Refund_model::TABLE_NAME)
            ->row()->refunded;
    }

    /**
     * @return array
     */
    public function getRefundIds(): array
    {
        if ($this->isRelationLoaded(Refund_model::class)) {
            return array_map(static function (Refund_model $refund) {
                return $refund->getRefundId();
            }, $this->getRefunds());
        }

        $query = $this->db->select('refund_id')
            ->where('transaction_id', $this->getTransactionId())
            ->get(Refund_model::TABLE_NAME);

        return array_column($query->result_array(), 'refund_id');
    }
}