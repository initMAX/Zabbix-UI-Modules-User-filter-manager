<?php

namespace Modules\UserFilterMAX\Actions;

use CArrayHelper;
use API, CController, CControllerResponseData;

use Modules\UserFilterMAX\Module;
use Modules\UserFilterMAX\Services\{
    ConfigStorageService,
    FilterManagerService
};

class UserFilterMaxForm extends CController {

    public Module $module;

    public const VALUE_DEFAULT = [
        'source_userid' => null,
        'source_filters' => [],
        'target_userids' => [],
        'target_usergroupids' => []
    ];

    public function init() {
        $this->disableCsrfValidation();
    }

    public function checkPermissions() {
        return true;
    }

    public function checkInput() {
        $fields = [
            'id' =>   'id'
        ];

        $this->validateInput($fields);

        return true;
    }

    public function doAction() {
        $filter = UserFilterMaxForm::VALUE_DEFAULT;

        if ($this->hasInput('id')) {
            $storage = new ConfigStorageService($this->module);
            $storage->setRowDefaults($filter);
            $db_filter = $storage->get(['ids' => [$this->getInput('id')]]);
            $filter = $db_filter ? reset($db_filter) : $filter;
        }

        $data = [
            'title' => $this->hasInput('id') ? _('Update filter') : _('Create filter'),
            'filter' => $filter,
            'multiselect' => $this->getMultiselectsViewData($filter),
            'user' => ['debug_mode' => $this->getDebugMode()]
        ];

        $response = new CControllerResponseData($data);
        $response->setTitle($data['title']);

        $this->setResponse($response);
    }

    protected function getMultiselectsViewData(array $filter): array {
        $ms = [];

        if ($filter['source_userid'] !== null) {
            $ms['source_user'] = API::User()->get([
                'output' => ['userid', 'username'],
                'userids' => [$filter['source_userid']]
            ]);
            $ms['source_user'] = CArrayHelper::renameObjectsKeys($ms['source_user'],
                ['userid' => 'id', 'username' => 'name']
            );
        }

        if ($filter['source_filters']) {
            $filter_service = new FilterManagerService();
            $profile_label = $filter_service->getLabels();
            $ms['source_filters'] = [];

            foreach ($filter_service->getFiltersByProfileIds($filter['source_filters']) as $profile_filter) {
                $ms['source_filters'][] = [
                    'id' => $profile_filter['profileid'],
                    'name' => $profile_filter['filter_name'],
                    'prefix' => $profile_label[$profile_filter['idx']].': '
                ];
            }
        }

        if ($filter['target_userids']) {
            $ms['target_users'] = API::User()->get([
                'output' => ['userid', 'username'],
                'userids' => $filter['target_userids']
            ]);
            $ms['target_users'] = CArrayHelper::renameObjectsKeys($ms['target_users'],
                ['userid' => 'id', 'username' => 'name']
            );
        }

        if ($filter['target_usergroupids']) {
            $ms['target_usergroups'] = API::UserGroup()->get([
                'output' => ['usrgrpid', 'name'],
                'usrgrpids' => $filter['target_usergroupids']
            ]);
            $ms['target_usergroups'] = CArrayHelper::renameObjectsKeys($ms['target_usergroups'], ['usrgrpid' => 'id']);
        }

        return $ms;
    }
}