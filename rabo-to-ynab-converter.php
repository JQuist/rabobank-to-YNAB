<?php

/**
 * Rabobank to YNAB converter v1.0
 *
 * Formats the (Dutch?) Rabobank CSV format for bank statements so it can be imported by YNAB.
 * 
 * Input format (http://www.rabobank.nl/images/formaatbeschrijving_csv_kommagescheiden_nieuw_29539176.pdf,
 * 1. REKENINGNUMMER_REKENINGHOUDER   IBAN number.
 * 2. MUNTSOORT
 * 3. RENTEDATUM                      YYYYMMDD
 * 4. BY_AF_CODE                      C or D
 * 5. BEDRAG                          This is always a positive number, for both credits and debits.
 * 6. TEGENREKENING
 * 7. NAAR_NAAM
 * 8. BOEKDATUM                       YYYYMMDD
 * 9. BOEKCODE
 * 10. FILLER
 * 11. OMSCHR1
 * 12. OMSCHR2
 * 13. OMSCHR3
 * 14. OMSCHR4
 * 15. OMSCHR5
 * 16. OMSCHR6
 * 17. END_TO_END_ID
 * 18. ID_TEGENREKENINGHOUDER
 * 19. MANDAAD_ID
 * 
 * Target format: Date,Payee,Category,Memo,Outflow,Inflow
 * 1. BOEKDATUM                       YYYYMMDD
 * 2. NAAR_NAAM
 * 3. Empty Category field 
 * 4. OMSCHR1                        All description lines are pasted together into this field.  
 * 5. BEDRAG   
 */

// Was the form submitted?
if( isset( $_FILES[ 'csv' ] ) && $_FILES[ 'csv' ][ 'error' ] == UPLOAD_ERR_OK )
{
  // Get the input.
  $raboCSV = getCSV( $_FILES[ 'csv' ][ 'tmp_name' ] );

  // Convert
  $YNABCSV = rabo2YNAB( $raboCSV );

  // Make string from array
  $YNABCSV = putCSV( $YNABCSV );

  // Send the new file as a download
  header( 'Content-type: application/force-download' );
  header( 'Content-Disposition: attachment; filename="mutaties.csv"' );
  echo $YNABCSV;
  exit;
}

// Function to read CSV for < PHP 5.3
// Inspired by http://nl.php.net/manual/en/function.str-getcsv.php
function getCSV( $inputFile, $delimiter = ',', $enclosure = '"', $escape = "\\" ) 
{ 
  // Create temporary file to read
  $fp = fopen( $inputFile, 'r+' );

  // Loop through lines of code
  // $escape only got added in 5.3.0 
  $data = array();
  while( ( $lineData = fgetcsv( $fp, 0, $delimiter, $enclosure ) ) !== false ) 
  {
    // A line should have 19 elements to qualify
    if( count( $lineData ) == 19 )
    {
      $data[] = $lineData;
    }
  }

  fclose( $fp );

  return $data;
}

// Transforms the data from the Rabobank so it can be used by YNAB
function rabo2YNAB( $csv )
{
  // Loop through the 2D-array
  $len = count( $csv );
  for( $i = 0; $i < $len; $i++ )
  {
	//Boekcode in position 1 & DateFormatFix
	$tempdate = DateTime::createFromFormat('Ymd', $csv[ $i ][ 7]);	
	$csv[ $i ][ 0] = date_format($tempdate, 'd/m/y');
			
	//Naarnaam in position 2
    $csv[ $i ][ 1] = $csv[ $i ][ 6];
	
	// If chipknip/betaalautomaat/pinterminal add value to position 2
	if( $csv[ $i ][ 8 ] == "ga" ) {$csv[ $i ][ 1] = 'GeldAutomaat';}
	if( $csv[ $i ][ 8 ] == "ck" ) {$csv[ $i ][ 1] = 'Chipknip';}
	if( $csv[ $i ][ 8 ] == "ba" ) {$csv[ $i ][ 1] = 'PIN';}
	
	//Add space to the Category field.
	$csv[ $i ][ 2] = '';
	
	//copy BYAF-code and amount to 18+19 in arrray
	$csv[ $i ][ 17] = $csv[ $i ][ 3];
	$csv[ $i ][ 18] = $csv[ $i ][ 4];
	
	// 3. Put the different lines of description together.
    // We also add the END_TO_END_ID to the description.
	$csv[ $i ][ 3 ] = trim( $csv[ $i ][ 10 ] . ' ' . $csv[ $i ][ 11 ] . ' ' . $csv[ $i ][ 12 ] . ' ' . $csv[ $i ][ 13 ] . ' ' . $csv[ $i ][ 14 ] . ' ' . $csv[ $i ][ 15 ] . ' ' . $csv[ $i ][ 16 ] );
	
		// Check if it was a credit transaction
	if( $csv[ $i ][ 17 ] == 'C' )
    {
      // If Credit.. add extra comma.
        $csv[ $i ][ 4 ] = '';
	  }
	
	// 1. Check if it was a debit transaction
	if( $csv[ $i ][ 17 ] == 'D' )
    {
      //if Debit remove the array value
      unset( $csv[ $i ][ 4 ]);
    }
	
	//GimmeMoney
	$csv[ $i ][ 5 ] = $csv[ $i ][ 18 ];

    //Remove the empty fields at the end.
    for( $j = 6; $j < 19; $j++ )
    {
      unset( $csv[ $i ][ $j ] );
    }
			
    $csv[ $i ] = array_values( $csv[ $i ] );
  }

  // Return the result.
  return $csv;
}

// Get CSV string from 2D-array
// Insipred by http://nl.php.net/manual/en/function.str-getcsv.php
function putCSV( $array, $delimiter = ',', $enclosure = '"', $escape = "\\" )
{
  $fiveMBs = 5 * 1024 * 1024;
  $fp = fopen( 'php://temp/maxmemory:' . $fiveMBs, 'r+' );

  // Convert all lines.
  $len = count( $array );
  
  //Array to store the YNAB-headline
  $YNABarray = array (
    array('Date','Payee','Category','Memo','Outflow','Inflow'));  
  
  fputcsv( $fp, $YNABarray[0], $delimiter ); 
    
  for( $i = 0; $i < $len; $i++ )
  {
    fputcsv( $fp, $array[ $i ], $delimiter );
  }
  // Read the result.
  rewind( $fp );
  $data = '';
  while( ( $lineData = fgets( $fp ) ) !== false )
  {
    $data .= $lineData;
  }

  fclose( $fp );
  return $data;
}

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Rabobank to YNAB bank statement converter</title>
  </head>
  <body>
    <form method="post" enctype="multipart/form-data">
      <p>
        Upload the comma seperated value file from Rabobank.
      </p>

      <p>
        <input type="file" name="csv" />
      </p>

      <p>
        <input type="submit" value="Convert &amp; download" />
      </p>
    </form>
  </body>
</html>
