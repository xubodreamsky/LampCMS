<?php
/**
 *
 * License, TERMS and CONDITIONS
 *
 * This software is lisensed under the GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * Please read the license here : http://www.gnu.org/licenses/lgpl-3.0.txt
 *
 *  Redistribution and use in source and binary forms, with or without
 *  modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * ATTRIBUTION REQUIRED
 * 4. All web pages generated by the use of this software, or at least
 * 	  the page that lists the recent questions (usually home page) must include
 *    a link to the http://www.lampcms.com and text of the link must indicate that
 *    the website's Questions/Answers functionality is powered by lampcms.com
 *    An example of acceptable link would be "Powered by <a href="http://www.lampcms.com">LampCMS</a>"
 *    The location of the link is not important, it can be in the footer of the page
 *    but it must not be hidden by style attibutes
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR "AS IS" AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE FREEBSD PROJECT OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This product includes GeoLite data created by MaxMind,
 *  available from http://www.maxmind.com/
 *
 *
 * @author     Dmitri Snytkine <cms@lampcms.com>
 * @copyright  2005-2011 (or current year) ExamNotes.net inc.
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * @link       http://www.lampcms.com   Lampcms.com project
 * @version    Release: @package_version@
 *
 *
 */

namespace Lampcms\Controllers;

use \Lampcms\Request;
use \Lampcms\Responder;
use \Lampcms\AnswerParser;
use \Lampcms\SubmittedAnswerWWW;

/**
 * Controller for processing the answer form
 * It extends wwwViewquestion because in case of form validation
 * we will be able to just return the page with question plus
 * the same form with errors added to it.
 *
 * @author Dmitri Snytkine
 *
 */
class Answer extends Viewquestion
{

	protected $permission = 'answer';

	protected $membersOnly = true;

	//protected $aAllowedVars = array('qbody');


	protected function main(){
		$this->getQuestion()->makeForm();
		if($this->oForm->validate()){
			/**
			 * The process() will either send out
			 * json for ajax request OR will
			 * redirect to page, in either case
			 * we will not get to makeHtml() method
			 *
			 */
			$this->process();
		} else {
			/**
			 * Need to show entire question page with
			 * form appended (it will contain errors this time)
			 */
			$this->showFormWithErrors();
		}
	}


	protected function showFormWithErrors(){
		$this->setTitle()
		->setAnswersHeader()
		->setAnswers()
		->setSimilar()
		->setAnswerForm()
		->setFooter();
	}


	/**
	 * Process submitted Answer
	 * 
	 * @return void
	 */
	protected function process(){
		$formVals = $this->oForm->getSubmittedValues();
		d('formVals: '.print_r($formVals, 1));
		$oAdapter = new AnswerParser($this->oRegistry);
		try{
			$oAnswer = $oAdapter->parse(new SubmittedAnswerWWW($this->oRegistry, $formVals));
			d('cp created new answer: '.print_r($oAnswer->getArrayCopy(), 1));
			d('ans id: '.$oAnswer->getResourceId());

			/**
			 * In case of ajax we need to send out a
			 * parsed html block with one answer
			 * under the 'answer' key
			 *
			 * In case of non-ajax redirect back to question page,
			 * hopefull the new answer will show up there too
			 */
			if(Request::isAjax()){
				$aAnswer = $oAnswer->getArrayCopy();
				/**
				 * Add edit and delete tools because
				 * Viewer already owns this comment and is
				 * allowed to edit or delete it right away.
				 * Javascript that usually dynamically adds these tools
				 * is not going to be fired, so these tools
				 * must alreayd be included in the returned html
				 *
				 */
				$aAnswer['edit_delete'] = ' <span class="ico del ajax" title="Delete">delete</span>  <span class="ico edit ajax" title="Edit">edit</span>';
				$a = array('answer' => \tplAnswer::parse($aAnswer));
				d('before sending out $a: '.print_r($a, 1));

				Responder::sendJSON($a);

			} else {
				Responder::redirectToPage($this->oQuestion->getUrl());
			}

		} catch (\Lampcms\AnswerParserException $e){
			/**
			 * The setFormError in Form sends our json in
			 * case of Ajax request, so we don't have to
			 * worry about it here
			 */
			$this->oForm->setFormError($e->getMessage());
			$this->showFormWithErrors();
		}
	}
}
