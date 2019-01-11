<?php include("$_SERVER[DOCUMENT_ROOT]/php/startup.php"); ?>
<!DOCTYPE html>
<html>
<head>
	<title>New Ratings</title>
    <?php includeHeadInfo(); ?>
    <script src="javascript/enter.js"></script>
    <script src="javascript/enterRanked.js"></script>
</head>

<body>

	<!--Header-->
    <?php includeHeader(); ?>
    <div class="col-10 header">
        <div class="title center">New Ratings</div>
    </div>

<!--
TODO
Review entering:

Update movies in list
Add movies in list (default to top)
Add movies in ranking
Update movies in ranking
(Update any file and archive latest)
Download all in Zip
View Watch/Read
Link to GoodReads
Build in Later?
Add book images
-->

    <!--Main-->
    <div class="main">
        <div class="col-10 center" style="padding-bottom: 0">
            <input id="title" type="search" class="input" onkeydown="autofillTab( event )" onkeyup="autofill( event )" placeholder="Title (or ID)">
        </div>
        <div class="col-3r center" style="padding-bottom: 0">
            <input id="year" type="number" class="input" placeholder="Year">
        </div>
        <div class="col-3r center" style="padding-bottom: 0">
            <input id="rating" type="number" class="input" placeholder="Rating">
        </div>
        <div class="col-3r center" style="padding-bottom: 0">
            <input id="index" type="number" class="input" placeholder="Index">
        </div>
        <div class="col-10 center">
            <div><textarea id="review" class="input" placeholder="Review"></textarea></div>
            <div><input id="submit" type="button" class="button" style="width: 10em; margin-bottom: 1em" onclick="checkSubmit()" value="Submit"></div>
            <!--<div><input id="load" type="button" class="button" style="width: 10em; margin-bottom: 1em" onclick="load()" value="Load"></div>-->
            <div><input id="delete" type="button" class="button" style="width: 10em; margin-bottom: 1em" onclick="remove()" value="Delete"></div>
            <div><input id="download" type="button" class="button" style="width: 10em; margin-bottom: 1em" onclick="download()" value="Download"></div>
            <input id="id" type="hidden" value="">
            <input id="poster" type="hidden" value="">
        </div>
    </div>

</body>
<!-- todo - change modal colors (needs to use css variables in Common) -->
<?php includeModals(); ?>
</html>