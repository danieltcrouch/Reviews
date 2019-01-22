<?php
include_once( "utility.php" );

function requestGoodReads( $endpoint, array $params = array() )
{
    $params['key'] = "nyo2HHxoCfMtYFnzaGzaaQ"; //https://www.goodreads.com/api/keys
    $url = "https://www.goodreads.com/" . $endpoint . "?" . ( !empty( $params ) ? http_build_query( $params, "", "&" ) : "" );
    $response = file_get_contents( $url );

    $xmlArray = (array)simplexml_load_string( $response, 'SimpleXMLElement', LIBXML_NOCDATA );
    return ( empty($xmlArray) || $xmlArray[0] === false ) ? $response : json_decode( json_encode( $xmlArray ), 1 );
}

function getBookIdFromFile( $title )
{
    $file = fopen( getPath( "book-read.csv" ), "r" );
    $columns = getColumns( fgetcsv( $file ) );
    $books = createEntryList( $file, $columns['iIndex'], $columns['tIndex'] );
    $bookId = findEntry( $books, $title );
    fclose( $file );
    return $bookId;
}

function getBook( $title )
{
    $response = requestGoodReads( 'review/show_by_user_and_book', [
        'user_id' => "55277264",
        'book_id' => getBookIdFromFile( $title )
    ]);

    return [
        'isSuccess' => ($response) ? true : false,
        'title'     => $response['review']['book']['title'],
        'year'      => $response['review']['book']['publication_year'],
        'author'    => $response['review']['book']['authors']['author']['name'],
        'cover'     => $response['review']['book']['image_url'],
        'grRating'  => $response['review']['book']['average_rating'],
        'rating'    => $response['review']['rating'],
        'review'    => $response['review']['body']
    ];
}

function getCleanedReview( $review )
{
    $result = is_array( $review ) ? implode( $review ) : $review;
    $result = trim( $result );
    if ( strlen( $result ) > 1000 )
    {
        $result = substr( $result, 0, 1000 ) . "...";
    }

    $result = str_replace( "<a ", "<a class='link'", $result );

    $result = empty( $result ) ? "No Review" : $result;
    return $result;
}

function getCDATA( $url )
{
    return preg_replace( '/^\s*\/\/<!\[CDATA\[([\s\S]*)\/\/\]\]>\s*\z/', '$1', $url );
}

function getImages()
{
    $file = fopen( getPath( "book-images.csv" ), "r" );
    $columns = getColumns( fgetcsv( $file ) );
    $images = createEntryList( $file, $columns['iIndex'], $columns['pIndex'] );
    fclose( $file );
    return $images;
}

function getList( $shelf, $sortType, $includeImages )
{
    $result = [];

    $index = 1;
    $books = [];
    do {
        $response = requestGoodReads( 'review/list', [
            'v'         => "2",
            'id'        => "55277264",
            'shelf'     => $shelf,
            'sort'      => $sortType,
            'page'      => $index,
            'per_page'  => "100"
        ]);
        $books = array_merge( $books, $response['reviews']['review'] );
        $total = $response['reviews']['@attributes']['total'] ;
        $index++;
    } while ( $total > count( $books ) );

    $file = null;
    if ( $shelf )
    {
        $file = fopen( getPath( "book-$shelf.csv" ), "w" );
        fputcsv( $file, array( "Title", "Author", "ID", "Year", "Rating", "Review", "Image" ) );
    }

    $images = [];
    if ( $includeImages )
    {
        $images = getImages();
    }

    $index = 1;
    foreach ( $books as $book )
    {
        if ( $book['book']['id'] )
        {
            $year = $book['book']['publication_year'];
            $year = is_numeric( $year ) ? $year : "";
            $displayYear = is_numeric( $year ) ? "($year) " : "";

            $id = $book['book']['id'];
            $title = $book['book']['title'];
            $author = $book['book']['authors']['author']['name'];
            $rating = $book['rating'];
            $review = getCleanedReview( $book['body'] );
            $url = getCDATA( $book['url'] );
            $image = ( $images[$id] ) ? $images[$id] : $book['book']['image_url'];
            $item = "<div>$index. <a class='link' href='$url'>$title</a>, $author $displayYear- <strong>$rating/5</strong> - $review</div>";
            $item .= $includeImages ? "<img src='$image' height='300px' alt='Book Cover' /><br/><br/>" : "";

            $index++;
            array_push( $result, $item );
            if ( $file )
            {
                fputcsv( $file, array( $title, $author, $id, $year, $rating, $review, $image ) );
            }
        }
    }

    if ( $file )
    {
        fclose( $file );
    }

    return $result;
}

function getBookList()
{
    return (object)[
        "read"  => getList( "read", "date_read", false ),
        "title" => getList( "read", "title", false )
    ];
}

function getFavoritesList()
{
    return getList( "favorites", "date_read", true );
}

function getTempList( $shelf, $includeImages )
{
    $result = [];
    $file = fopen( getPath( "book-$shelf.csv" ), "r" );
    if ( $file )
    {
        $columns = getColumns(fgetcsv($file));

        $index = 1;
        $row = fgetcsv($file);
        while ($row !== false) {
            $year = $row[$columns['yIndex']];
            $year = is_numeric($year) ? $year : "";
            $displayYear = is_numeric($year) ? "($year) " : "";

            $title = $row[$columns['tIndex']];
            $author = $row[$columns['aIndex']];
            $rating = $row[$columns['rIndex']];
            $image = $row[$columns['pIndex']];
            $review = getCleanedReview($row[$columns['cIndex']]);
            $item = "<div>$index. <strong>$title</strong>, $author $displayYear- <strong>$rating/5</strong> - $review</div>";
            $item .= $includeImages ? "<img src='$image' height='300px' alt='Book Cover' /><br/><br/>" : "";

            $index++;
            array_push($result, $item);
            $row = fgetcsv($file);
        }

        fclose($file);
    }
    return $result;
}

function getTempBookList()
{
    return getTempList( "read", false );
}

function getTempFavoritesList()
{
    return getTempList( "favorites", true );
}

function saveBookToRead( $title )
{
    saveFailedSearch( "ToRead", $title );
}

if ( isset( $_POST['action'] ) && function_exists( $_POST['action'] ) )
{
	$action = $_POST['action'];
    $result = null;

    if ( isset( $_POST['title'] ) && isset( $_POST['author'] ) )
	{
		$result = $action( $_POST['title'], $_POST['author'] );
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