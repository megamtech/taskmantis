<?php

require_once( 'core.php' );
layout_page_header_begin("Smart Sheet - Mantis");
compress_enable();
html_robots_noindex();
layout_page_header_end();

get_all_user();




auth_ensure_user_authenticated();
# Get Project Id and set it as current
$t_project_id = gpc_get_int('project_id', helper_get_current_project());
if (( ALL_PROJECTS == $t_project_id || project_exists($t_project_id) ) && $t_project_id != helper_get_current_project()) {
    helper_set_current_project($t_project_id);
    # Reloading the page is required so that the project browser
    # reflects the new current project
    print_header_redirect($_SERVER['REQUEST_URI'], true, false, true);
}
layout_page_begin();

//layout_page_header_begin(lang_get('view_bugs_link'));
//layout_page_header_end();
//layout_page_begin(__FILE__);

?>
<?php html_javascript_link('handsontable.full.min.js'); ?>
<?php html_javascript_link('chosen.js'); ?>
<?php html_javascript_link('handsontable-chosen-editor.js'); ?>
<?php html_javascript_link('script.js'); ?>
<?php html_css_link('handsontable.full.min.css'); ?>
<?php

html_css_link('chosen.css');
$t_project_id = gpc_get_int('project_id', helper_get_current_project());

?>

<div id="tasks"></div>
<script>


    var tasksElement = document.querySelector('#tasks');
    var tasksSettings = {
        data:<?php echo json_encode(get_task_by_projectid($t_project_id), true); ?>,
        columns: [
            {
                data: 'id',
                renderer: htmlTaskLinkRenderer,
                readOnly: true,
//                type: 'text',
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
                data: 'task_start_date',
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
        minSpareRows: 1,
        columnSorting: true,
        manualColumnResize: true,
        autoWrapRow: true,
        sortIndicator: true,
        contextMenu: ['row_above', 'row_below', 'remove_row', 'undo', 'redo', 'alignment', 'borders', 'commentsAddEdit', 'commentsRemove '],
        customBorders: true,
        comments: true,
        rowHeaders: false,
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
    function htmlTaskLinkRenderer(instance, td, row, col, prop, value, cellProperties) {
        var escaped = Handsontable.helper.stringify(value);
//        escaped = strip_tags(escaped, '<em><b><strong><a><big>'); //be sure you only allow certain HTML tags to avoid XSS threats (you should also remove unwanted HTML attributes)
        td.innerHTML = '<?php echo '<a target="_blank" href="' . config_get_global('path') . 'view.php?id='; ?>' + escaped + '">' + escaped + '</a>';

        return td;
    }
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
<?php

layout_page_end();

?>