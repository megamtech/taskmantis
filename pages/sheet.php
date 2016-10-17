<?php

require_once( 'core.php' );
layout_page_header();

function get_bug_list() {
//    require_once( 'core.php' );
    require_api('filter_api.php');
    $f_page_number = gpc_get_int('page_number', 1);
    $t_per_page = null;
    $t_bug_count = null;
    $t_page_count = null;
    $bugs = filter_get_bug_rows($f_page_number, $t_per_page, $t_page_count,
            $t_bug_count, null, null, null, true);
    foreach ((array) $bugs as $key => $value) {
        $bugs_array[$key]['id'] = $value->id;
        $bugs_array[$key]['project_id'] = $value->project_id;
        $bugs_array[$key]['reporter_id'] = $value->reporter_id;
        $bugs_array[$key]['priority'] = $value->priority;
        $bugs_array[$key]['severity'] = $value->severity;
        $bugs_array[$key]['status'] = $value->status;
        $bugs_array[$key]['date_created'] = date('m/d/Y', $value->date_submitted);
        $bugs_array[$key]['last_updated'] = date('m/d/Y', $value->last_updated);
        $bugs_array[$key]['task_name'] = $value->summary;
        $bugs_array[$key]['task_completion_date'] = $value->due_date;
        $bugs_array[$key]['task_start_date'] = $value->due_date;
        $bugs_array[$key]['description'] = $value->description;
    }
    return $bugs_array;

}

function get_statuses() {
    $statuses = MantisEnum::getAssocArrayIndexedByValues(config_get('status_enum_string'));
    $result = array();
    foreach ($statuses as $key => $value) {
        $result[] = array('id' => $key, 'label' => $value);
    }
    return $result;

}

function get_all_user() {
    $t_query = 'SELECT id,username as label,realname,email FROM {user} where enabled=1';
    $t_result = db_query($t_query);
    $t_users = array();
    while ($t_row = db_fetch_array($t_result)) {
        $t_users[] = $t_row;
    }
    return $t_users;

}

get_all_user();

layout_page_begin();

?>
<?php html_javascript_link('handsontable.full.min.js'); ?>
<?php html_javascript_link('chosen.js'); ?>
<?php html_javascript_link('handsontable-chosen-editor.js'); ?>
<?php html_javascript_link('script.js'); ?>
<?php html_css_link('handsontable.full.min.css'); ?>
<?php html_css_link('chosen.css'); ?>

<div id="tasks"></div>
<script>


    var tasksElement = document.querySelector('#tasks');
    var tasksSettings = {
        data:<?php echo json_encode(get_bug_list(), true); ?>,
        columns: [
            {
                data: 'id',
                type: 'text',
                width: 40
            },
            {
                data: 'task_name',
                type: 'text',
            },
            {
                data: 'date_created',
                type: 'date',
                dateFormat: 'MM/DD/YYYY',
            },
            {
                data: 'last_updated',
                type: 'date',
                dateFormat: 'MM/DD/YYYY',
            },
            {
                data: 'task_completion_date',
                type: 'date',
                dateFormat: 'MM/DD/YYYY',
            },
            {
                data: 'reporter_id',
                type: 'numeric',
                renderer: customDropdownRenderer,
                editor: "chosen",
                width: 150,
                chosenOptions: {
                    data: <?php

echo json_encode(get_all_user(), true);

?>
                }

            },
            {
                data: 'duration',
                type: 'text'
            },
            {
                data: 'percentage_complete',
                type: 'numeric',
                format: '0.00%'
            },
            {
                data: 'status',
                type: 'numeric',
                renderer: customDropdownRenderer,
                editor: "chosen",
                width: 150,
                chosenOptions: {
                    data: <?php

echo json_encode(get_statuses(), true);

?>
                }
            },
            {
                data: 'predecessors',
                type: 'text',
            },
            {
                data: 'dependents',
                type: 'text',
            },
            {
                data: 'descriptionn',
                type: 'text',
            },
            {
                data: 'tags',
                type: 'text',
            }
        ],
        filters: true,
        stretchH: 'all',
        columnSorting: true,
        manualColumnResize: true,
        autoWrapRow: true,
        rowHeaders: true,
        sortIndicator: true,
        contextMenu: true,
        colHeaders: [
            'ID',
            'Task Name',
            'Created Date',
            'Start Date',
            'End Date',
            'Assigned',
            'Duration',
            '% of Complete',
            'Status',
            'Predecessors',
            'Dependents',
            'Description',
            'Tags'
        ]
    };
    var tasks = new Handsontable(tasksElement, tasksSettings);
    function customDropdownRenderer(instance, td, row, col, prop, value, cellProperties) {
        var selectedId;
        var optionsList = cellProperties.chosenOptions.data;

        var values = (value + "").split(",");
        var value = [];
        for (var index = 0; index < optionsList.length; index++) {
            if (values.indexOf(optionsList[index].id + "") > -1) {
                selectedId = optionsList[index].id;
                value.push(optionsList[index].label);
            }
        }
        value = value.join(", ");

        Handsontable.TextCell.renderer.apply(this, arguments);
    }


</script>