<?php

// Input and output files
$inputFile     = 'contacts.csv';
$outputFile    = 'contacts_form_data.csv';
$publicKeyFile = 'public_key.txt';

// Load public key
$publicKey = file_get_contents( $publicKeyFile );
if ( ! $publicKey ) {
    die( "Failed to read public key from $publicKeyFile\n" );
}

// Load public key resource
$pubKeyResource = openssl_pkey_get_public( $publicKey );
if ( ! $pubKeyResource ) {
    die( "Invalid public key format\n" );
}

// Open input and output CSV files
$in = fopen( $inputFile, 'r' );
if ( ! $in ) {
    die( "Failed to open input file: $inputFile\n" );
}

$out = fopen( $outputFile, 'w' );
if ( ! $out ) {
    die( "Failed to open output file: $outputFile\n" );
}

// Write header to output CSV
fputcsv( $out, [ 'Email', '1 Click Form Data' ] );

// Get the header row from the input CSV
$headers = fgetcsv( $in );
if ( ! $headers ) {
    die( "CSV file is empty or unreadable\n" );
}

// Determine the column indexes
$firstNameIndex = array_search( 'first_name', $headers );
$lastNameIndex  = array_search( 'last_name', $headers );
$emailIndex     = array_search( 'email', $headers );

if ( $firstNameIndex === false || $lastNameIndex === false || $emailIndex === false ) {
    die( "Missing required columns in CSV\n" );
}

// Process each row
while ( ( $row = fgetcsv( $in ) ) !== false ) {
    $firstName = $row[ $firstNameIndex ];
    $lastName  = $row[ $lastNameIndex ];
    $email     = $row[ $emailIndex ];

    // Create the object
    $data = [
        'f' => $firstName,
        'l' => $lastName,
        'e' => $email
    ];

    // Convert to JSON
    $json = json_encode( $data );

    // Encrypt using public key
    $encrypted = '';
    if ( ! openssl_public_encrypt( $json, $encrypted, $pubKeyResource ) ) {
        echo "Failed to encrypt data for $email\n";
        continue;
    }

    // Base64 encode encrypted string for safe CSV output
    $encryptedBase64 = gpch_base64url_encode( $encrypted );

    // Write to output
    fputcsv( $out, [ $email, $encryptedBase64 ] );
}

// Cleanup
fclose( $in );
fclose( $out );
openssl_free_key( $pubKeyResource );

echo "Encryption complete. Output saved to $outputFile\n";


/**
 * URL safe base64 encode of binary data
 *
 * @param $data
 *
 * @return string
 */
function gpch_base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}