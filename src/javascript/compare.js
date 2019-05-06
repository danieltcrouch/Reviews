var relevantListTitle = [];
var relevantListFull = [];

function compareRankings( type )
{
    setRelevantList( type );
    openCompareModal();
}

function setRelevantList( type )
{
    switch ( type )
    {
    case "Disney":
        relevantListTitle = disneyList.map(movie => movie.title );
        relevantListFull = disneyList;
        break;
    case "Marvel":
        relevantListTitle = marvelList.map(movie => movie.title );
        relevantListFull = marvelList;
        break;
    case "StarWars":
        relevantListTitle = starWarsList.map(movie => movie.title );
        relevantListFull = starWarsList;
    }
}

function openCompareModal()
{
    var modal = $('#compareModal');
    modal.show();
    setCloseHandlers( modal, null, scoreRankings );
    blurBackground();

    var listDiv = $('#modalList');
    listDiv.empty();
    listDiv.css( "text-align", "center" );
    for (var i = 0; i < relevantListTitle.length; i++ )
    {
        listDiv.append( "<div id='listItem" + i + "' style='display: flex; flex-direction: row; justify-content: center; margin-bottom: .5em'></div>" );
        var listItem = $('#listItem' + i);

        listItem.append( "<div id='arrows" + i + "' style='display: flex; flex-direction: column; justify-content: center; margin-right: .2em'></div>" );
        var arrowDiv = $('#arrows' + i);
        arrowDiv.append( "<img class='clickable' style='width: 1em' src='images/up.png'   alt='up'    onclick='moveItem( " + i + ", true  )'>" );
        arrowDiv.append( "<img class='clickable' style='width: 1em' src='images/down.png' alt='down'  onclick='moveItem( " + i + ", false )'>" );

        listItem.append( "<div id='item" + i + "'></div>" );
        var itemDiv = $('#item' + i);
        itemDiv.append( "<img style='width: 6em' src='" + relevantListFull[i].image + "' title='" + relevantListTitle[i] + "' alt='" + relevantListTitle[i] + "'>" );
    }
}

function moveItem( index, isUp )
{
    var item = $('#listItem' + index);
    isUp ? item.prev().insertAfter( item ) : item.next().insertBefore( item );
}

function compareText()
{
    closeModal( $('#compareModal') );

    var placeholder = relevantListTitle.join('\n');
    showBigPrompt( "Compare Rankings", "Enter your ranking by rearranging the series below:", scoreTextRankings, placeholder );
}

function scoreRankings()
{
    var answers = [];
    $('div[id*="listItem"]').each(function(){
        var title = relevantListTitle[this.id.substring(8)];
        answers.push( title );
    });

    closeModal( $('#compareModal') );
    scoreAnswers( answers, false );
}

function scoreTextRankings( answer )
{
    var answers = answer.split('\n').map( movie => movie.trim() ).filter( Boolean );
    scoreAnswers( answers, true );
}

//Uses SPEARMAN'S COEFFICIENT OF RANK CORRELATION
function scoreAnswers( answers, isText )
{
    var sum = 0;
    var count = relevantListTitle.length;
    var answerIndexes = [];

    for ( var i = 0; i < count; i++ )
    {
        var diff = 0;
        for ( var j = 0; j < answers.length; j++ )
        {
            if ( relevantListTitle[i] === answers[j] )
            {
                answerIndexes.push( j );
                diff = i - j;
                break;
            }
        }
        sum += Math.pow( diff, 2 );
    }

    var score = 1 - ( 6 * sum ) / ( count * ( Math.pow( count, 2 ) - 1 ) );
    score = ( score + 1 ) * 50;
    score = ( score < 100 ) ? score.toPrecision( 4 ) : 100;

    displayResults( score, answerIndexes, isText );
}

function displayResults( score, answerIndexes, isText )
{
    var movieList = [];
    var movieDisplay = "<div style='align-content: center; margin-bottom: .5em'><strong>You&nbsp;|&nbsp;Me&nbsp;</strong></div>";
    for ( var i = 0; i < answerIndexes.length; i++ )
    {
        var yIndex = i;
        var mIndex = answerIndexes[i];
        movieList.push( relevantListTitle[mIndex] );
        movieDisplay += "<div>" +
                        "<img style='width: 5em; margin-right: 1.5em' src='" + relevantListFull[mIndex].image + "' title='" + relevantListTitle[mIndex] + "' alt='" + relevantListTitle[mIndex] + "'>" +
                        "<img style='width: 5em'                      src='" + relevantListFull[yIndex].image + "' title='" + relevantListTitle[yIndex] + "' alt='" + relevantListTitle[yIndex] + "'>" +
                        "</div>";
    }

    var resultsDisplay = isText ? movieList.join("<br/>") : movieDisplay;
    showConfirm( "Comparison Results", "If 0% is the opposite and 100% a total match, our rankings match:" +
                                       "<div class='center' style='font-size: 1.5em'>" + score + "%</div>\n" +
                                       "<br/>" +
                                       resultsDisplay );
}