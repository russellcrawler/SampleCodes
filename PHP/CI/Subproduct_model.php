<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Subproduct_model extends My_Model
{
    const TABLE_NAME = SUBPRODUCT;

    /**
     * @param int $productId
     * @param array $options
     * @return array
     */
    public function getIdsByProductAndAttributeNameValuePairs(int $productId, array $options): array
    {
        if (!$options) {
            return [];
        }

        $query = $this->db->select('pid');

        foreach ($options as $key => $value) {
            $query
                ->or_group_start()
                ->where('attr_name', $key)
                ->where('attr_value', $value)
                ->where('product_id', $productId)
                ->group_end();
        }

        $optionIds = $query->get(self::TABLE_NAME)->result_array();

        return array_column($optionIds, 'pid');
    }

    /**
     * @param int $productId
     * @param array $optionIds
     * @return array
     */
    public function getAttributeNameValuePairsByProductAndIds(int $productId, array $optionIds): array
    {
        if (!$optionIds) {
            return [];
        }

        $options = $this->db->select('attr_name, attr_value')
            ->where('product_id', $productId)
            ->where_in('pid', $optionIds)
            ->get(self::TABLE_NAME)
            ->result_array();

        return array_column($options, 'attr_value', 'attr_name');
    }
}