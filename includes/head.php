<?php include_once 'php_action/core.php';
@$get_company =mysqli_fetch_assoc(mysqli_query($dbc,"SELECT * FROM company ORDER BY id DESC LIMIT 1 "));

  ?>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="a software deleveloped by Sam'z Creations">
    <meta name="author" content="TheWebConcept.coms">
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
    <script>
      (function() {
        try {
          var mode = localStorage.getItem("mode");
          if (mode === "dark") {
            var dark = document.getElementById("darkTheme");
            var light = document.getElementById("lightTheme");
            if (dark) dark.removeAttribute("disabled");
            if (light) light.setAttribute("disabled", "disabled");
            document.documentElement.classList.add("dark-theme");
            document.documentElement.classList.add("dark");
          }
        } catch(e) {}
      })();
    </script>
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
        /* ===================================================================
           GLOBAL FORM & SCREEN UI UNIFICATION
           Harmonizes appearance of all forms, cards, tables, and buttons
           =================================================================== */

        /* Standardized Card and Card-Header */
        .card {
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 6px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }

        .card-header.card-bg {
            background-color: #930027 !important;
            color: #ffffff !important;
            border-top-left-radius: 5px !important;
            border-top-right-radius: 5px !important;
            padding: 0.75rem 1.25rem !important;
            border-bottom: none !important;
        }

        .card-header.card-bg .card-text,
        .card-header.card-bg b,
        .card-header.card-bg h4,
        .card-header.card-bg h5 {
            color: #ffffff !important;
            font-weight: 700 !important;
            letter-spacing: 0.5px;
            margin: 0;
            line-height: 1.5;
        }

        /* Form Labels Consistency */
        form label, .form-group label {
            font-size: 13px !important;
            font-weight: 600 !important;
            color: #2c3e50 !important;
            margin-bottom: 4px !important;
            display: inline-block;
        }

        /* Standard Form Control styling */
        .form-control {
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 13.5px;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .form-control:focus {
            border-color: #930027 !important;
            box-shadow: 0 0 0 0.15rem rgba(147, 0, 39, 0.2) !important;
        }

        /* Consistent Action Buttons across forms */
        .btn-admin {
            background-color: #930027 !important;
            border-color: #930027 !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            box-shadow: 0 2px 4px rgba(147, 0, 39, 0.25) !important;
            transition: all 0.2s ease-in-out;
        }

        .btn-admin:hover,
        .btn-admin:focus {
            background-color: #75001f !important;
            border-color: #75001f !important;
            color: #ffffff !important;
        }

        .btn-admin2 {
            background-color: #75001f !important;
            border-color: #75001f !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2) !important;
            transition: all 0.2s ease-in-out;
        }

        .btn-admin2:hover,
        .btn-admin2:focus {
            background-color: #580017 !important;
            border-color: #580017 !important;
            color: #ffffff !important;
        }

        /* Standardized DataTable and General Table Headers */
        .table thead th,
        .dataTable thead th,
        .dataTableAsc thead th {
            background-color: #2b3035 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 12.5px !important;
            border-color: #454d55 !important;
            vertical-align: middle !important;
            text-align: center;
            padding: 8px 6px !important;
            letter-spacing: 0.3px;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(147, 0, 39, 0.03) !important;
        }

        /* Balance badge in input-groups */
        .input-group .input-group-text#basic-addon1 {
            font-size: 12px;
            font-weight: 700;
            background-color: #e9ecef;
            color: #111;
            padding: 0.375rem 0.5rem;
        }

        .input-group .input-group-text#basic-addon1 span {
            color: #930027;
            font-weight: 800;
            margin-left: 2px;
        }

        /* ===================================================================
           DARK THEME UNIFICATION & CONTRAST ENHANCEMENTS
           Applies automatically when Dark Mode is active (.dark / .dark-theme)
           =================================================================== */

        /* Scoped to dark theme root / body */
        .dark, .dark-theme {
            color-scheme: dark;
        }

        /* Mode switcher icon toggling (Moon in light mode, Sun in dark mode) */
        #modeSwitcher[data-mode="dark"] .fe::before {
            content: "\e9e3" !important;
        }
        #modeSwitcher[data-mode="light"] .fe::before,
        #modeSwitcher:not([data-mode="dark"]) .fe::before {
            content: "\e9a3" !important;
        }

        /* Company Logo Theme Switching in Navbar */
        .logo-light {
            display: inline-block !important;
        }
        .logo-dark {
            display: none !important;
        }

        .dark .logo-light,
        .dark-theme .logo-light {
            display: none !important;
        }
        .dark .logo-dark,
        .dark-theme .logo-dark {
            display: inline-block !important;
        }

        /* Fallback enhancement if only one logo image exists */
        .dark .navbar-brand img:only-child,
        .dark-theme .navbar-brand img:only-child {
            filter: drop-shadow(0 0 1px rgba(255, 255, 255, 0.9)) drop-shadow(0 0 2px rgba(255, 255, 255, 0.5));
        }

        /* Form Labels contrast in dark mode */
        .dark form label, .dark .form-group label, .dark .form-row label, .dark .col-form-label,
        .dark-theme form label, .dark-theme .form-group label, .dark-theme .form-row label, .dark-theme .col-form-label {
            color: #ced4da !important;
        }

        /* Cards in dark mode */
        .dark .card, .dark-theme .card {
            background-color: #2b3035 !important;
            border-color: #3e444a !important;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.35) !important;
        }

        .dark .card-header.card-bg, .dark-theme .card-header.card-bg {
            background-color: #1b68ff !important;
            border-bottom: 1px solid #1558d6 !important;
            color: #ffffff !important;
        }

        /* Action buttons in dark mode: harmonized with dashboard primary blue */
        .dark .btn-admin, .dark-theme .btn-admin {
            background-color: #1b68ff !important;
            border-color: #1b68ff !important;
            color: #ffffff !important;
            box-shadow: 0 2px 4px rgba(27, 104, 255, 0.3) !important;
        }

        .dark .btn-admin:hover, .dark .btn-admin:focus,
        .dark-theme .btn-admin:hover, .dark-theme .btn-admin:focus {
            background-color: #1558d6 !important;
            border-color: #1558d6 !important;
            color: #ffffff !important;
        }

        .dark .btn-admin2, .dark-theme .btn-admin2 {
            background-color: #1558d6 !important;
            border-color: #1558d6 !important;
            color: #ffffff !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.25) !important;
        }

        .dark .btn-admin2:hover, .dark .btn-admin2:focus,
        .dark-theme .btn-admin2:hover, .dark-theme .btn-admin2:focus {
            background-color: #0f47b2 !important;
            border-color: #0f47b2 !important;
            color: #ffffff !important;
        }

        /* Form Controls in dark mode */
        .dark .form-control, .dark-theme .form-control {
            background-color: #343a40 !important;
            border-color: #495057 !important;
            color: #ced4da !important;
        }

        .dark .form-control:focus, .dark-theme .form-control:focus {
            background-color: #343a40 !important;
            border-color: #1b68ff !important;
            color: #ffffff !important;
            box-shadow: 0 0 0 0.15rem rgba(27, 104, 255, 0.35) !important;
        }

        .dark .form-control:disabled, .dark .form-control[readonly],
        .dark-theme .form-control:disabled, .dark-theme .form-control[readonly] {
            background-color: #2b3035 !important;
            border-color: #3e444a !important;
            color: #adb5bd !important;
        }

        /* Input Groups & Prepend/Append in dark mode */
        .dark .input-group-text, .dark-theme .input-group-text {
            background-color: #2b3035 !important;
            border-color: #495057 !important;
            color: #ced4da !important;
        }

        .dark .input-group .input-group-text#basic-addon1,
        .dark-theme .input-group .input-group-text#basic-addon1 {
            background-color: #2b3035 !important;
            border-color: #495057 !important;
            color: #ced4da !important;
        }

        .dark .input-group .input-group-text#basic-addon1 span,
        .dark-theme .input-group .input-group-text#basic-addon1 span {
            color: #6ea8fe !important;
        }

        /* Select2 Dropdowns & Selections in dark mode (Supports both default & bootstrap4) */
        .dark .select2-container--default .select2-selection--single,
        .dark .select2-container--bootstrap4 .select2-selection--single,
        .dark-theme .select2-container--default .select2-selection--single,
        .dark-theme .select2-container--bootstrap4 .select2-selection--single {
            background-color: #343a40 !important;
            border-color: #495057 !important;
            color: #ced4da !important;
        }

        .dark .select2-container--default .select2-selection--single .select2-selection__rendered,
        .dark .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered,
        .dark-theme .select2-container--default .select2-selection--single .select2-selection__rendered,
        .dark-theme .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
            color: #ced4da !important;
        }

        .dark .select2-container--default .select2-selection--single .select2-selection__arrow b,
        .dark .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow b,
        .dark-theme .select2-container--default .select2-selection--single .select2-selection__arrow b,
        .dark-theme .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow b {
            border-color: #ced4da transparent transparent transparent !important;
        }

        .dark .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b,
        .dark .select2-container--bootstrap4.select2-container--open .select2-selection--single .select2-selection__arrow b,
        .dark-theme .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b,
        .dark-theme .select2-container--bootstrap4.select2-container--open .select2-selection--single .select2-selection__arrow b {
            border-color: transparent transparent #ced4da transparent !important;
        }

        .dark .select2-dropdown,
        .dark .select2-container--default .select2-dropdown,
        .dark .select2-container--bootstrap4 .select2-dropdown,
        .dark-theme .select2-dropdown,
        .dark-theme .select2-container--default .select2-dropdown,
        .dark-theme .select2-container--bootstrap4 .select2-dropdown {
            background-color: #2b3035 !important;
            border-color: #495057 !important;
            color: #ced4da !important;
        }

        .dark .select2-search--dropdown .select2-search__field,
        .dark .select2-container--default .select2-search--dropdown .select2-search__field,
        .dark-theme .select2-search--dropdown .select2-search__field,
        .dark-theme .select2-container--default .select2-search--dropdown .select2-search__field {
            background-color: #343a40 !important;
            border-color: #495057 !important;
            color: #ced4da !important;
        }

        .dark .select2-container--default .select2-results__option,
        .dark .select2-container--bootstrap4 .select2-results__option,
        .dark-theme .select2-container--default .select2-results__option,
        .dark-theme .select2-container--bootstrap4 .select2-results__option {
            background-color: #2b3035;
            color: #ced4da;
        }

        .dark .select2-container--default .select2-results__option--highlighted,
        .dark .select2-container--default .select2-results__option--highlighted[aria-selected],
        .dark .select2-container--bootstrap4 .select2-results__option--highlighted,
        .dark .select2-container--bootstrap4 .select2-results__option--highlighted[aria-selected],
        .dark-theme .select2-container--default .select2-results__option--highlighted,
        .dark-theme .select2-container--default .select2-results__option--highlighted[aria-selected],
        .dark-theme .select2-container--bootstrap4 .select2-results__option--highlighted,
        .dark-theme .select2-container--bootstrap4 .select2-results__option--highlighted[aria-selected] {
            background-color: #1b68ff !important;
            color: #ffffff !important;
        }

        .dark .select2-container--default .select2-results__option[aria-selected=true],
        .dark .select2-container--bootstrap4 .select2-results__option[aria-selected=true],
        .dark-theme .select2-container--default .select2-results__option[aria-selected=true],
        .dark-theme .select2-container--bootstrap4 .select2-results__option[aria-selected=true] {
            background-color: #3e444a !important;
            color: #ffffff !important;
        }

        /* DataTables dark mode styling */
        .dark .dataTables_wrapper .dataTables_filter input,
        .dark-theme .dataTables_wrapper .dataTables_filter input {
            background-color: #343a40 !important;
            border: 1px solid #495057 !important;
            color: #ced4da !important;
            border-radius: 4px;
            padding: 0.25rem 0.5rem;
        }

        .dark .dataTables_wrapper .dataTables_length select,
        .dark-theme .dataTables_wrapper .dataTables_length select {
            background-color: #343a40 !important;
            border: 1px solid #495057 !important;
            color: #ced4da !important;
            border-radius: 4px;
            padding: 0.25rem 0.5rem;
        }

        .dark .dataTables_wrapper .dataTables_info,
        .dark .dataTables_wrapper .dataTables_length label,
        .dark .dataTables_wrapper .dataTables_filter label,
        .dark-theme .dataTables_wrapper .dataTables_info,
        .dark-theme .dataTables_wrapper .dataTables_length label,
        .dark-theme .dataTables_wrapper .dataTables_filter label {
            color: #adb5bd !important;
        }

        .dark .dataTables_wrapper .paginate_button.page-item .page-link,
        .dark-theme .dataTables_wrapper .paginate_button.page-item .page-link {
            background-color: #343a40 !important;
            border-color: #495057 !important;
            color: #ced4da !important;
        }

        .dark .dataTables_wrapper .paginate_button.page-item.active .page-link,
        .dark-theme .dataTables_wrapper .paginate_button.page-item.active .page-link {
            background-color: #1b68ff !important;
            border-color: #1b68ff !important;
            color: #ffffff !important;
        }

        .dark .dataTables_wrapper .paginate_button.page-item.disabled .page-link,
        .dark-theme .dataTables_wrapper .paginate_button.page-item.disabled .page-link {
            background-color: #2b3035 !important;
            border-color: #343a40 !important;
            color: #6c757d !important;
        }

        .dark .table, .dark-theme .table {
            color: #ced4da !important;
            border-color: #3e444a !important;
        }

        .dark .table th, .dark .table td,
        .dark-theme .table th, .dark-theme .table td {
            border-color: #3e444a !important;
        }

        .dark .table thead th,
        .dark .dataTable thead th,
        .dark .dataTableAsc thead th,
        .dark-theme .table thead th,
        .dark-theme .dataTable thead th,
        .dark-theme .dataTableAsc thead th {
            background-color: #1a1d20 !important;
            border-color: #3e444a !important;
            color: #ffffff !important;
        }

        .dark .table-hover tbody tr:hover,
        .dark-theme .table-hover tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.04) !important;
        }

        /* Modals in dark mode */
        .dark .modal-content, .dark-theme .modal-content {
            background-color: #2b3035 !important;
            border: 1px solid #495057 !important;
            color: #ced4da !important;
        }

        .dark .modal-header, .dark .modal-footer,
        .dark-theme .modal-header, .dark-theme .modal-footer {
            border-color: #3e444a !important;
        }

        .dark .modal-title, .dark-theme .modal-title {
            color: #ffffff !important;
        }

        .dark .modal-header .close, .dark-theme .modal-header .close {
            color: #ced4da !important;
            text-shadow: none !important;
            opacity: 0.8;
        }

        .dark .modal-header .close:hover, .dark-theme .modal-header .close:hover {
            color: #ffffff !important;
            opacity: 1;
        }

        /* Navbar & Dropdown menus in dark mode */
        .dark .dropdown-menu, .dark-theme .dropdown-menu {
            background-color: #2b3035 !important;
            border: 1px solid #495057 !important;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.4) !important;
        }

        .dark .dropdown-menu .nav-link,
        .dark .dropdown-item,
        .dark-theme .dropdown-menu .nav-link,
        .dark-theme .dropdown-item {
            color: #ced4da !important;
        }

        .dark .dropdown-menu .nav-link:hover,
        .dark .dropdown-item:hover,
        .dark-theme .dropdown-menu .nav-link:hover,
        .dark-theme .dropdown-item:hover {
            background-color: #383f45 !important;
            color: #ffffff !important;
        }

        /* SweetAlert2 in dark mode */
        .dark .swal2-popup, .dark-theme .swal2-popup {
            background: #2b3035 !important;
            color: #ced4da !important;
            border: 1px solid #495057 !important;
        }

        .dark .swal2-title, .dark .swal2-html-container,
        .dark-theme .swal2-title, .dark-theme .swal2-html-container {
            color: #ced4da !important;
        }

        /* Screen Report Header contrast in dark mode (print stays paper white) */
        @media screen {
            .dark .unified-report-header,
            .dark-theme .unified-report-header {
                border-bottom-color: #495057;
            }
            .dark .unified-report-header .comp-address-line,
            .dark .unified-report-header .comp-phone-line,
            .dark-theme .unified-report-header .comp-address-line,
            .dark-theme .unified-report-header .comp-phone-line {
                color: #8da4ef !important;
            }
            .dark .unified-report-header .report-title-text,
            .dark-theme .unified-report-header .report-title-text {
                color: #ffffff !important;
                border-color: #adb5bd !important;
            }
            .dark .unified-report-header .cust-name-row,
            .dark .unified-report-header .cust-name-val,
            .dark .unified-report-header .cust-address-row,
            .dark .unified-report-header .cust-duration-row,
            .dark .unified-report-header .report-duration-meta,
            .dark-theme .unified-report-header .cust-name-row,
            .dark-theme .unified-report-header .cust-name-val,
            .dark-theme .unified-report-header .cust-address-row,
            .dark-theme .unified-report-header .cust-duration-row,
            .dark-theme .unified-report-header .report-duration-meta {
                color: #ced4da !important;
            }
        }
    </style>
    <link rel="stylesheet" href="css/unified_print.css?v=<?=time()?>">
  </head>
  