<?php

class User {
    public $id;
    public $username;
    public $password;
    public $full_name;
    public $student_id;
    public $email;
    public $reset_token;
    public $reset_expire;
}


class Semester {
    public $id;
    public $semester_name;
    public $user_id; 
}


class Course {
    public $id;
    public $course_name;
    public $credits;
    public $score;
    public $semester_id; 
}

class Criterion {
    public $id;
    public $criterion_name;
    public $max_score;
    public $type;
}


class Evidence {
    public $id;
    public $content;     
    public $score_value;
    public $event_date;
    public $criterion_id; 
    public $user_id;    
    public $semester_id; 
}

?>