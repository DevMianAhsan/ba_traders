$(document).ready(function () {

	  $('.dataTableAsc').DataTable(
      {
        autoWidth: true,
        order: [[0, 'asc']],
        "lengthMenu": [
          [10, 20, 30, -1],
          [10, 20, 30, "All"]
        ]
      });

});
