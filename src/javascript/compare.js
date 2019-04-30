var relevantList = [];
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
        relevantList = disneyList.map( movie => movie.title );
        relevantListFull = disneyList;
        break;
    case "Marvel":
        relevantList = marvelList.map( movie => movie.title );
        relevantListFull = marvelList;
        break;
    case "StarWars":
        relevantList = starWarsList.map( movie => movie.title );
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
    for ( var i = 0; i < relevantList.length; i++ )
    {
        listDiv.append( "<div id='listItem" + i + "' style='display: flex; flex-direction: row; justify-content: center; margin-bottom: .5em'></div>" );
        var listItem = $('#listItem' + i);

        listItem.append( "<div id='arrows" + i + "' style='display: flex; flex-direction: column; justify-content: center; margin-right: .2em'></div>" );
        var arrowDiv = $('#arrows' + i);
        arrowDiv.append( "<img class='clickable' style='width: 1em' src='images/up.png'   alt='up'    onclick='moveItem( " + i + ", true  )'>" );
        arrowDiv.append( "<img class='clickable' style='width: 1em' src='images/down.png' alt='down'  onclick='moveItem( " + i + ", false )'>" );

        listItem.append( "<div id='item" + i + "'></div>" );
        var itemDiv = $('#item' + i);
        itemDiv.append( "<img style='width: 6em' src='" + relevantListFull[i].image + "' title='" + relevantList[i] + "' alt='" + relevantList[i] + "'>" );
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

    var placeholder = relevantList.join('\n');
    showBigPrompt( "Compare Rankings", "Enter your ranking by rearranging the series below:", scoreTextRankings, placeholder );
}

function scoreRankings()
{
    var answers = [];
    $('div[id*="listItem"]').each(function(){
        var title = relevantList[this.id.substring(8)];
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

function scoreAnswers( answers, isText )
{
    var sum = 0;
    var count = relevantList.length;

    for ( var i = 0; i < count; i++ )
    {
        var diff = 0;
        for ( var j = 0; j < answers.length; j++ )
        {
            if ( relevantList[i] === answers[j] )
            {
                diff = i - j;
                break;
            }
        }
        sum += Math.pow( diff, 2 );
    }

    var score = 1 - ( 6 * sum ) / ( count * ( Math.pow( count, 2 ) - 1 ) );
    score = ( score + 1 ) * 50;
    score = ( score < 100 ) ? score.toPrecision( 4 ) : 100;

    displayResults( score, answers, isText );
}

function displayResults( score, rankings, isText )
{
    var resultsDisplay = rankings.join("<br/>"); //todo - isText ? rankings.join("<br/>") : *Display as side-byside images*
    showConfirm( "Comparison Results", "If 0% is the opposite and 100% a total match, our rankings match:" +
                                       "<div class='center' style='font-size: 1.5em'>" + score + "%</div>\n" +
                                       "<br/>" +
                                       resultsDisplay );
}