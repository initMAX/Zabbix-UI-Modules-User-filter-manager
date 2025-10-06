<?php

namespace Modules\UserFilterMAX\Services;

use Zabbix\Core\CModule;

interface StorageInterface {

    public function __construct(CModule $module);

    /**
     * Must return array of rows or empty array.
     *
     * Filter must support:
     * - **output**  Array of properties to return for each entry in results array.
     * - **ids**     Array of primary keys of entries to return.
     * - **offset**
     * - **limit**
     */
    public function get(array $filter): array;

    /**
     * Expects array of rows to create. Must return input array with pk set for each created row.
     * May throw exception on error.
     */
    public function create(array $rows): array;

    /**
     * Expects array of rows to update. Must return input array with updated rows or empty array.
     * May throw exception on error.
     */
    public function update(array $rows): array;

    /**
     * Expects array of rows pk values. Must return array of pks of deleted rows.
     * May throw exception on error.
     */
    public function delete(array $pks): array;
}
