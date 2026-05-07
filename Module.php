<?php

namespace Modules\UserFilterMAX;

use APP, CMenuItem;
use CController as Action;
use CControllerTabFilterProfileUpdate, CControllerPopupTabFilterDelete, CWebUser;
use Zabbix\Core\CModule;
use Modules\UserFilterMAX\Services\{
    FilterManagerService,
    ConfigStorageService
};

class Module extends CModule {

    public function init(): void {
        $this->registerMenuEntry();
    }

    public function onBeforeAction(Action $action): void {
        // Define property $module for action to have module class instance.
        if (strpos($action::class, __NAMESPACE__) === 0 && property_exists($action, 'module')) {
            $action->module = $this;
        }

        if ($action::class === CControllerTabFilterProfileUpdate::class
                || $action::class === CControllerPopupTabFilterDelete::class) {
            // Handle profiles user filters changes.
            $input = [
                'idx' => getRequest('idx', ''),
                'value_str' => getRequest('value_str', ''),
                'idx2' => getRequest('idx2', '')
            ];

            if ($action::class === CControllerPopupTabFilterDelete::class) {
                $input['idx'] = $input['idx'].'.tabdelete';
            }

            $service = new FilterManagerService();
            $service->syncFilterUpdate($this->getStorageService(), CWebUser::$data['userid'], $input);
        }
    }

    public function onTerminate(Action $action): void {
    }

    /**
     * Get user filter copy presets storage service.
     *
     * @return ConfigStorageService
     */
    public function getStorageService(): ConfigStorageService {
        static $storage_service;

        if ($storage_service === null) {
            $storage_service = new ConfigStorageService($this);
        }

        return $storage_service;
    }

    protected function registerMenuEntry() {
        /** @var CMenuItem $menu */
        $menu = APP::Component()->get('menu.main')->find(_('Users'));

        if ($menu instanceof CMenuItem) {
            $menu->getSubMenu()
                ->insertAfter(_('Users'), (new CMenuItem(_('User filter manager')))->setAction('mod.userfiltermax.list'));
        }
    }
}
