<?php

call_user_func(function ($extkey) {
    $pluginSignature = $extkey . '_queryresult';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
        $pluginSignature,
        'FILE:EXT:' . $extkey . '/Configuration/FlexForms/Plugin.xml',
        'list'
    );

    \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($GLOBALS['TCA']['tt_content']['types']['list'], [
        'subtypes_addlist' => [
            $pluginSignature => 'pi_flexform',
        ],
        'subtypes_excludelist' => [
            $pluginSignature => 'recursive, pages',
        ],
    ]);
}, 'querybuilder');
