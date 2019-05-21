<?php include("$_SERVER[DOCUMENT_ROOT]/php/startup.php"); ?>
<!DOCTYPE html>
<html>
<head>
	<title>Daniel&rsquo;s Ratings</title>
    <?php includeHeadInfo(); ?>
    <link rel="stylesheet" type="text/css" href="css/reviews.css"/>
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
            In addition, the Top 10 for ten different genres are included as well as specific rankings for Disney&rsquo;s Animated Classics, the MCU, and the Star Wars Franchise.
            Under books, there is also a list of favorites in order of date read.<br/><br/>
            Reviews can be found on <a href="https://www.goodreads.com/user/show/55277264-daniel-crouch" class="link">Goodreads</a>,
            <a href="https://letterboxd.com/danieltcrouch/" class="link">Letterboxd</a>, and
            <a href="https://www.criticker.com/profile/dcrouch1/" class="link">Criticker</a>.
        </div>
    </div>

    <!--Main-->
    <div class="col-10 main">
        <a href="#main-top"></a>
        <div class="center" style="font-size: 1.5em">Select a Category</div>
        <div class="col-5 center">
            <img src="images/movies.png" class="icon clickable" style="margin-bottom: 1em" onclick="toggleMovieSubMenu()">
            <div id="movieSubMenu" style="display: none">
                <input id="findMovie" type="search"  class="input" onkeyup="findMovieOnEnter( event )" placeholder="Find a movie">
                <div>
                    <img src="images/list.png" class="sub-icon clickable" onclick="showFullMovieList()" title="Click to see all movies">
                    <img src="images/ten.png"       class="sub-icon clickable" onclick="showGenreList()"  title="Click to see Ten Top 10 movies">
                    <img src="images/disney.png"    class="sub-icon clickable" onclick="showDisneyList()" title="Click to see Disney movies">
                    <img src="images/marvel.png"    class="sub-icon clickable" onclick="showMarvelList()" title="Click to see Marvel movies">
                    <img src="images/star-wars.png" class="sub-icon clickable" onclick="showSWList()"     title="Click to see Star Wars movies">
                </div>
            </div>
        </div>
        <div class="col-5 center">
            <img src="images/books.png" class="icon clickable" style="margin-bottom: 1em" onclick="toggleBookSubMenu()">
            <div id="bookSubMenu" style="display: none">
                <input id="findBook" type="search" class="input" onkeyup="findBookOnEnter( event )" placeholder="Find a book">
                <div>
                    <img src="images/list.png" class="sub-icon clickable" onclick="showFullBookList()"  title="Click to see all books">
                    <img src="images/star.png" class="sub-icon clickable" onclick="showFavoritesList()" title="Click to see favorite books">
                </div>
            </div>
        </div>
    </div>

    <div class="col-10 main">
        <div id="MovieContainer" style="display: none">
            <div class="center" style="margin-bottom: 1em">
                <button id="title"  name="movieSorting" class="button inverseButton" style="width: 5em; margin: .25em;">Title</button>
                <button id="year"   name="movieSorting" class="button inverseButton" style="width: 5em; margin: .25em;">Year</button>
                <button id="rating" name="movieSorting" class="button inverseButton" style="width: 5em; margin: .25em;">Rating</button>
            </div>
            <div id="Movies" class="center textBlock"></div>
        </div>
        <div id="GenreContainer" style="display: none">
            <!-- Genres go here -->
        </div>
        <div id="DisneyContainer" style="display: none">
            <div class="center" style="margin-bottom: 1em">
                <button class="button" style="width: 10em" onclick="compareFranchiseRankings('Disney')">Compare</button>
            </div>
            <div class="center" style="margin-bottom: 1em">
                <button class="button" style="width: 10em" onclick="displayAverageFranchiseRanking('Disney')">See Average</button>
            </div>
            <div id="Disney" class="center textBlock"></div>
        </div>
        <div id="MarvelContainer" style="display: none">
            <div class="center" style="margin-bottom: 1em">
                <button class="button" style="width: 10em" onclick="compareFranchiseRankings('Marvel')">Compare</button>
            </div>
            <div class="center" style="margin-bottom: 1em">
                <button class="button" style="width: 10em" onclick="displayAverageFranchiseRanking('Marvel')">See Average</button>
            </div>
            <div id="Marvel" class="center textBlock"></div>
        </div>
        <div id="StarWarsContainer" style="display: none">
            <div class="center" style="margin-bottom: 1em">
                <button class="button" style="width: 10em" onclick="compareFranchiseRankings('StarWars')">Compare</button>
            </div>
            <div class="center" style="margin-bottom: 1em">
                <button class="button" style="width: 10em" onclick="displayAverageFranchiseRanking('StarWars')">See Average</button>
            </div>
            <div id="StarWars" class="center textBlock"></div>
        </div>

        <div id="BookContainer" style="display: none">
            <div class="center" style="margin-bottom: 1em">
                <button id="title"  name="bookSorting" class="button inverseButton" style="width: 5em; margin: .25em;">Title</button>
                <button id="year"   name="bookSorting" class="button inverseButton" style="width: 5em; margin: .25em;">Year</button>
                <button id="rating" name="bookSorting" class="button inverseButton" style="width: 5em; margin: .25em;">Rating</button>
            </div>
            <div id="Books" class="center textBlock">Books are loading... May take a minute...</div>
        </div>
        <div id="FavoritesContainer" style="display: none">
            <div id="Favorites" class="center textBlock">Favorites are loading... May take a minute...</div>
        </div>
    </div>

</body>

<script>
    populateFullMovieList();
    populateGenreLists();
    populateDisneyList();
    populateMarvelList();
    populateStarWarsList();
    populateFullBookList();
    populateFavoritesList();

    showSection();

    setRadioCallback( "movieSorting", function( sortType ) {
        displayFullMovies( sortType );
    });
    setRadioCallback( "bookSorting", function( sortType ) {
        displayFullBooks( sortType );
    });
</script>
<?php include("html/genre-modal.html"); ?>
<?php include("html/sort-modal.html"); ?>
<?php includeModals(); ?>
</html>