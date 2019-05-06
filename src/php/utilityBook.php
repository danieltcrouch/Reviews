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
    return ( empty($xmlArray) || $xmlArray[0] === false ) ? $response : json_decode( json_encode( $xmlArray ), 1 );
}

function getBookFromGoodreadsById( $id )
{
    $response = requestGoodReads( "book/show/$id.xml", [] );

    return [
        'isSuccess' => ($response) ? true : false,
        'id'        => $response['book']['id'],
        'title'     => $response['book']['title'],
        'year'      => $response['book']['publication_year'],
        'author'    => $response['book']['authors']['author']['name'],
        'image'     => $response['book']['image_url'],
        'grRating'  => $response['book']['average_rating']
    ];
}

function getBookFromGoodreadsByTitle( $title )
{
    $response = requestGoodReads( 'book/title.xml', [
        'title' => $title
    ]);

    return [
        'isSuccess' => ($response) ? true : false,
        'id'        => $response['book']['id'],
        'title'     => $response['book']['title'],
        'year'      => $response['book']['publication_year'],
        'author'    => $response['book']['authors']['author']['name'],
        'image'     => $response['book']['image_url'],
        'grRating'  => $response['book']['average_rating']
    ];
}

function getReviewedBookFromGoodreads( $id )
{
    $response = null;
    if ( $id )
    {
        $response = requestGoodReads( 'review/show_by_user_and_book', [
            'user_id' => "55277264",
            'book_id' => $id
        ]);
    }

    return [
        'isSuccess' => ($response) ? true : false,
        'id'        => $response['review']['book']['id'],
        'title'     => $response['review']['book']['title'],
        'year'      => $response['review']['book']['publication_year'],
        'author'    => $response['review']['book']['authors']['author']['name'],
        'image'     => $response['review']['book']['image_url'],
        'grRating'  => $response['review']['book']['average_rating'],
        'rating'    => $response['review']['rating'],
        'review'    => getCleanedReview( $response['review']['body'] )
    ];
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
        if ( $book['book']['id'] )
        {
            $id = $book['book']['id'];
            $result[$index] = [
                "title"  => $book['book']['title'],
                "author" => $book['book']['authors']['author']['name'],
                "id"     => $id,
                "year"   => is_numeric( $book['book']['publication_year'] ) ? $book['book']['publication_year'] : "",
                "rating" => $book['rating'],
                "review" => getCleanedReview( $book['body'] ),
                "image"  => ( $images[$id] ) ? $images[$id] : $book['book']['image_url'],
                "url"    => getCDATA( $book['url'] )
            ];
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
