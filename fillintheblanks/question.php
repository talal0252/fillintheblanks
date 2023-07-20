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
 * fillintheblanks question definition class.
 *
 * @package    qtype
 * @subpackage fillintheblanks
 * @copyright  THEYEAR YOURNAME (YOURCONTACTINFO)

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/** 
*This holds the definition of a particular question of this type. 
*If you load three questions from the question bank, then you will get three instances of 
*that class. This class is not just the question definition, it can also track the current
*state of a question as a student attempts it through a question_attempt instance. 
*/


/**
 * Represents a fillintheblanks question.
 *
 * @copyright  THEYEAR YOURNAME (YOURCONTACTINFO)

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_fillintheblanks_question extends question_graded_by_strategy implements question_response_answer_comparer {

    /** @var array of question_answer. */
    public $answers = array();

    public function __construct() {
        parent::__construct(new question_first_matching_answer_grading_strategy($this));
    }
    /* it may make more sense to think of this as get expected data types */
    public function get_expected_data() {
        // TODO.
        return $result = array('answer' => PARAM_TEXT);
    }
    
    //  public function start_attempt(question_attempt_step $step, $variant) {
    //     //TODO
    //     /* there are 9 occurrances of this method defined in files called question.php a new install of Moodle
    //     so you are probably going to have to define it */
    // }
    
    /**
     * @return summary 
     * A string that summarises how the user responded. 
     * It is written to responsesummary field of
     * the question_attempts table, and used in the
     * the quiz responses report
     * */
    public function summarise_response(array $response) {
        // TODO.

        if(isset($response['answer'])) {
            return $response['answer'];
        }
        return null;
    }

    public function un_summarise_response(string $summary) {
        if (!empty($summary)) {
            return ['answer' => $summary];
        } else {
            return [];
        }
    }

    public function is_complete_response(array $response) {
        // TODO.
        /* You might want to check that the user has done something
            before returning true, e.g. clicked a radio button or entered some 
            text 
            */

        return array_key_exists('answer', $response) && ($response['answer'] || $response['answer'] === '0');
    }

    public function get_validation_error(array $response) {
        // TODO.
        if($this->is_gradable_response($response)) {
            return '';
        }
        return get_string('enteranswer', 'qtype_fillintheblanks');
    }
    
    /** 
     * if you are moving from viewing one question to another this will
     * discard the processing if the answer has not changed. If you don't
     * use this method it will constantantly generate new question steps and
     * the question will be repeatedly set to incomplete. This is a comparison of
     * the equality of two arrays.
     * Comment from base class:
     * 
     * Use by many of the behaviours to determine whether the student's
     * response has changed. This is normally used to determine that a new set
     * of responses can safely be discarded.
     *
     * @param array $prevresponse the responses previously recorded for this question,
     *      as returned by {@link question_attempt_step::get_qt_data()}
     * @param array $newresponse the new responses, in the same format.
     * @return bool whether the two sets of responses are the same - that is
     *      whether the new set of responses can safely be discarded.
     */
     
    public function is_same_response(array $prevresponse, array $newresponse) {
        // TODO.
        return question_utils::arrays_same_at_key_missing_is_blank($prevresponse, $newresponse, 'answer');
    }

    public function get_answers() {
        // TODO.
        return $this->answers;
    }

    public function compare_response_with_answer(array $response, question_answer $answer) {
        if (!array_key_exists('answer', $response) || is_null($response['answer'])) {
            return false;
        }

        $response['answer'] = $this->clean_response($response['answer']);
        if($response['answer'] === $answer->answer) {
            return true;
        }
        return false;
    }

    public function get_matching_answer(array $response, $temp = -1) {
        $answers = $this->get_answers();
        if(count($answers) > 0 && isset($response['answer'])) {
            $match = "";
            $correct = " { ";
            $response['answer'] = explode("+", $response['answer']);
            $attempts = array();
            $feedback = array();
            $i = 0;
            foreach($answers as $answer) {
                $match .= $answer->answer . "+";
                if (strpos($correct, $answer->answer) !== true) {
                    $correct .= $answer->answer . ", ";
                }
                if(strtolower($response['answer'][$i]) != strtolower($answer->answer)) {
                    $attempts[] = false;                    
                    $feedback[] = $i;
                }
                $i += 1;
            }
            $match = substr($match, 0, -1);
            $correct = substr($correct, 0, -2);
            // lowercase the response and the match
            foreach($answers as $answer) {
                if(count($attempts) == 0) {
                    $answer->answer = $match;
                    $answer->fraction = 1;
                } else if(count($attempts) == count($answers)) {
                    $correct .= " }";
                    $answer->answer = $correct;
                    $answer->fraction = 0;
                } else {
                    $correct .= " }";
                    $answer->answer = $correct;
                    $answer->fraction = (count($answers) - count($attempts))/count($answers);
                }
                if($temp == 1) {
                    return $feedback;
                }
                return $answer;
            }
        }        
        return null;
   }

     /**
     * @return question_answer an answer that
     * contains the a response that would get full marks.
     * used in preview mode. If this doesn't return a 
     * correct value the button labeled "Fill in correct response"
     * in the preview form will not work. This value gets written
     * into the rightanswer field of the question_attempts table
     * when a quiz containing this question starts.
     */
    public function get_correct_response() {
        // TODO.        
        $response = parent::get_correct_response();


        if($response) {
            $response['answer'] = $this->clean_response($response['answer']);
        }
        return $response;
    }

    public function clean_response($answer) {
        // Break the string on non-escaped asterisks.
        $bits = preg_split('/(?<!\\\\)\*/', $answer);

        // Unescape *s in the bits.
        $cleanbits = array();
        foreach ($bits as $bit) {
            $cleanbits[] = str_replace('\*', '*', $bit);
        }

        // Put it back together with spaces to look nice.
        return trim(implode(' ', $cleanbits));
    }
    /**
     * Given a response, reset the parts that are wrong. Relevent in
     * interactive with multiple tries
     * @param array $response a response
     * @return array a cleaned up response with the wrong bits reset.
     */
    public function clear_wrong_from_response(array $response) {
        foreach ($response as $key => $value) {
            /*clear the wrong response/s*/
        }
        return $response;
    }

    public function check_file_access($qa, $options, $component, $filearea,
            $args, $forcedownload) {
        // TODO.
        if ($component == 'question' && $filearea == 'answerfeedback') {
            $cur_answer = $qa->get_last_qt_var('answer');
            $answer = $this->get_matching_answer($cur_answer);
            $answerid = reset($args);
            return $options->feedback && $answer && $answer->id == $answer->id;
        } 
        else if($component == 'question' && $filearea == 'hint') {
            return $this->check_hint_file_access($qa, $options, $args);
        }
        else {
            return parent::check_file_access($qa, $options, $component, $filearea,
                    $args, $forcedownload);
        }
    }
}