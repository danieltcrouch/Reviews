<?php
include_once( "utility.php" );

/**********************BOOK**********************/


include_once( "utilityBook.php" );


/**********************MOVIE*********************/


function getFullMovieList()
{
    if ( empty($_SESSION['fullMovieList']) )
    {
        getMovieList(); //updates list
    }
    return $_SESSION['fullMovieList'];
}

function getMovieFromIMDB( $id )
{
    $url = "http://www.omdbapi.com/?i=$id&y=&plot=short&r=json&apikey=8f0ce8a6";
    $response = json_decode( file_get_contents( $url ) );
    $success = $response->Response === "True";
    $result['image']    = $success ? $response->Poster : "";
    $result['rtScore']  = $success ? $response->Ratings[1]->Value : "--%";
    return $result;
}

function getMovieFromFile( $title )
{
    $movies = getFullMovieList();
    $movieTitles = [];
    array_walk( $movies, function($value, $key) use( &$movieTitles ) {
        $movieTitles[$key] = $value['title'];
    });

    $movieId = findEntry( $movieTitles, $title );
    return ( $movieId ) ? $movies[$movieId] : null;
}

function getMovie( $title )
{
    $result['isSuccess'] = false;

    $movie = getMovieFromFile( $title );
    if ( $movie )
    {
        $result = $movie;
        $movieData = getMovieFromIMDB( $result['id'] );
        $result['image'] = $movieData['image'];
        $result['rtScore'] = $movieData['rtScore'];
        $result['isSuccess'] = true;
    }
    return $result;
}

function getList( $fileName )
{
    $file = fopen( $fileName, "r" );
    $columns = getColumns( fgetcsv( $file ) );
    $movies = createEntryObjectList( $file, $columns, function( $row, $columns ) {
        return [
            "id"     => $row[$columns['iIndex']],
            "title"  => $row[$columns['tIndex']],
            "year"   => $row[$columns['yIndex']],
            "review" => $row[$columns['cIndex']],
            "rating" => $row[$columns['rIndex']],
            "image"  => $row[$columns['pIndex']]
        ];
    });
    fclose( $file );

    return $movies;
}

function getMovieList()
{
    $_SESSION['fullMovieList'] = getList( getPath( "ratings.csv" ) );
    return $_SESSION['fullMovieList'];
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

function saveSearch( $title, $type )
{
    $file = fopen( getPath( "searches.txt" ), "a" );
    fwrite( $file, $type . " - " . $title . "\n" );
    fclose( $file );
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
        $result = $action( $_POST['title'], $_POST['type'] );
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