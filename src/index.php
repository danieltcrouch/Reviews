<?php include("$_SERVER[DOCUMENT_ROOT]/php/startup.php"); ?>
<!DOCTYPE html>
<html>
<head>
	<title>Daniel&rsquo;s Ratings</title>
    <?php includeHeadInfo(); ?>
    <script src="javascript/reviews.js"></script>
    <script src="javascript/compare.js"></script>
</head>

<body>

	<!--Header-->
    <?php includeHeader(); ?>
    <div class="col-10 header">
        <div class="title center"><span class="clickable">
            Daniel&rsquo;s Ratings
            <img style="width: .5em; padding-bottom: .25em" src="<?php getHelpImage() ?>" alt="help">
        </span></div>
        <div id="instructions" style="display: none">
            Click to view either movies or books. From here, you can search Daniel&rsquo;s reviews for either category or click to view the whole list.
            Each entry has a rating (out of 10 for movies, out of 5 for books), and some entries have written reviews.<br/><br/>
            In addition, specific rankings are included for Disney&rsquo;s Animated Classics, the MCU, and the Star Wars Franchise.
            Under books, there is also a list of favorites in order of date read.<br/><br/>
            Reviews can also be found on <a href="https://www.goodreads.com/user/show/55277264-daniel-crouch">Goodreads</a>,
            <a href="https://www.rottentomatoes.com/user/id/807873993/">Rotten Tomatoes</a>, and
            <a href="https://www.criticker.com/profile/dcrouch1/">Criticker</a>.
        </div>
    </div>

    <!--Main-->
    <div class="col-10 main">
        <a href="#main-top"></a>
        <div class="center" style="font-size: 1.5em">Please Select a Category</div>
        <div class="col-5 center">
            <img src="images/movies.png" class="logoImage clickable" style="margin-bottom: 1em" onclick="toggleMovieSubMenu()">
            <div id="movieSubMenu" style="display: none">
                <input id="findMovie" type="search" class="input" onkeyup="findMovieOnEnter( event )" placeholder="Find a movie">
                <div><img src="images/list.png" class="logoImage clickable" onclick="showMovieList()" title="Click to see all movies"></div>
                <div><img src="images/disney.png" class="logoImage clickable" onclick="showDisneyList()" title="Click to see Disney movies"></div>
                <div><img src="images/marvel.png" class="logoImage clickable" onclick="showMarvelList()" title="Click to see Marvel movies"></div>
                <div><img src="images/star-wars.png" class="logoImage clickable" onclick="showSWList()" title="Click to see Star Wars movies"></div>
            </div>
        </div>
        <div class="col-5 center">
            <img src="images/books.png" class="logoImage clickable" style="margin-bottom: 1em" onclick="toggleBookSubMenu()">
            <div id="bookSubMenu" style="display: none">
                <input id="findBook" type="search" class="input" onkeyup="findBookOnEnter( event )" placeholder="Find a book">
                <div><img src="images/list.png" class="logoImage clickable" onclick="showBookList()" title="Click to see all books"></div>
                <div><img src="images/star.png" class="logoImage clickable" onclick="showFavoritesList()" title="Click to see favorite books"></div>
            </div>
        </div>
    </div>

    <div class="col-10 main">
        <div id="MovieContainer" style="display: none">
            <div class="center" style="margin-bottom: 1em">
                <button id="title" name="movieSorting" class="button inverseButton" style="width: 5em; margin: .25em;">Title</button>
                <button id="year" name="movieSorting" class="button inverseButton" style="width: 5em; margin: .25em;">Year</button>
                <button id="rating" name="movieSorting" class="button inverseButton" style="width: 5em; margin: .25em;">Rating</button>
            </div>
            <div id="Movies" class="center textBlock"></div>
        </div>
        <div id="DisneyContainer" style="display: none">
            <div class="center" style="margin-bottom: 1em">
                <button class="button" style="width: 10em" onclick="compareRankings('Disney')">Compare</button>
            </div>
            <div id="Disney" class="center textBlock"></div>
        </div>
        <div id="MarvelContainer" style="display: none">
            <div class="center" style="margin-bottom: 1em">
                <button class="button" style="width: 10em" onclick="compareRankings('Marvel')">Compare</button>
            </div>
            <div id="Marvel" class="center textBlock"></div>
        </div>
        <div id="StarWarsContainer" style="display: none">
            <div class="center" style="margin-bottom: 1em">
                <button class="button" style="width: 10em" onclick="compareRankings('StarWars')">Compare</button>
            </div>
            <div id="StarWars" class="center textBlock"></div>
        </div>

        <div id="BookContainer" style="display: none">
            <div class="center" style="margin-bottom: 1em">
                <button id="title" name="bookSorting" class="button inverseButton" style="width: 5em; margin: .25em;">Title</button>
                <button id="read" name="bookSorting" class="button inverseButton" style="width: 5em; margin: .25em;">Read</button>
            </div>
            <div id="Books" class="center textBlock">Books are loading... May take a minute...</div>
        </div>
        <div id="FavoritesContainer" style="display: none">
            <!--div class="center" style="margin-bottom: 1em">
            </div-->
            <div id="Favorites" class="center textBlock">Favorites are loading... May take a minute...</div>
        </div>
    </div>

</body>

<script>
    populateMovieList();
    populateDisneyList();
    populateMarvelList();
    populateStarWarsList();
    populateBookList();
    populateFavoritesList();

    showSection();

    setRadioCallback( "movieSorting", function( sortType ) {
        displayMovies( sortType );
    });
    setRadioCallback( "bookSorting", function( sortType ) {
        displayBooks( sortType );
    });
</script>
<?php includeModals(); ?>
</html>