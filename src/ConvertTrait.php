<?php

namespace blacksenator;

/**
 * This trait provides basic conversion functions
 *
 * Copyright (c) 2021 Volker Püschel
 * @license MIT
 */

trait ConvertTrait
{
    /**
     * delivers name parts which consisted only of one string
     * if the is separated by a comma, then it is assumed that
     * it is '[lastname], [firstname]' otherwise [company]
     *
     * @param string §realName
     * @return array $result
     */
    protected function getNameParts($realName)
    {
        $result = [
            'firstname' => '',
            'lastname'  => '',
            'company'   => '',
        ];
        $name = htmlspecialchars($realName);
        $nameParts = explode(',', $name, 2);
        if (count($nameParts) == 2) {
            $result['lastname'] = $nameParts[0];
            $result['firstname'] = $nameParts[1];
        } else {
            $result['company'] = $name;
        }

        return $result;
    }
}
