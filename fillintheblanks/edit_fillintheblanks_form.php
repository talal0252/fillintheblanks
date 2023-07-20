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
 * Defines the editing form for the fillintheblanks question type.
 *
 * @package    qtype
 * @subpackage fillintheblanks
 * @copyright  THEYEAR YOURNAME (YOURCONTACTINFO)

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/**
 * fillintheblanks question editing form definition.
 *
 * @copyright  THEYEAR YOURNAME (YOURCONTACTINFO)

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_fillintheblanks_edit_form extends question_edit_form {
    // public $reload = false;

    // public function __construct($submiturl, $question, $category, $contexts, $formeditable = true) {
    //     parent::__construct($submiturl, $question, $category, $contexts, $formeditable);
    //     $this->reload = optional_param('reload', false, PARAM_BOOL);
    // }

    protected function definition_inner($mform) {
        //Add fields specific to this question type
        //remove any that come with the parent class you don't want
        $mform->addElement('static', 'answersinstruct', get_string('correctanswers', 'qtype_fillintheblanks'),
                get_string('filloutoneanswer', 'qtype_fillintheblanks'));
        $mform->closeHeaderBefore('answersinstruct');

        $this->add_per_answer_fields($mform, get_string('answerno', 'qtype_fillintheblanks', '{no}'),
                question_bank::fraction_options(), 1, 1);
        
        // To add combined feedback (correct, partial and incorrect).
        // $this->add_combined_feedback_fields(true);
        // Adds hinting features.
        $this->add_interactive_settings();
    }

    protected function get_more_choices_string() {
        return get_string('addmoreanswerblanks', 'qtype_fillintheblanks');
    }

    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_hints($question);
        $question = $this->data_preprocessing_hints($question);
        return $question;
    }

    public function validation($fromform, $files) {
        $errors = parent::validation($fromform, $files);
        $answers = $fromform['answer'];
        $question_text = $fromform['questiontext']['text'];

        // Get all regex matches.
        $count = preg_match_all('/__+/', $question_text, $matches);
        if($count <= 0) {
            $errors['questiontext'] = "Make sure to enter atleast one blank in the question text. (__)";
            return $errors;
        }
        $answer_count = 0;
        foreach($answers as $key => $answer) {
            $trimmed = trim($answer);
            if($trimmed !== '') {
                $answer_count++;
                $fromform['fraction'][$key] = 1;
            } else if(!html_is_blank($fromform['feedback'][$key]['text'])) {
                $errors["answeroptions[{$key}]"] = get_string('answermustbegiven', 'qtype_fillintheblanks');
                $answer_count++;
            }
        }
        if($answer_count != $count) {
            $errors["answeroptions[0]"] = "Must have atleast". $count ." answers.";
        }
        return $errors;
    }

    public function qtype() : string {
        return 'fillintheblanks';
    }
}