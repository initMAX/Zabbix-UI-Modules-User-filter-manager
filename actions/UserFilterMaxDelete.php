<?php

namespace Modules\UserFilterMAX\Actions;

use CController, CControllerResponseData;

use Modules\UserFilterMAX\Module;
use Modules\UserFilterMAX\Services\ConfigStorageService;

class UserFilterMaxDelete extends CController {

    public Module $module;

    protected function init(): void {
        $this->setPostContentType(self::POST_CONTENT_TYPE_JSON);
    }

    public function checkPermissions() {
        return true;
    }

    public function checkInput() {
        $fields = [
            'id' => 'id',
            'ids' => 'array_id'
        ];
        $ret = $this->validateInput($fields) && $this->getInputAll() !== [];

        if (!$ret) {
            $this->setResponse(
                new CControllerResponseData(['main_block' => json_encode([
                    'error' => [
                        'title' => _('Cannot delete user filter'),
                        'messages' => array_column(get_and_clear_messages(), 'message')
                    ]
                ])])
            );
        }

        return $ret;
    }

    public function doAction() {
        $storage = new ConfigStorageService($this->module);
        $ids = $this->getInput('ids', []);

        if ($this->hasInput('id')) {
            $ids[] = $this->getInput('id');
        }

        $result = $storage->delete($ids);

        if ($result) {
            $output = [
                'success' => [
                    'title' => _('User filter deleted'),
                    'messages' => []
                ]
            ];
            unset($storage);
        }
        else {
            $output = [
                'error' => [
                    'title' => _('Cannot delete user filter'),
                    'messages' => []
                ]
            ];
        }

        $this->setResponse(new CControllerResponseData(['main_block' => json_encode($output)]));
    }
}
