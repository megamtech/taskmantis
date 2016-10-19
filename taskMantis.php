<?php

class taskMantisPlugin extends MantisPlugin {

    /**
     * A method that populates the plugin information and minimum requirements.
     * @return void
     */
    function register() {
        $this->name = plugin_lang_get('title');
        $this->description = plugin_lang_get('description');
        $this->page = 'taskMantis.php';

        $this->version = MANTIS_VERSION;
        $this->requires = array(
            'MantisCore' => '2.0.0',
        );

        $this->author = 'Megam Technologies LLP';
        $this->contact = 'itsupport@megamtech.com';
        $this->url = 'http://www.megamtech.com';

    }

    function init() {
        // Get path to core folder
        $t_core_path = config_get_global('plugin_path') .
                plugin_get_current() .
                DIRECTORY_SEPARATOR .
                'core' .
                DIRECTORY_SEPARATOR;

        // Include constants
        require_once($t_core_path . 'functions.php');

    }

    public function hooks() {
        return array(
            "EVENT_MENU_MAIN" => "menu",
            'EVENT_LAYOUT_RESOURCES' => 'resources',
            'EVENT_REPORT_BUG_FORM' => 'report_bug_form',
            'EVENT_REPORT_BUG' => 'report_bug',
            'EVENT_FILTER_COLUMNS' => 'check_fn',
            'EVENT_UPDATE_BUG_FORM' => 'update_bug_form',
            'EVENT_UPDATE_BUG' => 'update_bug',
            'EVENT_VIEW_BUG_DETAILS' => 'view_bug_details',
            'EVENT_VIEW_BUG_EXTRA' => 'view_bug_extra',
            'EVENT_VIEW_BUGNOTES_START' => 'view_bugnotes_start',
            'EVENT_VIEW_BUGNOTE' => 'view_bugnote',
            'EVENT_MANAGE_PROJECT_CREATE_FORM' => 'project_create_form',
            'EVENT_MANAGE_PROJECT_CREATE' => 'project_update',
            'EVENT_MANAGE_PROJECT_UPDATE_FORM' => 'project_update_form',
            'EVENT_MANAGE_PROJECT_UPDATE' => 'project_update',
            'EVENT_MENU_SUMMARY' => 'view_timecard',
            'EVENT_FILTER_COLUMNS' => 'add_columns'
        );

    }

    function check_fn() {
        return array(1, 2);

    }

    function view_bug_details($event, $task_id) {
        $parent_task_details = '';
        $task_duration_details = '';
        $task_due_date = '';
        $child_tasks_text = '';

        $task_details = get_task_by_id($task_id);
        $child_tasks = get_child_tasks($task_id);
        if ($task_details['parent_task_id'] != '') {
            $parent_task_details = '<a href="' . config_get_global('path') . 'view.php?id=' . $task_details['parent_task_id'] . '">' . $task_details['summary'] . ' (' . $task_details['parent_task_id'] . ')</a>';
        }
        if ($task_details['duration'] > 0 && $task_details['duration_type'] != '') {
            $task_duration_details = $task_details['duration'] . ' ' . plugin_lang_get('timein_' . $task_details['duration_type']);
        }
        if ($task_details['due_date'] != '0000-00-00 00:00:00') {
            $task_due_date = date("j M, Y", strtotime($task_details['due_date']));
        }

        if (isset($child_tasks[0])) {
            foreach ($child_tasks as $c_task => $value) {
                $child_tasks_text .= '<a href="' . config_get_global('path') . 'view.php?id=' . $value['task_id'] . '">' . $value['summary'] . ' (' . $value['task_id'] . ')' . '</a></br>';
            }
        }
        echo
        '<tr ', helper_alternate_class(), '>'
        . '<td class="category">' . plugin_lang_get('parent_task')
        . '</td>'
        . '<td>'
        . $parent_task_details
        . '</td>'
        . '<td class="category">' . plugin_lang_get('duration')
        . '</td>'
        . '<td>'
        . $task_duration_details
        . '</td>'
        . '<td class="category">' . plugin_lang_get('due_date')
        . '</td>'
        . '<td>'
        . $task_due_date
        . '</td>'
        . '</tr>'
        . '<tr>'
        . '<td class="category">' . plugin_lang_get('child_task')
        . '</td>'
        . '<td colspan="5" >'
        . $child_tasks_text
        . '</td>'
        . '</tr>';

    }

    public function config() {
        return array(
            "status_board_order_default" => array(
                "New", "Feedback", "Acknowledged", "Confirmed", "Assigned", "Resolved",
                "Closed"
            ),
            "status_board_order" => array(
                "New", "Feedback", "Acknowledged", "Confirmed", "Assigned", "Resolved",
                "Closed"
            ),
            "cooldown_period_days" => 14,
            "cooldown_period_hours" => 0,
            'estimate_threshold' => DEVELOPER,
            'issue_types' => array(
                1 => 'Bug',
                2 => 'Improvement',
                3 => 'New Feature',
                4 => 'Task',
                5 => 'Custom Issue'
            )
        );

    }

    function resources($p_event) {
        return '<script type="text/javascript" src="' . plugin_file('bootstrap-datepicker/js/bootstrap-datepicker.min.js') . '"></script>' .
                '<link rel="stylesheet" type="text/css" href="' . plugin_file('bootstrap-datepicker/css/bootstrap-datepicker3.standalone.min.css') . '"/>';

    }

    public function menu($event) {
        $links = array();

        $links[] = array('url' => plugin_page("sheet", true), 'title' => plugin_lang_get("board"),
            'icon' => 'fa-edit');
        $links[] = array('url' => plugin_page("tasks", true), 'title' => plugin_lang_get("tasks"),
            'icon' => 'fa-tasks');
        return $links;

    }

