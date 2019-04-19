<?php
include_once( "utility.php" );

function getMovieList()
{
    if ( empty($GLOBALS['fullMovieList']) )
    {
        $GLOBALS['fullMovieList'] = getMovieListFromFile( getPath( "ratings.csv" ) );
    }
    return $GLOBALS['fullMovieList'];
}


/********************INTERNET********************/


function requestIMDB( array $params = array() )
{
    $result = [];
    if ( !empty($params) )
    {
        $paramString = http_build_query($params, "", "&");
        $url = "http://www.omdbapi.com/?$paramString&y=&plot=short&r=json&apikey=522c6900"; //OMDB API requires API Key -> go to their site if this one stops working
        $response = json_decode(file_get_contents($url));
        $result = getDataFromResponse($response);
    }
    return $result;
}

function getMovieFromImdbByTitle( $title )
{
    $title = trim( $title );
    $searchTitle = urlencode( $title );
    $result = requestIMDB( [ 't' => $searchTitle ] );
    $result['search'] = $title;
    return $result;
}

function getMovieFromImdbById( $id )
{
    return requestIMDB( [ 'i' => $id ] );
}

function getDataFromResponse( $response )
{
    $result['isSuccess'] = false;
    if ( $response->Response === "True" )
    {
        $result['isSuccess'] = true;
        $result['id'] = $response->imdbID;
        $result['title'] = $response->Title;
        $result['year'] = $response->Year;
        $result['image'] = $response->Poster;
        $result['rtScore'] = $response->Ratings[1]->Value;
    }
    return $result;
}


/********************FILE I/O********************/


function getMovieListFromFile( $fileName )
{
    return getListFromFile( $fileName, function( $row, $columns ) {
        return [
            "id"     => $row[$columns['iIndex']],
            "title"  => $row[$columns['tIndex']],
            "year"   => $row[$columns['yIndex']],
            "review" => $row[$columns['cIndex']],
            "rating" => $row[$columns['rIndex']],
            "image"  => $row[$columns['pIndex']]
        ];
    } );
}

function getMovieFromFile( $title )
{
    $movies = getMovieList();
    $movieTitles = [];
    array_walk( $movies, function($value, $key) use( &$movieTitles ) {
        $movieTitles[$key] = $value['title'];
    });

    $movieId = findEntry( $movieTitles, $title );
    return ( $movieId ) ? $movies[$movieId] : null;
}

function saveFullMoviesToFile( $movies )
{
    saveListToFile(
        getPath( "ratings.csv" ),
        array( "Title", "ID", "Year", "Rating", "Review" ),
        $movies,
        function( $movie ) {
            return array( $movie['title'], $movie['id'], $movie['year'], $movie['rating'], $movie['review'] );
    } );
    $GLOBALS['fullMovieList'] = $movies;
}

function archive( $fileName )
{
    $fileBase = str_replace( ".csv", "", $fileName );
    unlink( "$fileBase 5.csv" );
    rename( "$fileBase 4.csv", "$fileBase 5.csv" );
    rename( "$fileBase 3.csv", "$fileBase 4.csv" );
    rename( "$fileBase 2.csv", "$fileBase 3.csv" );
    rename( "$fileBase 1.csv", "$fileBase 2.csv" );
    copy( $fileName, "$fileBase 1.csv" );
}

//RANK ********************

function getRankMovieFromFileByTitle( $list, $title )
{
    $movies = getMovieListFromFile( getPath( "rank-$list.csv" ) );
    $index = getIndexFromListByTitle( $movies, $title );
    return ( $index ) ? $movies[$index] : null;
}

function getRankMovieFromFileById( $list, $id )
{
    $movies = getMovieListFromFile( getPath( "rank-$list.csv" ) );
    $index = getIndexFromListById( $movies, $id );
    $result = $movies[$index];
    $result['index'] = $index + 1;
    $result['list'] = $list;
    return is_numeric( $index ) ? $result : null;
}

function getRankMovieFromFilesById( $id )
{
    $movie = null;
    foreach ( getRankLists() as $list )
    {
        $movie = getRankMovieFromFileById( $list, $id );
        if ( isset( $movie ) )
        {
            break;
        }
    }
    return $movie;
}

function saveRankMoviesToFile( $list, $movies )
{
    saveListToFile(
        getPath( "rank-$list.csv" ),
        array( "Title", "ID", "Year", "Image", "Review" ),
        $movies,
        function( $movie ) {
            return array( $movie['title'], $movie['id'], $movie['year'], $movie['image'], $movie['review'] );
    } );
}


/*********************OTHER**********************/


function getRankLists()
{
    return array( "Disney", "Marvel", "StarWars" );
}

function getListName( $list )
{
    $list = strtolower( preg_replace('/\s+/', '', $list) );
    switch ( $list )
    {
        case "d":
        case "disney":
            $list = "Disney";
            break;
        case "m":
        case "mcu":
        case "marvel":
            $list = "Marvel";
            break;
        case "s":
        case "sw":
        case "starwars":
        case "star wars":
            $list = "StarWars";
            break;
        default:
            $list = null;
    }
    return $list;
}

?>
