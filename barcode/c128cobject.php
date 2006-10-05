<?php
/*
Barcode Render Class for PHP using the GD graphics library 
Copyright (C) 2001  Karim Mribti
  -- Written on 2001-08-03 by Sam Michaels
       to add Code 128-C support.
       swampgas@swampgas.org
								
   Version  0.0.7a  2001-08-03  
								
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
    Render for Code 128-C
        Code 128-C is numeric only and provides the most efficiency.
  */
    
    
  class C128CObject extends BarcodeObject {
   var $mCharSet, $mChars;
   function C128CObject($Width, $Height, $Style, $Value)
   {
     $this->BarcodeObject($Width, $Height, $Style);
	 $this->mValue   = $Value;
         $this->mChars   = array
          (
            "00", "01", "02", "03", "04", "05", "06", "07", "08", "09",
            "10", "11", "12", "13", "14", "15", "16", "17", "18", "19",
            "20", "21", "22", "23", "24", "25", "26", "27", "28", "29",
            "30", "31", "32", "33", "34", "35", "36", "37", "38", "39",
            "40", "41", "42", "43", "44", "45", "46", "47", "48", "49",
            "50", "51", "52", "53", "54", "55", "56", "57", "58", "59",
            "60", "61", "62", "63", "64", "65", "66", "67", "68", "69",
            "70", "71", "72", "73", "74", "75", "76", "77", "78", "79",
            "80", "81", "82", "83", "84", "85", "86", "87", "88", "89",
            "90", "91", "92", "93", "94", "95", "96", "97", "98", "99",
          );
	 $this->mCharSet = array
	  (
			"212222",   /*   00 */
		  	"222122",   /*   01 */
		  	"222221",   /*   02 */
		  	"121223",   /*   03 */
		  	"121322",   /*   04 */
		  	"131222",   /*   05 */
		  	"122213",   /*   06 */
		  	"122312",   /*   07 */
		  	"132212",   /*   08 */
		  	"221213",   /*   09 */
		  	"221312",   /*   10 */
		  	"231212",   /*   11 */
		  	"112232",   /*   12 */
		  	"122132",   /*   13 */
		  	"122231",   /*   14 */
			"113222",   /*   15 */
		  	"123122",   /*   16 */
		  	"123221",   /*   17 */
		  	"223211",   /*   18 */
			"221132",   /*   19 */
		  	"221231",   /*   20 */
		  	"213212",   /*   21 */
		  	"223112",   /*   22 */
		  	"312131",   /*   23 */
		  	"311222",   /*   24 */
		  	"321122",   /*   25 */
		  	"321221",   /*   26 */
		  	"312212",   /*   27 */
		  	"322112",   /*   28 */
			"322211",   /*   29 */
		  	"212123",   /*   30 */
		  	"212321",   /*   31 */
			"232121",   /*   32 */
		  	"111323",   /*   33 */
		  	"131123",   /*   34 */
			"131321",   /*   35 */
		  	"112313",   /*   36 */
		  	"132113",   /*   37 */
		  	"132311",   /*   38 */
		  	"211313",   /*   39 */
		  	"231113",   /*   40 */
		  	"231311",   /*   41 */
		  	"112133",   /*   42 */
		  	"112331",   /*   43 */
		  	"132131",   /*   44 */
		  	"113123",   /*   45 */
		  	"113321",   /*   46 */
		  	"133121",   /*   47 */
		  	"313121",   /*   48 */
  			"211331",   /*   49 */
		  	"231131",   /*   50 */
		  	"213113",   /*   51 */
		  	"213311",   /*   52 */
		  	"213131",   /*   53 */
		  	"311123",   /*   54 */
		  	"311321",   /*   55 */
		  	"331121",   /*   56 */
		  	"312113",   /*   57 */
		  	"312311",   /*   58 */
		  	"332111",   /*   59 */
		  	"314111",   /*   60 */
		  	"221411",   /*   61 */
		  	"431111",   /*   62 */
		  	"111224",   /*   63 */
		  	"111422",   /*   64 */
		  	"121124",   /*   65 */
		  	"121421",   /*   66 */
		  	"141122",   /*   67 */
		  	"141221",   /*   68 */
		  	"112214",   /*   69 */
		  	"112412",   /*   70 */
		  	"122114",   /*   71 */
		  	"122411",   /*   72 */
		  	"142112",   /*   73 */
		  	"142211",   /*   74 */
		  	"241211",   /*   75 */
		  	"221114",   /*   76 */
		  	"413111",   /*   77 */
		  	"241112",   /*   78 */
		  	"134111",   /*   79 */
		  	"111242",   /*   80 */
		  	"121142",   /*   81 */
		  	"121241",   /*   82 */
		  	"114212",   /*   83 */
		  	"124112",   /*   84 */
		  	"124211",   /*   85 */
		  	"411212",   /*   86 */
		  	"421112",   /*   87 */
		  	"421211",   /*   88 */
		  	"212141",   /*   89 */
		  	"214121",   /*   90 */
		  	"412121",   /*   91 */
		  	"111143",   /*   92 */
		  	"111341",   /*   93 */
		  	"131141",   /*   94 */
		  	"114113",   /*   95 */
			"114311",   /*   96 */
		  	"411113",   /*   97 */
		  	"411311",   /*   98 */
		  	"113141",   /*   99 */
	);
   }
   
   function GetCharIndex ($char) {
    for ($i=0;$i<100;$i++) {
	  if ($this->mChars[$i] == $char)
	     return $i;
	 }
	 return -1;
   }
   
   function GetBarSize ($xres, $char) { 
     switch ($char)
	 {
	  case '1':
	  			$cVal = BCD_C128_BAR_1;
				break;
	  case '2':
	  			$cVal = BCD_C128_BAR_2;
				break;
	  case '3':
	  			$cVal = BCD_C128_BAR_3;
				break;
	  case '4':
	  			$cVal = BCD_C128_BAR_4;
				break;
	  default:
	  			$cVal = 0;
	 }
    return  $cVal * $xres;
   }

    
   function GetSize($xres) {
     $len = strlen($this->mValue);
	 
	 if ($len == 0)  {
	   $this->mError = "Null value";
   	   __DEBUG__("GetRealSize: null barcode value");
	   return false;
	   }
	 $ret = 0;

         for ($i=0;$i<$len;$i++) {
	  if ((ord($this->mValue[$i])<48) || (ord($this->mValue[$i])>57)) {
                $this->mError = "Code-128C is numeric only";
			return false;
		 }
	 }

	 if (($len%2) != 0) {
            $this->mError = "The length of barcode value must be even.  You must pad the number with zeros.";
            __DEBUG__("GetSize: failed C128-C requiremente");
		return false;
		}		 

        for ($i=0;$i<$len;$i+=2) {
          $id = $this->GetCharIndex($this->mValue[$i].$this->mValue[$i+1]);
          $cset = $this->mCharSet[$id];
          $ret += $this->GetBarSize($xres, $cset[0]);
          $ret += $this->GetBarSize($xres, $cset[1]);
          $ret += $this->GetBarSize($xres, $cset[2]);
          $ret += $this->GetBarSize($xres, $cset[3]);
          $ret += $this->GetBarSize($xres, $cset[4]);
          $ret += $this->GetBarSize($xres, $cset[5]);
	 }   
	 /* length of Check character */
	 $cset = $this->GetCheckCharValue();
         for ($i=0;$i<6;$i++) {
	   $CheckSize += $this->GetBarSize($cset[$i], $xres);
	 }
	 	  
	 $StartSize = 2*BCD_C128_BAR_2*$xres + 3*BCD_C128_BAR_1*$xres + BCD_C128_BAR_4*$xres;
 	 $StopSize  = 2*BCD_C128_BAR_2*$xres + 3*BCD_C128_BAR_1*$xres + 2*BCD_C128_BAR_3*$xres;
	  return $StartSize + $ret + $CheckSize + $StopSize;
   }
   
   function GetCheckCharValue()
   {
     $len = strlen($this->mValue);
         $sum = 105; // 'C' type;
         $m = 0;
         for ($i=0;$i<$len;$i+=2) {
          $m++;
          $sum +=  $this->GetCharIndex($this->mValue[$i].$this->mValue[$i+1]) * $m;
	 }
	 $check  = $sum % 103;
 	 return $this->mCharSet[$check];
    }
   
   function DrawStart($DrawPos, $yPos, $ySize, $xres)
   {  /* Start code is '211232' */
	  $this->DrawSingleBar($DrawPos, BCD_DEFAULT_MAR_Y1, $this->GetBarSize('2', $xres) , $ySize);
	  $DrawPos += $this->GetBarSize('2', $xres);
	  $DrawPos += $this->GetBarSize('1', $xres);
	  $this->DrawSingleBar($DrawPos, BCD_DEFAULT_MAR_Y1, $this->GetBarSize('1', $xres) , $ySize);
	  $DrawPos += $this->GetBarSize('1', $xres);
	  $DrawPos += $this->GetBarSize('2', $xres);
          $this->DrawSingleBar($DrawPos, BCD_DEFAULT_MAR_Y1, $this->GetBarSize('3', $xres) , $ySize);
          $DrawPos += $this->GetBarSize('3', $xres);
          $DrawPos += $this->GetBarSize('2', $xres);
	  return $DrawPos;
   }
   
   function DrawStop($DrawPos, $yPos, $ySize, $xres)
   {  /* Stop code is '2331112' */
	  $this->DrawSingleBar($DrawPos, BCD_DEFAULT_MAR_Y1, $this->GetBarSize('2', $xres) , $ySize);
	  $DrawPos += $this->GetBarSize('2', $xres);
	  $DrawPos += $this->GetBarSize('3', $xres);
	  $this->DrawSingleBar($DrawPos, BCD_DEFAULT_MAR_Y1, $this->GetBarSize('3', $xres) , $ySize);
	  $DrawPos += $this->GetBarSize('3', $xres);
	  $DrawPos += $this->GetBarSize('1', $xres);
	  $this->DrawSingleBar($DrawPos, BCD_DEFAULT_MAR_Y1, $this->GetBarSize('1', $xres) , $ySize);
	  $DrawPos += $this->GetBarSize('1', $xres);
	  $DrawPos += $this->GetBarSize('1', $xres);
	  $this->DrawSingleBar($DrawPos, BCD_DEFAULT_MAR_Y1, $this->GetBarSize('2', $xres) , $ySize);
	  $DrawPos += $this->GetBarSize('2', $xres);
	  return $DrawPos;
   }
	
   function DrawCheckChar($DrawPos, $yPos, $ySize, $xres)
   {
     $cset = $this->GetCheckCharValue();
	 $this->DrawSingleBar($DrawPos, BCD_DEFAULT_MAR_Y1, $this->GetBarSize($cset[0], $xres) , $ySize);
	 $DrawPos += $this->GetBarSize($cset[0], $xres);
	 $DrawPos += $this->GetBarSize($cset[1], $xres);
	 $this->DrawSingleBar($DrawPos, BCD_DEFAULT_MAR_Y1, $this->GetBarSize($cset[2], $xres) , $ySize);
	 $DrawPos += $this->GetBarSize($cset[2], $xres);
	 $DrawPos += $this->GetBarSize($cset[3], $xres);
	 $this->DrawSingleBar($DrawPos, BCD_DEFAULT_MAR_Y1, $this->GetBarSize($cset[4], $xres) , $ySize);
	 $DrawPos += $this->GetBarSize($cset[4], $xres);
	 $DrawPos += $this->GetBarSize($cset[5], $xres); 
	 return $DrawPos;
    }
	 
    function DrawObject ($xres)
    {
     $len = strlen($this->mValue);  		  
	 if (($size = $this->GetSize($xres))==0) {
     	__DEBUG__("GetSize: failed");
	    return false;
	 }    
	 	  
	 if ($this->mStyle & BCS_ALIGN_CENTER) $sPos = (integer)(($this->mWidth - $size ) / 2);
	 else if ($this->mStyle & BCS_ALIGN_RIGHT) $sPos = $this->mWidth - $size;
	 	  else $sPos = 0;		
		  						
	 /* Total height of bar code -Bars only- */					
	 if ($this->mStyle & BCS_DRAW_TEXT) $ysize = $this->mHeight - BCD_DEFAULT_MAR_Y1 - BCD_DEFAULT_MAR_Y2 - $this->GetFontHeight($this->mFont);
	 else $ysize = $this->mHeight - BCD_DEFAULT_MAR_Y1 - BCD_DEFAULT_MAR_Y2;
										 
	 /* Draw text */ 
	 if ($this->mStyle & BCS_DRAW_TEXT) {
	   	 if ($this->mStyle & BCS_STRETCH_TEXT) {
			for ($i=0;$i<$len;$i++) {
	   	  	  	$this->DrawChar($this->mFont, $sPos+(2*BCD_C128_BAR_2*$xres + 3*BCD_C128_BAR_1*$xres + BCD_C128_BAR_4*$xres)+($size/$len)*$i,
			  			 $ysize + BCD_DEFAULT_MAR_Y1 + BCD_DEFAULT_TEXT_OFFSET, $this->mValue[$i]);
				}                                                                                  
		 } else {/* Center */
		  	 $text_width = $this->GetFontWidth($this->mFont) * strlen($this->mValue);
			 $this->DrawText($this->mFont, $sPos+(($size-$text_width)/2)+(2*BCD_C128_BAR_2*$xres + 3*BCD_C128_BAR_1*$xres + BCD_C128_BAR_4*$xres),
			 				  $ysize + BCD_DEFAULT_MAR_Y1 + BCD_DEFAULT_TEXT_OFFSET, $this->mValue);
	      }	 
	  }                      
									
	 $cPos = 0;	  		                 
	 $DrawPos = $this->DrawStart($sPos, BCD_DEFAULT_MAR_Y1 , $ysize, $xres); 
	 do {      			     
        $c     = $this->GetCharIndex($this->mValue[$cPos].$this->mValue[$cPos+1]);
		$cset  = $this->mCharSet[$c];       		
	    $this->DrawSingleBar($DrawPos, BCD_DEFAULT_MAR_Y1, $this->GetBarSize($cset[0], $xres) , $ysize);
	    $DrawPos += $this->GetBarSize($cset[0], $xres);
	    $DrawPos += $this->GetBarSize($cset[1], $xres);
	    $this->DrawSingleBar($DrawPos, BCD_DEFAULT_MAR_Y1, $this->GetBarSize($cset[2], $xres) , $ysize);
	    $DrawPos += $this->GetBarSize($cset[2], $xres);
	    $DrawPos += $this->GetBarSize($cset[3], $xres);
	    $this->DrawSingleBar($DrawPos, BCD_DEFAULT_MAR_Y1, $this->GetBarSize($cset[4], $xres) , $ysize);
	    $DrawPos += $this->GetBarSize($cset[4], $xres);
	    $DrawPos += $this->GetBarSize($cset[5], $xres);
                $cPos += 2; 
	  } while ($cPos<$len);
 	  $DrawPos = $this->DrawCheckChar($DrawPos, BCD_DEFAULT_MAR_Y1 , $ysize, $xres);
	  $DrawPos =  $this->DrawStop($DrawPos, BCD_DEFAULT_MAR_Y1 , $ysize, $xres);
	  return true;
	 }
  }
?>
