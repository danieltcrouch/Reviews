function saveRankedMovie()
{
    showPrompt( "Enter List", "Enter the relevant list: \"Disney\" | \"Marvel\" | \"StarWars\" ", function( answer ) {
        $.post(
            "php/addRatings.php",
            {
                action: "checkRankedOverwrite",
                list:   answer,
                id:     $('#id').val()
            },
            checkRankedOverwriteCallback
        );
    }, "", true );
}

function checkRankedOverwriteCallback( response )
{
    response = JSON.parse( response );
    if ( response && response.isSuccess )
    {
        getRanking( response, $('#index').val() );
    }
    else if ( response && response.message === "Duplicate" )
    {
        showConfirm( "Entry Exists", "This movie has already been ranked. Overwrite?", function( answer ) {
            if ( answer )
            {
                getRanking( response, response.rank );
            }
        });
    }
    else
    {
        showToaster( response.message || "An error has occurred." );
    }
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