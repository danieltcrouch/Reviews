<?php
include_once( "utility.php" );

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

function getRatingsIfCleared()
{
    $fileHandle = file( "../archive/ratings.csv", FILE_SKIP_EMPTY_LINES );
    $count = count( $fileHandle );

    if ( $count < 2 )
    {
        $ratingsFile = "";
        $ratingsDate = date( "Y-m-d H:i:s", strtotime("-1 year") );
        $archiveDirectory = "../archive/";

        if ( is_dir( $archiveDirectory ) && $dirHandle = opendir( $archiveDirectory ) )
        {
            while ( ( $file = readdir( $dirHandle ) ) !== false )
            {
                if ( stripos( $file, "ratings " ) !== false )
                {
                    $fileDate = substr( $file, strlen( "ratings " ) );
                    $fileDate = str_replace( ".csv", "", $fileDate );
                    if ( $fileDate > $ratingsDate )
                    {
                        $ratingsFile = $file;
                    }
                }
            }
            closedir( $dirHandle );
        }

        if ( $ratingsFile )
        {
            copy( $archiveDirectory . $ratingsFile, "../archive/ratings.csv" );
        }
    }
}

function getMovieList()
{
    getRatingsIfCleared();
    return getList( "../archive/ratings.csv" );
}

function getDisneyList()
{
    return getList( "../archive/rank-Disney.csv" );
}

function getMarvelList()
{
    return getList( "../archive/rank-Marvel.csv" );
}

function getStarWarsList()
{
    return getList( "../archive/rank-StarWars.csv" );
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

    $file = fopen( "../archive/ratings.csv", "r" );
    $columns = getColumns( fgetcsv( $file ) );

    $movies = createEntryObjectList( $file, $columns, function( $row, $columns ) {
        return [
            "id"     => $row[$columns['iIndex']],
            "title"  => $row[$columns['tIndex']],
            "year"   => $row[$columns['yIndex']],
            "review" => $row[$columns['cIndex']],
            "rating" => $row[$columns['rIndex']]
        ];
    });

    $movieId = findEntry( $movies, $title );
    fclose( $file );

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

function saveMovieToWatch( $title )
{
    saveFailedSearch( "ToWatch", $title );
}

if ( isset( $_POST['action'] ) && function_exists( $_POST['action'] ) )
{
	$action = $_POST['action'];
    $result = null;

    if ( isset( $_POST['title'] ) )
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