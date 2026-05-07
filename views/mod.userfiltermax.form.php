<?php


use Modules\UserFilterMAX\Html\SuggestBox;

/**
 * @var CView $this
 * @var array $data
 */

$form = (new CForm('post'))
    ->setName('user-filter-manager-form')
    ->addItem(getMessages());

// Enable form submitting on Enter.
$form->addItem((new CSubmitButton())->addClass(ZBX_STYLE_FORM_SUBMIT_HIDDEN));
$buttons = [];

if ($data['filter']['id'] ?? false) {
    $form->addVar('id', $data['filter']['id']);
    $buttons[] = [
        'title' => _('Update'),
        'keepOpen' => true,
        'isSubmit' => true,
        'action' => 'user_filter_manager_form.update()'
    ];

    $buttons[] = [
        'title' => _('Update and run'),
        'keepOpen' => true,
        'isSubmit' => true,
        'action' => 'user_filter_manager_form.run()'
    ];

    $buttons[] = [
        'title' => _('Delete'),
        'confirmation' => _('Delete user filter?'),
        'class' => ZBX_STYLE_BTN_ALT,
        'keepOpen' => true,
        'isSubmit' => true,
        'action' => 'user_filter_manager_form.delete();'
    ];
}
else {
    $buttons[] = [
        'title' => _('Add'),
        'keepOpen' => true,
        'isSubmit' => true,
        'action' => 'user_filter_manager_form.create();'
    ];
    $buttons[] = [
        'title' => _('Add and run'),
        'keepOpen' => true,
        'isSubmit' => true,
        'action' => 'user_filter_manager_form.run();'
    ];
}

$formgrid = new CFormGrid();
$formgrid->addItem([
    (new CLabel(_('Source user'), 'source_userid_ms'))->setAsteriskMark(),
    new CFormField(
        (new CMultiSelect([
            'name' => 'source_userid',
            'object_name' => 'users',
            'multiple' => false,
            'data' => $data['multiselect']['source_user']??[],
            'popup' => [
                'parameters' => [
                    'srctbl' => 'users',
                    'srcfld1' => 'userid',
                    'srcfld2' => 'fullname',
                    'dstfrm' => $form->getName(),
                    'dstfld1' => 'source_userid'
                ]
            ]
        ]))
            ->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
            ->setAriaRequired()
    )
]);
$formgrid->addItem([
    (new CLabel(_('Source filters')))->setAsteriskMark(),
    new CFormField(
        (new SuggestBox([
            'name' => 'source_filters[]',
            'suggest_action' => 'mod.userfiltermax.suggest',
            'multiple' => true,
            'custom_select' => true,
            'data' => $data['multiselect']['source_filters']??[],
            'autosuggest' => [
                'filter_preselect' => [
                    'id' => 'source_userid',
                    'submit_as' => 'source_userid'
                ]
            ]
        ]))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
    )
]);
$formgrid->addItem(new CFormField(
    (new CLabel(_('At least one destination user or user groups must be selected.')))->setAsteriskMark()
));
$formgrid->addItem([
    new CLabel(_('Destination user group'), 'target_usergroupids__ms'),
    new CFormField(
        (new CMultiSelect([
            'name' => 'target_usergroupids[]',
            'object_name' => 'usersGroups',
            'data' => $data['multiselect']['target_usergroups']??[],
            'popup' => [
                'parameters' => [
                    'srctbl' => 'usrgrp',
                    'srcfld1' => 'usrgrpid',
                    'dstfrm' => $form->getName(),
                    'dstfld1' => 'target_usergroupids_'
                ]
            ]
        ]))
            ->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
            ->setAriaRequired()
    )
]);
$formgrid->addItem([
    new CLabel(_('Destination users'), 'target_userids__ms'),
    new CFormField(
        (new CMultiSelect([
            'name' => 'target_userids[]',
            'object_name' => 'users',
            'data' => $data['multiselect']['target_users']??[],
            'popup' => [
                'parameters' => [
                    'srctbl' => 'users',
                    'srcfld1' => 'userid',
                    'srcfld2' => 'fullname',
                    'dstfrm' => $form->getName(),
                    'dstfld1' => 'target_userids_'
                ]
            ]
        ]))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
    )
]);

$form
    ->addItem(
        (new CTabView())
            ->setSelected(0)
            ->addTab('', '', $formgrid)
    )
    ->addItem((new CScriptTag('user_filter_manager_form.init('.json_encode([
        'csrf' => [
            'mod.userfiltermax.run' => CCsrfTokenHelper::get('mod.userfiltermax.run'),
            'mod.userfiltermax.create' => CCsrfTokenHelper::get('mod.userfiltermax.create'),
            'mod.userfiltermax.update' => CCsrfTokenHelper::get('mod.userfiltermax.update'),
            'mod.userfiltermax.delete' => CCsrfTokenHelper::get('mod.userfiltermax.delete')
        ]
    ]).');'))->setOnDocumentReady());

$output = [
    'header' => ($data['filter']['id'] ?? false) ? _('User filter') : _('New user filter'),
    'body' => $form->toString(),
    'buttons' => $buttons,
    'script_inline' => getPagePostJs().$this->readJsFile('mod.userfiltermax.form.js.php'),
    'dialogue_class' => 'modal-popup-large'
];

if ($data['user']['debug_mode'] == GROUP_DEBUG_MODE_ENABLED) {
    CProfiler::getInstance()->stop();
    $output['debug'] = CProfiler::getInstance()->make()->toString();
}

echo json_encode($output);
