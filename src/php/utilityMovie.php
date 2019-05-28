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
        $paramString = http_build_query( $params, "", "&", PHP_QUERY_RFC3986 );
        $url = "http://www.omdbapi.com/?$paramString&y=&plot=short&r=json&apikey=522c6900"; //OMDB API requires API Key -> go to their site if this one stops working
        $response = json_decode(file_get_contents($url));
        $result = getDataFromResponse($response);
    }
    return $result;
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
        $result['date'] = date("m/d/Y", strtotime( $response->Released ) );
        $result['image'] = $response->Poster;
        $result['rtScore'] = $response->Ratings[1]->Value;
    }
    return $result;
}

function getMovieFromImdbByTitle( $title )
{
    $title = trim( $title );
    $result = requestIMDB( [ 't' => $title ] );
    $result['search'] = $title;
    return $result;
}

function getMovieFromImdbById( $id )
{
    return requestIMDB( [ 'i' => $id ] );
}

function addImdbFields( $movie )
{
    $movieData = getMovieFromImdbById( $movie['id'] );
    $movie['image']   = $movieData['image'];
    $movie['rtScore'] = $movieData['rtScore'];
    return $movie;
}


/********************FILE I/O********************/


function getMovieFromFullList($value, $type = 'id' )
{
    $result = null;
    $movies = getMovieList();
    $index = ( $type === "id" ) ? getIndexFromListById( $movies, $value ) : getIndexFromListByTitle( $movies, $value );
    if ( is_numeric($index) && $index >= 0 )
    {
        $result = $movies[$index];
        $result['index'] = $index;
    }
    return  $result;
}

function getMovieListFromFile( $fileName )
{
    return getListFromFile( $fileName, function( $row, $columns ) {
        return [
            "id"     => $row[$columns['iIndex']],
            "title"  => $row[$columns['tIndex']],
            "year"   => $row[$columns['yIndex']],
            "date"   => $row[$columns['dIndex']],
            "review" => $row[$columns['cIndex']],
            "rating" => $row[$columns['rIndex']],
            "image"  => $row[$columns['pIndex']]
        ];
    } );
}

function getMovieListsFromFiles( $fileNames, $prefix )
{
    $movies = [];
    foreach ( $fileNames as $fileName )
    {
        $id = array_key_exists( "id", $fileName ) ? $fileName['id'] : $fileName;
        $title = array_key_exists( "title", $fileName ) ? $fileName['title'] : $id;
        $list = getMovieListFromFile( getPath( $prefix . "$id.csv" ) );
        $movieList = [
            "id"    => $id,
            "title" => $title,
            "list"  => $list
        ];
        array_push( $movies, $movieList );
    }
    return $movies;
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
    unlink( "$fileBase 3.csv" );
    rename( "$fileBase 2.csv", "$fileBase 3.csv" );
    rename( "$fileBase 1.csv", "$fileBase 2.csv" );
    copy( $fileName, "$fileBase 1.csv" );

    $zipName = archiveFolder( "archive", "../archive" );
    rename( $zipName, "../../" . $zipName );
}

function archiveFolder( $fileName, $folderLocation )
{
    $zip = new ZipArchive();
    $zipName = $fileName . ".zip";

    $isOpen = $zip->open( $zipName, ZIPARCHIVE::CREATE );
    if ( $isOpen )
    {
        $folder = $folderLocation;
        foreach ( scandir($folder) as $file )
        {
            $file = "$folder/" . $file;
            if ( file_exists($file) && is_file($file) )
            {
                $zip->addFile($file);
            }
        }
        $zip->close();
    }

    return $zipName;
}

//RANK ********************

function getGenresFromFile()
{
    return getListFromFile( getPath( "genres.csv" ), function( $row, $columns ) {
        return [
            "id"     => $row[$columns['iIndex']],
            "title"  => $row[$columns['tIndex']]
        ];
    } );
}

function getRankMovieFromFile( $type, $list, $value, $valueType )
{
    $movies = getMovieListFromFile( getPath( "$type-$list.csv" ) );
    $index = ( $valueType === "id" ) ? getIndexFromListById( $movies, $value ) : getIndexFromListByTitle( $movies, $value );
    $result = $movies[$index];
    $result['index'] = $index + 1;
    $result['list'] = $list;
    return is_numeric( $index ) ? $result : null;
}

function getRankMovieFromFiles( $type, $value, $valueType )
{
    $movie = null;
    $lists = getLists( $type );
    foreach ( $lists as $list )
    {
        $movie = getRankMovieFromFile( $type, $list, $value, $valueType );
        if ( isset( $movie ) )
        {
            break;
        }
    }
    return $movie;
}

function saveRankMoviesToFile( $type, $list, $movies )
{
    saveListToFile(
        getPath( "$type-$list.csv" ),
        array( "Title", "ID", "Year", "Image", "Review" ),
        $movies,
        function( $movie ) {
            return array( $movie['title'], $movie['id'], $movie['year'], $movie['image'], $movie['review'] );
    } );
}


/*********************OTHER**********************/


function getLists( $type )
{
    return $type === "genre" ? getGenreListNames() : getFranchiseListNames();
}

function getGenreListNames()
{
    return getListFromFile( getPath( "genres.csv" ), function( $row, $columns ) { return $row[$columns['iIndex']]; } );
}

function getFranchiseListNames()
{
    return array( "Disney", "Marvel", "StarWars" );
}

//ARCHIVE
//function getFranchiseName( $list )
//{
//    $list = strtolower( preg_replace('/\s+/', '', $list) );
//    switch ( $list )
//    {
//        case "d":
//        case "disney":
//            $list = "Disney";
//            break;
//        case "m":
//        case "mcu":
//        case "marvel":
//            $list = "Marvel";
//            break;
//        case "s":
//        case "sw":
//        case "starwars":
//        case "star wars":
//            $list = "StarWars";
//            break;
//        default:
//            $list = null;
//    }
//    return $list;
//}

?>
