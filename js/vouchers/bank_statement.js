$(function (){
   $("h3.card-title:contains(Basic Details)").closest("div.card").prepend(
       "<div class='row'>" +
       "<div class='col-sm-6'>" +
       "<h3 class='card-title'>Basic Details</h3>" +
       "</div>" +
       "<div class='col-sm-6 text-right'>" +
       "<a href='../../assets/templates/bank_statement_template.csv' target='_blank' class='btn btn-success text-white' download='download'>" +
       "<i class='mdi mdi-download'></i> Download Template</a>" +
       "</div>" +
       "</div>"
   );

   $("h3.card-title:contains(Basic Details):eq(1)").remove();
});