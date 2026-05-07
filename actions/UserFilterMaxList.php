<?php

namespace Modules\UserFilterMAX\Actions;

use API, CController, CControllerResponseData;

use Modules\UserFilterMAX\Module;
use Modules\UserFilterMAX\Services\{
    ConfigStorageService,
    FilterManagerService
};

class UserFilterMaxList extends CController {

    public Module $module;

    public function init() {
        $this->disableCsrfValidation();
    }

    public function checkPermissions() {
        return true;
    }

    public function checkInput() {
        $fields = [
            'page' =>   'int32'// TODO: Add pagination
        ];

        $this->validateInput($fields);

        return true;
    }

    public function doAction() {
        $storage = new ConfigStorageService($this->module);
        $storage->setRowDefaults(UserFilterMaxForm::VALUE_DEFAULT);

        $filters = $storage->get();
        $data = [
            'title' => _('User filter manager'),
            'page' => $this->getInput('page', 0),
            'filters' => $filters,
            'profile_filters' => $this->getProfileFilters($filters),
            'users' => $this->getFiltersUsers($filters),
            'usergroups' => $this->getFiltersUsersGroups($filters)
        ];

        $response = new CControllerResponseData($data);
        $response->setTitle($data['title']);

        $this->setResponse($response);
    }

    protected function getProfileFilters(array $filters): array {
        $profileids = [];

        foreach ($filters as $filter) {
            $profileids = array_merge($profileids, $filter['source_filters']);
        }

        $filter_service = new FilterManagerService();
        $profile_label = $filter_service->getLabels();
        $profile_filters = $filter_service->getFiltersByProfileIds($profileids);
        $profile_filters = array_map(
            static fn($filter) => ['label' => $profile_label[$filter['idx']]] + $filter,
            $profile_filters
        );
        $profile_filters = array_column($profile_filters, null, 'profileid');

        return $profile_filters;
    }

    protected function getFiltersUsers(array $filters): array {
        $users = [];
        $userids = array_column($filters, 'source_userid');

        foreach ($filters as $filter) {
            $userids = array_merge($userids, $filter['target_userids']);
        }

        if ($userids) {
            $users = API::User()->get([
                'output' => ['userid', 'username'],
                'userids' => $userids
            ]);
            $users = array_column($users, null, 'userid');
        }

        return $users;
    }

    protected function getFiltersUsersGroups(array $filters): array {
        $groups = [];
        $groupids = [];

        foreach ($filters as $filter) {
            $groupids = array_merge($groupids, $filter['target_usergroupids']);
        }

        if ($groupids) {
            $groups = API::UserGroup()->get([
                'output' => ['usrgrpid', 'name'],
                'usrgrpids' => $groupids
            ]);
            $groups = array_column($groups, null, 'usrgrpid');
        }

        return $groups;
    }
}