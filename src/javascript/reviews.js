var movieList = {
    watch:  [],
    title:  [],
    year:   [],
    rating: []
};
var disneyList = [];
var marvelList = [];
var starWarsList = [];
var bookList = {
    title:  [],
    read:   []
};
var favoritesList = [];

function toggleMovieSubMenu()
{
    $('#movieSubMenu').toggle();
}

function toggleBookSubMenu()
{
    $('#bookSubMenu').toggle();
}

function findMovieOnEnter( e )
{
    var charCode = (typeof e.which === "number") ? e.which : e.keyCode;
    if ( charCode === 13 )
    {
        findMovie( $('#findMovie').val() );
    }
}

function findMovie( title )
{
    $.post(
        "php/reviews.php",
        {
            action: "getMovieFromFile",
            title: title
        },
        findMovieCallback
    );
}

function findMovieCallback( response )
{
    response = JSON.parse( response );
    if ( response.isSuccess )
    {
        var rtImage = ( parseInt( response.rtScore ) > 60 ) ?
                      "https://techpolicyinstitute.org/wp-content/uploads/2017/09/la-fi-ct-rotten-tomatoes-pictures-20170723-004.jpg" :
                      "https://techpolicyinstitute.org/wp-content/uploads/2017/09/la-fi-ct-rotten-tomatoes-pictures-20170723-005.jpg" ;
        var innerHTML = "<strong>" + response.title + "</strong> (" + response.year + ")<br/>" +
                        response.review + "<br/>" +
                        "<strong>" + response.rating + "/10</strong> (" + response.rtScore +
                        ( (response.rtScore === "--%") ? ")" : "<img src='" + rtImage + "' height='24px' alt='RT Logo'>)" ) +
                        "<br/><br/>" +
                        "<img src='" + response.poster + "' height='300px' alt='Movie Poster'>";
        showMessage( "Movie Found", innerHTML );
    }
    else
    {
        showToaster( "Movie not found!<br />Maybe I should watch it" );
    }
    saveSearch( $('#findBook').val(), "Movie" );
}

function findBookOnEnter( e )
{
    var charCode = (typeof e.which === "number") ? e.which : e.keyCode;
    if ( charCode === 13 )
    {
        findBook( $('#findBook').val() );
        //showPrompt( "Author", "Enter an author for more accurate results", function(response) { findBook( $('#findBook').val(), response ); }, "Shakespeare" );
    }
}

function findBook( title, author )
{
    $.post(
        "php/reviews.php",
        {
            action: "getBook",
            title: title,
            author: author
        },
        findBookCallback
    );
}

function findBookCallback( response )
{
    response = JSON.parse( response );
    if ( response.isSuccess )
    {
        var year = isNaN( response.year ) ? "" : ( "(" + response.year + ")" );
        var review = response.review + "<br/>";
        var innerHTML = "<strong>" + response.title + "</strong>, " + response.author + " " + year + "<br/>" +
                        review + "<br/>" +
                        "<strong>" + response.rating + "/5</strong> (GoodReads: " + response.grRating + "/5)" +
                        "<br/><br/>" +
                        "<img src='" + response.cover + "' height='300px' alt='Book Cover'>";
        showMessage( "Book Found", innerHTML );
    }
    else
    {
        showToaster( "Book not found!<br />Maybe I should read it" );
    }
    saveSearch( $('#findBook').val(), "Book" );
}

function saveSearch( title, type )
{
    $.post(
        "php/reviews.php",
        {
            action: "saveSearch",
            title: title,
            type: type
        }
    );
}

function populateMovieList()
{
    $.post(
        "php/reviews.php",
        { action: "getMovieList" },
        parseMovies
    );
}

function populateDisneyList()
{
    $.post(
        "php/reviews.php",
        { action: "getDisneyList" },
        function( response ) { parseRankings( "Disney", response ); }
    );
}

function populateMarvelList()
{
    $.post(
        "php/reviews.php",
        { action: "getMarvelList" },
        function( response ) { parseRankings( "Marvel", response ); }
    );
}

function populateStarWarsList()
{
    $.post(
        "php/reviews.php",
        { action: "getStarWarsList" },
        function( response ) { parseRankings( "StarWars", response ); }
    );
}

function parseMovies( response )
{
    var movieArray = JSON.parse( response );
    movieArray = Object.keys( movieArray ).map( function( key ){
      return [key, movieArray[key]];
    });
    movieArray.reverse();

    for ( var index = 0; index < movieArray.length; index++ )
    {
        if ( movieArray[index][0] )
        {
            var movie = movieArray[index][1];
            movie.review = ( movie.review === "***" ) ? "No Review" : movie.review;
            movieList.watch.push( movie );
        }
    }

    movieList.title = sortMovies( Array.from(movieList.watch), "title" );
    movieList.year = sortMovies( Array.from(movieList.watch), "year" );
    movieList.rating = sortMovies( Array.from(movieList.watch), "rating" );

    displayMovies( "watch" );
}

