var enterMovieType;

function setMediaType( mediaType )
{
    if ( mediaType === "movie" )
    {
        $('#addImage').hide();

        $('#movieInputs').show();
        $('#movieTypeButtons').show();
        $('#delete').show();
    }
    else
    {
        $('#movieInputs').hide();
        $('#movieTypeButtons').hide();
        $('#delete').hide();

        $('#addImage').show();
    }
}

function setMovieType( movieType )
{
    enterMovieType = movieType;
}

function autoFillTab( e )
{
    var charCode = (typeof e.which === "number") ? e.which : e.keyCode;
    if ( charCode === 9 )
    {
        autoFill( e );
    }
}

function autoFill( e )
{
    var charCode = (typeof e.which === "number") ? e.which : e.keyCode;
    if ( charCode === 13 || charCode === 9 )
    {
        var search = $('#title').val();
        if ( search )
        {
            if ( search.search( /tt\d{7}/i ) === -1 )
            {
                $.post(
                    "php/addRatings.php",
                    {
                        action: "getMovieData",
                        title: search
                    },
                    autoFillCallback
                );
            }
            else
            {
                autoFillById( search );
            }
        }
        else
        {
            clear();
        }
    }
}

function autoFillCallback( response )
{
    var movieResponse = JSON.parse( response );
    if ( movieResponse.search )
    {
        if ( movieResponse.isSuccess )
        {
            var innerHTML = "Is this the correct movie?<br/><br/>" +
                            "<strong>" + movieResponse.title + "</strong> (" + movieResponse.year + ")<br/>" +
                            "(" + movieResponse.id + ")<br/><br/>" +
                            "<img src='" + movieResponse.poster + "' height='300px' alt='Movie Poster'>";
            showConfirm( "Movie Match", innerHTML, function( answer ) {
                if ( answer )
                {
                    fillData( movieResponse );
                }
                else
                {
                    var html = "Try finding the ID here: <a class='link' href='https://www.google.com/search?q=IMDB%20" + movieResponse.search + "'>Google</a><br/>Then enter ID:";
                    showPrompt( "Enter ID", html, autoFillById, "tt0082971", true );
                }
            });
        }
        else
        {
            var html = "Try finding the ID here: <a class='link' href='https://www.google.com/search?q=IMDB%20" + movieResponse.search + "'>Google</a><br/>Then enter ID:";
            showPrompt( "Enter ID", html, autoFillById, "tt0082971", true );
        }
    }
}

function autoFillById( id )
{
    if ( id )
    {
        $.post(
            "php/addRatings.php",
            {
                action: "getMovieDataById",
                id: id
            },
            autoFillByIdCallback
        );
    }
}

function autoFillByIdCallback( response )
{
    response = JSON.parse( response );
    if ( response.isSuccess )
    {
        fillData( response );
    }
}

function fillData( movie )
{
    clear();
    $('#title').val( movie.title );
    $('#year').val( movie.year );
    $('#id').val( movie.id );
    $('#poster').val( movie.poster );

    loadFromFile( $('#id').val() );
}

function loadFromFile( response )
{
    if ( response )
    {
        $.post(
            "php/addRatings.php",
            {
                action: "load",
                movie: response
            },
            loadFromFileCallback
        );
    }
}

function loadFromFileCallback( response )
{
    response = JSON.parse( response );
    if ( response.isSuccess )
    {
        $('#title').val( response.title );
        $('#year').val( response.year );
        $('#index').val( response.index );
        $('#rating').val( response.rating );
        $('#review').val( response.review );
        $('#id').val( response.id );
    }
    else
    {
        showToaster( "Movie not previously reviewed." );
    }
}

function clear()
{
    $('#title').val( "" );
    $('#year').val( "" );
    $('#index').val( "" );
    $('#rating').val( "" );
    $('#review').val( "" );
    $('#id').val( "" );
}

function remove()
{
    showPrompt( "Delete Movie", "Enter a previously rated movie:", removeCallback, "Raiders of the Lost Ark | tt0082971" );
}

function removeCallback( response )
{
    if ( response )
    {
        if ( response.search( /tt\d{7}/i ) === -1 )
        {
            $.post(
                "php/addRatings.php",
                {
                    action: "getMovieData",
                    title: response
                },
                confirmRemoveMovie
            );
        }
        else
        {
            removeMovie( response );
        }
    }
}

function confirmRemoveMovie( response )
{
    var movieResponse = JSON.parse( response );
    if ( movieResponse && movieResponse.isSuccess )
    {
        var innerHTML = "Is this the correct movie?<br/><br/>" +
                        "<strong>" + movieResponse.title + "</strong> (" + movieResponse.year + ")<br/>" +
                        "(" + movieResponse.id + ")<br/><br/>" +
                        "<img src='" + movieResponse.poster + "' height='300px' alt='Movie Poster'>";
        showConfirm( "Movie Match", innerHTML, function( answer ) {
            if ( answer )
            {
                removeMovie( movieResponse.id );
            }
            else
            {
                var html = "Try finding the ID here: <a class='link' href='https://www.google.com/search?q=IMDB%20" + movieResponse.search + "'>Google</a><br/>Then enter ID:";
                showPrompt( "Enter ID", html, removeMovie, "tt0082971", true );
            }
        });
    }
    else
    {
        showToaster( "Movie not found." );
    }
}

