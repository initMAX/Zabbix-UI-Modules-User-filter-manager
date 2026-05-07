<?php

namespace Modules\UserFilterMAX\Actions;


use CController, CControllerResponseData;
use Modules\UserFilterMAX\Services\FilterManagerService;

class Suggest extends CController {

    public $module;

    public function init() {
        $this->disableCsrfValidation();
    }

    public function checkPermissions() {
        return true;
    }

    public function checkInput() {
        $fields = [
            'source_userid' =>  'id',
            'search' =>         'string',
            'limit' =>          'int32'
        ];

        $ret = $this->validateInput($fields);

        if (!$ret) {
            $this->setResponse([]);
        }

        return $ret;
    }

    public function doAction() {
        $search = preg_quote($this->getInput('search'), '/');
        $match = [];

        switch ($this->getAction()) {
            case 'mod.userfiltermax.suggest':
                if (!$this->hasInput('source_userid')) {
                    break;
                }

                $filter_service = new FilterManagerService();
                $profile_label = $filter_service->getLabels();

                foreach ($filter_service->getUserFilters($this->getInput('source_userid'), $search) as $filter) {
                    $match[] = [
                        'id' => $filter['profileid'],
                        'name' => $filter['filter_name'],
                        'prefix' => $profile_label[$filter['idx']].': '
                    ];
                }

                break;
        }

        if ($this->hasInput('limit')) {
            $match = array_slice($match, 0, $this->getInput('limit'));
        }

        $this->setResponse(['result' => $match]);
    }

    protected function setResponse($data) {
        $errors = array_column(get_and_clear_messages(), 'message');

        if ($errors) {
            $data['error']['messages'] = $errors;
        }

        parent::setResponse(
            new CControllerResponseData(['main_block' => json_encode($data)])
        );
    }
}
