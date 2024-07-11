<?php

$requiredFields = [
    'device' => [
        'geo' => [
            'city' => 'string',
            'country' => 'string'
        ],
        'devicetype' => 'integer',
        'os' => 'string'
    ],
    'imp' => [
        [
            'banner' => [
                'w' => 'integer',
                'h' => 'integer'
            ],
            'bidfloor' => 'double'
        ]
    ]
];

//Checking validation according to their parent key
function validateFields($data, $requiredFields, $parentKey = '') {
    foreach ($requiredFields as $key => $expectedType) {
        if (!isset($data[$key])) {
            return "Missing required field: " . ($parentKey ? $parentKey . '.' : '') . $key; 
        }

        $value = $data[$key];

        if ($expectedType == 'array' && !is_array($value)) {
            return "Invalid type for field: " . ($parentKey ? $parentKey . '.' : '') . $key . ". Expected array, got " . gettype($value);
        } elseif ($expectedType == 'string' && !is_string($value)) {
            return "Invalid type for field: " . ($parentKey ? $parentKey . '.' : '') . $key . ". Expected string, got " . gettype($value);
        } elseif ($expectedType == 'integer' && !is_int($value)) {
            return "Invalid type for field: " . ($parentKey ? $parentKey . '.' : '') . $key . ". Expected integer, got " . gettype($value);
        } elseif ($expectedType == 'double' && !is_float($value) && !is_double($value)) {
            return "Invalid type for field: " . ($parentKey ? $parentKey . '.' : '') . $key . ". Expected double, got " . gettype($value);
        }

        // Check nested arrays
        if (is_array($expectedType) && is_array($value)) {
            $result = validateFields($value, $expectedType, ($parentKey ? $parentKey . '.' : '') . $key);
            if ($result !== true) {
                return $result;
            }
        }
    }
    return true;
}