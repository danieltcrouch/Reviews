var movieList = {
    watch:  [],
    title:  [],
    year:   [],
    rating: []
};
var genres = [];
var tenLists = [];
var disneyList = [];
var marvelList = [];
var starWarsList = [];

var bookList = {
    read:   [],
    title:  [],
    year:   [],
    rating: []
};
var favoritesList = [];

var hasFinalBookListReturned = false;

function toggleMovieSubMenu()
{
    $('#movieSubMenu').toggle();
}

function toggleBookSubMenu()
{
    $('#bookSubMenu').toggle();
}

function getListFromType( type )
{
    type = type.toLowerCase();
    var result;
    switch ( type )
    {
    case "d":
    case "disney":
        result = disneyList;
        break;
    case "m":
    case "mcu":
    case "marvel":
        result = marvelList;
        break;
    case "s":
    case "sw":
    case "starwars":
    case "star wars":
        result = starWarsList;
    }
    return result;
}


/*********************FIND***********************/

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
    showWarning();
    $.post(
        "php/reviews.php",
        {
            action: "getMovieByTitle",
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
                        ( (response.review === "***" ) ? "No Review" : response.review ) + "<br/>" +
                        "<strong>" + response.rating + "/10</strong> (" + response.rtScore +
                        ( (response.rtScore === "--%") ? ")" : " <img src='" + rtImage + "' style='position: relative; top: 5px' height='24px' alt='RT Logo'>)" ) +
                        "<br/><br/>" +
                        "<img src='" + response.image + "' height='300px' alt='Movie Poster'>";
        showMessage( "Movie Found", innerHTML );
    }
    else
    {
        showToaster( "Movie not found!<br />Maybe I should watch it" );
    }
    saveSearch( $('#findMovie').val(), "Movie" );
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
    showWarning();
    $.post(
        "php/reviews.php",
        {
            action: "getBookByTitle",
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
                        "<img src='" + response.image + "' height='300px' alt='Book Cover'>";
        showMessage( "Book Found", innerHTML );
    }
    else
    {
        showToaster( "Book not found!<br />Maybe I should read it" );
    }
    saveSearch( $('#findBook').val(), "Book" );
}

function showWarning()
{
    if ( !hasFinalBookListReturned )
    {
        showToaster( "Background information loading...<br />Response time may be slow..." );
    }
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


/*******************POPULATE*********************/


function populateMovieList()
{
    $.post(
        "php/reviews.php",
        { action: "getFullMovieList" },
        parseMovies
    );
}

function populateTenList()
{
    $.post(
        "php/reviews.php",
        { action: "getTenList" },
        parseTen
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

function populateBookList()
{
    $.post(
        "php/reviews.php",
        { action: "getTempFullBookList" },
        function( response ) {
            parseBooks( response );
            $.post(
                "php/reviews.php",
                { action: "getFullBookList" },
                function( response ) { hasFinalBookListReturned = true; parseBooks( response ); }
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
            parseFavorites( response );
            $.post(
                "php/reviews.php",
                { action: "getFavoritesList" },
                parseFavorites
            );
        }
    );
}


/*********************PARSE**********************/


function parseMovies( response )
{
    movieList.watch = JSON.parse( response );

    for ( var index = 0; index < movieList.watch.length; index++ )
    {
        var movie = movieList.watch[index];
        movieList.watch[index].review = ( movie.review === "***" || movie.review === "" ) ? "No Review" : movie.review;
    }

    movieList.title =   sortList( Array.from(movieList.watch), "title" );
    movieList.year =    sortList( Array.from(movieList.watch), "year" );
    movieList.rating =  sortList( Array.from(movieList.watch), "rating" );

    displayMovies( "watch" );
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

    $('#Movies').html( getMovieDisplay( movies ) );
}

function getMovieDisplay( movies )
{
    var result = "";
    for ( var i = 0; i < movies.length; i++ )
    {
        var index = i + 1;
        var movie = movies[i];
        result += "<div>" + index + ". <strong>" + movie.title + "</strong> (" + movie.year + ") - <strong>" +
                  movie.rating + "/10</strong> - " + movie.review + "</div>";
    }
    return result;
}

function parseTen( response )
{
    tenLists = JSON.parse( response );
    genres = tenLists.map( function(g) { return { id: g.id, title: g.title }; } );

    for ( var i = 0; i < tenLists.length; i++ )
    {
        var tenDiv = $('#TenContainer');
        var genreId = tenLists[i].id;
        var genre   = tenLists[i].title;
        var tenList = tenLists[i].list;

        tenList.forEach( function( movie ) {
            movie.review = ( movie.review === "***" || movie.review === "" ) ? "No Review" : movie.review;
        } );

        tenDiv.append( "<div id=\"" + genreId + "\" class=\"center textBlock\" style=\"display: none\"></div>" );
        $('#' + genreId).html( "<div class='subtitle'>" + genre + "</div>" );
        $('#' + genreId).html( getMovieDisplay( tenList ) ); //todo - do you want to display images?
    }
}

function parseRankings( type, response )
{
    var list = JSON.parse( response );
    switch ( type )
    {
    case "Disney":
        disneyList = list;
        break;
    case "Marvel":
        marvelList = list;
        break;
    case "StarWars":
        starWarsList = list;
        break;
    }

    for ( var index = 0; index < list.length; index++ )
    {
        var movie = list[index];
        list.review = ( movie.review === "***" || movie.review === "" ) ? "No Review" : movie.review;
    }

    displayRankings( list, type );
}

function displayRankings( list, type )
{
    var movieDisplay = "";
    for ( var index = 0; index < list.length; index++ )
    {
        var movie = list[index];
        movie.review = ( movie.review === "***" ) ? "No Review" : movie.review;
        movieDisplay += "<div>" + (index+1) + ". <strong>" + movie.title + "</strong> (" + movie.year + ") - " + movie.review + "</div>" +
                        "<img src='" + movie.image + "' height='300px' alt='Movie Poster' /><br/><br/>";
    }
    $('#' + type).html( movieDisplay );
}

//BOOK ********************

function parseBooks( response )
{
    bookList.read = JSON.parse( response );

    for ( var index = 0; index < movieList.watch.length; index++ )
    {
        var movie = movieList.watch[index];
        movieList.watch.review = ( movie.review === "***" || movie.review === "" ) ? "No Review" : movie.review;
    }

    bookList.title =   sortList( Array.from(bookList.read), "title" );
    bookList.year =    sortList( Array.from(bookList.read), "year" );
    bookList.rating =  sortList( Array.from(bookList.read), "rating" );

    displayBooks( "read" );
}

function displayBooks( sortType )
{
    var books;
    switch ( sortType )
    {
    case "year":
        books = bookList.year;
        break;
    case "rating":
        books = bookList.rating;
        break;
    case "title":
        books = bookList.title;
        break;
    case "read":
    default:
        books = bookList.read;
    }

    var bookDisplay = "";
    for ( var index = 0; index < books.length; index++ )
    {
        var book = books[index];
        var yearDisplay = book.year ? " (" + book.year + ")" : "";
        bookDisplay += "<div>" + (index + 1) + ". <a class='link' href='" + book.url + "'>" + book.title + "</a>, " + book.author + yearDisplay +
                       " - <strong>" + book.rating + "/5</strong> - " + book.review + "</div>";
    }
    $('#Books').html( bookDisplay );
}

function parseFavorites( response )
{
    favoritesList = JSON.parse( response );
    for ( var index = 0; index < favoritesList.length; index++ )
    {
        var book = favoritesList[index];
        favoritesList.review = ( book.review === "***" || book.review === "" ) ? "No Review" : book.review;
    }
    displayFavorites();
}

function displayFavorites()
{
    var bookDisplay = "";
    for ( var index = 0; index < favoritesList.length; index++ )
    {
        var book = favoritesList[index];
        var yearDisplay = book.year ? " (" + book.year + ")" : "";
        bookDisplay += "<div>" + (index + 1) + ". <a class='link' href='" + book.url + "'>" + book.title + "</a>, " + book.author + yearDisplay +
                       " - <strong>" + book.rating + "/5</strong> - " + book.review + "</div>" +
                       "<img src='" + book.image + "' height='300px' alt='Book Cover' /><br/><br/>";
    }
    $('#Favorites').html( bookDisplay );
}

function sortList( list, sortType )
{
    return list.sort( function(a, b) {
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


/********************DISPLAY*********************/


function showSection()
{
	var anchorSections = document.URL.split('#');
	var anchor = (anchorSections.length > 1) ? anchorSections[1] : null;
	if ( anchor )
    {
        switch (anchor)
        {
        case "Movies":
        case "MovieContainer":
            showMovieList();
            break;
        case "Disney":
        case "DisneyContainer":
            showDisneyList();
            break;
        case "Marvel":
        case "MarvelContainer":
            showMarvelList();
            break;
        case "StarWars":
        case "StarWarsContainer":
            showSWList();
            break;
        case "Books":
        case "BookContainer":
            showBookList();
            break;
        case "Favorites":
        case "FavoritesContainer":
            showFavoritesList();
            break;
        }
    }
}

function showMovieList()
{
    hideAll();

    deselectAllRadioButtons( "movieSorting" );
    displayMovies( "watch" );

    $('#MovieContainer').show();
    scrollToId( "MovieContainer" );
}

function showTenList()
{
    hideAll();

    openTenModal( genres, function( genre ) {
        if ( genre )
        {
            $('#TenContainer').show();
            $('#' + genre).show();
            scrollToId( "TenContainer" );
        }
    } );
}

function showDisneyList()
{
    hideAll();
    $('#DisneyContainer').show();
    scrollToId( "DisneyContainer" );
}

function showMarvelList()
{
    hideAll();
    $('#MarvelContainer').show();
    scrollToId( "MarvelContainer" );
}

function showSWList()
{
    hideAll();
    $('#StarWarsContainer').show();
    scrollToId( "StarWarsContainer" );
}

function showBookList()
{
    hideAll();

    deselectAllRadioButtons( "bookSorting" );
    displayBooks( "read" );

    $('#BookContainer').show();
    scrollToId( "BookContainer" );
}

function showFavoritesList()
{
    hideAll();
    $('#FavoritesContainer').show();
    scrollToId( "FavoritesContainer" );
}

function hideAll()
{
    $('#MovieContainer').hide();
    $('#DisneyContainer').hide();
    $('#MarvelContainer').hide();
    $('#StarWarsContainer').hide();
    $('#BookContainer').hide();
    $('#FavoritesContainer').hide();

    hideTen();
}

function hideTen()
{
    genres.forEach( function(genre) {
        $('#' + genre.id).hide();
    });
}


/********************RANKING*********************/


//Compare logic found in compare.js

function displayAverageRanking( type )
{
    $.post(
        "php/database.php",
        {
            action:    "getAverageRanking",
            type:      type
        },
        function ( response ) {
            list = JSON.parse( response );
            displayAverageRankingCallback( list, type );
        }
    );
}

function displayAverageRankingCallback( list, type )
{
    var fullList = getListFromType( type );
    var averageList = list.map( id => fullList.find( movie => { return movie.id === id } ) );

    var rankingImages = "";
    for ( var i = 0; i < averageList.length; i++ )
    {
        var title = averageList[i].title.replace(/'/g, "&apos;").replace(/"/g, "&quot;");
        rankingImages += "<div> <img style='width: 5em' src='" + averageList[i].image + "' title='" + title + "' alt='" + title + "'> </div>";
    }

    showMessage( "Average Rankings", rankingImages );
}