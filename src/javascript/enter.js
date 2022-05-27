var EARLY_DATE = new Date('01/28/1993');
var MIN_YEAR = 1997;
var MED_YEAR = 2010;
var originalIndex;

var enterMediaType;
var enterMovieType;
var isOverwrite;

var fullMovieList = [];
var genres = [];
var rankList = [];

function setMediaType( mediaType )
{
    enterMediaType = mediaType;
    if ( mediaType === "movie" )
    {
        $('#movieInputs').show();
        $('#movieTypeButtons').show();
        $('#delete').show();

        $('#addImage').hide();
        if ( isFullList() )
        {
            $('#advIndex').show();
        }
        else
        {
            $('#updateImages').show();
        }
    }
    else
    {
        $('#advIndex').hide();
        $('#updateImages').hide();
        $('#addImage').show();

        $('#movieInputs').hide();
        $('#movieTypeButtons').hide();
        $('#delete').hide();
    }

    clear();
}

function isMovie()
{
    return enterMediaType === "movie";
}

function getByMediaType( movieValue, bookValue )
{
    var result = null;
    switch ( enterMediaType ) {
        case "movie":
            result = movieValue;
            break;
        case "book":
            result = bookValue;
    }
    return result;
}

function setMovieType( movieType )
{
    enterMovieType = movieType;
    var id = $('#id').val();
    clear();
    findMovie( id, enterMovieType, findMediaCallback );

    if ( isFullList() )
    {
        $('#rating').show();
        $('#list').hide();
        $('#updateImages').hide();
        $('#advIndex').show();
    }
    else
    {
        $('#rating').hide();
        $('#list').show();
        $('#list').attr( "placeholder", isFranchiseList() ? "Franchise" : "Genre" );
        $('#updateImages').show();
        $('#advIndex').hide();
    }
    setIndexHandlers();
}

function isFullList()
{
    return enterMovieType === "full";
}

function isGenreList()
{
    return enterMovieType === "genre";
}

function isFranchiseList()
{
    return enterMovieType === "franchise";
}

function getByMovieType( fullValue, genreValue, franchiseValue )
{
    var result = null;
    switch ( enterMovieType ) {
        case "full":
            result = fullValue;
            break;
        case "genre":
            result = genreValue;
            break;
        case "franchise":
            result = franchiseValue;
    }
    return result;
}


/********************FIND********************/


function findMediaOnEnter( e )
{
    if ( e.which === 13 || e.keyCode === 13 )
    {
        var value = $('#title').val();
        if ( value )
        {
            if ( isMovie() )
            {
                findMovie( value, enterMovieType, findMediaCallback );
            }
            else
            {
                findBook( value, findMediaCallback );
            }
        }
        else
        {
            clear();
        }
    }
}

function findMediaCallback( response )
{
    if ( response && response.isSuccess )
    {
        if ( response.isSearchId )
        {
            fillData( response );
        }
        else
        {
            confirmMatch( response );
        }
    }
    else
    {
        showToaster( "No " + getByMediaType( "movie", "book" ) + " found." );
        clear();
    }
}

function confirmMatch( response )
{
    var title     = getByMediaType( "Movie Match", "Library Look-up" );
    var term      = getByMediaType( "movie", "book" );
    var year      = response.year ? "(" + response.year + ")" : "";
    var info      = getByMediaType( "ID: " + response.id, response.author || "" );
    var imageAlt  = getByMediaType( "Movie Poster", "Book Cover" );

    var innerHTML = "Is this the correct " + term + "?<br/><br/>" +
                    "<strong>" + response.title + "</strong> " + year + "<br/>" +
                    info + "<br/><br/>" +
                    "<img src='" + response.image + "' height='300px' alt='" + imageAlt + "'>";

    showConfirm( title, innerHTML, function( answer ) {
        ( answer ) ? fillData( response ) : showToaster( "Try entering " + getByMediaType( "IMDB", "GoodReads" ) + " ID." );
    });
}

//ARCHIVE
// function promptGoogle( search )
// {
//     var db = isMovie() ? "IMDB" : "GoodReads";
//     var html = "Try finding the ID here: <a class='link' href='https://www.google.com/search?q=" + db + "%20" + search + "'>Google</a><br/>Then enter ID:";
//     showPrompt( "Enter ID", html, autoFillById, "tt0082971", true );
// }

