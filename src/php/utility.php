<?php
session_start();

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
    $result['uIndex'] = array_search( "URL", $firstRow, true );

    return $result;
}


/*****************LIST & SEARCH*******************/


function findEntryAdvanced( $list, $searchItem, $customSearch )
{
    $result = null;
    $highScore = 0;
    foreach( $list as $key => $value )
    {
        $score = $customSearch( $searchItem, $value );
        if ( $score >= $highScore )
        {
            $highScore = $score;
            $result = $key;
            if ( $score === 1 )
            {
                break;
            }
        }
    }
    return $result;
}

function findEntry( $list, $searchItem )
{
    $result = null;
    $highScore = 0;
    foreach( $list as $key => $value )
    {
        $score = compareEntry( $searchItem, $value );
        if ( $score >= $highScore )
        {
            $highScore = $score;
            $result = $key;
            if ( $score === 1 )
            {
                break;
            }
        }
    }
    return $result;
}

function compareEntry( $search, $entry )
{
    $matchScore = -1;

    $search = trim( $search );
    $entry = trim( $entry );
    if ( strcasecmp( $entry, $search ) === 0 )
    {
        $matchScore = 1;
    }
    elseif ( stripos( $entry, $search ) !== false )
    {
        similar_text( $entry, $search, $matchScore );
        $matchScore /= 100;
    }
    else
    {
        $search = preg_replace( '/\b(a|an|and|for|from|the)\b/',' ', $search );
        $search = preg_replace( '/("|,|-|\.)/',' ', $search );
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

        if ( $allMatch )
        {
            similar_text( $entry, $search, $matchScore );
            $matchScore /= 100;
        }
    }

    return $matchScore;
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

function createEntryObjectList( $file, $columns, $getValueFunction )
{
    $result = [];
    $index = 0;
    $row = fgetcsv( $file );
    while ( $row !== false )
    {
        $result[$index] = $getValueFunction( $row, $columns );
        $row = fgetcsv( $file );
        $index++;
    }
    return $result;
}

function getIndexFromListByTitle( $list, $title )
{
    $titles = [];
    array_walk( $list, function($value, $key) use( &$titles ) {
        $titles[$key] = $value['title'];
    });
    return findEntry( $titles, $title );
}

function getIndexFromListById( $list, $id )
{
    return findEntryAdvanced( $list, $id, function( $id, $value ) {
        return ( $value['id'] === $id ) ? 1 : -1;
    } );
}


/*****************LIST FILE IO********************/


function getListFromFile( $fileName, $mapperFunction )
{
    $file = fopen( $fileName, "r" );
    $columns = getColumns( fgetcsv( $file ) );
    $list = createEntryObjectList( $file, $columns, $mapperFunction );
    fclose( $file );
    return $list;
}

function saveListToFile( $fileName, $titles, $list, $mapperFunction )
{
    $file = fopen( $fileName, "w" );
    fputcsv( $file, $titles );
    foreach ( $list as $item )
    {
        fputcsv( $file, $mapperFunction( $item ) );
    }
    fclose( $file );
}

function appendToFile( $fileName, $content, $isCSV )
{
    $file = fopen( $fileName, "a" );
    $isCSV ? fputcsv( $file, $content ) : fwrite( $file, $content );
    fclose( $file );
}

?>
