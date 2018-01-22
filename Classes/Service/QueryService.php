<?php
namespace T3G\Querybuilder\Service;

/*
 * Copyright (C) 2018  Daniel Siepmann <coding@daniel-siepmann.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 */

use T3G\Querybuilder\Parser\QueryParser;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class QueryService
{
    /**
     * @var \T3G\Querybuilder\Parser\QueryParser
     * @inject
     */
    protected $queryParser;

    public function fetchRecordsForQuery(int $queryUid) : array
    {
        $query = $this->getQuery($queryUid);
        $table = $query['affected_table'];
        $where = $this->queryParser->parse(json_decode($query['where_parts']), $table);

        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table)
            ->select('*')
            ->from($table)
            ->where($where)
            ->execute()
            ->fetchAll();
    }

    protected function getQuery(int $queryUid) : array
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_querybuilder')
            ->select(['affected_table', 'where_parts'], 'sys_querybuilder', ['uid' => $queryUid])
            ->fetch();
    }
}