function fillData( response )
{
    clear();
    $('#title').val( response.title );
    $('#year').val( response.year );

    $('#rating').val( response.rating );
    $('#review').val( response.review );
    $('#index').val( response.index + 1 );
    originalIndex = response.index;

    var list = isFullList() ? "" : getRankListName( response.list );
    $('#list').val( list );
    $('#id').val( response.id );
    $('#released').val( response.released );
    $('#watched').val( response.watched );
    $('#image').val( response.image );

    isOverwrite = response.isPreviouslyReviewed;
    if ( isOverwrite )
    {
        if ( !isFullList() )
        {
            setRankList( response.list );
        }
    }
    else
    {
        showToaster( getByMediaType( "Movie", "Book" ) + " not previously reviewed." );
    }
}

function setRankList( list )
{
    $.post(
        "php/enter.php",
        {
            action: "get" + (isGenreList() ? "Genre" : "Franchise"),
            list:   list
        },
        function ( response ) {
            rankList = JSON.parse( response );
        }
    );
}

function clear()
{
    $('#title').val( "" );
    $('#year').val( "" );
    $('#index').val( "" );
    $('#rating').val( "" );
    $('#review').val( "" );
    $('#id').val( "" );
    $('#list').val( "" );
    $('#released').val( "" );
    $('#watched').val( "" );
    $('#image').val( "" );
    originalIndex = null;
    isOverwrite = null;
    rankList = null;
}


/*********************SUBMIT*********************/


function submitMedia()
{
    if ( isMovie() )
    {
        checkSubmit();
    }
    else
    {
        checkBookSubmit();
    }
}


/*********************MOVIE**********************/


function checkSubmit()
{
    var submit = getByMovieType( submitMovie, submitRank, submitRank );
    if ( validate() )
    {
        if ( isOverwrite )
        {
            var term = isFullList() ? "rated" : "ranked";
            showConfirm( "Entry Exists", "This movie has already been " + term + ". Overwrite?", function( answer ) {
                if ( answer )
                {
                    submit();
                }
            });
        }
        else
        {
            submit();
        }
    }
}

function validate()
{
    var result = true;

    var id = $('#id').val();
    var year = $('#year').val();

    if ( !id )
    {
        result = false;
        showToaster( "No movie ID present." );
    }
    else if ( !year )
    {
        result = false;
        showToaster( "No year present." );
    }
    else if ( isFullList() )
    {
        var rating = $('#rating').val();
        var index = $('#index').val();
        var indexValue = Math.abs( parseInt( index ) );
        var released = $('#released').val();
        var watched = $('#watched').val();
        if ( !rating )
        {
            result = false;
            showToaster( "No rating present." );
        }
        else if ( index && !(indexValue > 0 && indexValue <= fullMovieList.length) )
        {
            result = false;
            showToaster( "The index must be between 1 and the current number of movies." );
        }
        else if ( watched && (new Date(watched)).getTime() < (new Date(released)).getTime() )
        {
            result = false;
            showToaster( "Watched date must be after released date." );
        }
    }
    else
    {
        var list = $('#list').val();
        var rank = $('#index').val();
        if ( !list )
        {
            result = false;
            showToaster( "No list type present." );
        }
        else if ( !rank )
        {
            result = false;
            showToaster( "No rank present." );
        }
    }

    return result;
}

function submitMovie()
{
    synchronizeIndexAndDate();
    var spliceIndex = calculateSpliceIndex();

    $.post(
        "php/enter.php",
        {
            action:   "saveMovie",
            index:    spliceIndex,
            title:    $('#title').val(),
            id:       $('#id').val(),
            year:     $('#year').val(),
            released: $('#released').val(),
            watched:  $('#watched').val(),
            rating:   $('#rating').val(),
            review:   $('#review').val() || "***",
            overwrite: isOverwrite
        },
        function( response ) {
            populateFullMovieList();
            submitCallback( response );
        }
    );
}

function submitCallback( response )
{
    clear();
    showToaster( "Success!" );
}


/**********************RANK**********************/
//Genres and Franchise


function populateGenres()
{
    $.post(
        "php/enter.php",
        { action: "getGenres" },
        function( response ) {
            genres = JSON.parse( response );
        }
    );
}

function setIndexHandlers()
{
    var index = $('#index');
    index.attr( "placeholder", isFullList() ? "Index" : "Click to change Rank" );
    if ( isFullList() )
    {
        index.unbind( "click", getRanking );
        index.unbind( "keypress", getRanking );

        index.change( clearWatched );
    }
    else
    {
        index.click( getRanking );
        index.keypress( getRanking );

        index.unbind( "change", clearWatched );
    }
}