    function report_bug($p_event, $p_task_details, $task_id) {
        $is_taskmantis_enabled = gpc_get_string('plugin_taskmantis_enabled');
        $p_taskmantis_duedate = date("Y-m-d H:i:s",
                strtotime(gpc_get_string('plugin_taskmantis_duedate')));
        $p_taskmantis_duration = gpc_get_string('plugin_taskmantis_duration');
        $p_taskmantis_duration_type = gpc_get_string('plugin_taskmantis_duration_type');
        $p_taskmantis_parent_task = gpc_get_string('plugin_taskmantis_parent_task');
        $p_taskmantis_issue_type = gpc_get_string('plugin_taskmantis_issue_types');
        $task_id = $p_task_details->id;
        $reporter_id = $p_task_details->reporter_id;
        $assigned_id = $p_task_details->handler_id;
        if ($p_taskmantis_duration_type == 'h') {
            $p_taskmantis_duration_in_min = $p_taskmantis_duration * 60;
        } else {
            $p_taskmantis_duration_in_min = $p_taskmantis_duration;
        }
        $info = '';
        $task_id = create_task($p_task_details->id,
                $p_task_details->reporter_id, $p_task_details->handler_id,
                $p_taskmantis_duedate, $p_taskmantis_parent_task,
                $p_taskmantis_duration, $p_taskmantis_duration_in_min, $info,
                $p_taskmantis_duration_type, $p_taskmantis_issue_type);

    }

    /**
     * When reporting a bug, show appropriate form elements to the user.
     * @param string Event name
     * @param int Project ID
     */
    function report_bug_form($p_event, $p_project_id) {

        if (access_has_project_level(plugin_config_get('estimate_threshold'),
                        $p_project_id)) {
//            echo '<tr ', helper_alternate_class(), '>'
//            . '<td class="category">', plugin_lang_get('issue_type'),
//            '</td>'
//            . '<td>'
//            . '<select name="plugin_taskmantis_issue_type" class="col-sm-2">';
//            foreach (plugin_config_get('issue_types') as $key => $value) {
//                echo '<option value="' . $key . '">' . $value . '</option>';
//            }
//            echo '</select>'
//            . '</td>'
//            . '</tr>';
            echo '<tr ', helper_alternate_class(), '>'
            . '<td class="category">', plugin_lang_get('parent_task'),
            '</td>'
            . '<td>' . '<select name="plugin_taskmantis_parent_task" class="col-sm-12">'
            . '<option value="">--' . plugin_lang_get('select_empty_option') . '--</option>'
            . create_selectbox_options_from_object(get_all_tasks_by_projectId($p_project_id),
                    'id', 'summary')
            . '</select>'
            . '</td>'
            . '</tr>';
            echo '<tr ', helper_alternate_class(), '>'
            . '<td class="category">', plugin_lang_get('due_date'),
            '<input type="hidden" name="plugin_taskmantis_enabled" value="1"/>',
            '</td>'
            . '<td>'
            . '<input  class="datepicker col-sm-4" readonly name="plugin_taskmantis_duedate" size="8" type="text"/>'
            . '<script>$(".datepicker").datepicker({
                    format: "dd MM yyyy",
                    autoclose:true,
                    daysOfWeekHighlighted:[1,2,3,4,5],
                    todayBtn:true,
                    startDate: "-3d"
              });</script>'
            . '</td></tr>';


            echo '<tr ', helper_alternate_class(), '>'
            . '<td class="category">', plugin_lang_get('duration'),
            '</td>'
            . '<td>'
            . '<input name="plugin_taskmantis_duration" size="8" class="input-sm" type="number" value="30" min="1" max="3600"/>&nbsp;&nbsp;&nbsp;'
            . '<select name="plugin_taskmantis_duration_type" class="input-sm">'
            . '<option value="m" selected>Minute(s)</option>'
            . '<option value="h">Hour(s)</option>'
            . '</select>', '</td>'
            . '</tr>';
        }

    }

    function plugin_callback_taskmantis_install() {

    }

    function plugin_callback_taskmantis_upgrade() {

    }

    function uninstall() {
        $t_query = 'Drop Table IF Exists ' . plugin_table('tasks');
        $t_result = db_query($t_query, array());

    }

    function schema() {


        return array(
            array('CreateTableSQL',
                array(plugin_table('tasks'), "
				id			I       NOTNULL AUTOINCREMENT PRIMARY,
				task_id			I       NOTNULL ,
				reporter_id			I       NOTNULL ,
				assigned_id               I       NOTNULL ,
				due_date    T       NOTNULL,
				parent_task_id              I DEFAULT 0 NOTNULL,
				duration              F(15,3) DEFAULT 10 NOTNULL,
				duration_type              C(1) DEFAULT 'm',
				duration_in_min              F(15,3) NOTNULL,
				percentage_of_completion              F(2,2) Default 0 NOTNULL,
				date_created          T       DEFAULT NULL,
				last_updated          T       DEFAULT NULL,
				info               C(255)  DEFAULT NULL
				", array("mysql" => "ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin")
                )
            ),
            array('CreateTableSQL',
                array(plugin_table('tasks'), "
                    user_id			I			NOTNULL UNSIGNED PRIMARY,
                    developer		L			NOTNULL DEFAULT '0',
                    participant 	L			NOTNULL DEFAULT '0',
                    administrator 	L			NOTNULL DEFAULT '0'
                    ",
                    array("mysql" => "ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin"))),
        );

    }

}
