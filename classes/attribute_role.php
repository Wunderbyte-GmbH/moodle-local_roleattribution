<?php

require_once($CFG->libdir.'/accesslib.php');

function auth_saml2_sync_roles($saml, $user, $attributes) {

    $saml_courses = $attributes['MEN-Affilation'];

    # IAM course creator role
    $roletohandle = 12;
    # IDs for access to global secondaire and fondamental folders
    $ES_EST_ID=get_contextid_by_catname("ES et EST");
    // $ES_EST_ID=3;
    $EF_ID=get_contextid_by_catname("Enseignement fondamental");
    // $EF_ID=3630;



#if ($user->username == "karst801"){
#    $saml_courses = array("LTAM-TEACHER","LAM-TEACHER","LAM-OTHER");
# }

    $saml->log(" username: ".$user->username." - id: ".$user->id);
    $saml->log("AFF: ".implode("  ",$saml_courses));
    if (!$user->id) {
        $saml->log("Invalid user, doing no assignments here....");
        return;
    }

    $affilations = $saml_courses;

    $acl_should_have = array();
    foreach($affilations as $key => $affilation) {
        if(preg_match("/(.*)-TEACHER/i",$affilation,$school)) {
            $contextid = get_contextid_by_catname($school[1]);
            if ($school[1] == 'EP-ALL'){
                if (! in_array($EF_ID, $acl_should_have)) {
                    $acl_should_have[] = $EF_ID;
                }
                $saml->log("Schoulmeeschter");
            } else {
                if (! in_array($ES_EST_ID, $acl_should_have)) {
                    $acl_should_have[] = $ES_EST_ID;
                }
                $saml->log("Prof");
            }
            $saml->log("School ".$school[1]." context id: ".$contextid);
            if ($contextid) {
                $acl_should_have[] = $contextid;
            }
        } else {
            $saml->log("non-teacher aff ".$affilation);
        }
    }


    $acl_has = array();
    $uacl = get_user_access_sitewide($user->id);
    foreach($uacl['ra'] as $key => $value) {
        if(reset($value)==12) {
            $contextid = get_contextid_by_path($key);
            $saml->log("context id: ".$contextid." path: ".$key);
            if ($contextid) $acl_has[] = $contextid;
        }
    }

    $toassign = array_diff($acl_should_have, $acl_has);
    foreach($toassign as $key => $contextid) {
        $saml->log("assign role for context id: ".$contextid);
        role_assign($roletohandle, $user->id, $contextid);
    }

    $tounassign = array_diff($acl_has, $acl_should_have);
    foreach($tounassign as $key => $contextid) {
        $saml->log("unassign role for context id: ".$contextid);
        role_unassign($roletohandle, $user->id, $contextid);
    }

}

function get_contextid_by_catname($school) {
    global $DB;
    $params = array($school);
    $sql = "SELECT con.id
              FROM {context} con
         LEFT JOIN {course_categories} cat ON (con.instanceid = cat.id)
             WHERE con.contextlevel = 40
               AND cat.name like ? ";

    return reset($DB->get_records_sql($sql, $params))->id;
}

function get_contextid_by_path($path) {
    global $DB;
    $params = array($path);
    $sql = "SELECT id
              FROM {context}
             WHERE contextlevel = 40
               AND path like ?";

    return reset($DB->get_records_sql($sql, $params))->id;
}

