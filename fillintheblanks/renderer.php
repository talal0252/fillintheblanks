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
 * fillintheblanks question renderer class.
 *
 * @package    qtype
 * @subpackage fillintheblanks
 * @copyright  THEYEAR YOURNAME (YOURCONTACTINFO)

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Generates the output for fillintheblanks questions.
 *
 * @copyright  THEYEAR YOURNAME (YOURCONTACTINFO)

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_fillintheblanks_renderer extends qtype_renderer {
    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {

        // $this->page->requires->js_amd_inline('
        //     require(["jquery"], function($) {
        //         $(document).ready(function() {
        //             alert("' . $qa->get_qt_field_name('answer') . '");
        //         });
        //     });');
        
            
        $question = $qa->get_question();
        $answers = count($question->get_answers());
        $currentanswer = $qa->get_last_qt_var('answer');

        $inputname = $qa->get_qt_field_name('answer');
        $inputattributes = array(
            'type' => 'text',
            'name' => $inputname,
            'value' => $currentanswer,
            'id' => $inputname,
            'size' => 80,
            'class' => 'form-control d-inline',
            'style' => 'display: none !important;'
        );

        if ($options->readonly) {
            $inputattributes['readonly'] = 'readonly';
        }

        $correct = $question->get_matching_answer(array('answer' => $currentanswer), 1);
        $questiontext = $question->format_questiontext($qa);
        $placeholder = false;
        if (preg_match('/__+/', $questiontext, $matches)) {
            $placeholder = $matches[0];
            $inputattributes['size'] = round(strlen($placeholder) * 1.1);
        }
        // $input = html_writer::empty_tag('input', $inputattributes).$feedbackimg;
        $input = html_writer::empty_tag('input', $inputattributes);
        if($placeholder) {
            $inputinplace = html_writer::tag('label', $options->add_question_identifier_to_label(get_string('answer')),
                    array('for' => $inputattributes['id'], 'class' => 'sr-only'));
            $inputinplace .= $input;
            // add text to input in place
            $inputinplace .= "__";
            $questiontext = substr_replace($questiontext, $inputinplace,
                    strpos($questiontext, $placeholder), strlen($placeholder));
        }

        $result = html_writer::tag('div', $questiontext, array('class' => 'qtext', 'id' => 'mydiv'));

        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                    $question->get_validation_error(array('answer' => $currentanswer)),
                    array('class' => 'validationerror'));
        }
        $read = $options->readonly ? 'true' : 'false';

        $this->page->requires->js_amd_inline('
            require([], function() {
                let html = document.getElementById("mydiv").innerHTML;
                let count = ' . $answers . ';
                let inputName = "' . $inputname . '";
                let currentAnswer = "' . $currentanswer . '";
                let correct = "' . json_encode($correct) . '";            
                let canswers = [];
                if(currentAnswer && currentAnswer != "") {
                    let sanswers = currentAnswer.split("+");
                    for(let j = 0; j < count; j++) {
                        canswers.push(sanswers[j]);
                    }
                } else {
                    for(let j = 0; j < count; j++) {
                        canswers.push("");
                    }
                }
                for(let j = 0; j < count; j++) {
                    let inputname = "answer" + j;
                    let feedbackimg = "";
                    let read = "' . $read . '";
                    if(read == "true") {
                        read = "readonly";
                        if(!correct.includes(j)){
                            feedbackimg = "<img src=\"https://img.icons8.com/?size=512&id=YDFV6MVN9cNl&format=png\" width=\"35\" height=\"35\" />";
                        } else {
                            feedbackimg = "<img src=\"https://img.icons8.com/?size=512&id=OZuepOQd0omj&format=png\" width=\"35\" height=\"35\" />";
                        }
                    } else {
                        read = "";
                    }                    
                    html = html.replace(/__+/, "<input type=\"text\" name=\"" + inputname + "\" id=\"" + inputname + "\" size=\"10\" class=\"form-control d-inline\" value=\"" + canswers[j] + "\" " + read + "/>" +feedbackimg);

                }
                document.getElementById("mydiv").innerHTML = html;

                if(document.getElementsByName("next").length > 0) {
                    document.getElementsByName("next")[0].addEventListener("click", function() {
                        let answers = "";
                        for(let j = 0; j < count; j++) {
                            let inputname = "answer" + j;
                            answers += document.getElementById(inputname).value + "+";
                        }
                        answers = answers.substring(0, answers.length - 1);
                        document.getElementById(inputName).value = answers;
                    });
                }
            });
        ');

        /* Some code to restore the state of the question as you move back and forth
        from one question to another in a quiz and some code to disable the input fields
        once a quesiton is submitted/marked */

        /* if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                    $question->get_validation_error(array('answer' => $currentanswer)),
                    array('class' => 'validationerror'));
        }*/
        return $result;
    }

    public function specific_feedback(question_attempt $qa) {
        // TODO.
        $question = $qa->get_question();
        $answer = $question->get_matching_answer(array('answer' => $qa->get_last_qt_var('answer')));
        if(!$answer || $answer->feedback){
            return '';
        }
        $res = $question->clean_response($answer->answer);
        if(substr_count($res, '{') > 1) {
            // remove first {
            $res = substr($res, 2);
        }
        // find }, and remove string after it
        $pos = strpos($res, '},');
        $res = substr($res, 0, $pos+1);
        return get_string('correctansweris', 'qtype_fillintheblanks', s($res));
    }

    public function correct_response(question_attempt $qa) {
        // TODO.
        $question = $qa->get_question();
        $answer = $question->get_matching_answer($question->get_correct_response());
        if (!$answer) {
            return '';
        }
    }
}