function getRanking()
{
    if ( rankList )
    {
        var id = $('#id').val();
        if ( id && !rankList.some( m => m.id === id ) )
        {
            rankList.push( {
                id:     id,
                title:  $('#title').val(),
                image:  $('#image').val()
            } );
        }

        openSortModal( rankList, getRankingCallback, true );
    }
    else
    {
        if ( isGenreList() && genres )
        {
            openGenreModal(
                genres,
                function( listName, response ) {
                    $('#list').val( getRankListName( listName ) );
                    rankList = response;
                    getRanking();
                },
                true
            );
        }
        else if ( isFranchiseList() )
        {
            openFranchiseModal( function( listName, response ) {
                $('#list').val( getRankListName( listName ) );
                rankList = response;
                getRanking();
            } );
        }
    }
}

function getRankingCallback( response )
{
    var mainIndex = response.indexOf( $('#title').val() );
    var changed = false;
    rankList.sort(function( a, b ){
        var result = response.indexOf(a.title) - response.indexOf(b.title);
        changed = changed || result < 0;
        return result;
    });
    if ( mainIndex >= 0 )
    {
        $('#index').val( mainIndex + 1 );
    }

    if ( changed )
    {
        submitRanks();
    }
}

function submitRanks()
{
    $.post(
        "php/enter.php",
        {
            action: "saveRankedMovies",
            type:   enterMovieType,
            list:   getRankListId( $('#list').val() ),
            movies: rankList
        },
        function() {
            showToaster( "Ranks updated." );
            if ( isFranchiseList() )
            {
                updatePersonalRankings();
            }
        }
    );
}

function updatePersonalRankings() //used for Averages
{
    $.post(
        "php/database.php",
        {
            action: "updatePersonalRankings",
            type:   getRankListId( $('#list').val() ),
            movies: rankList
        }
    );
}

function submitRank()
{
    $.post(
        "php/enter.php",
        {
            action: "saveRankedMovie",
            type:   enterMovieType,
            list:   getRankListId( $('#list').val() ),
            rank:   $('#index').val(),
            title:  $('#title').val(),
            id:     $('#id').val(),
            year:   $('#year').val(),
            image:  $('#image').val(),
            review: $('#review').val() || "***"
        },
        submitCallback
    );
}

function getRankListId( list )
{
    var result;

    if ( isGenreList() )
    {
        var genre = (genres) ? genres.find( genre => genre.title === list ) : null;
        result = (genre) ? genre.id : list;
    }
    else
    {
        result = getRankListName( list );
        result = (result === "Star Wars") ? "StarWars" : result;
    }

    return result;
}

function getRankListName( list )
{
    var result;

    if ( isGenreList() )
    {
        var genre = (genres) ? genres.find( genre => genre.id === list ) : null;
        result = (genre) ? genre.title : list;
    }
    else
    {
        list = (list) ? list.toLowerCase() : "";
        switch ( list )
        {
        case "d":
        case "disney":
            result = "Disney";
            break;
        case "m":
        case "mcu":
        case "marvel":
            result = "Marvel";
            break;
        case "s":
        case "sw":
        case "starwars":
        case "star wars":
            result = "Star Wars";
        }
    }

    return result;
}


/*********************GENRE**********************/


// // test when genre is entered that doesn't exist
// function editGenres()
// {
//     var genreText = genreNames.join('\n');
//     showBigPrompt(
//         "Edit Genres",
//         "Choose genre names to appear on main page:",
//         editGenresCallback,
//         genreText );
// }
//
// function editGenresCallback( response )
// {
//     genreNames = response.split('\n').map( movie => { return {id: movie.trim(), title: movie.trim() }; } ).filter( Boolean );
//     $.post(
//         "php/enter.php",
//         {
//             action: "",
//             type:   null
//         },
//         null
//     );
// }


/*********************DELETE********************/


function checkDelete()
{
    if ( $('#id').val() )
    {
        var innerHTML = "Are you sure you would like to delete this movie?<br/><br/>" +
                        "<strong>" + $('#title').val() + "</strong> (" + $('#year').val() + ")<br/>" +
                        "(" + $('#id').val() + ")<br/><br/>" +
                        "<img src='" + $('#image').val() + "' height='300px' alt='Movie Poster'>";
        showConfirm( "Delete Movie", innerHTML, function( answer ) {
            if ( answer )
            {
                deleteMovie();
            }
        });
    }
    else
    {
        showToaster( "No movie ID present." );
    }
}

