<?php

namespace T3G\Querybuilder;

use InvalidArgumentException;
use stdClass;
use T3G\Querybuilder\Backend\Form\FormDataGroup\TcaOnly;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use UnexpectedValueException;

/**
 * Class QueryParser.
 */
class QueryBuilder
{
    const FORMAT_DATETIME = 'YYYY-MM-DD HH:mm';
    const FORMAT_DATE = 'YYYY-MM-DD';
    const FORMAT_TIME = 'HH:mm';
    const FORMAT_TIMESEC = 'HH:mm:ss';
    const FORMAT_YEAR = 'YYYY';

    /**
     * Build the filter configuration from TCA
     *
     * @param string $table
     *
     * @return array
     * @throws UnexpectedValueException
     * @throws InvalidArgumentException
     */
    public function buildFilterFromTca($table)
    {
        $dataProviderResult = $this->prepareTca($table);
        $TCA = $dataProviderResult['processedTca'];
        $filters = [];
        $filterFields = !empty($TCA['ctrl']['queryFilterFields']) ? $TCA['ctrl']['queryFilterFields'] : $TCA['ctrl']['searchFields'];
        $filterFields = GeneralUtility::trimExplode(',', $filterFields);
        foreach ($filterFields as $filterField) {
            $fieldConfig = $TCA['columns'][$filterField];
            if (!is_array($fieldConfig)) {
                // if a filter field has no column declaration continue...
                continue;
            }
            // Filter:Types: string, integer, double, date, time, datetime and boolean.
            // Filter:Required: id, type, values*
            $filter = new stdClass();
            $filter->id = $filterField;
            $filter->type = $this->determineFilterType($fieldConfig);
            $filter->input = $this->determineFilterInput($fieldConfig);
            $filter->values = $this->determineFilterValues($fieldConfig);
            $filter->label = $fieldConfig['label'];
            $filter->description = !empty($fieldConfig['description']) ? $fieldConfig['description'] : '';
            $this->determineAndAddExtras($filter, $fieldConfig);
            $filters[] = $filter;
        }

        return $filters;
    }
    /**
     * Returns one of the possible filter types:
     * [string, integer, double, date, time, datetime and boolean].
     *
     * @param array $fieldConfig
     *
     * @return string
     */
    protected function determineFilterType(array $fieldConfig)
    {
        $type = 'string';
        switch ($fieldConfig['config']['type']) {
            case 'check':
                $type = 'boolean';
                break;
            case 'select':
            case 'input':
                if (strpos($fieldConfig['config']['eval'], 'double2') !== false) {
                    $type = 'double';
                }
                if (strpos($fieldConfig['config']['eval'], 'date') !== false) {
                    $type = 'date';
                }
                if (strpos($fieldConfig['config']['eval'], 'datetime') !== false) {
                    $type = 'datetime';
                }
                break;
        }

        return $type;
    }

    /**
     * @param array $fieldConfig
     *
     * @return string
     */
    protected function determineFilterInput(array $fieldConfig) : string
    {
        $input = 'text';
        switch ($fieldConfig['config']['type']) {
            case 'check':
                $input = 'checkbox';
                break;
            case 'select':
                $input = 'select';
                break;
        }

        return $input;
    }

    /**
     * @param array $fieldConfig
     * @return array
     */
    protected function determineFilterValues(array $fieldConfig)
    {
        $values = [];
        switch ($fieldConfig['config']['type']) {
            case 'select':
                if (!empty($fieldConfig['config']['items'])) {
                    foreach ($fieldConfig['config']['items'] as $item) {
                        $tmp = new stdClass();
                        $tmp->{$item[1]} = $item[0];
                        $values[] = $tmp;
                    }
                }
                break;
        }

        return $values;
    }

    /**
     * @param stdClass $filter
     * @param array $fieldConfig
     */
    protected function determineAndAddExtras(&$filter, $fieldConfig)
    {
        if ($filter->type === 'date' || $filter->type === 'datetime') {
            $filter->validation = new stdClass();
            $filter->plugin = 'datetimepicker';
            $filter->plugin_config = new stdClass();
            $filter->plugin_config->sideBySide = true;
            $filter->plugin_config->icons = new stdClass();
            $filter->plugin_config->icons->time = 'fa fa-clock-o';
            $filter->plugin_config->icons->date = 'fa fa-calendar';
            $filter->plugin_config->icons->up = 'fa fa-chevron-up';
            $filter->plugin_config->icons->down = 'fa fa-chevron-down';
            $filter->plugin_config->icons->previous = 'fa fa-chevron-left';
            $filter->plugin_config->icons->next = 'fa fa-chevron-right';
            $filter->plugin_config->icons->today = 'fa fa-calendar-o';
            $filter->plugin_config->icons->clear = 'fa fa-trash';
            switch ($filter->type) {
                case 'datetime':
                    $filter->plugin_config->format = self::FORMAT_DATETIME;
                    break;
                case 'date':
                    $filter->plugin_config->format = self::FORMAT_DATE;
                    break;
                case 'time':
                    $filter->plugin_config->format = self::FORMAT_TIME;
                    break;
                case 'timesec':
                    $filter->plugin_config->format = self::FORMAT_TIMESEC;
                    break;
                case 'year':
                    $filter->plugin_config->format = self::FORMAT_YEAR;
                    break;
            }
            $filter->validation->format = $filter->plugin_config->format;
        }
    }

    /**
     * @param string $tableName
     *
     * @return array
     * @throws UnexpectedValueException
     * @throws InvalidArgumentException
     */
    protected function prepareTca($tableName)
    {
        $formDataGroup = GeneralUtility::makeInstance(TcaOnly::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);

        $formDataCompilerInput = [
            'tableName' => $tableName,
            'command' => 'edit',
        ];

        return $formDataCompiler->compile($formDataCompilerInput);
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}