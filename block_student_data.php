<?php
class block_student_data extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_student_data');
    }

    public function get_content() {
        global $USER, $DB, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $context = context_system::instance();
        $this->content = new stdClass();

        // Check if the user has the capability to view all students.
        $canviewall = has_capability('moodle/site:config', $context);
        $role = $DB->get_record_sql("
            SELECT r.shortname 
            FROM {role_assignments} ra 
            JOIN {role} r ON ra.roleid = r.id
            WHERE ra.userid = ?", [$USER->id]
        );

        // Fetch data based on role.
        if ($role->shortname == 'student') {
            $students = $DB->get_records('user', ['id' => $USER->id]);
        } else if ($canviewall) {
            $students = $DB->get_records_sql("
                SELECT u.* 
                FROM {user} u
                JOIN {role_assignments} ra ON u.id = ra.userid
                JOIN {role} r ON ra.roleid = r.id
                WHERE r.shortname = 'student'
            ");
        } else {
            $this->content->text = get_string('noaccess', 'block_student_data');
            return $this->content;
        }

        // Fetch custom fields for users.
        $customfields = $DB->get_records('user_info_field');

        // Render the table.
        $table = new html_table();
        $table->head = ['ID', 'Name', 'Email', 'City', 'Country'] + array_map(function ($f) {
            return $f->name;
        }, $customfields);
        
        foreach ($students as $student) {
            $userdata = [$student->id, $student->firstname . ' ' . $student->lastname, $student->email, $student->city, $student->country];
            foreach ($customfields as $field) {
                $customvalue = $DB->get_field('user_info_data', 'data', ['userid' => $student->id, 'fieldid' => $field->id]);
                $userdata[] = $customvalue ?? '-';
            }
            $table->data[] = $userdata;
        }

        $this->content->text = html_writer::table($table);
        return $this->content;
    }
}