function deleteMovie()
{
    var data = isFullList() ?
        {
            action: "deleteMovie",
            id:     $('#id').val()
        } :
        {
            action: "deleteRankMovie",
            type:   enterMovieType,
            list:   getRankListId( $('#list').val() ),
            id:     $('#id').val()
        };

    $.post(
        "php/enter.php",
        data,
        function( response ) {
            response = JSON.parse( response );
            var success = ( response && response.isSuccess );
            showToaster( success ? "Movie removed." : "Movie not found." );
            if ( success )
            {
                clear();
            }
        }
    );
}


/*********************BOOK***********************/


function checkBookSubmit()
{
    if ( $('#id').val() )
    {
        submitBook();
    }
    else
    {
        showToaster( "No book ID present." );
    }
}

function submitBook()
{
    document.getElementById("review").select();
    document.execCommand("copy");
    showToaster( "Review copied to clipboard" );

    if ( isOverwrite )
    {
        window.open( "https://www.goodreads.com/review/edit/" + $('#id').val() );
    }
    else
    {
        window.open( "https://www.goodreads.com/review/list/55277264-daniel-crouch?utf8=%E2%9C%93&search%5Bquery%5D=" + $('#title').val() );
    }

    clear();
}


/*********************IMAGE**********************/


function addImage()
{
    if ( $('#id').val() )
    {
        var url = $('#review').val();
        var isImage = url.match(/\.(jpeg|jpg|gif|png)$/) != null &&
            url.startsWith("http") &&
            !url.includes(" ");

        if (!isImage)
        {
            showPrompt(
                "Enter Image URL",
                "Enter an image URL to use for this book:",
                function ( response ) { response ? submitImage( response ) : null; },
                "",
                true
            );
        }
        else
        {
            submitImage( url );
        }
    }
    else
    {
        showToaster( "No book ID present." );
    }
}

function submitImage( url )
{
    $.post(
        "php/enter.php",
        {
            action: "submitBookImage",
            id:     $('#id').val(),
            url:    url
        },
        submitCallback
    );
}

function updateImages()
{
    if ( enterMediaType === "movie" && !isFullList() )
    {
        updateMovieImages();
    }
    else
    {
        showToaster( "Can only update images for Genres or Series." );
    }
}

function updateMovieImages()
{
    $.post(
        "php/enter.php",
        {
            action: "updateMovieImages",
            type:   enterMovieType,
        },
        submitCallback
    );
}

/********************INDEXING********************/


function synchronizeIndexAndDate()
{
    var typedIndex = $('#index').val();
    var typedDate = $('#watched').val();

    if ( !(typedIndex && typedDate) ) //something changed
    {
        if ( typedIndex === "" && !typedDate )
        {
            typedIndex = "" + (fullMovieList.length + (isOverwrite?0:1));
            $('#index').val( typedIndex );
        }

        if ( typedIndex === "" )
        {
            var index = fullMovieList.findIndex( movie => movie.watched === typedDate ||
                (new Date(movie.watched)).getTime() > (new Date(typedDate)).getTime() ) + 1;
            $('#index').val( index );
        }
        else
        {
            var watchDate;
            var index = parseInt( typedIndex );
            index = index > 0 ? index - 1 : fullMovieList.length + index;
            if ( index === 0 )
            {
                watchDate = adjustDays( new Date(fullMovieList[0].watched), -1 );
            }
            else if ( index >= fullMovieList.length - 1 )
            {
                watchDate = new Date();
            }
            else
            {
                var startDate = new Date(fullMovieList[index-1].watched);
                var endDate = new Date(fullMovieList[index].watched);
                watchDate = getMiddleDate( startDate, endDate );
            }
            $('#watched').val( watchDate.toLocaleDateString('en-US') );
        }
    }
}

function calculateSpliceIndex()
{
    var index = parseInt( $('#index').val() );
    var count = fullMovieList.length;
    var isPositive = index > 0;
    var index = isPositive ? index - 1 : count + index;

//This code retains the positioning relative to the movie at that spot; without it, the actual index number is retained (which seems more intuitive)
//  When a movie is inserted new, it takes an index number and moves the movie at that number up one.
//  If, however, a movie is an overwrite and exists previously in the list, only one of those can be done:
//  Either it gets the index number written, or it displaces the movie at that index

//    if ( isOverwrite  )
//    {
//        if ( isPositive && index > originalIndex )
//        {
//            index--;
//        }
//        if ( !isPositive && originalIndex > (count+index) )
//        {
//            index++;
//        }
//    }

    return index;
}

