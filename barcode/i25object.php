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
  
  /* 
    render for Interleaved 2 of 5     
  	Interleaved 2 of 5 is a numeric only bar code with a optional check number.
  */
    
  class I25Object extends BarcodeObject {
   var $mCharSet;
   function I25Object($Width, $Height, $Style, $Value)
   {
     $this->BarcodeObject($Width, $Height, $Style);
	 $this->mValue   = $Value;
	 $this->mCharSet = array
	 (						
	   /* 0 */ "00110",
	   /* 1 */ "10001",
	   /* 2 */ "01001",
	   /* 3 */ "11000",
	   /* 4	*/ "00101",
	   /* 5 */ "10100",
	   /* 6	*/ "01100",
	   /* 7	*/ "00011",
	   /* 8	*/ "10010",
	   /* 9	*/ "01010" 
	 );
   }
   
   function GetSize($xres)
   {
     $len = strlen($this->mValue);
	 
	 if ($len == 0)  {
	   $this->mError = "Null value";
   	   __DEBUG__("GetRealSize: null barcode value");
	   return false;
	   }
	 
	 for ($i=0;$i<$len;$i++) {
	  if ((ord($this->mValue[$i])<48) || (ord($this->mValue[$i])>57)) {
	        $this->mError = "I25 is numeric only";
			return false;
		 }
	 }
	 
	 if (($len%2) != 0) {
	    $this->mError = "The length of barcode value must be even";
   	    __DEBUG__("GetSize: failed I25 requiremente");
		return false;
		}		 
	 $StartSize = BCD_I25_NARROW_BAR * 4  * $xres;
 	 $StopSize  = BCD_I25_WIDE_BAR * $xres + 2 * BCD_I25_NARROW_BAR * $xres;
	 $cPos = 0;
	 $sPos = 0;
	 do {      
		$c1    = $this->mValue[$cPos];
		$c2    = $this->mValue[$cPos+1];
		$cset1 = $this->mCharSet[$c1];
		$cset2 = $this->mCharSet[$c2];
		
		for ($i=0;$i<5;$i++) {
		  $type1 = ($cset1[$i]==0) ? (BCD_I25_NARROW_BAR  * $xres) : (BCD_I25_WIDE_BAR * $xres);
		  $type2 = ($cset2[$i]==0) ? (BCD_I25_NARROW_BAR  * $xres) : (BCD_I25_WIDE_BAR * $xres);
		  $sPos += ($type1 + $type2);
		}
		$cPos+=2;
	  } while ($cPos<$len);
	  
	  return $sPos + $StarSize + $StopSize;
   }
   
   function DrawStart($DrawPos, $yPos, $ySize, $xres)
   {  /* Start code is "0000" */
	  $this->DrawSingleBar($DrawPos, $yPos, BCD_I25_NARROW_BAR  * $xres , $ySize);
	  $DrawPos += BCD_I25_NARROW_BAR  * $xres;
	  $DrawPos += BCD_I25_NARROW_BAR  * $xres;
	  $this->DrawSingleBar($DrawPos, $yPos, BCD_I25_NARROW_BAR  * $xres , $ySize);
	  $DrawPos += BCD_I25_NARROW_BAR  * $xres;
  	  $DrawPos += BCD_I25_NARROW_BAR  * $xres;
	  return $DrawPos;
   }
   
   function DrawStop($DrawPos, $yPos, $ySize, $xres)
   {  /* Stop code is "100" */
	  $this->DrawSingleBar($DrawPos, $yPos, BCD_I25_WIDE_BAR * $xres , $ySize);
	  $DrawPos += BCD_I25_WIDE_BAR  * $xres;
	  $DrawPos += BCD_I25_NARROW_BAR  * $xres;
	  $this->DrawSingleBar($DrawPos, $yPos, BCD_I25_NARROW_BAR  * $xres , $ySize);
	  $DrawPos += BCD_I25_NARROW_BAR  * $xres; 
	  return $DrawPos;
   }
   
   function DrawObject ($xres)
   {
     $len = strlen($this->mValue);
	 							  
	 if (($size = $this->GetSize($xres))==0) {
     	__DEBUG__("GetSize: failed");
	    return false;
	 }    
	 	  
	 $cPos  = 0;
	 			
	 if ($this->mStyle & BCS_DRAW_TEXT) $ysize = $this->mHeight - BCD_DEFAULT_MAR_Y1 - BCD_DEFAULT_MAR_Y2 - $this->GetFontHeight($this->mFont);
	 else $ysize = $this->mHeight - BCD_DEFAULT_MAR_Y1 - BCD_DEFAULT_MAR_Y2;
	 																		
	 if ($this->mStyle & BCS_ALIGN_CENTER) $sPos = (integer)(($this->mWidth - $size ) / 2);
	 else if ($this->mStyle & BCS_ALIGN_RIGHT) $sPos = $this->mWidth - $size;
	 	  else $sPos = 0;
						 
	  if ($this->mStyle & BCS_DRAW_TEXT) {
	   	 if ($this->mStyle & BCS_STRETCH_TEXT) {
		   /* Stretch */
   	       for ($i=0;$i<$len;$i++) {
	   	  	  $this->DrawChar($this->mFont, $sPos+BCD_I25_NARROW_BAR*4*$xres+($size/$len)*$i, 
			  		 $ysize + BCD_DEFAULT_MAR_Y1 + BCD_DEFAULT_TEXT_OFFSET , $this->mValue[$i]);
	         }				 
	     }else {/* Center */
		  	 $text_width = $this->GetFontWidth($this->mFont) * strlen($this->mValue);
			 $this->DrawText($this->mFont, $sPos+(($size-$text_width)/2)+(BCD_I25_NARROW_BAR*4*$xres),
			 				  $ysize + BCD_DEFAULT_MAR_Y1 + BCD_DEFAULT_TEXT_OFFSET, $this->mValue);
	      } 	 			 
	 }						 
	  		                 
	 $sPos = $this->DrawStart($sPos, BCD_DEFAULT_MAR_Y1, $ysize, $xres); 
	 do {      						  
		$c1    = $this->mValue[$cPos];
		$c2    = $this->mValue[$cPos+1];
		$cset1 = $this->mCharSet[$c1];
		$cset2 = $this->mCharSet[$c2];
							  
		for ($i=0;$i<5;$i++) {
		  $type1 = ($cset1[$i]==0) ? (BCD_I25_NARROW_BAR * $xres) : (BCD_I25_WIDE_BAR * $xres);
		  $type2 = ($cset2[$i]==0) ? (BCD_I25_NARROW_BAR * $xres) : (BCD_I25_WIDE_BAR * $xres);
		  $this->DrawSingleBar($sPos, BCD_DEFAULT_MAR_Y1, $type1 , $ysize);
		  $sPos += ($type1 + $type2);
		}		 
		$cPos+=2;
	  } while ($cPos<$len);
	  $sPos =  $this->DrawStop($sPos, BCD_DEFAULT_MAR_Y1, $ysize, $xres);
	  return true;
	 }
  }
?>