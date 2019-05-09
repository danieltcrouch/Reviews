var averageList = [];
var myListTitle = [];
var myListFull = [];
var listType;

function compareRankings( type )
{
    listType = type;
    setRelevantList( type );
    setAverageList( type );
    openCompareModal();
}

function setRelevantList( type )
{
    myListFull = getListFromType( type );
    myListTitle = myListFull.map(movie => movie.title );
}

function setAverageList( type )
{
    $.post(
        "php/database.php",
        {
            action:    "getAverageRanking",
            type:      type
        },
        setAverageCallback
    );
}

function setAverageCallback( list )
{
    list = JSON.parse( list );
    if ( list && list.length > 0 )
    {
        averageList = list.map( id => myListFull.find( movie => { return movie.id === id } ) );
    }
    else
    {
        averageList = myListFull;
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
    for (var i = 0; i < myListTitle.length; i++ )
    {
        listDiv.append( "<div id='listItem" + i + "' style='display: flex; flex-direction: row; justify-content: center; margin-bottom: .5em'></div>" );
        var listItem = $('#listItem' + i);

        listItem.append( "<div id='arrows" + i + "' style='display: flex; flex-direction: column; justify-content: center; margin-right: .2em'></div>" );
        var arrowDiv = $('#arrows' + i);
        arrowDiv.append( "<img class='clickable' style='width: 1em' src='images/up.png'   alt='up'    onclick='moveItem( " + i + ", true  )'>" );
        arrowDiv.append( "<img class='clickable' style='width: 1em' src='images/down.png' alt='down'  onclick='moveItem( " + i + ", false )'>" );

        listItem.append( "<div id='item" + i + "'></div>" );
        var itemDiv = $('#item' + i);
        var title = myListTitle[i].replace(/'/g, "&apos;").replace(/"/g, "&quot;");
        itemDiv.append( "<img style='width: 6em' src='" + myListFull[i].image + "' title='" + title + "' alt='" + title + "'>" );
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

    var placeholder = myListTitle.join('\n');
    showBigPrompt( "Compare Rankings", "Enter your ranking by rearranging the series below:", scoreTextRankings, placeholder );
}

function scoreRankings()
{
    var answers = [];
    $('div[id*="listItem"]').each(function(){
        var title = myListTitle[this.id.substring(8)];
        answers.push( title );
    });

    closeModal( $('#compareModal') );

    var score = getScore( answers );
    var avgScore = getAverageScore( answers );
    var results = getResultDisplay( answers );
    displayResults( score, avgScore, results );
}

function scoreTextRankings( answer )
{
    var answers = answer.split('\n').map( movie => movie.trim() ).filter( Boolean );

    var score = getScore( answers );
    var avgScore = getAverageScore( answers );
    var results = getTextResultDisplay( answers );
    displayResults( score, avgScore, results );
}

function getAverageScore( answers )
{
    averageListTitles = averageList.map(movie => movie.title );
    return getRankScore( averageListTitles, answers );
}

function getScore( answers )
{
    saveRanking( answers );
    return getRankScore( myListTitle, answers );
}

//Uses SPEARMAN'S COEFFICIENT OF RANK CORRELATION
function getRankScore( originalList, newList )
{
    var sum = 0;
    var count = originalList.length;

    for ( var i = 0; i < count; i++ )
    {
        var diff = 0;
        for ( var j = 0; j < newList.length; j++ )
        {
            if ( originalList[i] === newList[j] )
            {
                diff = i - j;
                break;
            }
        }
        sum += Math.pow( diff, 2 );
    }

    var score = 1 - ( 6 * sum ) / ( count * ( Math.pow( count, 2 ) - 1 ) );
    score = ( score + 1 ) * 50;
    var roundedScore = score.toPrecision( 4 );
    score = ( score < 100 && roundedScore == 100 ) ? 99.99 : ( score < 100 ) ? roundedScore : 100;

    return score;
}

function saveRanking( answers )
{
    var ids = [];
    for ( var i = 0; i < answers.length; i++ )
    {
        ids.push( myListFull[ myListTitle.indexOf( answers[i] ) ].id );
    }

    $.post(
        "php/database.php",
        {
            action:    "saveRanking",
            type:      listType,
            rankings:  ids
        }
    );
}

function getTextResultDisplay( answers )
{
    var result = "<span style='font-weight: bold'>Your Rankings:</span><br/>";
    result += answers.join("<br/>");
    result += "<br/><br/>";
    result += "<span style='font-weight: bold'>My Rankings:</span><br/>";
    result += myListTitle.join("<br/>");
    result += "<br/><br/>";
    result += "<span style='font-weight: bold'>Average Rankings:</span><br/>";
    result += averageList.join("<br/>");
    return result;
}

function getResultDisplay( answers )
{
    var result = "<div style='display: flex; width: 18em; margin: auto'>" +
                 "  <span style='flex: 1; align-content: center; font-weight: bold'>You</span>" +
                 "  <span style='flex: 1; align-content: center; font-weight: bold'>Me</span>" +
                 "  <span style='flex: 1; align-content: center; font-weight: bold'>Average</span>" +
                 "</div>";

    var yList = answers.map( title => myListFull.find( movie => { return movie.title === title } ) );
    var mList = myListFull;
    var aList = averageList;

    for ( var i = 0; i < yList.length; i++ )
    {
        var yTitle = yList[i].title.replace(/'/g, "&apos;").replace(/"/g, "&quot;");
        var mTitle = mList[i].title.replace(/'/g, "&apos;").replace(/"/g, "&quot;");
        var aTitle = aList[i].title.replace(/'/g, "&apos;").replace(/"/g, "&quot;");
        result += "<div>" +
                  " <img style='width: 5em; margin-right: 1em' src='" + yList[i].image + "' title='" + yTitle + "' alt='" + yTitle + "'>" +
                  " <img style='width: 5em; margin-right: 1em' src='" + mList[i].image + "' title='" + mTitle + "' alt='" + mTitle + "'>" +
                  " <img style='width: 5em'                    src='" + aList[i].image + "' title='" + aTitle + "' alt='" + aTitle + "'>" +
                  "</div>";
    }

    return result;
}

function displayResults( score, avgScore, resultDisplay )
{
    showMessage( "Comparison Results", "Our rankings match: <span style='font-weight: bold'>" + score + "%</span><br/>\n" +
                                       "Your rankings match the average: <span style='font-weight: bold'>" + avgScore + "%</span><br/>\n" +
                                       "<br/>\n" +
                                       resultDisplay );
}