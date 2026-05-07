<?php

namespace Modules\UserFilterMAX\Services;

use Zabbix\Core\CModule as Module;

class ConfigStorageService implements StorageInterface {

    protected Module $module;
    protected bool $is_modified = false;
    protected array $data = [];
    protected array $defaults = [];

    public const STORAGE_KEY = 'data';
    public const PK = 'id';

    public function __construct(Module $module) {
        $this->module = $module;
        $this->data = $this->module->getOption(static::STORAGE_KEY, []);
        $this->data = array_column($this->data, null, static::PK);
    }

    public function setRowDefaults(array $defaults) {
        $this->defaults = $defaults;
    }

    public function __destruct() {
        if ($this->is_modified) {
            $this->flush();
        }
    }

    public function flush() {
        $this->module->setConfig([static::STORAGE_KEY => array_values($this->data)] + $this->module->getConfig());
    }

    public function get(array $filter = []): array {
        $ids = array_keys($this->data);

        if ($filter['ids']??false) {
            $ids = array_intersect($ids, (array) $filter['ids']);
        }

        if ($filter['source_userid']??false) {
            $ids = array_filter($ids, fn($id) => $this->data[$id]['source_userid'] === $filter['source_userid']);
        }

        if ($filter['source_filters']??false) {
            $ids = array_filter($ids, fn($id) => array_intersect($this->data[$id]['source_filters'], $filter['source_filters']));
        }

        if (($filter['offset']??false) || ($filter['limit']??false)) {
            $ids = array_slice($ids, $filter['offset']??0, $filter['limit']??null, true);
        }

        $rows = array_intersect_key($this->data, array_flip($ids));

        if ($rows && ($filter['output']??false)) {
            $output = array_flip((array) $filter['output']);
            $rows = array_map(static fn($row) => array_intersect_key($row, $output), $rows);
        }

        foreach ($rows as &$row) {
            $row += $this->defaults;
        }
        unset($row);

        return $rows;
    }

    public function create(array $entities): array {
        $entities = array_filter($entities,
            static fn($entity) => is_array($entity) && !array_key_exists(static::PK, $entity)
        );

        if (!$entities) {
            return [];
        }

        $this->is_modified = true;
        $nextid = 1;

        if ($this->data) {
            $nextid = max(array_map(fn($id) => (int) base_convert($id, 35, 10), array_keys($this->data)));
        }

        foreach ($entities as &$entity) {
            $nextid++;
            $pk_value = base_convert($nextid, 10, 35);
            $entity[static::PK] = $pk_value;
            $this->data[$pk_value] = $entity;
        }
        unset($entity);

        return $entities;
    }

    public function update(array $entities): array {
        $entities = array_filter($entities,
            fn($entity) => is_array($entity) && array_key_exists($entity[static::PK]??'', $this->data)
        );

        if (!$entities) {
            return [];
        }

        foreach ($entities as &$entity) {
            $db_entity = $this->data[$entity[static::PK]];
            $diff = array_udiff_assoc($entity, $db_entity, static fn($a, $b) => $a !== $b);
            $entity = array_replace($db_entity, $diff);

            if ($diff) {
                $this->is_modified = true;
                $this->data[$entity[static::PK]] = $entity;
            }
        }
        unset($entity);

        return $entities;
    }

    public function delete(array $ids): array {
        $deletedids = array_intersect($ids, array_keys($this->data));

        if ($deletedids) {
            $this->is_modified = true;
            $this->data = array_diff_key($this->data, array_flip($deletedids));
        }

        return $deletedids;
    }
}
