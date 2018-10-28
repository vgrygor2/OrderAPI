<?php

namespace Magento\OrderBundle\Model;

use Symfony\Component\HttpFoundation\Response;

/**
 * Class Validator
 * @package Magento\OrderBundle\Model
 * @author vgrygor@adobe.com
 */
class Validator
{
    /**
     * Validate requested data
     *
     * @param array $data
     * @param array $paramList
     * @return array
     */
    public function validate(array $data, array $paramList = [])
    {
        if (!$data || !is_array($data)) {
            return ['message' => 'Invalid data format.', 'code' => Response::HTTP_BAD_REQUEST];
        }

        if ($paramList) {
            foreach ($paramList as $param) {
                if (!isset($data[$param])) {
                    return ['message' => 'Invalid data format.', 'code' => Response::HTTP_BAD_REQUEST];
                }
            }
        }
        return false;
    }

    /**
     * Validate order items data
     *
     * @param array $data
     * @return array
     */
    public function validateItems(array $data)
    {
        $errors = $this->validate($data);
        if ($errors) {
            return $errors;
        }
        $items = $data['lines'];
        if (!$items || !isset($items[0]['line_number'])) {
            return ['message' => 'Empty order items data.', 'code' => Response::HTTP_BAD_REQUEST];
        }

        if (!$this->isSequential($items)) {
            return ['message' => 'Order items are not sequential', 'code' => Response::HTTP_BAD_REQUEST];
        }
        return false;
    }

    /**
     * Check if order items are sequential
     *
     * @param array $orderItems
     * @return bool
     */
    private function isSequential(array $orderItems)
    {
        $firstItem = reset($orderItems);
        if ((int)$firstItem['line_number'] !== 1) {
            return false;
        }
        $counter = 0;
        foreach ($orderItems as $item) {
            if ($counter != 0 && (int) $item['line_number'] == $counter) {
                return false;
            }
            $counter += (int) $item['line_number'];
        }

        return true;
    }
}
