<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>Multisite Maker</title>

    <!-- Bootstrap -->
    <style type="text/css" media="all">
      @import url("/sites/all/themes/greyhead_bootstrap/css/subtheme-styles.css");
    </style>

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="//oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="//oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body class="maintenance-page in-maintenance db-offline one-sidebar sidebar-first">
    <div class="row">
      <div class="container">
        <div class="col-md-2"></div>
        <div class="col-md-8">
          <div id="branding">
            <h1 class="page-title">Create a Drupal website</h1>
          </div>
          <div id="page">
            <?php print $output ?>
          </div>
        </div>
        <div class="col-md-2"></div>
      </div>
    </div>

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>

    <!-- JS awesomeness. -->
    <script src="/multisitemaker/js/multisitemaker.js"></script>
  </body>
</html>
