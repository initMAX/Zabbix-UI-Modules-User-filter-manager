<?php

namespace Modules\UserFilterMAX\Html;


use CUrl, CMultiSelect;

class SuggestBox extends CMultiSelect {

    public function __construct(array $options = []) {
        parent::__construct(['add_post_js' => false] + $options);

        $this->params['url'] = (new Curl)->setArgument('action', $options['suggest_action'])->getUrl();

        if ($options['buttons'] ?? false) {
            $this->params['popup']['buttons'] = $options['buttons'];
        }

        $this->setAttribute('data-params', $this->params);

        if ($options['add_post_js'] ?? true) {
            zbx_add_post_js($this->getPostJS());
        }
    }
}