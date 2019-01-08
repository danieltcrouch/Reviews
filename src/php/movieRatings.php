<?php
function getPath( $fileName )
{
    $newDirectory = $_SERVER["DOCUMENT_ROOT"] . "/archive/";
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
    $result['yIndex'] = array_search( "Year", $firstRow, true );
    $result['cIndex'] = array_search( "Review", $firstRow, true );
    $result['rIndex'] = array_search( "Rating", $firstRow, true );
    $result['pIndex'] = array_search( "Image", $firstRow, true );

    return $result;
}

function getList( $fileName )
{
    $file = fopen( $fileName, "r" );
    $columns = getColumns( fgetcsv( $file ) );

    $movies = [];
    $row = fgetcsv( $file );
    while ( $row !== false )
    {
        $id = $row[$columns['iIndex']];
        $movies[$id] = [ "title" => $row[$columns['tIndex']], "year" => $row[$columns['yIndex']], "review" => $row[$columns['cIndex']], "rating" => $row[$columns['rIndex']], "poster" => $row[$columns['pIndex']] ];
        $row = fgetcsv( $file );
    }
    fclose( $file );

    return $movies;
}

function getRatingsIfCleared()
{
    $fileHandle = file( "../resources/ratings.csv", FILE_SKIP_EMPTY_LINES );
    $count = count( $fileHandle );

    if ( $count < 2 )
    {
        $ratingsFile = "";
        $ratingsDate = date( "Y-m-d H:i:s", strtotime("-1 year") );
        $archiveDirectory = $_SERVER["DOCUMENT_ROOT"] . "/archive/";

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
            copy( $archiveDirectory . $ratingsFile, "../resources/ratings.csv" );
        }
    }
}

function getMovieList()
{
    getRatingsIfCleared();
    return getList( "../resources/ratings.csv" );
}

function getDisneyList()
{
    return getList( "../resources/Disney.csv" );
}

function getMarvelList()
{
    return getList( "../resources/Marvel.csv" );
}

function getStarWarsList()
{
    return getList( "../resources/StarWars.csv" );
}

function compareTitles( $searchTitle, $rowTitle )
{
    $result = false;
    if ( stripos( $rowTitle, $searchTitle ) !== false ) //todo - develop for more sophisticated search
    {
        $result = true;
    }
    return $result;
}

function getMovieFromFile( $title )
{
    $result['isSuccess'] = false;

    $file = fopen( "../resources/ratings.csv", "r" );
    $columns = getColumns( fgetcsv( $file ) );

    $row = fgetcsv( $file );
    while ( $row !== false )
    {
        if ( compareTitles( $title, trim( $row[$columns['tIndex']] ) ) )
        {
            $result['isSuccess'] = true;
            $result['title'] = $row[$columns['tIndex']];
            $result['year'] = $row[$columns['yIndex']];
            $result['review'] = $row[$columns['cIndex']];
            $result['rating'] = $row[$columns['rIndex']];

            $id = $row[$columns['iIndex']];
            $movieData = getMovieFromIMDB( $id );
            $result['poster'] = $movieData['poster'];
            $result['rtScore'] = $movieData['rtScore'];
            break;
        }
        $row = fgetcsv( $file );
    }

    fclose( $file );

    return $result;
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

function saveMovieToWatch( $title )
{
    $file = fopen( getPath( "ToWatch.txt" ), "a" );
    fwrite( $file, $title . "\n" );
    fclose( $file );
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