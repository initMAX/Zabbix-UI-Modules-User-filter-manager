<?php

namespace Modules\UserFilterMAX\Services;

use CArrayHelper, DB;
use CControllerHost, CControllerProblem, CControllerLatest;

class FilterManagerService {

    const SUPPORTED_FILTERS = [
        CControllerProblem::FILTER_IDX,
        CControllerHost::FILTER_IDX,
        CControllerLatest::FILTER_IDX
    ];

    /**
     * Get user filters by filter name.
     *
     * @param int    $userid   Source user id.
     * @param string $pattern  Filter name search pattern.
     *
     * @return array of filters.
     */
    public function getUserFilters($userid, string $pattern): array {
        $idx_filters = [
            CControllerProblem::FILTER_IDX.'.properties',
            CControllerHost::FILTER_IDX.'.properties',
            CControllerLatest::FILTER_IDX.'.properties'
        ];
        $filters = DB::select('profiles', [
            'output' => ['profileid', 'type', 'value_str', 'idx', 'idx2'],
            'filter' => ['userid' => $userid, 'idx' => $idx_filters],
            'search' => ['value_str' => $pattern]
        ]);
        $filters = array_filter(
            array_map(static fn($v) => json_decode($v['value_str'], true) + $v, $filters),
            static fn($v) => stripos($v['filter_name']??'', $pattern) !== false
        );

        return $filters;
    }

    public function getFiltersByProfileIds($profileids): array {
        $filters = DB::select('profiles', [
            'output' => ['profileid', 'type', 'value_str', 'idx', 'idx2'],
            'filter' => ['profileid' => $profileids]
        ]);
        $filters = array_column($filters, null, 'profileid');
        $filters = array_map(static fn($v) => json_decode($v['value_str'], true) + $v, $filters);

        return $filters;
    }

    /**
     * Get all user defined filters.
     *
     * @param int    $userid   Source user id.
     *
     * @return array of filters.
     */
    public function getUserDefinedFilters($userid): array {
        $idx_filters = [
            CControllerProblem::FILTER_IDX.'.properties',
            CControllerHost::FILTER_IDX.'.properties',
            CControllerLatest::FILTER_IDX.'.properties'
        ];
        $filters = array_fill_keys($idx_filters, []);
        $db_filters = DB::select('profiles', [
            'output' => ['profileid', 'type', 'value_str', 'idx', 'idx2'],
            'filter' => ['userid' => $userid, 'idx' => $idx_filters]
        ]);
        $db_filters = array_map(static fn($v) => $v + ['value' => json_decode($v['value_str'], true)], $db_filters);
        CArrayHelper::sort($db_filters, ['idx', 'idx2']);

        foreach ($db_filters as $db_filter) {
            if ($db_filter['idx2'] == 0) {
                // Skip default filter.
                continue;
            }

            $filters[$db_filter['idx']][] = [
                'profileid' => $db_filter['profileid'],
                'name' => $db_filter['value']['filter_name'],
                'filter' => $db_filter['value']
            ];
        }

        return $filters;
    }

    /**
     * TODO: remove when profileid will be not used as unique identifier of selected filter
     */
    public function getIdxFilterTab($profileid): array {
        $idx_filter_tab = DB::select('profiles', [
            'output' => ['value_str', 'idx'],
            'filter' => ['profileid' => $profileid]
        ]);

        if (!$idx_filter_tab) {
            return [];
        }

        $idx_filter_tab = $idx_filter_tab[0];
        $idx_filter_tab['value'] = json_decode($idx_filter_tab['value_str'], true);

        return $idx_filter_tab;
    }


    /**
     * Update idx filter tab if already exists. Otherwise add new idx filter tab.
     *
     * @param array $filter      User filter.
     * @param array $idx_filter  Array of idx filter tabs.
     *
     * @return array of idx filter tabs.
     */
    public function addUserFilterToIdxFilter(array $filter, array $idx_filter): array {
        $filter_index = count($idx_filter);

        foreach ($idx_filter as $i => $idx_filter_tab) {
            if (!array_diff_assoc($filter, $idx_filter_tab)) {
                $filter_index = $i;

                break;
            }
        }

        $idx_filter[$filter_index] = $filter;

        return $idx_filter;
    }

    /**
     * Get stored user filters array from profiles table.
     *
     * @param string $idx     Profile idx key.
     * @param int    $userid  User id to get profile for.
     *
     * @return array of arrays with user filters for specific idx.
     */
    public function getUserIdxFilter(string $idx, $userid): array {
        $profile = [];
        $db_profiles = DB::select('profiles', [
            'output' => ['profileid', 'value_str', 'idx', 'idx2'],
            'filter' => ['userid' => $userid, 'idx' => $idx]
        ]);
        CArrayHelper::sort($db_profiles, ['idx2']);

        foreach ($db_profiles as $db_profile) {
            $profile[] = json_decode($db_profile['value_str'], true);
        }

        return $profile;
    }

