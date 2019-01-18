<?php

function compareEntry( $search, $entry )
{
    $search = trim( $search );
    $entry = trim( $entry );
    if ( stripos( $entry, $search ) !== false )
    {
        $result = true;
    }
    else
    {
        $search = preg_replace( '/(,|-|\.)+/',' ', $search );
        $search = preg_replace('/\s+/', ' ', $search);
        $search = trim( $search );
        $searchTerms = explode( ' ', $search );
        $allMatch = true;
        foreach ( $searchTerms as $term )
        {
            if ( stripos( $entry, $term ) === false )
            {
                $allMatch = false;
                break;
            }
        }

        $result = $allMatch;
    }

    return $result;
}

function findEntry( $list, $searchItem )
{
    $result = null;
    foreach( $list as $key => $value )
    {
        if ( compareEntry( $searchItem, $value ) )
        {
            $result = $key;
            break;
        }
    }
    return $result;
}

function createEntryList( $file, $keyIndex, $valueIndex )
{
    $result = [];
    $row = fgetcsv( $file );
    $index = 0;
    while ( $row !== false )
    {
        $key = $keyIndex === false ? $index : $row[$keyIndex];
        $result[$key] = $row[$valueIndex];
        $row = fgetcsv( $file );
        $index++;
    }
    return $result;
}

function createEntryObjectList( $file, $columns, $getValue )
{
    $result = [];
    $row = fgetcsv( $file );
    $index = 0;
    while ( $row !== false )
    {
        $result[$row[$columns['iIndex']]] = $getValue( $row, $columns );
        $row = fgetcsv( $file );
        $index++;
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