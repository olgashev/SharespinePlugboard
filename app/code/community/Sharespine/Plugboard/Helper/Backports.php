<?php
class Sharespine_Plugboard_Helper_Backports extends Sharespine_Plugboard_Helper_Data {
    /**
     * Copy from: Mage_Api_Helper_Data (1.9.1.0)
     * Since this is not available in older version
     *
     * Parse filters and format them to be applicable for collection filtration
     *
     * @param null|object|array $filters
     * @param array $fieldsMap Map of field names in format: array('field_name_in_filter' => 'field_name_in_db')
     * @return array
     */
    public function parseFilters($filters, $fieldsMap = null)
    {
        // if filters are used in SOAP they must be represented in array format to be used for collection filtration
        if (is_object($filters)) {
            $parsedFilters = array();
            // parse simple filter
            if (isset($filters->filter) && is_array($filters->filter)) {
                foreach ($filters->filter as $field => $value) {
                    if (is_object($value) && isset($value->key) && isset($value->value)) {
                        $parsedFilters[$value->key] = $value->value;
                    } else {
                        $parsedFilters[$field] = $value;
                    }
                }
            }
            // parse complex filter
            if (isset($filters->complex_filter) && is_array($filters->complex_filter)) {
                $parsedFilters += $this->_parseComplexFilter($filters->complex_filter);
            }

            $filters = $parsedFilters;
        }
        // make sure that method result is always array
        if (!is_array($filters)) {
            $filters = array();
        }
        // apply fields mapping
        if (isset($fieldsMap) && is_array($fieldsMap)) {
            foreach ($filters as $field => $value) {
                if (isset($fieldsMap[$field])) {
                    unset($filters[$field]);
                    $field = $fieldsMap[$field];
                    $filters[$field] = $value;
                }
            }
        }
        return $filters;
    }

    /**
     * Copy from: Mage_Api_Helper_Data (1.9.1.0)
     * Since this is not available in older version
     *
     * Parses complex filter, which may contain several nodes, e.g. when user want to fetch orders which were updated
     * between two dates.
     *
     * @param array $complexFilter
     * @return array
     */
    protected function _parseComplexFilter($complexFilter)
    {
        $parsedFilters = array();

        foreach ($complexFilter as $filter) {
            if (!isset($filter->key) || !isset($filter->value)) {
                continue;
            }

            list($fieldName, $condition) = array($filter->key, $filter->value);
            $conditionName = $condition->key;
            $conditionValue = $condition->value;
            $this->formatFilterConditionValue($conditionName, $conditionValue);

            if (array_key_exists($fieldName, $parsedFilters)) {
                $parsedFilters[$fieldName] += array($conditionName => $conditionValue);
            } else {
                $parsedFilters[$fieldName] = array($conditionName => $conditionValue);
            }
        }

        return $parsedFilters;
    }
}