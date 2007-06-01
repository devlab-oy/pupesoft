<?php
/*
Barcode Render Class for PHP using the GD graphics library 
Copyright (C) 2001  Karim Mribti
								
   Version  0.0.7a  2001-04-01  
								
This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.
																  
This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.
											   
You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
																		 
Copy of GNU Lesser General Public License at: http://www.gnu.org/copyleft/lesser.txt
													 
Source code home page: http://www.mribti.com/barcode/
Contact author at: barcode@mribti.com
*/

define(__DEBUG_HOST__, "localhost");
define(__DEBUG_PORT__, "9999");

define(__TRACE_HOST__, "localhost");
define(__TRACE_PORT__, "9999");

define(__TIMEOUT__, 3);
						   
function __TRACE__ ($str) {
if (__TRACE_ENABLED__) {
	$errno  = 0;
	$errstr = "no error";
	
	$fp = @fsockopen(__TRACE_HOST__, __TRACE_PORT__, &$errno, &$errstr, __TIMEOUT__);
	
	if ($fp)
	{
	   @fputs($fp, $str);
	   @fclose($fp);
	}
 }
}	
	
function __DEBUG__ ($str) {
if (__DEBUG_ENABLED__) {
	$errno  = 0;
	$errstr = "no error";
	
	$fp = @fsockopen(__DEBUG_HOST__, __DEGUB_PORT__, &$errno, &$errstr, __TIMEOUT__);
	
	if ($fp)
	{
	   @fputs($fp, $str);
	   @fclose($fp);
	} 
 }
}
