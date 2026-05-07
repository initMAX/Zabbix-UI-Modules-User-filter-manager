<?php

/**
 * @var CView $this
 */
$item_list = (new CTableInfo())
    ->setHeader([
        (new CColHeader((new CCheckBox('all_userfilters'))))->addClass(ZBX_STYLE_CELL_WIDTH),
        (new CColHeader(_('Actions')))->addClass(ZBX_STYLE_CELL_WIDTH),
        _('Source user'),
        _('Filters'),
        _('Target users'),
        _('Target user groups')
    ]);

foreach ($data['filters'] as $filter) {
    $users = [];

    foreach ($filter['target_userids'] as $userid) {
        $users[] = (new CSpan($data['users'][$userid]['username']))->addClass(ZBX_STYLE_TAG);
    }

    $groups = [];

    foreach ($filter['target_usergroupids'] as $groupid) {
        $groups[] = (new CSpan($data['usergroups'][$groupid]['name']))->addClass(ZBX_STYLE_TAG);
    }

    $filters = [];

    foreach ($filter['source_filters'] as $profileid) {
        if ($profileid == 0) {
            $label = _('Deleted filter');
        }
        else {
            $profile_filter = $data['profile_filters'][$profileid];
            $label = [$profile_filter['label'], ': ', $profile_filter['filter_name']];
        }

        $filters[] = (new CSpan($label))
            ->addClass(ZBX_STYLE_TAG)
            ->addStyle(($profileid == 0 ? 'background-color: var(--severity-color-disaster-bg)' : null));
    }

    $row = [
        new CCheckBox('userfilters['.$filter['id'].']', $filter['id']),
        (new CLinkAction(_('Edit')))->setAttribute('data-id', $filter['id'])->addClass('js-user-filter-edit'),
        (new CSpan($data['users'][$filter['source_userid']]['username']))->addClass(ZBX_STYLE_TAG),
        $filters,
        $users,
        $groups
    ];

    $item_list->addRow($row);
}

$page = (new CHtmlPage())
    ->setTitle($data['title'])
    ->setDocUrl('https://initmax.cz/user-filter-manager')
    ->setControls(
        (new CTag('nav', true,
            (new CList())->addItem(
                (new CSimpleButton(_('Create user filter')))->addClass('js-user-filter-create')
            )
        ))->setAttribute('aria-label', _('Content controls'))
    )
    ->addItem(
        (new CForm())
            ->setId('userfilterMAX')
            ->setName('userfilters')
            ->addItem($item_list)
            ->addItem(new CActionButtonList('action', 'userfilters', [
                [
                    'content' => (new CSimpleButton(_('Run')))
                        ->addClass(ZBX_STYLE_BTN_ALT)
                        ->addClass('js-selected-run')
                ],
                [
                    'content' => (new CSimpleButton(_('Delete')))
                        ->addClass(ZBX_STYLE_BTN_ALT)
                        ->addClass('js-selected-delete')
                ]
            ], 'userfilters'))
    )
    ->addItem((new CScriptTag('view.init('.json_encode([
        'messages' => [
            'confirm.run' => [_('Run selected user filter?'), _('Run selected users filters?')],
            'confirm.delete' => [_('Delete selected user filter?'), _('Delete selected users filters?')]
        ],
        'csrf' => [
            'mod.userfiltermax.run' => CCsrfTokenHelper::get('mod.userfiltermax.run'),
            'mod.userfiltermax.delete' => CCsrfTokenHelper::get('mod.userfiltermax.delete')
        ]
    ]).');'))->setOnDocumentReady());

$this->includeJsFile('mod.userfiltermax.list.js.php');
$page->show();

