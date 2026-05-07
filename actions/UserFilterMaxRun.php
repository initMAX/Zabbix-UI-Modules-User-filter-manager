<?php

namespace Modules\UserFilterMAX\Actions;

use API, CNewValidator, CController, CControllerResponseData;

use Modules\UserFilterMAX\Module;
use Modules\UserFilterMAX\Services\{
    ConfigStorageService,
    FilterManagerService
};

class UserFilterMaxRun extends CController {

    public Module $module;

    protected function init(): void {
        $this->setPostContentType(self::POST_CONTENT_TYPE_JSON);
    }

    public function checkPermissions() {
        return true;
    }

    public function checkInput() {
        $fields = [
            // List
            'ids' => 'array_id',
            // Form
            'source_userid' => 'id',
            'source_filters' => 'array_id',
            'target_usergroupids' => 'array_id',
            'target_userids' => 'array_id'
        ];
        $ret = $this->validateInput($fields) && $this->validateRequired();

        if (!$ret) {
            $this->setResponse(
                new CControllerResponseData(['main_block' => json_encode([
                    'error' => [
                        'title' => _('Cannot copy user filters'),
                        'messages' => array_column(get_and_clear_messages(), 'message')
                    ]
                ])])
            );
        }

        return $ret;
    }

    protected function validateRequired(): bool {
        if ($this->hasInput('ids')) {
            return true;
        }

        $fields = [
            'source_userid' => 'id|required',
            'source_filters' => 'array_id|required',
            'target_usergroupids' => 'array_id',
            'target_userids' => 'array_id'
        ];
        $validator = new CNewValidator($this->getInputAll(), $fields);

        foreach ($validator->getAllErrors() as $error) {
            info($error);
        }

        $ret = !$validator->isError();

        if ($ret && !$this->hasInput('target_usergroupids') && !$this->hasInput('target_userids')) {
            error(_('At least one destination user or user groups must be selected.'));

            $ret = false;
        }

        return $ret;
    }

    public function doAction() {
        $storage = new ConfigStorageService($this->module);
        $filter_service = new FilterManagerService();

        if ($this->hasInput('ids')) {
            $userfilters = $storage->get(['ids' => $this->getInput('ids')]);
        }
        else {
            $userfilters = [['target_usergroupids' => [], 'target_userids' => []]];
            $this->getInputs($userfilters[0], [
                'source_userid', 'source_filters', 'target_usergroupids', 'target_userids'
            ]);
        }

        $result = true;

        foreach ($userfilters as $userfilter) {
            $add_idx_filter = [];

            foreach ($userfilter['source_filters'] as $profileid) {
                $db_user_filter = $filter_service->getIdxFilterTab($profileid);

                if (!$db_user_filter) {
                    // Skip copy of deleted user filter.
                    continue;
                }

                if (!array_key_exists($db_user_filter['idx'], $add_idx_filter)) {
                    $add_idx_filter[$db_user_filter['idx']] = [];
                }

                $add_idx_filter[$db_user_filter['idx']][] = $db_user_filter['value'];
            }

            if (!$add_idx_filter) {
                // TODO: notification, no source user filter found.
                continue;
            }

            $target_userids = [];

            if ($userfilter['target_usergroupids']) {
                $target_userids = API::User()->get([
                    'output' => ['userid'],
                    'usrgrpids' => $userfilter['target_usergroupids']
                ]);
                $target_userids = array_column($target_userids, 'userid');
            }

            $target_userids = array_merge($target_userids, $userfilter['target_userids']);
            $target_userids = array_diff($target_userids, [$userfilter['source_userid']]);

            foreach ($target_userids as $userid) {
                foreach (array_keys($add_idx_filter) as $idx) {
                    $idx_filter = $filter_service->getUserIdxFilter($idx, $userid);

                    if (!$idx_filter) {
                        $idx_filter = $filter_service->getUserIdxFilterDefault($idx);
                    }

                    foreach ($add_idx_filter[$idx] as $idx_filter_tab) {
                        $idx_filter = $filter_service->addUserFilterToIdxFilter($idx_filter_tab, $idx_filter);
                    }

                    $filter_service->setUserIdxFilter($idx, $userid, $idx_filter);
                }
            }
        }

        if ($result) {
            $output = [
                'success' => [
                    'title' => _('User filter copied'),
                    'messages' => []
                ]
            ];
            unset($storage);
        }
        else {
            $output = [
                'error' => [
                    'title' => _('Cannot copy user filter'),
                    'messages' => array_column(get_and_clear_messages(), 'message')
                ]
            ];
        }

        $this->setResponse(new CControllerResponseData(['main_block' => json_encode($output)]));
    }
}
