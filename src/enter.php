<?php include("$_SERVER[DOCUMENT_ROOT]/php/startup.php"); ?>
<!DOCTYPE html>
<html>
<head>
	<title>New Ratings</title>
    <?php includeHeadInfo(); ?>
    <script src="http://religionandstory.webutu.com/utility/ratings/javascript/enter.js"></script>
    <script src="http://religionandstory.webutu.com/utility/ratings/javascript/enterRanked.js"></script>
</head>

<body>

	<!--Header-->
    <?php includeHeader(); ?>
    <div class="col-10 header">
        <div class="title center">New Ratings</div>
    </div>

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
            <div><input id="submit" class="button" style="width: 10em; margin-bottom: 1em" onclick="checkSubmit()" value="Submit"></div>
            <!--<div><input id="load" class="button" style="width: 10em; margin-bottom: 1em" onclick="load()" value="Load"></div>-->
            <div><input id="delete" class="button" style="width: 10em; margin-bottom: 1em" onclick="remove()" value="Delete"></div>
            <div><input id="download" class="button" style="width: 10em; margin-bottom: 1em" onclick="download()" value="Download"></div>
            <input id="id" type="hidden" value="">
            <input id="poster" type="hidden" value="">
        </div>
    </div>

</body>
<?php includeModals(); ?>
</html>