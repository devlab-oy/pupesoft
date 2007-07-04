<?php
/**
 * Class ProgressBar
 * Easy to use progress bar in html and css.
 *
 * @author David Bongard (mail@bongard.net | www.bongard.net | www.pinkorange.
 * at)
 * @version 1.0 - 20070418
 * @licence http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * Copyright (c) 2007 David Bongard
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * Example of usage:
 * <code>
 * require_once 'ProgressBar.class.php';
 * $bar = new ProgressBar();
 *
 * $elements = 100000; //total number of elements to process
 * $bar->initialize($elements); //print the empty bar
 *
 * for($i=0;$i<$elements;$i++){
 *  	//do something here...
 * 		$bar->increase(); //calls the bar with every processed element
 * }
 * </code>
 *
 * Another example:
 * <code>
 * require_once 'ProgressBar.class.php'; $bar = new ProgressBar();
 *
 * $bar->initialize(3); //initialize the bar with the total number of elements to process
 *
 * //do something time consuming here...
 * $bar->increase(); //call for first element
 *
 * //do something time consuming here...
 * $bar->increase(); //call for second element
 *
 * //do something time consuming here...
 * $bar->increase(); //call for third element. end of bar...
 * </code>
 */
class ProgressBar {

	/**
	 * Constructor
	 *
	 * @param str $message Message shown above the bar eg. "Please wait...". Default: ''
	 * @param bool $hide Hide the bar after completion (with JavaScript).
	 * Default: false
	 * @param int $sleepOnFinish Seconds to sleep after bar completion. Default: 0
	 * @param int $barLength Length in pixels. Default: 200
	 * @param int $precision Desired number of steps to show. Default: 20. Precision will become $numElements when greater than $numElements. $barLength will increase if $precision is greater than $barLength.
	 * @param str $backgroundColor Color of the bar background
	 * @param str $foregroundColor Color of the actual progress-bar
	 * @param str $domID Html-Attribute "id" for the bar
	 * @param str $stepElement Element the bar is build from
	 */
    function ProgressBar($message='', $hide=false, $sleepOnFinish=0, $barLength=200, $precision=20,
    					 $backgroundColor='#cccccc', $foregroundColor='blue', $domID='progressbar',
    					 $stepElement='<div style="width:%spx;height:20px;float:left;"></div>'
    					 ){

    	//increase time limit
		if(!ini_get('safe_mode')){
			set_time_limit(0);
		}

    	$this->hide = (bool) $hide;
    	$this->sleepOnFinish = (int) $sleepOnFinish;
    	$this->domID = strip_tags($domID);
    	$this->message = $message;
    	$this->stepElement = $stepElement;
    	$this->barLength = (int) $barLength;
    	$this->precision = (int) $precision;
    	$this->backgroundColor = strip_tags($backgroundColor);
		$this->foregroundColor = strip_tags($foregroundColor);
    	if($this->barLength < $this->precision){
    		$this->barLength = $this->precision;
    	}

    	$this->StepCount = 0;
    	$this->CallCount = 0;
    }

	/**
	 * Print the empty progress bar
	 * @param int $numElements Number of Elements to be processed and number of times $bar->initialize() will be called while processing
	 */
	function initialize($numElements)
	{
		$numElements = (int) $numElements ;
    	if($numElements == 0){
    		$numElements = 1;
    	}
		//calculate the number of calls for one step
    	$this->CallsPerStep = ceil(($numElements/$this->precision)); // eg. 1000/200 = 100

		//calculate the total number of steps
		if($numElements >= $this->CallsPerStep){
			$this->numSteps = round($numElements/$this->CallsPerStep);
		}else{
			$this->numSteps = round($numElements);
		}

    	//calculate the length of one step
    	$stepLength = floor($this->barLength/$this->numSteps);  // eg. 100/10 = 10

    	//the rest is the first step
    	$this->rest = $this->barLength-($stepLength*$this->numSteps);
    	if($this->rest > 0){
			$this->firstStep = sprintf($this->stepElement,$this->rest);
    	}

		//build the basic step-element
		$this->oneStep = sprintf($this->stepElement,$stepLength);

		//build bar background
		$backgroundLength = $this->rest+($stepLength*$this->numSteps);
		$this->backgroundBar = sprintf($this->stepElement,$backgroundLength);

		//stop buffering
    	ob_end_flush();
    	//start buffering
    	ob_start();

		echo '<div id="'.$this->domID.'">'.
			 $this->message.'<br/>'.
			 '<div style="position:absolute;color:'.$this->backgroundColor.';background-color:'.$this->backgroundColor.'">'.$this->backgroundBar.'</div>' .
			 '<div style="position:absolute;color:'.$this->foregroundColor.';background-color:'.$this->foregroundColor.'">';

		ob_flush();
		flush();
	}

	/**
	 * Count steps and increase bar length
	 *
	 */
	function increase()
	{
		$this->CallCount++;

		if(!$this->started){
			//rest output
			echo $this->firstStep;
			ob_flush();
			flush();
		}

		if($this->StepCount < $this->numSteps
		&&(!$this->started || $this->CallCount == $this->CallsPerStep)){

			//add a step
			echo $this->oneStep;
			ob_flush();
			flush();

			$this->StepCount++;
			$this->CallCount=0;
		}
		$this->started = true;

		if(!$this->finished && $this->StepCount == $this->numSteps){

			// close the bar
			echo '</div></div><br/>';
			ob_flush();
			flush();

			//sleep x seconds before ending the script
			if($this->sleepOnFinish > 0){
				sleep($this->sleepOnFinish);
			}

			//hide the bar
			if($this->hide){
				echo '<script type="text/javascript">document.getElementById("'.$this->domID.'").style.display = "none";</script>';
				ob_flush();
				flush();
			}
			$this->finished = true;
		}
	}
}
?>