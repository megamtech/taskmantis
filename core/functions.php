<?php

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
        $bugs_array[$key]['task_completion_date'] = date('m/d/Y',
                $value->due_date);
        $bugs_array[$key]['task_start_date'] = date('m/d/Y', $value->due_date);
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

function get_all_tasks_by_projectId($p_project_id) {
    $f_page_number = gpc_get_int('page_number', 1);
    $t_per_page = null;
    $t_bug_count = null;
    $t_page_count = null;
    $t = filter_get_bug_rows($f_page_number, $t_per_page, $t_page_count,
            $t_bug_count, null, $p_project_id, null, true);
    return $t;

}

function create_task($c_task_id, $reporter_id, $assigned_id, $due_date, $parent_task_id, $duration, $duration_in_min, $issue_type, $info = '', $duration_type = 'm') {

    $t_query = 'INSERT INTO ' . plugin_table('tasks') . '
						( task_id, reporter_id, assigned_id, due_date, parent_task_id,
                                                duration, duration_type, duration_in_min, percentage_of_completion,issue_type,last_updated,date_created,info )
					  VALUES
						( '
            . db_param() . ', '
            . db_param() . ', '
            . db_param() . ', '
            . db_param() . ', '
            . db_param() . ', '
            . db_param() . ', '
            . db_param() . ', '
            . db_param() . ', '
            . db_param() . ', '
            . 0 . ', '
            . date("Y-m-d H:i:s", time()) . ', '
            . date("Y-m-d H:i:s", time()) . ', '
            . db_param() . ' )';
    db_query($t_query,
            array(
        $c_task_id, $reporter_id, $assigned_id, $due_date,
        $parent_task_id,
        $duration, $duration_type, $duration_in_min, $issue_type,
        $info));
    # Recall the query, we want the filter ID
    db_param_push();
    $t_query = 'SELECT id
						FROM ' . plugin_table('tasks') . '
						WHERE task_id=' . db_param();
    $t_result = db_query($t_query, array($c_task_id));

    if ($t_row = db_fetch_array($t_result)) {
        return $t_row['id'];
    }
    return -1;

}

function get_task_by_id($task_id) {
    $t_query = 'SELECT t.*,mb.summary
						FROM ' . plugin_table('tasks') . ' t Join ' . db_get_table('bug') . ' mb on mb.id=t.task_id
						WHERE task_id=' . db_param();
    $t_result = db_query($t_query, array($task_id));
    if ($t_row = db_fetch_array($t_result)) {
        return $t_row;
    }
    return -1;

}

function get_task_by_projectid($project_id) {
    $t_query = 'SELECT t.*,mb.summary
						FROM ' . plugin_table('tasks') . ' t left Join ' . db_get_table('bug') . ' mb on mb.id=t.task_id
						WHERE project_id=' . db_param();
    $t_result = db_query($t_query, array($project_id));
    if ($t_row = db_fetch_array($t_result)) {
        return $t_row;
    }
    return -1;

}

function get_child_tasks($parent_id) {
    $t_query = 'SELECT *
						FROM ' . plugin_table('tasks') . ' t Join ' . db_get_table('bug') . ' mb on mb.id=t.task_id
						WHERE parent_task_id=' . db_param();
    $t_result = db_query($t_query, array($parent_id));
    $t_child = [];
    while ($t_row = db_fetch_array($t_result)) {
        $t_child[] = $t_row;
    }
    return $t_child;

}

function create_selectbox_options_from_object($data, $value, $text) {
    $result = '';
    foreach ((array) $data as $key => $row) {
        $result.='<option value="' . $row->{$value} . '">' . $row->{$text} . '</option>';
    }
    return $result;

}

function create_selectbox_options_from_array($data, $value, $text) {
    $result = '';
    foreach ($data as $key => $row) {
        $result.='<option value="' . $row[$value] . '">' . $row[$text] . '</option>';
    }
    return $result;

}
