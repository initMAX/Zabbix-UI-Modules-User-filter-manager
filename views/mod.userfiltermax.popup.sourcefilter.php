<?php


$form = (new CDiv())->addClass('user-filter-manager-user-filter');

$i = 0;
foreach ($data['user_filters'] as $idx => $filter_group) {
    $i++;
    $block = (new CForm())
        ->setName('user_filters'.$i)
        ->addItem($data['filters_labels'][$idx]);
    $table = (new CTableInfo())
        ->setHeader([
            (new CColHeader(new CCheckBox('check_all')))->addClass(ZBX_STYLE_CELL_WIDTH),
            _('Name')
        ]);

    foreach ($filter_group as $filter) {
        $checked = in_array($filter['profileid'], $data['source_filters']);
        $checkbox = (new CCheckBox('user_filters'.$i.'['.$filter['profileid'].']', $filter['profileid']))
            ->setAttribute('data-label', $data['filters_labels'][$idx].': '.$filter['name']);
        $label = (new CLink($filter['name']))->addClass('js-user-filter');

        if ($checked) {
            $label = $filter['name'];
            $checkbox->setEnabled(false)->setChecked(true);
        }

        $table->addRow([$checkbox, $label]);
    }

    $form->addItem($block->addItem($table));
}

// Javascript
$script = <<<'JS'
(() => {
chkbxRange.init();
const overlay = overlays_stack.end();
const dialogue = overlay.$dialogue[0];

for (const checkall of dialogue.querySelectorAll('[name="check_all"]')) {
    const name = checkall.closest('form').getAttribute('name');
    checkall.addEventListener('change', () => checkAll(name, 'check_all', name));
}

dialogue.addEventListener('click', e => {
    let selected = [];
    let submit = false;

    switch (true) {
        case e.target.classList.contains('js-user-filter'):
            const input = e.target.closest('tr').querySelector('input[type="checkbox"]');

            submit = true;
            selected = [{id: input.value, name: input.getAttribute('data-label')}];

            break;

        case e.target.classList.contains('js-select-user-filters'):
            submit = true;
            selected = [...dialogue.querySelectorAll('[type="checkbox"][name^="user_filter"]:checked')].map(f => ({
                id: f.value, name: f.getAttribute('data-label')
            }));

            break;
    }

    if (submit) {
        overlayDialogueDestroy(overlay.dialogueid);
        dialogue.dispatchEvent(new CustomEvent('dialogue.submit', {detail: selected}));
    }
});
})();
JS;

// Style.
$form->addItem(new CTag('style', true, <<<'CSS'
.user-filter-manager-user-filter {
    form { margin-bottom: 20px; }
    .list-table.no-data tbody tr .no-data-message { margin-top: 10px; margin-bottom: 10px; }
}
CSS
));

$output = [
    'header' => _('User filter'),
    'body' => $form->toString(),
    'buttons' => [
        [
            'title' => _('Select'),
            'class' => 'js-select-user-filters',
            'isSubmit' => true
        ]
    ],
    'script_inline' => $script,
    'dialogue_class' => 'modal-popup-medium'
];

echo json_encode($output);
