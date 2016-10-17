<?php

require_once( 'core.php' );
layout_page_header();
layout_page_begin();
print_form_button(plugin_page("sheet", true),
        lang_get('create_new_account_link'), null, null,
        'btn btn-primary btn-white btn-round');
