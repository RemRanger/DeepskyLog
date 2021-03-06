<?php  
// contrast.php
// The contrast class calculates the contrast and magnification of a certain object, 
// with a certain instrument, under a certain sky

global $inIndex;
if((!isset($inIndex))||(!$inIndex)) include "../../redirect.php";

class Contrast
{ private function calcSubroutine($x, $SBObj, $minObjArcmin, $maxObjArcmin, $maxLog, $logObjContrast) // This function should not be used. Only needed for the calculations
  { $SBReduc = 5 * log10( $x / (2.833 * $_SESSION['aperIn']));
    $SBB = $_SESSION['initBB'] + $SBReduc;
    $SBScopeAtX = -2.5 * log10( pow( 10, (-0.4 * $SBObj))) + $SBReduc;

    /* surface brightness of object + background brightness */
    $SBBBScopeAtX = -2.5 * log10( pow( 10, (0.16 * $SBObj * $_SESSION['initBB']))) + $SBReduc;

    /* 2 dimensional interpolation of LTC array */
    $ang = $x * $minObjArcmin;
    $logAng = log10($ang);
    $SB = $SBB;
    $I = 0;

    /* int of surface brightness */
    $intSB = (int) $SB;
    /* surface brightness index A */
    $SBIA = $intSB - 4;
    /* min index must be at least 0 */
    if($SBIA < 0)
        $SBIA = 0;
    /* max SBIA index cannot > 22 so that max SBIB <= 23 */
    if( $SBIA > $_SESSION['LTCSize'] - 2)
        $SBIA = $_SESSION['LTCSize'] - 2;
    /* surface brightness index B */
    $SBIB = $SBIA + 1;

    while( $I < $_SESSION['angleSize'] && $logAng > $_SESSION['angle'][$I++])
        ;

    /* found 1st Angle[] value > LogAng, so back up 2 */
    $I -= 2;
    if( $I < 0)
    { $I = 0;
      $logAng = $_SESSION['angle'][0];
    } 
    /* ie, if LogAng = 4 and Angle[I] = 3 and Angle[I+1] = 5, InterpAngle = .5,
     or .5 of the way between Angle[I] and Angle{I+1] */
    $interpAngle = ($logAng - $_SESSION['angle'][$I]) / ($_SESSION['angle'][$I + 1] - $_SESSION['angle'][$I]);
    /* add 1 to I because first entry in LTC is sky background brightness */
    $interpA = $_SESSION['LTC'][$SBIA][$I + 1] + $interpAngle * ($_SESSION['LTC'][$SBIA][$I + 2] - $_SESSION['LTC'][$SBIA][$I + 1]);
    $interpB = $_SESSION['LTC'][$SBIB][$I + 1] + $interpAngle * ($_SESSION['LTC'][$SBIB][$I + 2] - $_SESSION['LTC'][$SBIB][$I + 1]);
    if( $SB < $_SESSION['LTC'][0][0])
        $SB = $_SESSION['LTC'][0][0];
    if( $intSB >= $_SESSION['LTC'][$_SESSION['LTCSize'] - 1][0])
        $logThreshContrast = $interpB + ($SB - $_SESSION['LTC'][$_SESSION['LTCSize'] - 1][0]) * ($interpB - $interpA);
    else
        $logThreshContrast = $interpA + ($SB - $intSB) * ($interpB - $interpA);
    if( $logThreshContrast > $maxLog)
        $logThreshContrast = $maxLog;
    else
        if( $logThreshContrast < - $maxLog)
            $logThreshContrast = - $maxLog;
    $logContrastDiff = $logObjContrast - $logThreshContrast;
    return array($logContrastDiff, $SBReduc, $SBBBScopeAtX, $SBScopeAtX, $SBB, $logThreshContrast);
 }
 public	 function calculateContrast($objMag, $SBObj, $minObjArcmin, $maxObjArcmin)
	{	 // limMag is the limiting magnitude
		 // aperMm is the telescope aperture in mm
		 // objMag is the magnitude of the object
		 // minObjArcmin is the small axis of the object
		 // maxObjArcmin is the large axis of the object
		 // We return an array with the following information :
		 // array(Contrast Difference, minimum usefull magnification, Optimum detection magnification)
		 // If the contrast difference is < 0, the object is not visible.
		 // contrast difference < -0.2 : Not visible - Dark Gray : 777777
		 // -0.2 < contrast difference < 0.1 : Fraglich - Gray : 999999
		 // 0.10 < contrast difference < 0.35 : Schwierig - Red : CC0000
		 // 0.35 < contrast difference < 0.5 : Mittelschwer - Orange : FF6600
		 // 0.50 < contrast difference < 1.0 : Leicht Sichtbar - Dark green : 339900
		 // 1.00 < contrast difference : Leicht Sichtbar - Light green : 66FF00
  	
		global $objObject;
		
		if( $minObjArcmin > $maxObjArcmin)
  	{ $temp = $minObjArcmin;
  		$minObjArcmin = $maxObjArcmin;
  		$maxObjArcmin = $temp;
  	}
  	$maxLog = 37;
  	$maxX = 1000;

  	// Log Object contrast
  	$logObjContrast = -0.4 * ($SBObj - $_SESSION['initBB']);

  	$bestLogContrastDiff = - $maxLog;
  	$bestX = 0;

   // The preparations are finished, we can now start the calculations
		$mags = $_SESSION['magnifications'];
    $magsName = $_SESSION['magnificationsName'];
    $fovs = $_SESSION['fov'];

		if (count($mags) > 1)
		{
			$check = 0;
			$fovMax = 0;
			$fovMaxcnt = -1;

			for ( $cnt = 0; $cnt < count($mags); $cnt++)
			{
				if ($fovs[$cnt] > $fovMax)
				{
					$fovMaxcnt = $cnt;
					$fovMax = $fovs[$cnt];
				}

				if ($fovs[$cnt] > $maxObjArcmin)
				{
					$doCalc[] = 1;
					$check = 1;
				} else {
					$doCalc[] = 0;
				}
			}

			if ($check == 0)
			{
				$doCalc[$fovMaxcnt] = 1;
			}
		} else {
			$doCalc[0] = 1;
		}

		for ( $cnt = 0; $cnt < count($mags); $cnt++)
		{
			if ($doCalc[$cnt] == 1)
			{
				$x = $mags[$cnt];
				$xName = $magsName[$cnt];

    		$SBReduc = 5 * log10($x);
				$SBB = $_SESSION['SBB1'] + $SBReduc;
    		$SBScopeAtX = $_SESSION['SBB2']  + $SBObj + $SBReduc;
    		/* surface brightness of object + background brightness */
    		$SBBBScopeAtX = $_SESSION['SBB2'] - (0.4 * $SBObj * $_SESSION['initBB']) + $SBReduc;

    		/* 2 dimensional interpolation of LTC array */
    		$ang = $x * $minObjArcmin;
    		$logAng = log10($ang);
    		$SB = $SBB;
    		$I = 0;

    		/* int of surface brightness */
    		$intSB = (int) $SB;
    		/* surface brightness index A */
    		$SBIA = $intSB - 4;
    		/* min index must be at least 0 */
    		if($SBIA < 0)
     	   $SBIA = 0;
    		/* max SBIA index cannot > 22 so that max SBIB <= 23 */
    		if( $SBIA > $_SESSION['LTCSize'] - 2)
      	  $SBIA = $_SESSION['LTCSize'] - 2;
    		/* surface brightness index B */
    		$SBIB = $SBIA + 1;

    		while( $I < $_SESSION['angleSize'] && $logAng > $_SESSION['angle'][$I++])
      	  ;

    		/* found 1st Angle[] value > LogAng, so back up 2 */
    		$I -= 2;
    		if( $I < 0)
    		{
     	   $I = 0;
     	   $logAng = $_SESSION['angle'][0];
    		} 

    		/* ie, if LogAng = 4 and Angle[I] = 3 and Angle[I+1] = 5, InterpAngle = .5,
     		or .5 of the way between Angle[I] and Angle{I+1] */
    		$interpAngle = ($logAng - $_SESSION['angle'][$I]) / ($_SESSION['angle'][$I + 1] - $_SESSION['angle'][$I]);
    		/* add 1 to I because first entry in LTC is sky background brightness */
    		$interpA = $_SESSION['LTC'][$SBIA][$I + 1] + $interpAngle * ($_SESSION['LTC'][$SBIA][$I + 2] - $_SESSION['LTC'][$SBIA][$I + 1]);
    		$interpB = $_SESSION['LTC'][$SBIB][$I + 1] + $interpAngle * ($_SESSION['LTC'][$SBIB][$I + 2] - $_SESSION['LTC'][$SBIB][$I + 1]);
    		if($SB<$_SESSION['LTC'][0][0])
      		$SB = $_SESSION['LTC'][0][0];
				if( $intSB >= $_SESSION['LTC'][$_SESSION['LTCSize'] - 1][0])
      	  $logThreshContrast = $interpB + ($SB - $_SESSION['LTC'][$_SESSION['LTCSize'] - 1][0]) * ($interpB - $interpA);
    		else
      	  $logThreshContrast = $interpA + ($SB - $intSB) * ($interpB - $interpA);
    
				if( $logThreshContrast > $maxLog)
      	  $logThreshContrast = $maxLog;
    		else
     		 if( $logThreshContrast < - $maxLog)
      		  $logThreshContrast = - $maxLog;

     		$logContrastDiff = $logObjContrast - $logThreshContrast;

     		if( $logContrastDiff > $bestLogContrastDiff)
     		{
     	  	$bestLogContrastDiff = $logContrastDiff;
     	  	$bestX = $x;
					$bestXName = $xName;
     		}
   		}
		}
   	$x = $bestX;
		$xName = $bestXName;
   	$logContrastDiff = $bestLogContrastDiff;
   	return array($logContrastDiff, $x, $xName);
	}
 public  function calculateLimitingMagnitudeFromSkyBackground($initBB)
 { return (7.97 - 5 * log10(1 + pow(10, 4.316 - $initBB / 5.0)));
 }
 public  function calculateSkyBackgroundFromLimitingMagnitude($limMag)
 { return ((21.58 - 5 * log10(pow(10, (1.586 - $limMag / 5.0)) - 1.0)));
 }
}
?>
