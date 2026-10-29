 <?php include_once 'php_action/core.php';
@$get_company =mysqli_fetch_assoc(mysqli_query($dbc,"SELECT * FROM company ORDER BY id DESC LIMIT 1 "));

  ?>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="a software deleveloped by Sam'z Creations">
    <meta name="author" content="Samz Creations">
    <link rel="icon" href="img/logo/<?=$get_company['logo']?>" type="image/gif" sizes="16x16"> 
    <title><?=$get_company['name']?></title>
    <!-- Simple bar CSS -->
    <link rel="stylesheet" href="css/simplebar.css">
    <!-- Fonts CSS -->
  <!--   <link href="https://fonts.googleapis.com/css2?family=Overpass:ital,wght@0,100;0,200;0,300;0,400;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet"> -->
    <!-- Icons CSS -->
    <link rel="stylesheet" href="css/fontawesome.min.css">
    <link rel="stylesheet" href="css/feather.css">
    <link rel="stylesheet" href="css/select2.css">
    <link rel="stylesheet" href="css/dropzone.css">
    <link rel="stylesheet" href="css/uppy.min.css">
    <link rel="stylesheet" href="css/jquery.steps.css">
    <link rel="stylesheet" href="css/jquery.timepicker.css">
    <link rel="stylesheet" href="css/quill.snow.css">
    <!-- Date Range Picker CSS -->
    <link rel="stylesheet" href="css/daterangepicker.css">
    <!-- App CSS -->
    <link rel="stylesheet" href="css/app-light.css" id="lightTheme">
    <link rel="stylesheet" href="css/app-dark.css" id="darkTheme" disabled>
    <script src="js/sweetalert2.min.js"></script>
    <script src="js/all.min.js"></script>
    <link rel="stylesheet" href="css/sweetalert2.min.css">
    <link rel="stylesheet" href="css/dataTables.bootstrap4.css">
    <style type="text/css">

        .btn-admin,.bg-admin{
                color: #ffffff; 
                background-color: #930027; 
                border-color: #930027;
                box-shadow: 2px 2px gray;
        }
         .btn-admin2,.bg-admin2{
                color: white; 
                background-color: #930027; 
                border-color: #930027;
                box-shadow: 2px 2px gray;
        }

        .card-bg{
                
                background-color: #930027; 
               
        }
        .card-text{
         color: white;    
        }
      

        /* Fallback Select2 highlight and active colors */
        .select2-container--default .select2-results__option--highlighted[aria-selected],
        .select2-container--default .select2-results__option--highlighted {
            background-color: #1b68ff !important;
            color: #ffffff !important;
        }
        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #e9ecef !important;
            color: #212529 !important;
        }

        /* Fix input-group layout with Select2: keep buttons and badges on the same line */
        .input-group {
            flex-wrap: nowrap !important;
            align-items: stretch !important;
        }

        .input-group > .select2-container,
        .input-group > .select2-container--bootstrap4 {
            position: relative;
            flex: 1 1 auto !important;
            width: 1% !important;
            min-width: 0 !important;
            margin-bottom: 0 !important;
        }

        .input-group > .select2-container .select2-selection--single,
        .input-group > .select2-container--bootstrap4 .select2-selection--single {
            height: calc(1.5em + 0.75rem + 2px) !important;
            display: flex !important;
            align-items: center !important;
        }

        .input-group > .select2-container .select2-selection--single .select2-selection__rendered,
        .input-group > .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
            line-height: normal !important;
            padding-left: 0.75rem !important;
            padding-right: 1.75rem !important;
            text-overflow: ellipsis;
            overflow: hidden;
            white-space: nowrap;
        }

        .input-group > .select2-container .select2-selection--single .select2-selection__arrow,
        .input-group > .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
            height: 100% !important;
            top: 0 !important;
            right: 3px !important;
        }

        /* Seamless border connection */
        .input-group > .select2-container:not(:last-child) .select2-selection,
        .input-group > .select2-container--bootstrap4:not(:last-child) .select2-selection {
            border-top-right-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
        }

        .input-group > .input-group-prepend:first-child ~ .select2-container .select2-selection,
        .input-group > .input-group-prepend:first-child ~ .select2-container--bootstrap4 .select2-selection,
        .input-group > .select2-container:not(:first-child) .select2-selection,
        .input-group > .select2-container--bootstrap4:not(:first-child) .select2-selection {
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
        }

        .input-group > .select2-container + .input-group-prepend,
        .input-group > .select2-container + .input-group-append,
        .input-group > .select2-container--bootstrap4 + .input-group-prepend,
        .input-group > .select2-container--bootstrap4 + .input-group-append {
            display: flex !important;
            flex: 0 0 auto !important;
            margin-left: -1px !important;
        }

        .input-group > .select2-container + .input-group-prepend > .btn,
        .input-group > .select2-container + .input-group-prepend > .btn-sm,
        .input-group > .select2-container + .input-group-prepend > .btn-admin,
        .input-group > .select2-container + .input-group-prepend > button,
        .input-group > .select2-container + .input-group-prepend > .input-group-text,
        .input-group > .select2-container + .input-group-append > .btn,
        .input-group > .select2-container + .input-group-append > .btn-sm,
        .input-group > .select2-container + .input-group-append > .btn-admin,
        .input-group > .select2-container + .input-group-append > button,
        .input-group > .select2-container + .input-group-append > .input-group-text,
        .input-group > .select2-container--bootstrap4 + .input-group-prepend > .btn,
        .input-group > .select2-container--bootstrap4 + .input-group-prepend > .btn-sm,
        .input-group > .select2-container--bootstrap4 + .input-group-prepend > .btn-admin,
        .input-group > .select2-container--bootstrap4 + .input-group-prepend > button,
        .input-group > .select2-container--bootstrap4 + .input-group-prepend > .input-group-text,
        .input-group > .select2-container--bootstrap4 + .input-group-append > .btn,
        .input-group > .select2-container--bootstrap4 + .input-group-append > .btn-sm,
        .input-group > .select2-container--bootstrap4 + .input-group-append > .btn-admin,
        .input-group > .select2-container--bootstrap4 + .input-group-append > button,
        .input-group > .select2-container--bootstrap4 + .input-group-append > .input-group-text {
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
            border-top-right-radius: 0.25rem !important;
            border-bottom-right-radius: 0.25rem !important;
            height: 100% !important;
            min-height: calc(1.5em + 0.75rem + 2px) !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            white-space: nowrap !important;
            float: none !important;
        }

        .input-group > .select2-container + .input-group-prepend > :not(:last-child),
        .input-group > .select2-container + .input-group-append > :not(:last-child),
        .input-group > .select2-container--bootstrap4 + .input-group-prepend > :not(:last-child),
        .input-group > .select2-container--bootstrap4 + .input-group-append > :not(:last-child) {
            border-top-right-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
        }

        .input-group > .input-group-prepend button.btn-admin,
        .input-group > .input-group-append button.btn-admin {
            padding: 0 12px !important;
            box-shadow: none !important;
        }
    </style>
  </head>
  