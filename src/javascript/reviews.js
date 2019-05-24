var fullMovieList = {
    watch:  [],
    title:  [],
    year:   [],
    rating: []
};
var genreNames = [];
var genreLists = [];
var disneyList = [];
var marvelList = [];
var starWarsList = [];

var fullBookList = {
    read:   [],
    title:  [],
    year:   [],
    rating: []
};
var favoritesList = [];

var showGenreOptionsOnLoad = false;
var hasFinalBookListReturned = false;

function toggleMovieSubMenu()
{
    $('#movieSubMenu').toggle();
}

function toggleBookSubMenu()
{
    $('#bookSubMenu').toggle();
}

function getFranchiseFromId( listId )
{
    listId = listId.toLowerCase();
    var result;
    switch ( listId )
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

function findMovieById( id )
{
    showWarning();
    $.post(
        "php/reviews.php",
        {
            action: "getMovieById",
            id:     id
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
        showToaster( "Movie not found!<br />Maybe I should watch it." );
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
        showToaster( "Book not found!<br />Maybe I should read it." );
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


function populateFullMovieList()
{
    $.post(
        "php/reviews.php",
        { action: "getFullMovieList" },
        parseFullMovies
    );
}

function populateGenreLists()
{
    $.post(
        "php/reviews.php",
        { action: "getGenreLists" },
        parseGenres
    );
}

function populateDisneyList()
{
    $.post(
        "php/reviews.php",
        { action: "getDisneyList" },
        function( response ) { parseFranchise( "Disney", response ); }
    );
}

function populateMarvelList()
{
    $.post(
        "php/reviews.php",
        { action: "getMarvelList" },
        function( response ) { parseFranchise( "Marvel", response ); }
    );
}

function populateStarWarsList()
{
    $.post(
        "php/reviews.php",
        { action: "getStarWarsList" },
        function( response ) { parseFranchise( "StarWars", response ); }
    );
}

function populateFullBookList()
{
    $.post(
        "php/reviews.php",
        { action: "getTempFullBookList" },
        function( response ) {
            parseFullBooks( response );
            $.post(
                "php/reviews.php",
                { action: "getFullBookList" },
                function( response ) { hasFinalBookListReturned = true; parseFullBooks( response ); }
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


function parseFullMovies( response )
{
    fullMovieList.watch = JSON.parse( response );

    for ( var index = 0; index < fullMovieList.watch.length; index++ )
    {
        var movie = fullMovieList.watch[index];
        fullMovieList.watch[index].review = ( movie.review === "***" || movie.review === "" ) ? "No Review" : movie.review;
    }

    fullMovieList.title =   sortList( Array.from(fullMovieList.watch), "title" );
    fullMovieList.year =    sortList( Array.from(fullMovieList.watch), "year" );
    fullMovieList.rating =  sortList( Array.from(fullMovieList.watch), "rating" );

    displayFullMovies( "watch" );
}

function displayFullMovies( sortType )
{
    var movies;
    switch ( sortType )
    {
    case "year":
        movies = fullMovieList.year;
        break;
    case "rating":
        movies = fullMovieList.rating;
        break;
    case "title":
        movies = fullMovieList.title;
        break;
    case "watch":
    default:
        movies = fullMovieList.watch;
    }

    $('#Movies').html( getFullMovieDisplay( movies ) );
}

function getFullMovieDisplay( movies )
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

function parseGenres( response )
{
    genreLists = JSON.parse( response );
    genreNames = genreLists.map( function( g ) { return { id: g.id, title: g.title }; } );

    for ( var i = 0; i < genreLists.length; i++ )
    {
        var genreDiv = $('#GenreContainer');
        var genreId  = genreLists[i].id;
        var genre    = genreLists[i].title;
        var list     = genreLists[i].list;

        genreDiv.append( "<div id=\"" + genreId + "\" class=\"center textBlock\" style=\"display: none\"></div>" );
        $('#' + genreId).html( "<div class='subtitle center'>" + genre + "</div>" +
                               getRankingsDisplay( list ) );
    }

    if ( showGenreOptionsOnLoad )
    {
        showGenreList();
    }
}

function parseFranchise( listId, response )
{
    var movies = JSON.parse( response );
    switch ( listId )
    {
    case "Disney":
        disneyList = movies;
        break;
    case "Marvel":
        marvelList = movies;
        break;
    case "StarWars":
        starWarsList = movies;
        break;
    }

    $('#' + listId).html( getRankingsDisplay( movies ) );
}

function getRankingsDisplay( list )
{
    var movieDisplay = "";
    for ( var index = 0; index < list.length; index++ )
    {
        var movie = list[index];
        movie.review = ( movie.review === "***" ) ? "No Review" : movie.review;
        movieDisplay += "<div>" + (index+1) + ". <strong>" + movie.title + "</strong> (" + movie.year + ") - " + movie.review + "</div>" +
                        "<img src='" + movie.image + "' height='300px' alt='Movie Poster' /><br/><br/>";
    }
    return movieDisplay;
}

//BOOK ********************

function parseFullBooks( response )
{
    fullBookList.read = JSON.parse( response );

    for ( var index = 0; index < fullMovieList.watch.length; index++ )
    {
        var movie = fullMovieList.watch[index];
        fullMovieList.watch.review = ( movie.review === "***" || movie.review === "" ) ? "No Review" : movie.review;
    }

    fullBookList.title =   sortList( Array.from(fullBookList.read), "title" );
    fullBookList.year =    sortList( Array.from(fullBookList.read), "year" );
    fullBookList.rating =  sortList( Array.from(fullBookList.read), "rating" );

    displayFullBooks( "read" );
}

function displayFullBooks( sortType )
{
    var books;
    switch ( sortType )
    {
    case "year":
        books = fullBookList.year;
        break;
    case "rating":
        books = fullBookList.rating;
        break;
    case "title":
        books = fullBookList.title;
        break;
    case "read":
    default:
        books = fullBookList.read;
    }

    $('#Books').html( getFullBookDisplay( books ) );
}

function getFullBookDisplay( books )
{
    var bookDisplay = "";
    for ( var index = 0; index < books.length; index++ )
    {
        var book = books[index];
        var yearDisplay = book.year ? " (" + book.year + ")" : "";
        bookDisplay += "<div>" + (index + 1) + ". <a class='link' href='" + book.url + "'>" + book.title + "</a>, " + book.author + yearDisplay +
                       " - <strong>" + book.rating + "/5</strong> - " + book.review + "</div>";
    }
    return bookDisplay;
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
    $('#Favorites').html( "<div class='subtitle center'>Favorites</div>" +
                          bookDisplay );
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


function showDefaults( title, id )
{
    if ( title )
    {
        findMovie( title );
    }
    else if ( id )
    {
        findMovieById( id );
    }
    else
    {
        showSection();
    }
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
        case "MovieContainer":
            showFullMovieList();
            break;
        case "Genre":
        case "GenreContainer":
            setGenrePopOnLoad();
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
            showFullBookList();
            break;
        case "Favorites":
        case "FavoritesContainer":
            showFavoritesList();
            break;
        }
    }
}

function setGenrePopOnLoad()
{
    //this is needed because page load may occur before genres are returned
    showGenreOptionsOnLoad = true;
}

function showFullMovieList()
{
    hideAll();

    deselectAllRadioButtons( "movieSorting" );
    displayFullMovies( "watch" );

    $('#MovieContainer').show();
    scrollToId( "MovieContainer" );
}

function showGenreList()
{
    hideAll();

    openGenreModal( genreNames, function( genre ) {
        if ( genre )
        {
            $('#GenreContainer').show();
            $('#' + genre).show();
            scrollToId( "GenreContainer" );
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

function showFullBookList()
{
    hideAll();

    deselectAllRadioButtons( "bookSorting" );
    displayFullBooks( "read" );

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
    $('#GenreContainer').hide();
    $('#DisneyContainer').hide();
    $('#MarvelContainer').hide();
    $('#StarWarsContainer').hide();
    $('#BookContainer').hide();
    $('#FavoritesContainer').hide();

    hideGenres();
}

function hideGenres()
{
    genreNames.forEach( function( genre ) {
        $('#' + genre.id).hide();
    });
}


/********************RANKING*********************/


//Compare logic found in compare.js

function displayAverageFranchiseRanking( list )
{
    $.post(
        "php/database.php",
        {
            action:    "getAverageRanking",
            list:      list
        },
        function ( response ) {
            displayAverageFranchiseRankingCallback( JSON.parse( response ), list );
        }
    );
}

function displayAverageFranchiseRankingCallback( movies, listId )
{
    var detailList = getFranchiseFromId( listId );
    var averageList = movies.map( id => detailList.find( movie => { return movie.id === id } ) );

    var rankingImages = "";
    for ( var i = 0; i < averageList.length; i++ )
    {
        var title = averageList[i].title.replace(/'/g, "&apos;").replace(/"/g, "&quot;");
        rankingImages += "<div> <img style='width: 5em' src='" + averageList[i].image + "' title='" + title + "' alt='" + title + "'> </div>";
    }

    showMessage( "Average Rankings", rankingImages );
}