function showIndex()
{
    showBinaryChoice(
        "Advanced Indexing",
        "If you leave the index value blank, the watch date will be set to today. " +
            "If a value is entered, the movie will be inserted at that value (and the current movie there will be moved up numerically). If the index is negative, the insertion will count from the most recent movie (e.g. -1 will insert just before the most recent film). " +
            "Note, if the original index is between the beginning of the list and the new insertion, the new insertion will appear after the movie previously at that index. " +
            "Alternatively, you may enter a timeframe and have the index calculated:",
        "Choose Year",
        "Choose Date",
        function( answer ) {
            if ( answer === 0 )
            {
                showPrompt(
                    "Enter Year",
                    "Enter a year before " + MED_YEAR + ":",
                    chooseYearCallback,
                    "1999",
                    true //isNumber
                );
            }
            else if ( answer === 1 )
            {
                showPrompt(
                    "Enter Date",
                    "Enter a specific date with the format MM/DD/YYYY:",
                    chooseDateCallback,
                    "03/27/2016",
                    false //isNumber
                );
            }
        }
    );
}

function chooseYearCallback( response )
{
    if ( isNaN(response) || isNaN(parseInt(response)) || parseInt(response) >= MED_YEAR )
    {
        showToaster( "Year must be greater than " + (MIN_YEAR - 1) + " and less than " + MED_YEAR );
    }
    else
    {
        var index = 0;
        var year = parseInt(response);
        var closestPrevReleaseDate = EARLY_DATE;
        for ( var i = 0; i < fullMovieList.length; i++ )
        {
            var movie = fullMovieList[i]
            if ( movie.watched.includes( response ) && parseInt(movie.year) < year )
            {
                releaseDate = new Date(movie.released);
                if (releaseDate.getTime() > closestPrevReleaseDate.getTime())
                {
                    closestPrevReleaseDate = releaseDate;
                    index = i;
                }
            }
        }
        var watchDate = getMiddleDate( new Date( fullMovieList[index].watched ), new Date( fullMovieList[index+1].watched ) );

        $('#watched').val( watchDate.toLocaleDateString('en-US') );
        $('#index').val( "" );
        showToaster( "Watched date set" );
    }
}

function chooseDateCallback( response )
{
    var today = new Date();
    var watchDate = new Date(response);
    if ( isNaN(watchDate) && (watchDate.getTime() < EARLY_DATE.getTime() || watchDate.getTime() > today.getTime()) )
    {
        showToaster( "Date must be in the format MM/DD/YYYY, after my birthday, and before tomorrow" );
    }
    else
    {
        $('#watched').val( watchDate.toLocaleDateString('en-US') );
        $('#index').val( "" );
        showToaster( "Watched date set" );
    }
}

function getMiddleDate( date1, date2 )
{
    var difference = date2.getTime() - date1.getTime();
    var differenceInDays = difference / (1000 * 3600 * 24);
    var dayCount = Math.ceil( differenceInDays / 2 );
    return adjustDays( date1, dayCount );
}

function populateFullMovieList()
{
    $.post(
        "php/enter.php",
        { action: "getFullMovieList" },
        function( response ) { fullMovieList = JSON.parse( response ); }
    );
}

function clearWatched()
{
    $('#watched').val( "" );
}


/********************DOWNLOAD********************/


function view()
{
    showBinaryChoice(
        "Download",
        "Download Ratings or View To-Watch List?", "Download All", "View Searches",
        function( answer ) {
            if ( answer === 0 )
            {
                download();
            }
            else if ( answer === 1 )
            {
                viewSearches();
            }
        }
    );
}

function viewSearches()
{
    $.post(
        "php/enter.php",
        {action: "viewSearches"},
        function( response ) {
            showMessage( "User Searches", JSON.parse( response ) );
        }
    );
}

function download()
{
    var downloadForm = document.createElement('FORM');
    downloadForm.name='downloadForm';
    downloadForm.method='POST';
    downloadForm.action='php/enter.php';

    var formAction = document.createElement('INPUT');
    formAction.type='HIDDEN';
    formAction.name='action';
    formAction.value='downloadAll';
    downloadForm.appendChild( formAction );

    document.body.appendChild( downloadForm );
    downloadForm.submit();
}