function sortMovies( movies, sortType )
{
    return movies.sort( function(a, b) {
        var titleA = a.title.trim();
        var titleB = b.title.trim();
        var shortTitleA = titleA.replace(/^(((the|a|an) )|([^a-z])+)/i, "" );
        var shortTitleB = titleB.replace(/^(((the|a|an) )|([^a-z])+)/i, "" );
        var numTitleA = isNaN( parseInt( titleA ) ) ? 0 : parseInt( titleA );
        var numTitleB = isNaN( parseInt( titleB ) ) ? 0 : parseInt( titleB );
        var yearA = a.year;
        var yearB = b.year;
        var ratingA = a.rating;
        var ratingB = b.rating;

        var numTitleCompare = numTitleA - numTitleB;
        var shortTitleCompare = numTitleCompare !== 0 ? numTitleCompare : shortTitleA.localeCompare( shortTitleB );
        var longTitleCompare = titleA.localeCompare( titleB );
        var yearCompare = yearA - yearB;
        var inverseYearCompare = yearB - yearA;
        var ratingCompare = ratingA - ratingB;
        var inverseRatingCompare = ratingB - ratingA;

        var result = 0;
        switch ( sortType )
        {
        case "year":
            result = inverseYearCompare ? inverseYearCompare : ( shortTitleCompare ? shortTitleCompare : longTitleCompare );
            break;
        case "rating":
            result = inverseRatingCompare ? inverseRatingCompare : ( shortTitleCompare ? shortTitleCompare : ( yearCompare ? yearCompare : longTitleCompare ) );
            break;
        case "title":
        default:
            result = shortTitleCompare ? shortTitleCompare : ( yearCompare ? yearCompare : longTitleCompare );
        }
        return result;
    } );
}

function displayMovies( sortType )
{
    var movies;
    switch ( sortType )
    {
    case "year":
        movies = movieList.year;
        break;
    case "rating":
        movies = movieList.rating;
        break;
    case "title":
        movies = movieList.title;
        break;
    case "watch":
    default:
        movies = movieList.watch;
    }

    var movieDisplay = "";
    for ( var i = 0; i < movies.length; i++ )
    {
        var index = i + 1;
        var movie = movies[i];
        movieDisplay += "<div>" + index + ". <strong>" + movie.title + "</strong> (" + movie.year + ") - <strong>" +
                        movie.rating + "/10</strong> - " + movie.review + "</div>";
    }
    $('#Movies').html( movieDisplay );
}

function parseRankings( type, response )
{
    var movieArray = JSON.parse( response );
    movieArray = Object.keys( movieArray ).map( function( key ){
      return [key, movieArray[key]];
    });

    var movieDisplay = "";
    for ( var index = 0; index < movieArray.length; index++ )
    {
        if ( movieArray[index][0] )
        {
            var movie = movieArray[index][1];
            switch ( type )
            {
            case "Disney":
                disneyList.push( movie );
                break;
            case "Marvel":
                marvelList.push( movie );
                break;
            case "StarWars":
                starWarsList.push( movie );
            }
            movie.review = ( movie.review === "***" ) ? "No Review" : movie.review;
            movieDisplay += "<div>" + (index+1) + ". <strong>" + movie.title + "</strong> (" + movie.year + ") - " + movie.review + "</div>" +
                            "<img src='" + movie.poster + "' height='300px' alt='Movie Poster' /><br/><br/>";
        }
    }
    $('#' + type).html( movieDisplay );
}

function populateBookList()
{
    $.post(
        "php/reviews.php",
        { action: "getTempBookList" },
        function( response ) {
            try {
                var books = JSON.parse( response );
                $( '#Books' ).html( books );
                bookList.title = books;
                bookList.read = "Books are loading... May take a minute...";
            } catch (e) {}
            $.post(
                "php/reviews.php",
                { action: "getBookList" },
                parseBooks
            );
        }
    );
}

function populateFavoritesList()
{
    $.post(
        "php/reviews.php",
        { action: "getTempFavoritesList" },
        function( response ) {
            try { $( '#Favorites' ).html( JSON.parse( response ) ); } catch (e) {}
            $.post(
                "php/reviews.php",
                { action: "getFavoritesList" },
                parseFavorites
            );
        }
    );
}

function parseBooks( response )
{
    var books = JSON.parse( response );
    bookList.title = books.title;
    bookList.read = books.read;

    displayBooks( "title" );
}

function displayBooks( sortType )
{
    var books;
    switch ( sortType )
    {
    case "read":
        books = bookList.read;
        break;
    case "title":
    default:
        books = bookList.title;
    }

    $('#Books').html( books );
}

function parseFavorites( response )
{
    favoritesList = JSON.parse( response );
    $('#Favorites').html( favoritesList );
}

function showSection()
{
	var anchorSections = document.URL.split('#');
	var anchor = (anchorSections.length > 1) ? anchorSections[1] : null;
	if ( anchor )
    {
        switch (anchor)
        {
        case "Movies":
            showMovieList();
            break;
        case "Disney":
            showDisneyList();
            break;
        case "Marvel":
            showMarvelList();
            break;
        case "StarWars":
            showSWList();
            break;
        case "Books":
            showBookList();
            break;
        case "Favorites":
            showFavoritesList();
            break;
        }
    }
}

function showMovieList()
{
    hideAll();
    $('#MovieContainer').show();
    scrollToId( "Movies" );
}

function showDisneyList()
{
    hideAll();
    $('#DisneyContainer').show();
    scrollToId( "Disney" );
}

function showMarvelList()
{
    hideAll();
    $('#MarvelContainer').show();
    scrollToId( "Marvel" );
}

function showSWList()
{
    hideAll();
    $('#StarWarsContainer').show();
    scrollToId( "StarWars" );
}

function showBookList()
{
    hideAll();
    $('#BookContainer').show();
    scrollToId( "Books" );
}

function showFavoritesList()
{
    hideAll();
    $('#FavoritesContainer').show();
    scrollToId( "Favorites" );
}

function hideAll()
{
    $('#MovieContainer').hide();
    $('#DisneyContainer').hide();
    $('#MarvelContainer').hide();
    $('#StarWarsContainer').hide();
    $('#BookContainer').hide();
    $('#FavoritesContainer').hide();
}