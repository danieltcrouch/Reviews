<?php
include_once( "utility.php" );

function getBookList()
{
    if ( empty($GLOBALS['fullBookList']) )
    {
        $GLOBALS['fullBookList'] = getBookListFromFile( getPath( "book-read.csv" ) );
    }
    return $GLOBALS['fullBookList'];
}


/********************INTERNET********************/


function requestGoodReads( $endpoint, array $params = array() )
{
    $params['key'] = "nyo2HHxoCfMtYFnzaGzaaQ"; //https://www.goodreads.com/api/keys
    $paramString = ( !empty( $params ) ? http_build_query( $params, "", "&" ) : "" );
    $url = "https://www.goodreads.com/$endpoint?$paramString";
    $response = file_get_contents( $url );
    $xmlArray = (array)simplexml_load_string( $response, 'SimpleXMLElement', LIBXML_NOCDATA );
    return ( empty($xmlArray) ) ? $response : json_decode( json_encode( $xmlArray ), 1 );
}

function getBookFromGoodreadsById( $id )
{
    $response = requestGoodReads( "book/show/$id.xml", [] );
    return getBookData( $response );
}

function getBookFromGoodreadsByTitle( $title )
{
    $response = requestGoodReads( 'book/title.xml', [
        'title' => $title
    ]);
    return getBookData( $response );
}

function getReviewedBookFromGoodreads( $id )
{
    $response['isSuccess'] = false;
    if ( $id )
    {
        $response = requestGoodReads( 'review/show_by_user_and_book', [
            'user_id' => "55277264",
            'book_id' => $id
        ]);
        $response = getBookData( $response['review'] );
    }

    return $response;
}

function getListFromGoodreads( $shelf, $sortType )
{
    $index = 1;
    $books = [];
    do {
        $response = requestGoodReads('review/list', [
            'v' => "2",
            'id' => "55277264",
            'shelf' => $shelf,
            'sort' => $sortType,
            'page' => $index,
            'per_page' => "100"
        ]);
        $books = array_merge( $books, $response['reviews']['review'] );
        $total = $response['reviews']['@attributes']['total'];
        $index++;
    } while ( $total > count($books) );
    return $books;
}

function getBookListFromGoodreads( $shelf, $sortType = "date_read" )
{
    $result = [];
    $books = getListFromGoodreads( $shelf, $sortType );
    $images = getImages();

    $index = 0;
    foreach ( $books as $book )
    {
        $id = $book['book']['id'];
        if ( $id )
        {
            $image = array_key_exists($id,$images) ? $images[$id] : NULL;
            $result[$index] = getBookData( $book, $image );
            $index++;
        }
    }

    if ( $shelf === "read" )
    {
        $GLOBALS['fullBookList'] = $result;
    }
    saveFullBooksToFile( $shelf, $result );
    return $result;
}

function getBookData( $response, $imageOverride = NULL )
{
    $isSuccess  = ($response) ? true : false;
    $id         = $response['book']['id'];
    $index      = array_search( $id, array_column( getBookList(), 'id' ) );
    $title      = $response['book']['title'];
    $year       = getBookYear( $response['book'], $index );
    $author     = getAuthor( $response['book']['authors'] );
    $image      = $imageOverride ?? $response['book']['image_url'];
    $review     = getCleanedReview( array_key_exists( 'body', $response ) ? $response['body'] : "" );
    $rating     = array_key_exists( 'rating', $response ) ? $response['rating'] : "";
    $grRating   = $response['book']['average_rating'];
    $url        = getCDATA( array_key_exists( 'url', $response ) ? $response['url'] : "" );

    return [
        'isSuccess' => $isSuccess,
        'id'        => $id,
        'title'     => $title,
        'year'      => $year,
        'author'    => $author,
        'image'     => $image,
        'review'    => $review,
        'rating'    => $rating,
        'grRating'  => $grRating,
        'url'       => $url
    ];
}

function getBookYear( $responseBook, $index )
{
    $result = "";
    $result = ( array_key_exists( 'work', $responseBook ) && array_key_exists( 'original_publication_year', $responseBook['work'] ) ) ? $responseBook['work']['original_publication_year'] : $result;
    if ( is_numeric( $result ) )
    {
        $book = is_numeric($index) ? getBookList()[$index] : [];
        $result = array_key_exists( 'year', $book ) ? $book['year'] : $result;
        if ( is_numeric( $result ) )
        {
            $result = array_key_exists( 'publication_year', $responseBook ) ? $responseBook['publication_year'] : $result;
        }
    }
    return $result;
}


/********************FILE I/O********************/


function saveFullBooksToFile( $shelf, $books )
{
    saveListToFile(
        getPath( "book-$shelf.csv" ),
        array( "Title", "Author", "ID", "Year", "Rating", "Review", "Image", "URL" ),
        $books,
        function( $book ) {
            return array( $book['title'], $book['author'], $book['id'], $book['year'], $book['rating'], $book['review'], $book['image'], $book['url'] );
    } );
}

function getBookIdFromFile( $title )
{
    $bookTitles = [];
    array_walk( getBookList(), function($value) use( &$bookTitles ) {
        $bookTitles[$value['id']] = $value['title'];
    });
    $bookId = findEntry( $bookTitles, $title );

    return $bookId;
}

function getBookListFromFileByShelf( $shelf )
{
    return array_values( ( $shelf === "read" ) ? getBookList() : getBookListFromFile( getPath( "book-$shelf.csv" ) ) );
}

function getBookListFromFile( $fileName )
{
    return getListFromFile( $fileName, function( $row, $columns ) {
        return [
            "id"     => $row[$columns['iIndex']],
            "title"  => $row[$columns['tIndex']],
            "author" => $row[$columns['aIndex']],
            "year"   => $row[$columns['yIndex']],
            "review" => $row[$columns['cIndex']],
            "rating" => $row[$columns['rIndex']],
            "image"  => $row[$columns['pIndex']],
            "url"    => $row[$columns['uIndex']]
        ];
    } );
}


/*********************OTHER**********************/


function getImages()
{
    $file = fopen( getPath( "book-images.csv" ), "r" );
    $columns = getColumns( fgetcsv( $file ) );
    $images = createEntryList( $file, $columns['iIndex'], $columns['pIndex'] );
    fclose( $file );
    return $images;
}

function getAuthor( $authorsData )
{
    return $authorsData['author']['name']; //to retrieve other authors, will have to refactor from array to simpleXmlElement
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
?>
