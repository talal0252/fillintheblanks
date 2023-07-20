<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Question type class for the fillintheblanks question type.
 *
 * @package    qtype
 * @subpackage fillintheblanks
 * @copyright  THEYEAR YOURNAME (YOURCONTACTINFO)

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 /*https://docs.moodle.org/dev/Question_types#Question_type_and_question_definition_classes*/


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/type/fillintheblanks/question.php');


/**
 * The fillintheblanks question type.
 *
 * @copyright  THEYEAR YOURNAME (YOURCONTACTINFO)

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_fillintheblanks extends question_type {

      /* ties additional table fields to the database */
    // public function extra_question_fields() {
    //     return array('question_fillintheblanks');
    // }
    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_answers($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_hints($questionid, $oldcontextid, $newcontextid);
    }

    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_answers($questionid, $contextid);
        $this->delete_files_in_hints($questionid, $contextid);
    }

    public function save_defaults_for_new_questions(stdClass $fromform): void {
        parent::save_defaults_for_new_questions($fromform);
    }

    public function save_question($question, $form) {
        return parent::save_question($question, $form);
    }

    public function save_question_options($question) {
        global $DB;
        $result = new stdClass();
        $maxfraction = -1;
        foreach($question->fraction as $key => $fraction) {
            if($fraction > $maxfraction) {
                $maxfraction = $fraction;
            }
        }
        if($maxfraction == -1) {
            $result->error = get_string('fractionsnomax', 'question', $maxfraction*100);
            return $result;
        }
        parent::save_question_options($question);
        $this->save_question_answers($question);
        $this->save_hints($question);
    }

    protected function fill_answer_fields($answer, $questiondata, $key, $context) {
        $answer = parent::fill_answer_fields($answer, $questiondata, $key, $context);
        $answer->answer = trim($answer->answer);
        return $answer;
    }


    /**
     * executed at runtime (e.g. in a quiz or preview 
     **/
    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        $this->initialise_question_answers($question, $questiondata);
    }    
    



    public function get_random_guess_score($questiondata) {
        // TODO.
        foreach($questiondata->options->answers as $aid => $answer) {
            if("*" == trim($answer->answer)) {
                return $answer->fraction;
            }
        }
        return 0;
    }

    public function get_possible_responses($questiondata) {
        // TODO.
        $responses = array();
        $starfound = false;
        foreach($questiondata->options->answers as $aid => $answer) {
            $responses[$aid] = new question_possible_response($answer->answer, $answer->fraction);
            if(trim($answer->answer) == "*") {
                $starfound = true;
            }
        }
        if(!$starfound) {
            $responses[0] = new question_possible_response(get_string('didnotmatchanyanswer', 'question'), 0);
        }
        $responses[null] = question_possible_response::no_response();
        return array($questiondata->id => $responses);
    }
}