<?php
include_once( "utility.php" );

function saveSearch( $title, $type )
{
    appendToFile( getPath( "searches.txt" ), $type . " - " . $title . "\n", false );
}

/**********************BOOK**********************/


include_once( "utilityBook.php" );

function getFullBookList()
{
    return getBookListFromGoodreads( "read" );
}

function getFavoritesList()
{
    return getBookListFromGoodreads( "favorites", "title" );
}

function getTempFullBookList()
{
    return getBookListFromFileByShelf( "read" );
}

function getTempFavoritesList()
{
    return getBookListFromFileByShelf( "favorites" );
}

function getBookByTitle( $title )
{
    $id = getBookIdFromFile( $title );
    $result = getReviewedBookFromGoodreads( $id );
    return $result;
}


/**********************MOVIE*********************/


include_once( "utilityMovie.php" );

function getFullMovieList()
{
    return array_reverse( getMovieListFromFile( getPath( "ratings.csv" ) ) );
}

function getGenreLists()
{
    $genres = getGenresFromFile();
    return getMovieListsFromFiles( $genres, "genre-" );
}

function getDisneyList()
{
    return getMovieListFromFile( getPath( "franchise-Disney.csv" ) );
}

function getMarvelList()
{
    return getMovieListFromFile( getPath( "franchise-Marvel.csv" ) );
}

function getStarWarsList()
{
    return getMovieListFromFile( getPath( "franchise-StarWars.csv" ) );
}

function getMovieByTitle( $title )
{
    $movie = getMovieFromFullList( $title, "title" ); //todo - returns less exact matches from movies I've seen over movies I haven't seen ("Philadelphia" returns Philadelphia Story instead of Philadelphia)
    $movie = addImdbFields( $movie );
    $movie['isSuccess'] = (bool) $movie['id'];
    return $movie;
}

function getMovieById( $id )
{
    $movie = getMovieFromFullList( $id );
    $movie = addImdbFields( $movie );
    $movie['isSuccess'] = (bool) $movie['id'];
    return $movie;
}


/*********************************************************************************************************************/


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
    elseif ( isset( $_POST['id'] ) )
    {
        $result = $action( $_POST['id'] );
    }
	else
	{
		$result = $action();
	}

	echo json_encode( $result );
}
?>
