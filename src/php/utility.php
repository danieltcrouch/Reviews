<?php

function compareTitles( $searchTitle, $rowTitle )
{
    $result = false;
    if ( stripos( $rowTitle, $searchTitle ) !== false ) //todo - develop for more sophisticated search
    {
        $result = true;
    }
    return $result;
}

function getPath( $fileName )
{
    $newDirectory = "../archive/";
    $path = $newDirectory . $fileName;
    $pathInfo = pathinfo( $path );
    if ( !file_exists( $pathInfo['dirname'] ) )
    {
        mkdir( $pathInfo['dirname'], 0777, true );
    }
    return $path;
}

function getColumns( $firstRow )
{
    $result['iIndex'] = array_search( "ID", $firstRow, true );
    $result['tIndex'] = array_search( "Title", $firstRow, true );
    $result['aIndex'] = array_search( "Author", $firstRow, true );
    $result['yIndex'] = array_search( "Year", $firstRow, true );
    $result['cIndex'] = array_search( "Review", $firstRow, true );
    $result['rIndex'] = array_search( "Rating", $firstRow, true );
    $result['pIndex'] = array_search( "Image", $firstRow, true );

    return $result;
}

function saveFailedSearch( $file, $title )
{
    $file = fopen( getPath( "$file.txt" ), "a" );
    fwrite( $file, $title . "\n" );
    fclose( $file );
}

?>