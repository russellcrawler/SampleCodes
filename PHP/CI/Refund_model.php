<?php

defined('BASEPATH') OR exit('No direct script access allowed');

use libraries\services\relationships\RelationshipType;
use traits\AmountTrait;
use traits\CreatedTrait;

class Refund_model extends My_Model
{
    use CreatedTrait;
    use AmountTrait;

    const TABLE_NAME = 'refunds';

    /**
     * @var array $relations
     */
    protected $relations = [
        Transaction_model::class => [
            'type' => RelationshipType::BELONGS_TO,
            'foreign_key' => 'transaction_id',
        ],
    ];

    /**
     * @var string $primaryKey
     */
    protected $primaryKey = 'refund_id';

    /**
     * @var string $refund_id
     */
    protected $refund_id;

    /**
     * @var string $transaction_id
     */
    protected $transaction_id;

    /**
     * @return string
     */
    public function getRefundId(): string
    {
        return $this->refund_id;
    }

    /**
     * @param string $refundId
     * @return Refund_model
     */
    public function setRefundId(string $refundId): self
    {
        $this->refund_id = $refundId;

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
     * @return Refund_model
     */
    public function setTransactionId(string $transactionId): self
    {
        $this->unsetRelation(Transaction_model::class);

        $this->transaction_id = $transactionId;

        return $this;
    }

    /**
     * @return Transaction_model
     */
    public function getTransaction(): Transaction_model
    {
        return $this->getRelation(Transaction_model::class);
    }

    /**
     * @param Transaction_model $transaction
     * @return Refund_model
     */
    public function setTransaction(Transaction_model $transaction): self
    {
        $this->setRelation(Transaction_model::class, $transaction);

        $this->transaction_id = $transaction->getTransactionId();

        return $this;
    }
}