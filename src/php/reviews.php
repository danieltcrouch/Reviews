<?php
include_once( "utility.php" );


/**********************BOOK**********************/


include_once( "utilityBook.php" );


/**********************MOVIE*********************/


$fullMovieList = [];

function getFullMovieList()
{
    global $fullMovieList;
    $result = $fullMovieList;
    if ( empty($result) )
    {
        $result = getList( getPath( "ratings.csv" ) );
        $fullMovieList = $result;
    }
    return $result;
}

function getList( $fileName )
{
    $file = fopen( $fileName, "r" );
    $columns = getColumns( fgetcsv( $file ) );
    $movies = createEntryObjectList( $file, $columns, function( $row, $columns ) {
        return [
            "title"  => $row[$columns['tIndex']],
            "year"   => $row[$columns['yIndex']],
            "review" => $row[$columns['cIndex']],
            "rating" => $row[$columns['rIndex']],
            "poster" => $row[$columns['pIndex']]
        ];
    });
    fclose( $file );

    return $movies;
}

function getMovieList()
{
    return getFullMovieList();
}

function getDisneyList()
{
    return getList( getPath( "rank-Disney.csv" ) );
}

function getMarvelList()
{
    return getList( getPath( "rank-Marvel.csv" ) );
}

function getStarWarsList()
{
    return getList( getPath( "rank-StarWars.csv" ) );
}

function getMovieFromIMDB( $id )
{
    $result['poster'] = "";
    $result['rtScore'] = "--%";

    $url = "http://www.omdbapi.com/?i=$id&y=&plot=short&r=json&apikey=8f0ce8a6";
    $response = json_decode( file_get_contents( $url ) );

    if ( $response->Response === "True" )
    {
        $result['poster'] = $response->Poster;
        $result['rtScore'] = $response->Ratings[1]->Value;
    }

    return $result;
}

function getMovieFromFile( $title )
{
    $result['isSuccess'] = false;

    $movies = getFullMovieList(); //todo - do the same thing for books?

    $movieTitles = [];
    array_walk( $movies, function($value, $key) {
        $movieTitles[$key] = $value['title'];
    });

    $movieId = findEntry( $movieTitles, $title );

    if ( $movieId )
    {
        $result = $movies[$movieId];
        $movieData = getMovieFromIMDB( $result['id'] );
        $result['poster'] = $movieData['poster'];
        $result['rtScore'] = $movieData['rtScore'];
        $result['isSuccess'] = true;
    }
    return $result;
}

function saveSearch( $title, $type )
{
    saveSearch( $title, $type );
}

if ( isset( $_POST['action'] ) && function_exists( $_POST['action'] ) )
{
	$action = $_POST['action'];
    $result = null;

    if ( isset( $_POST['title'] ) && isset( $_POST['author'] ) )
    {
        $result = $action( $_POST['title'], $_POST['author'] );
    }
    elseif ( isset( $_POST['title'] ) && isset( $_POST['type'] ) )
    {
        $result = $action( $_POST['title'] );
    }
    elseif ( isset( $_POST['title'] ) )
    {
        $result = $action( $_POST['title'] );
    }
	else
	{
		$result = $action();
	}

	echo json_encode( $result );
}
?>