    /**
     * Get default value of idx_filter.
     *
     * @param string $idx  Profile idx key.
     *
     * @return array of arrays with default user filters for specific idx.
     */
    public function getUserIdxFilterDefault(string $idx): array {
        return [['filter_name' => '']];
    }

    /**
     * Store user filters array in profiles table.
     *
     * @param string $idx      Profile idx key.
     * @param int    $userid   Stored profile user id.
     * @param array  $profile  Array of arrays with user filters for specific idx.
     */
    public function setUserIdxFilter(string $idx, $userid, array $profile) {
        $db_profileids = DB::select('profiles', [
            'output' => ['profileid'],
            'filter' => ['userid' => $userid, 'idx' => $idx]
        ]);
        $db_profileids = array_column($db_profileids, 'profileid');
        $ins_profiles = [];
        $upd_profiles = [];

        foreach (array_values($profile) as $idx2 => $profile_entry) {
            $row = ['value_str' => json_encode($profile_entry), 'idx2' => $idx2];

            if ($db_profileids) {
                $upd_profiles[] = ['where' => ['profileid' => array_shift($db_profileids)], 'values' => $row];
            }
            else {
                $ins_profiles[] = $row + ['userid' => $userid, 'idx' => $idx, 'type' => PROFILE_TYPE_STR];
            }
        }

        if ($upd_profiles) {
            DB::update('profiles', $upd_profiles);
        }

        if ($ins_profiles) {
            DB::insert('profiles', $ins_profiles);
        }

        if ($db_profileids) {
            DB::delete('profiles', ['profileid' => $db_profileids]);
        }
    }

    /**
     * Get display labels for available filters.
     *
     * @return array of labels.
     */
    public function getLabels(): array {
        return [
            CControllerProblem::FILTER_IDX.'.properties' => _('Problems'),
            CControllerHost::FILTER_IDX.'.properties' => _('Hosts'),
            CControllerLatest::FILTER_IDX.'.properties' => _('Latest data')
        ];
    }

    /**
     * Synchronize user filter changes in profiles table with data stored in user filter copy presets.
     * Update profile id stored in user filter copy presets when user filter stored in profile is deleted or changes order.
     *
     * @param StorageInterface $storage_service  Service to get and update changed stored user filter copy presets.
     * @param int              $userid           User id for which user filter is updated.
     * @param array            $input            Controller input.
     */
    public function syncFilterUpdate(StorageInterface $storage_service, $userid, array $input): void {
        $idx = $input['idx'] ?? '';
        [$idx, $property] = str_split($idx, strrpos($idx, '.')) + ['', ''];

        if (!in_array($idx, static::SUPPORTED_FILTERS)) {
            return;
        }

        $profiles = DB::select('profiles', [
            'output' => ['profileid', 'idx2'],
            'filter' => ['idx' => $idx.'.properties', 'userid' => $userid]
        ]);
        $idx_profileid = array_column($profiles, 'profileid', 'idx2');

        if ($property === '.taborder' && $input['value_str'] !== '') {
            $order = array_map('intval', explode(',', $input['value_str']));
            $this->handleTaborderUpdate($storage_service, $userid, $order, $idx_profileid);
        }

        if ($property === '.tabdelete' && $input['idx2'] !== '') {
            $order = array_keys($idx_profileid);
            sort($order);
            unset($order[$input['idx2']]);
            $order = array_values($order);
            $order[-1] = $input['idx2'];
            $idx_profileid[-1] = 0;
            $this->handleTaborderUpdate($storage_service, $userid, $order, $idx_profileid);
        }
    }

    /**
     * Handle profiles user filter order change. Updates profileid of source_filters stored in user filter copy presets.
     *
     * @param StorageInterface $storage_service  Service to get and update changed stored user filter copy presets.
     * @param int              $userid           User id for which profileid filters should be updated.
     * @param array            $sort_order       Array of new order for profile filters of user with $userid.
     * @param array            $idx_profileid    Associative array, key is idx2 from profiles and value is it profileid.
     */
    protected function handleTaborderUpdate(StorageInterface $storage_service, $userid, array $sort_order, array $idx_profileid): void {
        $fromid_toid = [];

        foreach ($sort_order as $index => $value) {
            if ($index === $value) {
                continue;
            }

            $fromid_toid[$idx_profileid[$value]] = $idx_profileid[$index];
        }

        if (!$fromid_toid) {
            return;
        }

        $filters = $storage_service->get([
            'output' => ['id', 'source_filters'],
            'source_userid' => $userid,
            'source_filters' => array_values($idx_profileid)
        ]);

        foreach ($filters as $i => $filter) {
            foreach ($filter['source_filters'] as $j => $profileid) {
                if (array_key_exists($profileid, $fromid_toid)) {
                    $filters[$i]['source_filters'][$j] = $fromid_toid[$profileid];
                }
            }
        }

        $storage_service->update($filters);
    }
}