function removeMovie( id )
{
    $.post(
        "php/addRatings.php",
        {
            action: "remove",
            id:     id
        },
        function( response ) {
            response = JSON.parse( response );
            showToaster( ( response && response.isSuccess ) ? "Movie removed." : "Movie not found." );
        }
    );
}

function download()
{
    showBinaryChoice(
        "Download",
        "Download Ratings or View To-Watch List?", "Download", "To-Watch",
        function( answer ) {
            if ( answer )
            {
                $.post(
                    "php/addRatings.php",
                    {action: "download"},
                    downloadCallback
                );
            }
            else
            {
                $.post(
                    "php/addRatings.php",
                    {action: "viewToWatch"},
                    function( response ) {
                        showMessage( "Movies To-Watch", JSON.parse( response ).text );
                    }
                );
            }
        }
    );
}

function downloadCallback( response )
{
    var text = JSON.parse( response ).text;
    var url = null;
    var blob = new Blob( [text], {type: 'text/csv'} );
    if ( url !== null )
    {
        window.URL.revokeObjectURL( url );
    }
    url = window.URL.createObjectURL( blob );

    var a = document.createElement( "a" );
    document.body.appendChild( a );
    a.href = url;
    a.style = "display: none";
    a.download = "Ratings.csv";
    a.click();
    window.URL.revokeObjectURL( url );
}


/**********************LIST**********************/


function isList()
{
    return enterMovieType === "list";
}


function checkSubmit()
{
    if ( $('#id').val() )
    {
        if ( isList() )
        {
            checkListOverwrite();
        }
        else
        {
            checkRankOverwrite();
        }
    }
    else
    {
        showToaster( "No movie ID present." );
    }
}

function checkListOverwrite()
{
    $.post(
        "php/addRatings.php",
        {
            action: isList() ? "checkOverwrite" : "checkRankOverwrite",
            id: $('#id').val()
        },
        checkOverwriteCallback
    );
}

function checkOverwriteCallback( response )
{
    response = JSON.parse( response );
    if ( response && response.isSuccess )
    {
        submit();
    }
    else if ( response && response.message === "Duplicate" )
    {
        var term = isList() ? "rated" : "ranked";
        showConfirm( "Entry Exists", "This movie has already been " + term + ". Overwrite?", function( answer ) {
            if ( answer )
            {
                if ( isList() )
                {
                    submit( true );
                }
                else
                {
                    getRanking( response, response.rank );
                }
            }
        });
    }
    else
    {
        showToaster( response.message || "An error has occurred." );
    }
}

function submit( overwrite )
{
    $.post(
        "php/addRatings.php",
        {
            action: "saveMovie",
            id:     $('#id').val(),
            title:  $('#title').val(),
            year:   $('#year').val(),
            index:  $('#index').val(),
            rating: $('#rating').val(),
            review: $('#review').val() || "***",
            overwrite: overwrite
        },
        submitCallback
    );
}

function submitCallback()
{
    clear();
    showToaster( "Success!" );
}


/**********************RANK**********************/


function checkRankOverwrite()
{
    showPrompt( "Enter List", "Enter the relevant list: &ldquo;Disney&rdquo; | &ldquo;Marvel&rdquo; | &ldquo;StarWars&rdquo; ", function( answer ) {
        $.post(
            "php/addRatings.php",
            {
                action: "checkRankOverwrite",
                list:   answer,
                id:     $('#id').val()
            },
            checkOverwriteCallback
        );
    }, "", true );
}

function getRanking( movieData, placeholderRank )
{
    var innerHTML = "Where would you like to rank this movie?<br/>" +
                    "(e.g. 1, 2, 3, top, bottom, above [Movie], below [Movie])";
    showPrompt( "Where Does It Rank?", innerHTML, function( answer ) {
        $.post(
            "php/addRatings.php",
            {
                action: "validateRank",
                list: movieData.list,
                answer: answer
            },
            function( response ) {
                response = JSON.parse( response );
                if ( response && response.isSuccess )
                {
                    movieData.rank = response.rank;
                    submitRanked( movieData, !!placeholderRank );
                }
                else
                {
                    showToaster( response.message || "Invalid Ranking" );
                }
            }
        );
    }, placeholderRank, true );
}

function submitRanked( movieData, overwrite )
{
    $.post(
        "php/addRatings.php",
        {
            action: "saveRankedMovie",
            list:   movieData.list,
            rank:   movieData.rank,
            id:     $('#id').val(),
            title:  $('#title').val(),
            year:   $('#year').val(),
            image:  $('#poster').val(),
            review: $('#review').val() || "***",
            overwrite: overwrite
        },
        submitRankedCallback
    );
}

function submitRankedCallback()
{
    showConfirm( "Save Review", "Would you like to save this review to the Movie Review List?", function( answer ) {
        if ( answer )
        {
            saveMovie();
        }
        else
        {
            clear();
            showToaster( "Success!" );
        }
    });
}