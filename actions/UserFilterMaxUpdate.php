<?php

namespace Modules\UserFilterMAX\Actions;

use CController, CControllerResponseData;

use Modules\UserFilterMAX\Module;
use Modules\UserFilterMAX\Services\ConfigStorageService;

class UserFilterMaxUpdate extends CController {

    public Module $module;

    protected function init(): void {
        $this->setPostContentType(self::POST_CONTENT_TYPE_JSON);
    }

    public function checkPermissions() {
        return true;
    }

    public function checkInput() {
        $fields = [
            'id' => 'id|required',
            'source_userid' => 'id|required',
            'source_filters' => 'array_id|required',
            'target_usergroupids' => 'array_id',
            'target_userids' => 'array_id'
        ];
        $ret = $this->validateInput($fields) && $this->validateTargets();

        if (!$ret) {
            $this->setResponse(
                new CControllerResponseData(['main_block' => json_encode([
                    'error' => [
                        'title' => _('Cannot update user filter'),
                        'messages' => array_column(get_and_clear_messages(), 'message')
                    ]
                ])])
            );
        }

        return $ret;
    }

    protected function validateTargets(): bool {
        $targets = array_merge($this->getInput('target_usergroupids', []), $this->getInput('target_userids', []));

        if (!$targets) {
            error(_('At least one destination user or user groups must be selected.'));
        }

        return (bool) $targets;
    }

    public function doAction() {
        $storage = new ConfigStorageService($this->module);
        $filters = [$this->getInputAll() + ['target_usergroupids' => [], 'target_userids' => []]];
        $result = $storage->update($filters);

        if ($result) {
            $output = [
                'success' => [
                    'title' => _('User filter updated'),
                    'messages' => []
                ]
            ];
            unset($storage);
        }
        else {
            $output = [
                'error' => [
                    'title' => _('Cannot update user filter'),
                    'messages' => []
                ]
            ];
        }

        $this->setResponse(new CControllerResponseData(['main_block' => json_encode($output)]));
    }
}
