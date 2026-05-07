<?php

namespace Modules\UserFilterMAX\Actions;

use CController, CControllerResponseData;
use Modules\UserFilterMAX\Services\FilterManagerService;

class PopupSourceFilter extends CController {

    public function init() {
        $this->disableCsrfValidation();
    }

    protected function checkPermissions() {
        return true;
    }

    protected function checkInput() {
        $fields = [
            'source_userid' => 'id|required',
            'source_filters' => 'array_id'
        ];

        $valid = $this->validateInput($fields);

        return $valid;
    }

    public function doAction() {
        $service = new FilterManagerService;
        $data = [
            'source_userid' => 0,
            'source_filters' => [],
            'user_filters' => $service->getUserDefinedFilters($this->getInput('source_userid')),
            'filters_labels' => $service->getLabels()
        ];
        $this->getInputs($data, array_keys($data));

        $this->setResponse(new CControllerResponseData($data));
    }
}
