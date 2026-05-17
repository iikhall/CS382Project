/* Report - when the page loaded via "Generate PDF" (?print=1, snapshot
   already archived server-side), auto-open the print dialog. */
$(function () {
  if ($('#autoPrint').length) {
    window.print();
  }
});
